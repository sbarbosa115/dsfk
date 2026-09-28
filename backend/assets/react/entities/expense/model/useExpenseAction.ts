import {useQueryClient} from '@tanstack/react-query';
import {useSubmit} from '@/shared/lib/forms';
import {refreshExpenses} from '../api/expenseApi';

/**
 * An expense write with its busy state and errors. On success the project's expenses and money are reloaded;
 * the result is the API's answer, or null when it failed.
 */
export function useExpenseAction(projectId: number) {
  const queryClient = useQueryClient();
  const submit = useSubmit();

  const run = async <T>(action: () => Promise<T>): Promise<T | null> => {
    const result = await submit.run(action);
    if (!result.ok) {
      return null;
    }
    refreshExpenses(queryClient, projectId);

    return result.value;
  };

  return {...submit, run};
}
