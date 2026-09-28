import {useQueryClient} from '@tanstack/react-query';
import {useState} from 'react';
import {useSubmit} from '@/shared/lib/forms';
import {refreshFinance} from '../api/financeApi';

/**
 * A money write with its busy state and errors (per field, or one for the form). On success the project's
 * finance, movements and plan are reloaded; the result is the API's answer, or null when it failed (then
 * `error` holds what the API said).
 */
export function useFinanceAction(projectId: number) {
  const queryClient = useQueryClient();
  const submit = useSubmit();
  // The last failure as the API sent it, for errors on the parts of a list ("allocations[1].amount").
  const [error, setError] = useState<unknown>(null);

  const run = async <T>(action: () => Promise<T>): Promise<T | null> => {
    const result = await submit.run(action);
    if (!result.ok) {
      setError(result.error);

      return null;
    }
    setError(null);
    refreshFinance(queryClient, projectId);

    return result.value;
  };

  const reset = () => {
    submit.reset();
    setError(null);
  };

  return {...submit, error, run, reset};
}
