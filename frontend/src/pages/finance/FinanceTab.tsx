import AddIcon from '@mui/icons-material/Add'
import AttachIcon from '@mui/icons-material/AttachFileOutlined'
import DescriptionIcon from '@mui/icons-material/DescriptionOutlined'
import SavingsIcon from '@mui/icons-material/SavingsOutlined'
import {
  Alert,
  Box,
  Button,
  Chip,
  LinearProgress,
  Link,
  Paper,
  Stack,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Tooltip,
  Typography,
} from '@mui/material'
import { useState, type ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import type { Movement, StageFinance } from '../../api/types'
import { errorMessage } from '../../lib/errors'
import { formatDate, formatMoney, formatPercent } from '../../lib/format'
import { usePlan } from '../plan/usePlan'
import { AttachDialog, CompleteStageDialog, DepositDialog, DrawDialog, VoidDialog } from './dialogs'
import { useFinance, useMovements } from './useFinance'

type DialogState = { kind: 'deposit' | 'draw' } | { kind: 'complete'; stage: StageFinance } | { kind: 'void' | 'attach'; movement: Movement } | null

export function FinanceTab({ projectId }: { projectId: string }) {
  const { t } = useTranslation()
  const finance = useFinance(projectId)
  const movements = useMovements(projectId)
  const plan = usePlan(projectId)
  const [dialog, setDialog] = useState<DialogState>(null)
  const close = () => setDialog(null)

  if (finance.error) {
    return <Alert severity="error">{errorMessage(t, finance.error)}</Alert>
  }
  if (!finance.data || !plan.data) {
    return <Typography color="text.secondary">{t('common.loading')}</Typography>
  }
  const f = finance.data
  const money = (v: string | number) => formatMoney(v, f.currency)

  return (
    <Stack spacing={2}>
      {!f.budgetApproved && <Alert severity="info">{t('finance.notApproved')}</Alert>}

      <Box sx={{ display: 'grid', gap: 2, gridTemplateColumns: { xs: '1fr 1fr', md: 'repeat(4, 1fr)' } }}>
        <Kpi label={t('finance.deposited')} value={money(f.totals.deposited)} detail={t('finance.ofBudget', { budget: money(f.totals.budget) })} />
        <Kpi label={t('finance.totalSpent')} value={money(f.totals.spent)} detail={t('finance.stagesAvailable') + ': ' + money(f.totals.stagesAvailable)} />
        <Kpi label={t('finance.pettyCash')} value={money(f.totals.pettyCash)} detail={t('finance.pettyCashDetail', { deposited: money(f.pettyCash.deposited) })} />
        <Kpi
          label={t('finance.contingency')}
          value={money(f.totals.contingency)}
          detail={t('finance.contingencyDetail', {
            budgeted: money(f.contingency.budgeted),
            deposited: money(f.contingency.deposited),
            carriedIn: money(f.contingency.carriedIn),
            drawn: money(f.contingency.drawn),
          })}
        />
      </Box>

      {(f.permissions.deposit || f.permissions.drawContingency) && (
        <Stack direction="row" spacing={1} sx={{ flexWrap: 'wrap', gap: 1 }}>
          {f.permissions.deposit && (
            <Button variant="contained" startIcon={<AddIcon />} onClick={() => setDialog({ kind: 'deposit' })}>
              {t('finance.newDeposit')}
            </Button>
          )}
          {f.permissions.drawContingency && (
            <Button variant="outlined" startIcon={<SavingsIcon />} onClick={() => setDialog({ kind: 'draw' })} disabled={Number(f.contingency.balance) <= 0}>
              {t('finance.draw')}
            </Button>
          )}
        </Stack>
      )}

      <Paper>
        <Typography variant="subtitle1" sx={{ p: 2, pb: 1 }}>
          {t('finance.fundingByStage')}
        </Typography>
        <TableContainer>
          <Table size="small" sx={{ minWidth: 880 }}>
            <TableHead>
              <TableRow>
                <TableCell>{t('finance.stage')}</TableCell>
                <TableCell align="right">{t('finance.budget')}</TableCell>
                <TableCell align="right">{t('finance.spent')}</TableCell>
                <TableCell align="right">{t('finance.received')}</TableCell>
                <TableCell sx={{ width: 160 }}>{t('finance.funded')}</TableCell>
                <TableCell align="right">{t('finance.available')}</TableCell>
                <TableCell align="right">{t('finance.beyondBudget')}</TableCell>
                {f.permissions.completeStages && <TableCell />}
              </TableRow>
            </TableHead>
            <TableBody>
              {f.stages.map((s) => (
                <TableRow key={s.id} hover>
                  <TableCell>
                    <Typography variant="body2" sx={{ fontWeight: 600 }}>
                      {s.name}
                    </Typography>
                    <Typography variant="caption" color="text.secondary">
                      {t(`stageStatus.${s.status}`)}
                      {Number(s.carriedOut) > 0 && ` · ${t('finance.carriedOut', { amount: money(s.carriedOut) })}`}
                    </Typography>
                  </TableCell>
                  <TableCell align="right">{money(s.budget)}</TableCell>
                  <TableCell align="right">
                    <Tooltip title={t('finance.remaining', { amount: money(s.remainingBudget) })}>
                      <Box component="span" sx={{ color: s.executed > 10000 ? 'error.main' : 'inherit', fontWeight: s.executed > 10000 ? 700 : 400 }}>
                        {money(s.spent)} ({formatPercent(s.executed)})
                      </Box>
                    </Tooltip>
                  </TableCell>
                  <TableCell align="right">
                    <Tooltip title={t('finance.receivedDetail', { deposited: money(s.deposited), draws: money(s.contingencyDraws), carriedIn: money(s.carriedIn) })}>
                      <span>{money(s.received)}</span>
                    </Tooltip>
                  </TableCell>
                  <TableCell>
                    <Stack direction="row" spacing={1} sx={{ alignItems: 'center' }}>
                      <LinearProgress variant="determinate" value={Math.min(100, s.funded / 100)} color={s.funded > 10000 ? 'warning' : 'primary'} sx={{ flexGrow: 1, height: 6, borderRadius: 3 }} />
                      <Typography variant="caption">{formatPercent(s.funded)}</Typography>
                    </Stack>
                  </TableCell>
                  <TableCell align="right" sx={{ fontWeight: 600 }}>
                    {money(s.available)}
                  </TableCell>
                  <TableCell align="right">{Number(s.beyondBudget) > 0 ? <Chip size="small" color="warning" label={money(s.beyondBudget)} /> : '—'}</TableCell>
                  {f.permissions.completeStages && (
                    <TableCell align="right">
                      {s.status === 'IN_PROGRESS' && (
                        <Button size="small" onClick={() => setDialog({ kind: 'complete', stage: s })}>
                          {t('finance.completeStage')}
                        </Button>
                      )}
                    </TableCell>
                  )}
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </TableContainer>
      </Paper>

      {f.categories.length > 0 && (
        <Paper sx={{ p: 2 }}>
          <Typography variant="subtitle1" sx={{ mb: 1 }}>
            {t('finance.byCategory')}
          </Typography>
          <Stack spacing={1.5}>
            {f.categories.map((c) => (
              <Box key={c.id}>
                <Stack direction="row" sx={{ justifyContent: 'space-between' }}>
                  <Typography variant="body2" sx={{ fontWeight: 600 }}>
                    {c.name}
                  </Typography>
                  <Typography variant="body2" color={c.executed > 10000 ? 'error' : 'text.secondary'}>
                    {money(c.spent)} / {money(c.budget)} · {formatPercent(c.executed)}
                  </Typography>
                </Stack>
                <LinearProgress variant="determinate" value={Math.min(100, c.executed / 100)} color={c.executed > 10000 ? 'error' : c.executed >= 8000 ? 'warning' : 'primary'} sx={{ height: 6, borderRadius: 3 }} />
              </Box>
            ))}
          </Stack>
        </Paper>
      )}

      <Paper sx={{ p: 2 }}>
        <Typography variant="subtitle1" sx={{ mb: 1 }}>
          {t('finance.movements')}
        </Typography>
        {movements.data?.length === 0 && (
          <Typography variant="body2" color="text.secondary">
            {t('finance.noMovements')}
          </Typography>
        )}
        <Stack divider={<Box sx={{ borderBottom: 1, borderColor: 'divider' }} />}>
          {movements.data?.map((m) => (
            <MovementRow
              key={m.id}
              movement={m}
              currency={f.currency}
              canVoid={f.permissions.void && !m.voided && m.type !== 'CARRYOVER'}
              canAttach={f.permissions.void && !m.voided && m.type === 'DEPOSIT'}
              onVoid={() => setDialog({ kind: 'void', movement: m })}
              onAttach={() => setDialog({ kind: 'attach', movement: m })}
            />
          ))}
        </Stack>
      </Paper>

      {dialog?.kind === 'deposit' && <DepositDialog projectId={projectId} finance={f} plan={plan.data} onClose={close} />}
      {dialog?.kind === 'draw' && <DrawDialog projectId={projectId} finance={f} onClose={close} />}
      {dialog?.kind === 'complete' && <CompleteStageDialog projectId={projectId} finance={f} stage={dialog.stage} onClose={close} />}
      {dialog?.kind === 'void' && <VoidDialog projectId={projectId} movement={dialog.movement} onClose={close} />}
      {dialog?.kind === 'attach' && <AttachDialog projectId={projectId} movement={dialog.movement} onClose={close} />}
    </Stack>
  )
}

function Kpi({ label, value, detail }: { label: string; value: string; detail?: ReactNode }) {
  return (
    <Paper sx={{ p: 2 }}>
      <Typography variant="caption" color="text.secondary">
        {label}
      </Typography>
      <Typography variant="h6" sx={{ fontWeight: 700 }}>
        {value}
      </Typography>
      {detail && (
        <Typography variant="caption" color="text.secondary">
          {detail}
        </Typography>
      )}
    </Paper>
  )
}

interface MovementRowProps {
  movement: Movement
  currency: string
  canVoid: boolean
  canAttach: boolean
  onVoid: () => void
  onAttach: () => void
}

function MovementRow({ movement: m, currency, canVoid, canAttach, onVoid, onAttach }: MovementRowProps) {
  const { t } = useTranslation()
  const money = (v: string) => formatMoney(v, currency)
  const destination = (e: Movement['entries'][number]) =>
    e.account === 'STAGE' ? `${t('finance.stage')}: ${e.stageName}${e.categoryName ? ` (${e.categoryName})` : ''}` : t(`finance.account.${e.account}`)

  return (
    <Box sx={{ py: 1.5, opacity: m.voided ? 0.6 : 1 }}>
      <Stack direction={{ xs: 'column', sm: 'row' }} spacing={1} sx={{ alignItems: { sm: 'center' } }}>
        <Box sx={{ flexGrow: 1 }}>
          <Stack direction="row" spacing={1} sx={{ alignItems: 'center', flexWrap: 'wrap' }}>
            <Typography variant="body2" sx={{ fontWeight: 600 }}>
              {formatDate(m.date)} · {t(`finance.type.${m.type}`)}
            </Typography>
            {m.method && <Chip size="small" variant="outlined" label={t(`finance.method.${m.method}`)} />}
            {m.reference && <Chip size="small" variant="outlined" label={m.reference} />}
            {m.voided && <Chip size="small" color="error" label={t('finance.voided')} />}
          </Stack>
          <Typography variant="caption" color="text.secondary">
            {t('finance.by', { user: m.createdBy.fullName })}
            {m.note && ` · ${m.note}`}
          </Typography>
        </Box>
        <Typography sx={{ fontWeight: 700, textDecoration: m.voided ? 'line-through' : 'none' }}>{money(m.amount)}</Typography>
      </Stack>

      <Box component="ul" sx={{ m: 0, mt: 0.5, pl: 2.5 }}>
        {m.entries.map((e, i) => (
          <Typography component="li" variant="body2" key={i} color={e.amount.startsWith('-') ? 'text.secondary' : 'text.primary'}>
            {destination(e)}: {money(e.amount)}
          </Typography>
        ))}
      </Box>

      {m.voided && (
        <Typography variant="caption" color="error">
          {t('finance.voidedBy', { user: m.voided.by, reason: m.voided.reason })}
        </Typography>
      )}

      <Stack direction="row" spacing={1} sx={{ mt: 0.5, alignItems: 'center', flexWrap: 'wrap' }}>
        {m.attachments.map((a) => (
          <Link key={a.id} href={`/api/attachments/${a.id}`} target="_blank" rel="noopener" variant="body2" sx={{ display: 'inline-flex', alignItems: 'center', gap: 0.5 }}>
            <DescriptionIcon fontSize="small" /> {a.name}
          </Link>
        ))}
        {canAttach && (
          <Button size="small" startIcon={<AttachIcon />} onClick={onAttach}>
            {t('finance.attach')}
          </Button>
        )}
        {canVoid && (
          <Button size="small" color="error" onClick={onVoid}>
            {t('finance.void')}
          </Button>
        )}
      </Stack>
    </Box>
  )
}
