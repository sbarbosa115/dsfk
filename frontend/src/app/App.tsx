import {CssBaseline, ThemeProvider, Typography} from '@mui/material';
import {QueryClient, QueryClientProvider} from '@tanstack/react-query';
import {useTranslation} from 'react-i18next';
import {createBrowserRouter, RouterProvider} from 'react-router';
import {ApiError} from '@/shared/api';
import {theme} from '@/shared/config/theme';

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

function NotFound() {
  const {t} = useTranslation();

  return <Typography>{t('common.notFound')}</Typography>;
}

const router = createBrowserRouter([{path: '*', element: <NotFound />}]);

export function App() {
  return (
    <ThemeProvider theme={theme}>
      <CssBaseline />
      <QueryClientProvider client={queryClient}>
        <RouterProvider router={router} />
      </QueryClientProvider>
    </ThemeProvider>
  );
}
