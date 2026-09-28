import {useQueryClient} from '@tanstack/react-query';
import {api} from '@/shared/api';
import {useSubmit} from '@/shared/lib/forms';
import type {Cycle} from '../api/pettyCashApi';

/** A caja menor write (close, sign off): on success the caja menor and its cycles are reloaded. */
export function usePettyCashAction(projectId: number) {
  const queryClient = useQueryClient();
  const submit = useSubmit();

  const post = async (path: string, body?: unknown): Promise<Cycle | null> => {
    const result = await submit.run(() =>
      api<Cycle>(path, {method: 'POST', body}),
    );
    if (!result.ok) {
      return null;
    }
    void queryClient.invalidateQueries({
      queryKey: ['petty-cash', String(projectId)],
    });
    void queryClient.invalidateQueries({queryKey: ['petty-cash-cycle']});

    return result.value;
  };

  return {...submit, post};
}
