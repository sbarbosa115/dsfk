import { useMutation, useQuery, useQueryClient, type QueryClient } from '@tanstack/react-query'
import { api } from '../../api/client'
import type { ExpenseList, PettyCash } from '../../api/types'

export const expensesKey = (projectId: string) => ['expenses', projectId]
export const pettyCashKey = (projectId: string) => ['petty-cash', projectId]

export function useExpenses(projectId: string, status: string) {
  return useQuery({
    queryKey: [...expensesKey(projectId), status],
    queryFn: () => api<ExpenseList>(`/projects/${projectId}/expenses${status ? `?status=${status}` : ''}`),
  })
}

export function usePettyCash(projectId: string) {
  return useQuery({ queryKey: pettyCashKey(projectId), queryFn: () => api<PettyCash>(`/projects/${projectId}/petty-cash`) })
}

/** Expenses move money: refresh every view that shows balances. */
export function invalidateMoney(queryClient: QueryClient, projectId: string) {
  for (const key of [expensesKey(projectId), pettyCashKey(projectId), ['finance', projectId], ['movements', projectId]]) {
    void queryClient.invalidateQueries({ queryKey: key })
  }
}

export function useMoneyMutation<TVars, TResult>(projectId: string, fn: (vars: TVars) => Promise<TResult>, onDone?: (result: TResult) => void) {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: fn,
    onSuccess: (result) => {
      invalidateMoney(queryClient, projectId)
      onDone?.(result)
    },
  })
}
