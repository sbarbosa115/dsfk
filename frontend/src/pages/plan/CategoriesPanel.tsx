import AddIcon from '@mui/icons-material/Add'
import { Alert, Box, Chip, IconButton, Paper, Stack, TextField, Typography } from '@mui/material'
import { useState, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import type { Plan } from '../../api/types'
import { errorMessage, fieldError } from '../../lib/errors'
import { formatMoney } from '../../lib/format'
import { usePlanMutation } from './usePlan'

export function CategoriesPanel({ plan }: { plan: Plan }) {
  const { t } = useTranslation()
  const [name, setName] = useState('')
  const add = usePlanMutation(plan.project.id, () => ({ path: `/projects/${plan.project.id}/categories`, method: 'POST', body: { name } }), () => setName(''))
  const remove = usePlanMutation(plan.project.id, (id: number) => ({ path: `/categories/${id}`, method: 'DELETE' }))
  const totals = new Map(plan.budget?.byCategory.map((c) => [c.categoryId, c.total]))

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault()
    if (name.trim()) {
      add.mutate()
    }
  }

  return (
    <Paper sx={{ p: 2 }}>
      <Typography variant="subtitle1">{t('plan.categories')}</Typography>
      <Typography variant="caption" color="text.secondary">
        {t('plan.categoriesHint')}
      </Typography>
      <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 1, my: 1.5 }}>
        {plan.categories.map((c) => (
          <Chip
            key={c.id}
            label={totals.has(c.id) ? `${c.name} · ${formatMoney(totals.get(c.id)!, plan.project.currency)}` : c.name}
            onDelete={plan.permissions.manageCategories ? () => remove.mutate(c.id) : undefined}
          />
        ))}
      </Box>
      {remove.error && (
        <Alert severity="error" sx={{ mb: 1 }} onClose={() => remove.reset()}>
          {errorMessage(t, remove.error)}
        </Alert>
      )}
      {plan.permissions.manageCategories && (
        <Stack component="form" direction="row" spacing={1} onSubmit={handleSubmit}>
          <TextField
            label={t('plan.newCategory')}
            value={name}
            onChange={(e) => setName(e.target.value)}
            error={!!fieldError(add.error, 'name')}
            helperText={fieldError(add.error, 'name')}
          />
          <IconButton type="submit" color="primary" aria-label={t('plan.newCategory')} disabled={!name.trim() || add.isPending}>
            <AddIcon />
          </IconButton>
        </Stack>
      )}
    </Paper>
  )
}
