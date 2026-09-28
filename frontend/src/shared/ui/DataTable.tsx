import {
  Paper,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
} from '@mui/material';
import type {ReactNode} from 'react';

export interface Column<Row> {
  key: string;
  header: ReactNode;
  render: (row: Row) => ReactNode;
  align?: 'left' | 'right' | 'center';
  /** Hidden on phones. */
  hideOnMobile?: boolean;
}

/**
 * The house table: every list in the app uses it, so headers, density and alignment agree. Money columns are
 * right-aligned; the last column holds the row's actions. `empty` is shown instead of the table when there
 * are no rows.
 */
export function DataTable<Row>({
  columns,
  rows,
  rowKey,
  empty,
  label,
  footer,
}: {
  columns: Column<Row>[];
  rows: Row[];
  rowKey: (row: Row) => string | number;
  empty?: ReactNode;
  /** Accessible name of the table. */
  label: string;
  footer?: ReactNode;
}) {
  if (rows.length === 0 && empty) {
    return <>{empty}</>;
  }
  const display = (column: Column<Row>) =>
    column.hideOnMobile ? {display: {xs: 'none', md: 'table-cell'}} : undefined;

  return (
    <TableContainer component={Paper}>
      <Table size="small" aria-label={label}>
        <TableHead>
          <TableRow>
            {columns.map((column) => (
              <TableCell
                key={column.key}
                align={column.align}
                sx={{fontWeight: 600, ...display(column)}}
              >
                {column.header}
              </TableCell>
            ))}
          </TableRow>
        </TableHead>
        <TableBody>
          {rows.map((row) => (
            <TableRow key={rowKey(row)} hover>
              {columns.map((column) => (
                <TableCell
                  key={column.key}
                  align={column.align}
                  sx={display(column)}
                >
                  {column.render(row)}
                </TableCell>
              ))}
            </TableRow>
          ))}
          {footer}
        </TableBody>
      </Table>
    </TableContainer>
  );
}
