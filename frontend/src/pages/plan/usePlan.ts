import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { api } from '../../api/client'
import type { Plan } from '../../api/types'

export const planKey = (projectId: number | string) => ['plan', String(projectId)]

export function usePlan(projectId: number | string) {
  return useQuery({ queryKey: planKey(projectId), queryFn: () => api<Plan>(`/projects/${projectId}/plan`) })
}

/**
 * Every plan endpoint answers with the refreshed plan, which replaces the cached one.
 */
export function usePlanMutation<TVars = void>(projectId: number | string, request: (vars: TVars) => { path: string; method: string; body?: unknown }, onDone?: () => void) {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (vars: TVars) => {
      const { path, method, body } = request(vars)

      return api<Plan>(path, { method, body })
    },
    onSuccess: (plan) => {
      queryClient.setQueryData(planKey(projectId), plan)
      // Approval changes the project status shown elsewhere.
      void queryClient.invalidateQueries({ queryKey: ['projects'] })
      onDone?.()
    },
  })
}
