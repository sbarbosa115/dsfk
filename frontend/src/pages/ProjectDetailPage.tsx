import ArrowBackIcon from '@mui/icons-material/ArrowBack'
import DeleteIcon from '@mui/icons-material/DeleteOutlined'
import EditIcon from '@mui/icons-material/EditOutlined'
import {
  Alert,
  Box,
  Button,
  IconButton,
  List,
  ListItem,
  ListItemText,
  MenuItem,
  Paper,
  Stack,
  Tab,
  Tabs,
  TextField,
  Typography,
} from '@mui/material'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState, type ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useParams, useSearchParams } from 'react-router'
import { api } from '../api/client'
import type { Project, ProjectRole, User } from '../api/types'
import { useAuth } from '../auth/AuthContext'
import { PageHeader } from '../layout/PageHeader'
import { errorMessage } from '../lib/errors'
import { formatDate } from '../lib/format'
import { ProjectFormDialog } from './ProjectFormDialog'
import { ProjectStatusChip } from './ProjectStatusChip'
import { ProjectDashboardTab } from './dashboard/ProjectDashboardTab'
import { ExpensesTab } from './expenses/ExpensesTab'
import { FinanceTab } from './finance/FinanceTab'
import { PettyCashTab } from './pettycash/PettyCashTab'
import { PlanTab } from './plan/PlanTab'

export function ProjectDetailPage() {
  const { t } = useTranslation()
  const { id } = useParams()
  const { user } = useAuth()
  const [editing, setEditing] = useState(false)
  const [searchParams, setSearchParams] = useSearchParams()
  const tab = (searchParams.get('tab') ?? 'plan') as 'dashboard' | 'plan' | 'expenses' | 'finance' | 'pettyCash' | 'overview'
  const setTab = (value: string) => setSearchParams({ tab: value }, { replace: true })
  const project = useQuery({ queryKey: ['projects', id], queryFn: () => api<Project>(`/projects/${id}`) })

  if (project.error) {
    return <Alert severity="error">{errorMessage(t, project.error)}</Alert>
  }
  if (!project.data) {
    return <Typography color="text.secondary">{t('common.loading')}</Typography>
  }
  const p = project.data
  const canSeeFinance = !!user?.admin || user?.memberships.some((m) => m.projectId === p.id && m.role === 'PROJECT_MANAGER')

  return (
    <>
      <Button component={Link} to="/projects" startIcon={<ArrowBackIcon />} sx={{ mb: 1 }}>
        {t('common.back')}
      </Button>
      <PageHeader
        title={p.name}
        subtitle={p.description}
        action={
          user?.admin && (
            <Button variant="outlined" startIcon={<EditIcon />} onClick={() => setEditing(true)}>
              {t('common.edit')}
            </Button>
          )
        }
      />

      <Tabs value={tab} onChange={(_, v) => setTab(v)} variant="scrollable" allowScrollButtonsMobile sx={{ mb: 2, borderBottom: 1, borderColor: 'divider' }}>
        {canSeeFinance && <Tab value="dashboard" label={t('tabs.dashboard')} />}
        <Tab value="plan" label={t('tabs.plan')} />
        <Tab value="expenses" label={t('tabs.expenses')} />
        {canSeeFinance && <Tab value="finance" label={t('tabs.finance')} />}
        {canSeeFinance && <Tab value="pettyCash" label={t('tabs.pettyCash')} />}
        <Tab value="overview" label={t('tabs.overview')} />
      </Tabs>

      {tab === 'dashboard' && canSeeFinance && <ProjectDashboardTab projectId={String(p.id)} />}
      {tab === 'plan' && <PlanTab projectId={String(p.id)} />}
      {tab === 'expenses' && <ExpensesTab projectId={String(p.id)} />}
      {tab === 'finance' && canSeeFinance && <FinanceTab projectId={String(p.id)} />}
      {tab === 'pettyCash' && canSeeFinance && <PettyCashTab projectId={String(p.id)} currency={p.currency} />}

      {tab === 'overview' && (
      <Box sx={{ display: 'grid', gap: 2, gridTemplateColumns: { xs: '1fr', lg: '1fr 1fr' } }}>
        <Paper sx={{ p: 2 }}>
          <Typography variant="subtitle1" sx={{ mb: 1 }}>
            {t('projects.details')}
          </Typography>
          <Box component="dl" sx={{ display: 'grid', gridTemplateColumns: 'max-content 1fr', columnGap: 2, rowGap: 1, m: 0 }}>
            <Detail label={t('projects.status')} value={<ProjectStatusChip status={p.status} />} />
            <Detail label={t('projects.currency')} value={p.currency} />
            <Detail label={t('projects.plannedStart')} value={formatDate(p.plannedStart)} />
            <Detail label={t('projects.plannedEnd')} value={formatDate(p.plannedEnd)} />
          </Box>
        </Paper>

        <MembersPanel project={p} canManage={!!user?.admin} />
      </Box>
      )}

      {editing && <ProjectFormDialog project={p} onClose={() => setEditing(false)} />}
    </>
  )
}

function Detail({ label, value }: { label: string; value: ReactNode }) {
  return (
    <>
      <Typography component="dt" variant="body2" color="text.secondary">
        {label}
      </Typography>
      <Typography component="dd" variant="body2" sx={{ m: 0 }}>
        {value}
      </Typography>
    </>
  )
}

function MembersPanel({ project, canManage }: { project: Project; canManage: boolean }) {
  const { t } = useTranslation()
  const queryClient = useQueryClient()
  const [userId, setUserId] = useState('')
  const [role, setRole] = useState<ProjectRole>('TEAM_LEAD')
  const users = useQuery({ queryKey: ['users'], queryFn: () => api<User[]>('/users'), enabled: canManage })

  const onSaved = (updated?: Project) => {
    if (updated) {
      queryClient.setQueryData(['projects', String(project.id)], updated)
    } else {
      void queryClient.invalidateQueries({ queryKey: ['projects', String(project.id)] })
    }
  }

  const add = useMutation({
    mutationFn: () => api<Project>(`/projects/${project.id}/members`, { method: 'POST', body: { userId: Number(userId), role } }),
    onSuccess: (updated) => {
      onSaved(updated)
      setUserId('')
    },
  })
  const remove = useMutation({
    mutationFn: (memberId: number) => api<void>(`/projects/${project.id}/members/${memberId}`, { method: 'DELETE' }),
    onSuccess: () => onSaved(),
  })

  const members = project.members ?? []
  const assignable = (users.data ?? []).filter((u) => u.active && !u.admin && !members.some((m) => m.user.id === u.id))

  return (
    <Paper sx={{ p: 2 }}>
      <Typography variant="subtitle1">{t('members.title')}</Typography>
      {members.length === 0 ? (
        <Typography variant="body2" color="text.secondary" sx={{ py: 1 }}>
          {t('members.empty')}
        </Typography>
      ) : (
        <List dense>
          {members.map((m) => (
            <ListItem
              key={m.id}
              disableGutters
              secondaryAction={
                canManage && (
                  <IconButton edge="end" aria-label={t('common.remove')} onClick={() => remove.mutate(m.id)}>
                    <DeleteIcon />
                  </IconButton>
                )
              }
            >
              <ListItemText primary={m.user.fullName} secondary={`${t(`roles.${m.role}`)} · ${m.user.email}`} />
            </ListItem>
          ))}
        </List>
      )}

      {canManage && (
        <>
          {(add.error || remove.error) && (
            <Alert severity="error" sx={{ mb: 1 }}>
              {errorMessage(t, add.error ?? remove.error)}
            </Alert>
          )}
          <Stack direction={{ xs: 'column', sm: 'row' }} spacing={1} sx={{ mt: 1 }}>
            <TextField select label={t('members.user')} value={userId} onChange={(e) => setUserId(e.target.value)}>
              {assignable.map((u) => (
                <MenuItem key={u.id} value={String(u.id)}>
                  {u.fullName}
                </MenuItem>
              ))}
            </TextField>
            <TextField select label={t('members.role')} value={role} onChange={(e) => setRole(e.target.value as ProjectRole)}>
              <MenuItem value="PROJECT_MANAGER">{t('roles.PROJECT_MANAGER')}</MenuItem>
              <MenuItem value="TEAM_LEAD">{t('roles.TEAM_LEAD')}</MenuItem>
            </TextField>
            <Button variant="contained" onClick={() => add.mutate()} disabled={!userId || add.isPending} sx={{ flexShrink: 0 }}>
              {t('members.add')}
            </Button>
          </Stack>
          <Typography variant="caption" color="text.secondary" sx={{ display: 'block', mt: 1 }}>
            {t('members.adminNote')}
          </Typography>
        </>
      )}
    </Paper>
  )
}
