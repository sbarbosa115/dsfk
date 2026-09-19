import AddIcon from '@mui/icons-material/Add'
import { Alert, Box, Button, Stack, Typography } from '@mui/material'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { errorMessage } from '../../lib/errors'
import { BudgetSummary } from './BudgetSummary'
import { CategoriesPanel } from './CategoriesPanel'
import { StageDialog } from './dialogs'
import { StageCard } from './StageCard'
import { usePlan, usePlanMutation } from './usePlan'

export function PlanTab({ projectId }: { projectId: string }) {
  const { t } = useTranslation()
  const plan = usePlan(projectId)
  const [adding, setAdding] = useState(false)
  const reorder = usePlanMutation(projectId, (ids: number[]) => ({ path: `/projects/${projectId}/stages/order`, method: 'PUT', body: { ids } }))

  if (plan.error) {
    return <Alert severity="error">{errorMessage(t, plan.error)}</Alert>
  }
  if (!plan.data) {
    return <Typography color="text.secondary">{t('common.loading')}</Typography>
  }
  const p = plan.data

  const move = (from: number, to: number) => {
    const ids = p.stages.map((s) => s.id)
    const [id] = ids.splice(from, 1)
    ids.splice(to, 0, id)
    reorder.mutate(ids)
  }

  return (
    <Stack spacing={2}>
      <BudgetSummary plan={p} />
      {(p.permissions.viewFinancials || p.categories.length > 0) && <CategoriesPanel plan={p} />}

      <Box>
        <Stack direction="row" sx={{ alignItems: 'center', mb: 1 }}>
          <Typography variant="h6" sx={{ flexGrow: 1 }}>
            {t('plan.stages')}
          </Typography>
          {p.permissions.edit && (
            <Button variant="contained" startIcon={<AddIcon />} onClick={() => setAdding(true)}>
              {t('plan.addStage')}
            </Button>
          )}
        </Stack>
        {reorder.error && <Alert severity="error">{errorMessage(t, reorder.error)}</Alert>}
        {p.stages.length === 0 && <Alert severity="info">{t('plan.noStages')}</Alert>}
        <Stack spacing={1}>
          {p.stages.map((stage, index) => (
            <StageCard key={stage.id} plan={p} stage={stage} index={index} onMove={move} />
          ))}
        </Stack>
      </Box>

      {adding && <StageDialog plan={p} onClose={() => setAdding(false)} />}
    </Stack>
  )
}
