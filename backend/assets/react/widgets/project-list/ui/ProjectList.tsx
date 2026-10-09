import {useState} from 'react';
import {useNavigate} from 'react-router';
import {
  PROJECT_STATUSES,
  PROJECTS_KEY,
  projectStatusLabel,
  projectTone,
  type Project,
} from '@/entities/project';
import {useSession} from '@/entities/session';
import {ProjectFormModal} from '@/features/project-edit';
import {t} from '@/shared/i18n';
import {formatDateRange} from '@/shared/lib/format';
import {useList} from '@/shared/lib/list';
import {
  ActionButton,
  Actions,
  FilterBar,
  ListView,
  PageHeader,
  Row,
  RowActions,
  RowLegend,
} from '@/shared/ui';

/** The projects a person can see: search, status filter, and (Admins) create. */
export function ProjectList() {
  const {user} = useSession();
  const navigate = useNavigate();
  const list = useList<Project>(PROJECTS_KEY, '/projects', {q: '', status: ''});
  const [creating, setCreating] = useState(false);
  const newProject = user?.admin ? (
    <ActionButton
      action="setup"
      main
      size="md"
      icon="plus"
      onClick={() => setCreating(true)}
    >
      {t('projects.new')}
    </ActionButton>
  ) : null;

  return (
    <>
      <PageHeader
        title={t('projects.title')}
        subtitle={t('projects.subtitle')}
      />
      <FilterBar
        search={list.filters.q}
        onSearch={(q) => list.update({q})}
        searchPlaceholder={t('projects.searchPlaceholder')}
        filters={[
          {
            name: 'status',
            label: t('projects.statusLabel'),
            value: list.filters.status,
            onChange: (status) => list.update({status}),
            options: [
              {value: '', label: t('common.all')},
              ...PROJECT_STATUSES.map((s) => ({
                value: s,
                label: projectStatusLabel(s),
              })),
            ],
          },
        ]}
      >
        {newProject}
      </FilterBar>
      <RowLegend
        statuses={PROJECT_STATUSES.map((s) => ({
          value: projectTone(s),
          label: projectStatusLabel(s),
        }))}
      />
      <ListView
        list={list}
        empty={t('projects.empty')}
        showAll={{status: ''}}
        emptyAll={
          user?.admin ? t('projects.emptyAll') : t('projects.emptyMember')
        }
        emptyAction={newProject}
        columns={[
          t('projects.name'),
          t('projects.dates'),
          t('projects.currency'),
          t('projects.myRole'),
        ]}
        renderRow={(project) => (
          <Row
            key={project.id}
            status={projectTone(project.status)}
            label={projectStatusLabel(project.status)}
          >
            <td>
              <div className="strong">{project.name}</div>
              {project.description && (
                <div
                  className="small muted cell-clamp"
                  title={project.description}
                >
                  {project.description}
                </div>
              )}
            </td>
            <td className="nowrap">
              {formatDateRange(project.plannedStart, project.plannedEnd)}
            </td>
            <td className="nowrap">{project.currency}</td>
            <td>{t(`roles.${project.myRole}`)}</td>
            <Actions>
              <RowActions
                name={project.name}
                view={{
                  label: t('projects.open'),
                  to: `/projects/${project.id}`,
                }}
              />
            </Actions>
          </Row>
        )}
      />
      {creating && (
        <ProjectFormModal
          project={null}
          onClose={() => setCreating(false)}
          onSaved={(saved) => navigate(`/projects/${saved.id}`)}
        />
      )}
    </>
  );
}
