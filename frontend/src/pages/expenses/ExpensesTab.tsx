import AddIcon from '@mui/icons-material/Add'
import AttachIcon from '@mui/icons-material/AttachFileOutlined'
import WarningIcon from '@mui/icons-material/ReportProblemOutlined'
import {
  Alert,
  Box,
  Button,
  Checkbox,
  Chip,
  MenuItem,
  Paper,
  Stack,
  TextField,
  Typography,
} from '@mui/material'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { api } from '../../api/client'
import type { Expense, ExpenseStatus } from '../../api/types'
import { useAuth } from '../../auth/AuthContext'
import { errorMessage } from '../../lib/errors'
import { formatDate, formatMoney } from '../../lib/format'
import { usePlan } from '../plan/usePlan'
import { AttachReceiptDialog, ExpenseDetailDialog, ExpenseDialog, ReasonDialog, ReimburseDialog } from './dialogs'
import { useExpenses, useMoneyMutation } from './useExpenses'

const STATUS_COLORS: Record<ExpenseStatus, 'default' | 'info' | 'warning' | 'success' | 'error' | 'primary'> = {
  SUBMITTED: 'info',
  PM_APPROVED: 'warning',
  APPROVED: 'success',
  REJECTED: 'error',
  REIMBURSED: 'primary',
  VOIDED: 'default',
}

const FILTERS = ['', 'SUBMITTED,PM_APPROVED', 'APPROVED', 'REJECTED', 'REIMBURSED', 'VOIDED']

type DialogState =
  | { kind: 'new' }
  | { kind: 'edit' | 'reject' | 'void' | 'attach' | 'detail'; expense: Expense }
  | { kind: 'reimburse' }
  | null

export function ExpensesTab({ projectId }: { projectId: string }) {
  const { t } = useTranslation()
  const { user } = useAuth()
  const [filter, setFilter] = useState('')
  const [selected, setSelected] = useState<number[]>([])
  const [dialog, setDialog] = useState<DialogState>(null)
  const close = () => setDialog(null)
  const plan = usePlan(projectId)
  const expenses = useExpenses(projectId, filter)

  const role = user?.admin ? 'ADMIN' : user?.memberships.find((m) => String(m.projectId) === projectId)?.role
  const isTeamLead = role === 'TEAM_LEAD'
  const isManager = !isTeamLead

  const approve = useMoneyMutation(projectId, (id: number) => api(`/expenses/${id}/approve`, { method: 'POST' }))
  const reject = useMoneyMutation(projectId, ({ id, reason }: { id: number; reason: string }) => api(`/expenses/${id}/reject`, { method: 'POST', body: { reason } }), close)
  const voidExpense = useMoneyMutation(projectId, ({ id, reason }: { id: number; reason: string }) => api(`/expenses/${id}/void`, { method: 'POST', body: { reason } }), close)

  if (plan.error || expenses.error) {
    return <Alert severity="error">{errorMessage(t, plan.error ?? expenses.error)}</Alert>
  }
  if (!plan.data || !expenses.data) {
    return <Typography color="text.secondary">{t('common.loading')}</Typography>
  }
  const currency = plan.data.project.currency
  const money = (v: string | number) => formatMoney(v, currency)
  const { summary, items } = expenses.data
  const approved = plan.data.budgetStatus === 'APPROVED'
  const selectedExpenses = items.filter((e) => selected.includes(e.id))
  const toggle = (id: number) => setSelected(selected.includes(id) ? selected.filter((s) => s !== id) : [...selected, id])

  return (
    <Stack spacing={2}>
      {!approved && <Alert severity="info">{t('expenses.notApproved')}</Alert>}
      {approve.error && (
        <Alert severity="error" onClose={() => approve.reset()}>
          {errorMessage(t, approve.error)}
        </Alert>
      )}

      <Box sx={{ display: 'grid', gap: 2, gridTemplateColumns: { xs: '1fr 1fr', md: 'repeat(3, 1fr)' } }}>
        <Paper sx={{ p: 2 }}>
          <Typography variant="caption" color="text.secondary">
            {isTeamLead ? t('expenses.myPending') : t('expenses.pending')}
          </Typography>
          <Typography variant="h6">
            {summary.pendingCount} · {money(summary.pendingTotal)}
          </Typography>
        </Paper>
        <Paper sx={{ p: 2 }}>
          <Typography variant="caption" color="text.secondary">
            {isTeamLead ? t('expenses.owedToMe') : t('expenses.toReimburse')}
          </Typography>
          <Typography variant="h6">
            {summary.toReimburseCount} · {money(summary.toReimburseTotal)}
          </Typography>
        </Paper>
        <Box sx={{ gridColumn: { xs: '1 / -1', md: 'auto' }, alignSelf: 'center' }}>
          <Typography variant="caption" color="text.secondary">
            {t('expenses.limitHint', { limit: money(summary.teamLeadLimit) })}
          </Typography>
        </Box>
      </Box>

      <Stack direction={{ xs: 'column', sm: 'row' }} spacing={1} sx={{ alignItems: { sm: 'center' } }}>
        <TextField select label={t('expenses.filter')} value={filter} onChange={(e) => setFilter(e.target.value)} sx={{ maxWidth: { sm: 240 } }}>
          {FILTERS.map((f) => (
            <MenuItem key={f} value={f}>
              {f === '' ? t('expenses.all') : t(`expenses.status.${f.split(',')[0]}`)}
            </MenuItem>
          ))}
        </TextField>
        <Box sx={{ flexGrow: 1 }} />
        {isManager && selected.length > 0 && (
          <Button variant="outlined" onClick={() => setDialog({ kind: 'reimburse' })}>
            {t('expenses.reimburse', { count: selected.length })}
          </Button>
        )}
        {approved && (
          <Button variant="contained" startIcon={<AddIcon />} onClick={() => setDialog({ kind: 'new' })}>
            {t('expenses.new')}
          </Button>
        )}
      </Stack>

      {items.length === 0 && <Alert severity="info">{t('expenses.empty')}</Alert>}
      <Stack spacing={1}>
        {items.map((e) => (
          <Paper key={e.id} sx={{ p: 1.5, opacity: e.status === 'VOIDED' ? 0.6 : 1 }}>
            <Stack direction="row" spacing={1} sx={{ alignItems: 'flex-start' }}>
              {isManager && e.permissions.reimburse && (
                <Checkbox size="small" checked={selected.includes(e.id)} onChange={() => toggle(e.id)} slotProps={{ input: { 'aria-label': t('expenses.select') } }} sx={{ p: 0.5 }} />
              )}
              <Box sx={{ flexGrow: 1, minWidth: 0 }}>
                <Stack direction="row" spacing={1} sx={{ alignItems: 'center', flexWrap: 'wrap', gap: 0.5 }}>
                  <Typography sx={{ fontWeight: 600, cursor: 'pointer' }} onClick={() => setDialog({ kind: 'detail', expense: e })}>
                    {e.description}
                  </Typography>
                  <Chip size="small" color={STATUS_COLORS[e.status]} label={t(`expenses.status.${e.status}`)} />
                  {e.attachments.length === 0 && e.status !== 'VOIDED' && <Chip size="small" variant="outlined" color="warning" icon={<WarningIcon />} label={t('expenses.noReceipt')} />}
                </Stack>
                <Typography variant="body2" color="text.secondary">
                  {formatDate(e.date)} · {e.stage.name} · {e.category.name}
                  {e.supplier && ` · ${e.supplier}`}
                </Typography>
                <Typography variant="caption" color="text.secondary">
                  {t(`expenses.paidFrom.${e.paidFrom}`, { user: e.paidBy.fullName })}
                  {e.reimbursement && ` · ${t('expenses.reimbursedOn', { date: formatDate(e.reimbursement.date) })}`}
                </Typography>
                {e.status === 'REJECTED' && e.rejectionReason && (
                  <Typography variant="body2" color="error">
                    {t('expenses.rejected', { reason: e.rejectionReason })}
                  </Typography>
                )}
              </Box>
              <Typography sx={{ fontWeight: 700, whiteSpace: 'nowrap', textDecoration: e.status === 'VOIDED' ? 'line-through' : 'none' }}>{money(e.amount)}</Typography>
            </Stack>

            <Stack direction="row" spacing={1} sx={{ mt: 1, flexWrap: 'wrap', gap: 0.5, pl: isManager && e.permissions.reimburse ? 4 : 0 }}>
              {e.permissions.approve && (
                <Button size="small" variant="contained" color="success" onClick={() => approve.mutate(e.id)} disabled={approve.isPending}>
                  {t('expenses.approve')}
                </Button>
              )}
              {e.permissions.reject && (
                <Button size="small" color="error" onClick={() => setDialog({ kind: 'reject', expense: e })}>
                  {t('expenses.reject')}
                </Button>
              )}
              {e.permissions.edit && (
                <Button size="small" onClick={() => setDialog({ kind: 'edit', expense: e })}>
                  {t('expenses.edit')}
                </Button>
              )}
              {e.permissions.attach && (
                <Button size="small" startIcon={<AttachIcon />} onClick={() => setDialog({ kind: 'attach', expense: e })}>
                  {t('expenses.attach')}
                </Button>
              )}
              {e.permissions.void && (
                <Button size="small" color="error" onClick={() => setDialog({ kind: 'void', expense: e })}>
                  {t('expenses.void')}
                </Button>
              )}
              <Button size="small" onClick={() => setDialog({ kind: 'detail', expense: e })}>
                {t('expenses.details')}
              </Button>
            </Stack>
          </Paper>
        ))}
      </Stack>

      {dialog?.kind === 'new' && <ExpenseDialog projectId={projectId} plan={plan.data} isTeamLead={isTeamLead} onClose={close} />}
      {dialog?.kind === 'edit' && <ExpenseDialog projectId={projectId} plan={plan.data} isTeamLead={isTeamLead} expense={dialog.expense} onClose={close} />}
      {dialog?.kind === 'attach' && <AttachReceiptDialog projectId={projectId} expense={dialog.expense} onClose={close} />}
      {dialog?.kind === 'detail' && <ExpenseDetailDialog expenseId={dialog.expense.id} currency={currency} onClose={close} />}
      {dialog?.kind === 'reimburse' && (
        <ReimburseDialog
          projectId={projectId}
          expenses={selectedExpenses}
          currency={currency}
          onClose={() => {
            setSelected([])
            close()
          }}
        />
      )}
      {dialog?.kind === 'reject' && (
        <ReasonDialog
          title={t('expenses.rejectTitle')}
          label={t('expenses.rejectReason')}
          submitLabel={t('expenses.reject')}
          error={reject.error}
          pending={reject.isPending}
          onSubmit={(reason) => reject.mutate({ id: dialog.expense.id, reason })}
          onClose={close}
        />
      )}
      {dialog?.kind === 'void' && (
        <ReasonDialog
          title={t('expenses.voidTitle')}
          text={t('expenses.voidText')}
          label={t('expenses.reason')}
          submitLabel={t('expenses.void')}
          error={voidExpense.error}
          pending={voidExpense.isPending}
          onSubmit={(reason) => voidExpense.mutate({ id: dialog.expense.id, reason })}
          onClose={close}
        />
      )}
    </Stack>
  )
}
