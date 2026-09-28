import {keepPreviousData, useQuery} from '@tanstack/react-query';
import {useCallback, useState} from 'react';
import {api} from '@/shared/api';

/** One page of a list endpoint: {items, total, page, perPage}. */
export interface ListPage<T> {
  items: T[];
  total: number;
  page: number;
  perPage: number;
}

export type Filters = Record<string, string | number | undefined>;

function query(filters: Filters, page: number): string {
  const params = new URLSearchParams();
  for (const [name, value] of Object.entries(filters)) {
    if (value !== undefined && value !== '') {
      params.set(name, String(value));
    }
  }
  if (page > 1) {
    params.set('page', String(page));
  }
  const text = params.toString();

  return text ? `?${text}` : '';
}

/**
 * A paginated API list with filters (the shape ListView takes); changing a filter goes back to page 1. The query
 * key is `key` plus the filters and page, so invalidating `key` reloads every page of it.
 */
export function useList<T, F extends Filters = Filters>(
  key: readonly unknown[],
  path: string,
  initialFilters: F = {} as F,
) {
  const [filters, setFilters] = useState<F>(initialFilters);
  const [page, setPage] = useState(1);
  const result = useQuery({
    queryKey: [...key, filters, page],
    queryFn: () => api<ListPage<T>>(`${path}${query(filters, page)}`),
    placeholderData: keepPreviousData,
  });

  const update = useCallback((patch: Partial<F>) => {
    setFilters((current) => ({...current, ...patch}));
    setPage(1);
  }, []);

  return {
    data: result.data ?? null,
    error: result.error,
    loading: result.isFetching,
    reload: () => void result.refetch(),
    filters,
    update,
    page,
    setPage,
  };
}
