import {useQuery} from '@tanstack/react-query';
import {useState} from 'react';
import {Link, useParams} from 'react-router';
import {
  fetchProject,
  projectKey,
  projectStatusLabel,
  projectTone,
  type Project,
} from '@/entities/project';
import {ProjectFormModal} from '@/features/project-edit';
import {MembersPanel} from '@/features/project-members';
import {t} from '@/shared/i18n';
import {formatDate} from '@/shared/lib/format';
import {useTabParam} from '@/shared/lib/forms';
import {
  Badge,
  Button,
  DefinitionList,
  ErrorState,
  Loading,
  PageHeader,
  TabIntro,
  TabPanel,
  Tabs,
} from '@/shared/ui';
import {FinanceBoard} from '@/widgets/finance-board';
import {PlanBoard} from '@/widgets/plan-board';

type Tab = 'plan' | 'finance' | 'overview';
// Team Leads see no money: a link to ?tab=finance lands them on the plan.
const MANAGER_TABS: readonly Tab[] = ['plan', 'finance', 'overview'];
const TEAM_LEAD_TABS: readonly Tab[] = ['plan', 'overview'];

/** One project: its plan and budget first, its money (for the Admins and the PM), then who is in it. */
export function ProjectDetailPage() {
  const {id = ''} = useParams();
  const project = useQuery({
    queryKey: projectKey(id),
    queryFn: () => fetchProject(id),
  });

  if (project.error) {
    return (
      <>
        <Link to="/projects" className="btn btn-ghost btn-sm">
          ← {t('projects.backToList')}
        </Link>
        <ErrorState error={project.error} />
      </>
    );
  }
  if (!project.data) {
    return <Loading />;
  }

  return <ProjectView project={project.data} />;
}

function ProjectView({project}: {project: Project}) {
  const admin = project.myRole === 'ADMIN';
  const manager = admin || project.myRole === 'PROJECT_MANAGER';
  const [tab, setTab] = useTabParam(manager ? MANAGER_TABS : TEAM_LEAD_TABS);
  const [editing, setEditing] = useState(false);

  return (
    <>
      <Link to="/projects" className="btn btn-ghost btn-sm">
        ← {t('projects.backToList')}
      </Link>
      <PageHeader
        title={project.name}
        subtitle={project.description ?? undefined}
        actions={
          admin ? (
            <Button variant="secondary" onClick={() => setEditing(true)}>
              {t('projects.edit')}
            </Button>
          ) : undefined
        }
      />
      <Tabs
        id="project"
        variant="page"
        label={project.name}
        value={tab}
        onChange={setTab}
        options={[
          {value: 'plan', label: t('projects.tabs.plan'), icon: 'clipboard'},
          ...(manager
            ? [
                {
                  value: 'finance' as const,
                  label: t('projects.tabs.finance'),
                  icon: 'wallet' as const,
                },
              ]
            : []),
          {
            value: 'overview',
            label: t('projects.tabs.overview'),
            icon: 'dashboard',
          },
        ]}
      />
      <TabPanel id="project" value={tab}>
        {tab === 'plan' && <PlanBoard projectId={project.id} />}
        {tab === 'finance' && <FinanceBoard projectId={project.id} />}
        {tab === 'overview' && <Overview project={project} admin={admin} />}
      </TabPanel>
      {editing && (
        <ProjectFormModal project={project} onClose={() => setEditing(false)} />
      )}
    </>
  );
}

function Overview({project, admin}: {project: Project; admin: boolean}) {
  return (
    <>
      <TabIntro>{t('projects.overviewIntro')}</TabIntro>
      <div className="settings-sections">
        <section className="card">
          <div className="card-header">
            <h2>{t('projects.details')}</h2>
          </div>
          <DefinitionList
            items={[
              [
                t('projects.statusLabel'),
                <Badge key="status" value={projectTone(project.status)}>
                  {projectStatusLabel(project.status)}
                </Badge>,
              ],
              [t('projects.currency'), project.currency],
              [t('projects.plannedStart'), formatDate(project.plannedStart)],
              [t('projects.plannedEnd'), formatDate(project.plannedEnd)],
              [t('projects.myRole'), t(`roles.${project.myRole}`)],
            ]}
          />
        </section>
        <MembersPanel project={project} canManage={admin} />
      </div>
    </>
  );
}
