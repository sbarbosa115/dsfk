import {QueryClient, QueryClientProvider} from '@tanstack/react-query';
import {RouterProvider} from 'react-router';
import {SessionProvider} from '@/entities/session';
import {ApiError} from '@/shared/api';
import '@/shared/i18n';
import {ThemeProvider} from '@/shared/lib/theme';
import {router} from './router';

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      // Client errors (401/403/404/422) will not succeed on a retry.
      retry: (count, error) =>
        !(error instanceof ApiError && error.status < 500) && count < 2,
      refetchOnWindowFocus: false,
    },
  },
});

export function App() {
  return (
    <ThemeProvider>
      <QueryClientProvider client={queryClient}>
        <SessionProvider>
          <RouterProvider router={router} />
        </SessionProvider>
      </QueryClientProvider>
    </ThemeProvider>
  );
}
