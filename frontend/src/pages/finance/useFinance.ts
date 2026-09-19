import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { api } from '../../api/client'
import type { Finance, Movement } from '../../api/types'
import { planKey } from '../plan/usePlan'

export const financeKey = (projectId: number | string) => ['finance', String(projectId)]
export const movementsKey = (projectId: number | string) => ['movements', String(projectId)]

export function useFinance(projectId: number | string) {
  return useQuery({ queryKey: financeKey(projectId), queryFn: () => api<Finance>(`/projects/${projectId}/finance`) })
}

export function useMovements(projectId: number | string) {
  return useQuery({ queryKey: movementsKey(projectId), queryFn: () => api<Movement[]>(`/projects/${projectId}/movements`) })
}

/** Any money movement changes balances, the ledger and possibly stage statuses. */
export function useFinanceMutation<TVars, TResult>(projectId: number | string, fn: (vars: TVars) => Promise<TResult>, onDone?: () => void) {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: fn,
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: financeKey(projectId) })
      void queryClient.invalidateQueries({ queryKey: movementsKey(projectId) })
      void queryClient.invalidateQueries({ queryKey: planKey(projectId) })
      onDone?.()
    },
  })
}
