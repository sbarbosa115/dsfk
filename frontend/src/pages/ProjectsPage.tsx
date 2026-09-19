import AddIcon from '@mui/icons-material/Add'
import { Alert, Box, Button, Card, CardActionArea, CardContent, Stack, Typography } from '@mui/material'
import { useQuery } from '@tanstack/react-query'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useNavigate } from 'react-router'
import { api } from '../api/client'
import type { Project } from '../api/types'
import { useAuth } from '../auth/AuthContext'
import { PageHeader } from '../layout/PageHeader'
import { errorMessage } from '../lib/errors'
import { formatDate } from '../lib/format'
import { ProjectFormDialog } from './ProjectFormDialog'
import { ProjectStatusChip } from './ProjectStatusChip'

export function ProjectsPage() {
  const { t } = useTranslation()
  const { user } = useAuth()
  const navigate = useNavigate()
  const [creating, setCreating] = useState(false)
  const projects = useQuery({ queryKey: ['projects'], queryFn: () => api<Project[]>('/projects') })
  const roleIn = (projectId: number) => user?.memberships.find((m) => m.projectId === projectId)?.role

  return (
    <>
      <PageHeader
        title={t('projects.title')}
        action={
          user?.admin && (
            <Button variant="contained" startIcon={<AddIcon />} onClick={() => setCreating(true)}>
              {t('projects.new')}
            </Button>
          )
        }
      />

      {projects.isPending && <Typography color="text.secondary">{t('common.loading')}</Typography>}
      {projects.error && <Alert severity="error">{errorMessage(t, projects.error)}</Alert>}
      {projects.data?.length === 0 && <Alert severity="info">{t('projects.empty')}</Alert>}

      <Box sx={{ display: 'grid', gap: 2, gridTemplateColumns: 'repeat(auto-fill, minmax(280px, 1fr))' }}>
        {projects.data?.map((project) => {
          const role = roleIn(project.id)

          return (
            <Card key={project.id}>
              <CardActionArea component={Link} to={`/projects/${project.id}`} sx={{ height: '100%' }}>
                <CardContent>
                  <Stack direction="row" spacing={1} sx={{ justifyContent: 'space-between', alignItems: 'flex-start', mb: 1 }}>
                    <Typography variant="h6" component="h2">
                      {project.name}
                    </Typography>
                    <ProjectStatusChip status={project.status} />
                  </Stack>
                  <Typography variant="body2" color="text.secondary">
                    {formatDate(project.plannedStart)} – {formatDate(project.plannedEnd)} · {project.currency}
                  </Typography>
                  {role && (
                    <Typography variant="caption" color="text.secondary">
                      {t(`roles.${role}`)}
                    </Typography>
                  )}
                </CardContent>
              </CardActionArea>
            </Card>
          )
        })}
      </Box>

      {creating && <ProjectFormDialog onClose={() => setCreating(false)} onSaved={(p) => navigate(`/projects/${p.id}`)} />}
    </>
  )
}
