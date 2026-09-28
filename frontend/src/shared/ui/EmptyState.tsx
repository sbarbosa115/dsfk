import {Box, Button, Paper, Typography} from '@mui/material';
import type {ReactNode} from 'react';

/**
 * What a list shows when it has nothing: what the section is for and its primary action, or (when filtered to
 * nothing) a way back that clears every filter.
 */
export function EmptyState({
  title,
  hint,
  action,
  onAction,
}: {
  title: string;
  hint?: ReactNode;
  action?: string;
  onAction?: () => void;
}) {
  return (
    <Paper sx={{p: 4, textAlign: 'center'}}>
      <Typography variant="subtitle1">{title}</Typography>
      {hint && (
        <Typography variant="body2" color="text.secondary" sx={{mt: 0.5}}>
          {hint}
        </Typography>
      )}
      {action && onAction && (
        <Box sx={{mt: 2}}>
          <Button variant="outlined" onClick={onAction}>
            {action}
          </Button>
        </Box>
      )}
    </Paper>
  );
}
