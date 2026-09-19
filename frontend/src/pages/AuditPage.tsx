import { Alert, Box, Button, MenuItem, Paper, Stack, Table, TableBody, TableCell, TableContainer, TableHead, TableRow, TextField, Typography } from '@mui/material'
import { useQuery } from '@tanstack/react-query'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { api } from '../api/client'
import type { AuditPage as AuditData, Project } from '../api/types'
import { PageHeader } from '../layout/PageHeader'
import { errorMessage } from '../lib/errors'

const ENTITIES = ['Budget', 'BudgetLine', 'Expense', 'FundMovement', 'Milestone', 'PettyCashCycle', 'Project', 'ProjectMember', 'Reimbursement', 'Setting', 'Stage', 'User']

const dateTime = new Intl.DateTimeFormat('es-CO', { dateStyle: 'medium', timeStyle: 'short' })

export function AuditPage() {
  const { t } = useTranslation()
  const [projectId, setProjectId] = useState('')
  const [entityType, setEntityType] = useState('')
  const [page, setPage] = useState(1)
  const projects = useQuery({ queryKey: ['projects'], queryFn: () => api<Project[]>('/projects') })
  const params = new URLSearchParams({ page: String(page), ...(projectId && { projectId }), ...(entityType && { entityType }) })
  const log = useQuery({ queryKey: ['audit', params.toString()], queryFn: () => api<AuditData>(`/audit?${params}`) })
  const pages = log.data ? Math.max(1, Math.ceil(log.data.total / log.data.perPage)) : 1
  const projectName = (id: number | null) => projects.data?.find((p) => p.id === id)?.name ?? ''

  return (
    <>
      <PageHeader title={t('audit.title')} subtitle={t('audit.subtitle')} />
      <Stack direction={{ xs: 'column', sm: 'row' }} spacing={2} sx={{ mb: 2 }}>
        <TextField select label={t('audit.project')} value={projectId} onChange={(e) => { setProjectId(e.target.value); setPage(1) }} sx={{ maxWidth: { sm: 280 } }}>
          <MenuItem value="">{t('audit.allProjects')}</MenuItem>
          {projects.data?.map((p) => (
            <MenuItem key={p.id} value={String(p.id)}>
              {p.name}
            </MenuItem>
          ))}
        </TextField>
        <TextField select label={t('audit.entity')} value={entityType} onChange={(e) => { setEntityType(e.target.value); setPage(1) }} sx={{ maxWidth: { sm: 240 } }}>
          <MenuItem value="">{t('audit.allEntities')}</MenuItem>
          {ENTITIES.map((e) => (
            <MenuItem key={e} value={e}>
              {t(`audit.entities.${e}`)}
            </MenuItem>
          ))}
        </TextField>
      </Stack>

      {log.error && <Alert severity="error">{errorMessage(t, log.error)}</Alert>}
      {log.data && (
        <TableContainer component={Paper}>
          <Table size="small" sx={{ minWidth: 800 }}>
            <TableHead>
              <TableRow>
                <TableCell>{t('audit.when')}</TableCell>
                <TableCell>{t('audit.user')}</TableCell>
                <TableCell>{t('audit.action')}</TableCell>
                <TableCell>{t('audit.record')}</TableCell>
                <TableCell>{t('audit.changes')}</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {log.data.items.map((row) => (
                <TableRow key={row.id} sx={{ verticalAlign: 'top' }}>
                  <TableCell sx={{ whiteSpace: 'nowrap' }}>{dateTime.format(new Date(row.createdAt))}</TableCell>
                  <TableCell>{row.user ?? t('audit.system')}</TableCell>
                  <TableCell>{t(`audit.actions.${row.action}`)}</TableCell>
                  <TableCell>
                    {t(`audit.entities.${row.entityType}`, { defaultValue: row.entityType })} {row.entityId && `#${row.entityId}`}
                    {row.projectId && (
                      <Typography variant="caption" color="text.secondary" sx={{ display: 'block' }}>
                        {projectName(row.projectId)}
                      </Typography>
                    )}
                  </TableCell>
                  <TableCell>
                    <Box component="ul" sx={{ m: 0, pl: 2, fontSize: 13 }}>
                      {Object.entries(row.changes).map(([field, [before, after]]) => (
                        <li key={field}>
                          <strong>{field}</strong>: {row.action === 'create' ? show(after) : `${show(before)} → ${show(after)}`}
                        </li>
                      ))}
                    </Box>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </TableContainer>
      )}
      <Stack direction="row" spacing={2} sx={{ mt: 2, alignItems: 'center', justifyContent: 'flex-end' }}>
        <Button disabled={page <= 1} onClick={() => setPage(page - 1)}>
          {t('audit.previous')}
        </Button>
        <Typography variant="body2">{t('audit.pageOf', { page, pages })}</Typography>
        <Button disabled={page >= pages} onClick={() => setPage(page + 1)}>
          {t('audit.next')}
        </Button>
      </Stack>
    </>
  )
}

function show(value: unknown): string {
  if (value === null || value === undefined || value === '') {
    return '—'
  }

  return typeof value === 'object' ? JSON.stringify(value) : String(value)
}
