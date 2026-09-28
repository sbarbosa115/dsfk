import {useQueryClient} from '@tanstack/react-query';
import {useSubmit} from '@/shared/lib/forms';
import {changePlan, storePlan, type Plan} from '../api/planApi';

/**
 * A plan write with its busy state and errors (per field, or one for the form). On success the refreshed plan
 * the API answered with replaces the cached one, and the result says so.
 */
export function usePlanAction() {
  const queryClient = useQueryClient();
  const submit = useSubmit();

  const run = async (
    path: string,
    method: 'POST' | 'PUT' | 'PATCH' | 'DELETE',
    body?: unknown,
  ): Promise<Plan | null> => {
    const result = await submit.run(() => changePlan(path, method, body));
    if (!result.ok) {
      return null;
    }
    storePlan(queryClient, result.value);

    return result.value;
  };

  return {...submit, run};
}
