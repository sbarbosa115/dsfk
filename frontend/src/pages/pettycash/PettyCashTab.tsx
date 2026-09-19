import { Alert, Box, Button, Chip, Link, Paper, Stack, TextField, Typography } from '@mui/material'
import { useQuery } from '@tanstack/react-query'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { api } from '../../api/client'
import type { PettyCashCycle } from '../../api/types'
import { FormDialog } from '../../components/FormDialog'
import { errorMessage } from '../../lib/errors'
import { formatDate, formatMoney } from '../../lib/format'
import { useMoneyMutation, usePettyCash } from '../expenses/useExpenses'

const STATUS_COLORS = { OPEN: 'success', CLOSED: 'warning', SIGNED_OFF: 'default' } as const

export function PettyCashTab({ projectId, currency }: { projectId: string; currency: string }) {
  const { t } = useTranslation()
  const pc = usePettyCash(projectId)
  const [dialog, setDialog] = useState<{ kind: 'close' } | { kind: 'signOff' | 'view'; cycle: PettyCashCycle } | null>(null)
  const [note, setNote] = useState('')
  const close = () => setDialog(null)
  const closeCycle = useMoneyMutation(projectId, () => api(`/projects/${projectId}/petty-cash/close`, { method: 'POST', body: { note } }), close)
  const signOff = useMoneyMutation(projectId, (id: number) => api(`/petty-cash-cycles/${id}/sign-off`, { method: 'POST' }), close)

  if (pc.error) {
    return <Alert severity="error">{errorMessage(t, pc.error)}</Alert>
  }
  if (!pc.data) {
    return <Typography color="text.secondary">{t('common.loading')}</Typography>
  }
  const { current, history, permissions } = pc.data
  const money = (v: string) => formatMoney(v, currency)

  return (
    <Stack spacing={2}>
      {pc.data.unsignedCount > 0 && <Alert severity="warning">{t('pettyCash.unsigned', { count: pc.data.unsignedCount })}</Alert>}

      <Paper sx={{ p: 2 }}>
        <Stack direction={{ xs: 'column', sm: 'row' }} spacing={2} sx={{ alignItems: { sm: 'center' }, mb: 2 }}>
          <Box sx={{ flexGrow: 1 }}>
            <Typography variant="caption" color="text.secondary">
              {t('pettyCash.balance')}
            </Typography>
            <Typography variant="h5" sx={{ fontWeight: 700 }}>
              {money(pc.data.balance)}
            </Typography>
            <Typography variant="body2" color="text.secondary">
              {t('pettyCash.cycle', { number: current.number })} · {t('pettyCash.openSince', { date: formatDate(current.openedAt) })}
            </Typography>
          </Box>
          {permissions.close && (
            <Button variant="outlined" onClick={() => setDialog({ kind: 'close' })}>
              {t('pettyCash.close')}
            </Button>
          )}
        </Stack>
        <CycleFigures cycle={current} money={money} />
        <Typography variant="subtitle2" sx={{ mt: 2, mb: 1 }}>
          {t('pettyCash.movements')}
        </Typography>
        <Movements cycle={current} money={money} />
      </Paper>

      {history.length > 0 && (
        <Paper sx={{ p: 2 }}>
          <Typography variant="subtitle1" sx={{ mb: 1 }}>
            {t('pettyCash.history')}
          </Typography>
          <Stack divider={<Box sx={{ borderBottom: 1, borderColor: 'divider' }} />}>
            {history.map((c) => (
              <Box key={c.id} sx={{ py: 1.5 }}>
                <Stack direction={{ xs: 'column', sm: 'row' }} spacing={1} sx={{ alignItems: { sm: 'center' } }}>
                  <Box sx={{ flexGrow: 1 }}>
                    <Stack direction="row" spacing={1} sx={{ alignItems: 'center' }}>
                      <Typography sx={{ fontWeight: 600 }}>{t('pettyCash.cycle', { number: c.number })}</Typography>
                      <Chip size="small" color={STATUS_COLORS[c.status]} label={t(`pettyCash.status.${c.status}`)} />
                    </Stack>
                    <Typography variant="caption" color="text.secondary">
                      {t('pettyCash.closedBy', { date: formatDate(c.closedAt), user: c.closedBy })}
                      {c.signedOffAt && ` · ${t('pettyCash.signedBy', { date: formatDate(c.signedOffAt), user: c.signedOffBy })}`}
                      {c.closingNote && ` · “${c.closingNote}”`}
                    </Typography>
                  </Box>
                  <Button size="small" onClick={() => setDialog({ kind: 'view', cycle: c })}>
                    {t('pettyCash.view')}
                  </Button>
                  {permissions.signOff && c.status === 'CLOSED' && (
                    <Button size="small" variant="contained" onClick={() => setDialog({ kind: 'signOff', cycle: c })}>
                      {t('pettyCash.signOff')}
                    </Button>
                  )}
                </Stack>
                <CycleFigures cycle={c} money={money} dense />
              </Box>
            ))}
          </Stack>
        </Paper>
      )}

      {dialog?.kind === 'close' && (
        <FormDialog title={t('pettyCash.closeTitle')} submitLabel={t('pettyCash.close')} error={closeCycle.error} pending={closeCycle.isPending} onSubmit={() => closeCycle.mutate(undefined)} onClose={close}>
          <Typography>{t('pettyCash.closeText', { number: current.number, balance: money(pc.data.balance) })}</Typography>
          <TextField label={t('pettyCash.note')} multiline minRows={2} value={note} onChange={(e) => setNote(e.target.value)} />
        </FormDialog>
      )}
      {(dialog?.kind === 'signOff' || dialog?.kind === 'view') && (
        <CycleDialog
          cycleId={dialog.cycle.id}
          money={money}
          signOff={dialog.kind === 'signOff' ? { error: signOff.error, pending: signOff.isPending, onConfirm: () => signOff.mutate(dialog.cycle.id) } : undefined}
          onClose={close}
        />
      )}
    </Stack>
  )
}

function CycleFigures({ cycle, money, dense }: { cycle: PettyCashCycle; money: (v: string) => string; dense?: boolean }) {
  const { t } = useTranslation()
  const figures: [string, string][] = [
    [t('pettyCash.opening'), cycle.openingBalance],
    [t('pettyCash.topUps'), cycle.topUps],
    [t('pettyCash.spent'), cycle.spent],
    [t('pettyCash.reimbursed'), cycle.reimbursed],
    [cycle.status === 'OPEN' ? t('pettyCash.current') : t('pettyCash.closing'), cycle.closingBalance],
  ]

  return (
    <Box sx={{ display: 'grid', gap: 1, gridTemplateColumns: { xs: '1fr 1fr', sm: 'repeat(5, 1fr)' }, mt: dense ? 1 : 0 }}>
      {figures.map(([label, value]) => (
        <Box key={label}>
          <Typography variant="caption" color="text.secondary">
            {label}
          </Typography>
          <Typography variant={dense ? 'body2' : 'subtitle1'} sx={{ fontWeight: 600 }}>
            {money(value)}
          </Typography>
        </Box>
      ))}
    </Box>
  )
}

function Movements({ cycle, money }: { cycle: PettyCashCycle; money: (v: string) => string }) {
  const { t } = useTranslation()
  if (!cycle.movements?.length) {
    return (
      <Typography variant="body2" color="text.secondary">
        {t('pettyCash.noMovements')}
      </Typography>
    )
  }

  return (
    <Stack spacing={0.5}>
      {cycle.movements.map((m) => (
        <Stack key={m.id} direction="row" spacing={1} sx={{ opacity: m.voided ? 0.5 : 1, alignItems: 'baseline' }}>
          <Typography variant="body2" sx={{ minWidth: 90 }} color="text.secondary">
            {formatDate(m.date)}
          </Typography>
          <Box sx={{ flexGrow: 1, minWidth: 0 }}>
            <Typography variant="body2">
              {t(`pettyCash.type.${m.type}`)}
              {m.description && ` · ${m.description}`}
            </Typography>
            {m.attachments.map((a) => (
              <Link key={a.id} href={`/api/attachments/${a.id}`} target="_blank" rel="noopener" variant="caption" sx={{ mr: 1 }}>
                {a.name}
              </Link>
            ))}
          </Box>
          <Typography variant="body2" sx={{ fontWeight: 600, whiteSpace: 'nowrap', textDecoration: m.voided ? 'line-through' : 'none' }}>
            {money(m.amount)}
          </Typography>
        </Stack>
      ))}
    </Stack>
  )
}

function CycleDialog({ cycleId, money, signOff, onClose }: {
  cycleId: number
  money: (v: string) => string
  signOff?: { error: unknown; pending: boolean; onConfirm: () => void }
  onClose: () => void
}) {
  const { t } = useTranslation()
  const cycle = useQuery({ queryKey: ['petty-cash-cycle', cycleId], queryFn: () => api<PettyCashCycle>(`/petty-cash-cycles/${cycleId}`) })

  return (
    <FormDialog
      title={cycle.data ? (signOff ? t('pettyCash.signOffTitle', { number: cycle.data.number }) : t('pettyCash.cycle', { number: cycle.data.number })) : t('common.loading')}
      submitLabel={signOff ? t('pettyCash.signOff') : t('common.back')}
      error={signOff?.error ?? cycle.error}
      pending={signOff?.pending}
      onSubmit={signOff?.onConfirm ?? onClose}
      onClose={onClose}
      maxWidth="md"
    >
      {signOff && <Typography>{t('pettyCash.signOffText')}</Typography>}
      {cycle.data && (
        <>
          <CycleFigures cycle={cycle.data} money={money} />
          <Movements cycle={cycle.data} money={money} />
        </>
      )}
    </FormDialog>
  )
}
