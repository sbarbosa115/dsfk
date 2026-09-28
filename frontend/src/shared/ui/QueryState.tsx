import {Alert, Box, Button, CircularProgress, Typography} from '@mui/material';
import type {ReactNode} from 'react';
import {useTranslation} from 'react-i18next';
import {errorMessage} from '@/shared/lib/errors';

interface Query<T> {
  data: T | undefined;
  error: unknown;
  isPending: boolean;
  refetch: () => unknown;
}

/**
 * Loading, error and loaded states of one query, the same way everywhere. `children` gets the data.
 */
export function QueryState<T>({
  query,
  children,
}: {
  query: Query<T>;
  children: (data: T) => ReactNode;
}) {
  const {t} = useTranslation();

  if (query.isPending) {
    return (
      <Box
        sx={{display: 'flex', alignItems: 'center', gap: 1, py: 2}}
        role="status"
      >
        <CircularProgress size={18} />
        <Typography color="text.secondary">{t('common.loading')}</Typography>
      </Box>
    );
  }
  if (query.error || query.data === undefined) {
    return (
      <Alert
        severity="error"
        action={
          <Button
            color="inherit"
            size="small"
            onClick={() => void query.refetch()}
          >
            {t('common.retry')}
          </Button>
        }
      >
        {errorMessage(t, query.error)}
      </Alert>
    );
  }

  return <>{children(query.data)}</>;
}
