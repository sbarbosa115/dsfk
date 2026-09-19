import CheckIcon from '@mui/icons-material/CheckCircleOutlined'
import EditIcon from '@mui/icons-material/EditOutlined'
import SendIcon from '@mui/icons-material/SendOutlined'
import UndoIcon from '@mui/icons-material/UndoOutlined'
import { Alert, Box, Button, Chip, IconButton, Paper, Stack, Typography } from '@mui/material'
import { useState, type ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import type { BudgetStatus, Plan } from '../../api/types'
import { FormDialog } from '../../components/FormDialog'
import { ProgressBar } from '../../components/ProgressBar'
import { formatDate, formatMoney, formatPercent } from '../../lib/format'
import { ContingencyDialog, ReturnBudgetDialog } from './dialogs'
import { usePlanMutation } from './usePlan'

const STATUS_COLORS: Record<BudgetStatus, 'default' | 'info' | 'warning' | 'success'> = {
  DRAFT: 'default',
  SUBMITTED: 'info',
  RETURNED: 'warning',
  APPROVED: 'success',
}

export function BudgetSummary({ plan }: { plan: Plan }) {
  const { t } = useTranslation()
  const [dialog, setDialog] = useState<'contingency' | 'submit' | 'approve' | 'return' | null>(null)
  const close = () => setDialog(null)
  const projectPath = `/projects/${plan.project.id}/budget`
  const submit = usePlanMutation(plan.project.id, () => ({ path: `${projectPath}/submit`, method: 'POST' }), close)
  const approve = usePlanMutation(plan.project.id, () => ({ path: `${projectPath}/approve`, method: 'POST' }), close)

  const budget = plan.budget
  const currency = plan.project.currency
  const lastReturn = [...(budget?.events ?? [])].reverse().find((e) => e.status === 'RETURNED')
  const stageName = (id?: number) => plan.stages.find((s) => s.id === id)?.name ?? ''
  const stageWeights = (id?: number) => formatPercent(plan.stages.find((s) => s.id === id)?.milestoneWeightTotal ?? 0)

  return (
    <Paper sx={{ p: 2 }}>
      <Stack direction={{ xs: 'column', md: 'row' }} spacing={2} sx={{ alignItems: { md: 'center' }, mb: 2 }}>
        <Box sx={{ flexGrow: 1 }}>
          <Stack direction="row" spacing={1} sx={{ alignItems: 'center' }}>
            <Typography variant="h6">{t('plan.budget')}</Typography>
            <Chip size="small" label={t(`budgetStatus.${plan.budgetStatus}`)} color={STATUS_COLORS[plan.budgetStatus]} />
          </Stack>
        </Box>
        <Stack direction="row" spacing={1} sx={{ flexWrap: 'wrap', gap: 1 }}>
          {plan.permissions.submit && (
            <Button variant="contained" startIcon={<SendIcon />} onClick={() => setDialog('submit')} disabled={(plan.issues?.length ?? 0) > 0}>
              {t('plan.submit')}
            </Button>
          )}
          {plan.permissions.review && (
            <>
              <Button variant="outlined" color="warning" startIcon={<UndoIcon />} onClick={() => setDialog('return')}>
                {t('plan.returnBudget')}
              </Button>
              <Button variant="contained" color="success" startIcon={<CheckIcon />} onClick={() => setDialog('approve')}>
                {t('plan.approve')}
              </Button>
            </>
          )}
        </Stack>
      </Stack>

      {budget && (
        <Box sx={{ display: 'grid', gap: 2, gridTemplateColumns: { xs: '1fr 1fr', md: 'repeat(4, 1fr)' }, mb: 2 }}>
          <Figure label={t('plan.stagesTotal')} value={formatMoney(budget.stagesTotal, currency)} />
          <Figure
            label={t('plan.contingency')}
            value={formatMoney(budget.contingency, currency)}
            action={
              plan.permissions.edit && (
                <IconButton size="small" aria-label={t('plan.editContingency')} onClick={() => setDialog('contingency')}>
                  <EditIcon fontSize="small" />
                </IconButton>
              )
            }
          />
          <Figure label={t('plan.total')} value={formatMoney(budget.total, currency)} strong />
          <ProgressBar value={plan.progress} label={t('plan.progress')} />
        </Box>
      )}
      {!budget && <ProgressBar value={plan.progress} label={t('plan.progress')} />}

      <Stack spacing={1}>
        {plan.budgetStatus === 'APPROVED' && budget?.approvedAt && <Alert severity="success">{t('plan.lockedNote', { date: formatDate(budget.approvedAt) })}</Alert>}
        {plan.budgetStatus === 'SUBMITTED' && <Alert severity="info">{t('plan.submittedNote')}</Alert>}
        {plan.budgetStatus === 'RETURNED' && lastReturn && (
          <Alert severity="warning">{t('plan.returnedNote', { user: lastReturn.user.fullName, comment: lastReturn.comment })}</Alert>
        )}
        {plan.permissions.submit && (plan.issues?.length ?? 0) > 0 && (
          <Alert severity="info">
            <Typography variant="body2" sx={{ fontWeight: 600 }}>
              {t('plan.issuesTitle')}
            </Typography>
            <Box component="ul" sx={{ m: 0, pl: 2 }}>
              {plan.issues?.map((issue, i) => (
                <li key={i}>{t(`plan.issues.${issue.code}`, { stage: stageName(issue.stageId), total: stageWeights(issue.stageId) })}</li>
              ))}
            </Box>
          </Alert>
        )}
      </Stack>

      {dialog === 'contingency' && <ContingencyDialog plan={plan} onClose={close} />}
      {dialog === 'return' && <ReturnBudgetDialog plan={plan} onClose={close} />}
      {dialog === 'submit' && (
        <FormDialog title={t('plan.submit')} submitLabel={t('plan.submit')} error={submit.error} pending={submit.isPending} onSubmit={() => submit.mutate()} onClose={close} maxWidth="xs">
          <Typography>{t('plan.submitConfirm')}</Typography>
        </FormDialog>
      )}
      {dialog === 'approve' && (
        <FormDialog title={t('plan.approve')} submitLabel={t('plan.approve')} submitColor="success" error={approve.error} pending={approve.isPending} onSubmit={() => approve.mutate()} onClose={close} maxWidth="xs">
          <Typography>{t('plan.approveConfirm')}</Typography>
        </FormDialog>
      )}
    </Paper>
  )
}

function Figure({ label, value, strong, action }: { label: string; value: string; strong?: boolean; action?: ReactNode }) {
  return (
    <Box>
      <Typography variant="caption" color="text.secondary">
        {label}
      </Typography>
      <Stack direction="row" sx={{ alignItems: 'center' }}>
        <Typography variant={strong ? 'h6' : 'subtitle1'} sx={{ fontWeight: strong ? 700 : 500 }}>
          {value}
        </Typography>
        {action}
      </Stack>
    </Box>
  )
}
