import ArrowDownIcon from '@mui/icons-material/ArrowDownward'
import ArrowUpIcon from '@mui/icons-material/ArrowUpward'
import CheckIcon from '@mui/icons-material/CheckCircle'
import DeleteIcon from '@mui/icons-material/DeleteOutlined'
import EditIcon from '@mui/icons-material/EditOutlined'
import ExpandMoreIcon from '@mui/icons-material/ExpandMore'
import PlayIcon from '@mui/icons-material/PlayArrowOutlined'
import RadioIcon from '@mui/icons-material/RadioButtonUnchecked'
import {
  Accordion,
  AccordionDetails,
  AccordionSummary,
  Box,
  Button,
  Chip,
  IconButton,
  List,
  ListItem,
  ListItemIcon,
  ListItemText,
  Stack,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableFooter,
  TableHead,
  TableRow,
  Tooltip,
  Typography,
} from '@mui/material'
import { useState, type ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import type { BudgetLine, Milestone, Plan, Stage } from '../../api/types'
import { ConfirmDialog } from '../../components/ConfirmDialog'
import { ProgressBar } from '../../components/ProgressBar'
import { formatDate, formatMoney, formatPercent, formatQuantity } from '../../lib/format'
import { CompleteMilestoneDialog, LineDialog, MilestoneDialog, StageDialog, StartStageDialog } from './dialogs'
import { usePlanMutation } from './usePlan'

type DialogState =
  | { kind: 'editStage' | 'deleteStage' | 'startStage' | 'addLine' | 'addMilestone' }
  | { kind: 'editLine' | 'deleteLine'; line: BudgetLine }
  | { kind: 'editMilestone' | 'deleteMilestone' | 'completeMilestone'; milestone: Milestone }
  | null

const STAGE_COLORS = { PENDING: 'default', IN_PROGRESS: 'info', COMPLETED: 'success' } as const

interface Props {
  plan: Plan
  stage: Stage
  index: number
  onMove: (from: number, to: number) => void
}

export function StageCard({ plan, stage, index, onMove }: Props) {
  const { t } = useTranslation()
  const [dialog, setDialog] = useState<DialogState>(null)
  const close = () => setDialog(null)
  const { permissions } = plan
  const currency = plan.project.currency
  const categoryName = (id: number) => plan.categories.find((c) => c.id === id)?.name ?? ''

  const deleteStage = usePlanMutation(plan.project.id, () => ({ path: `/stages/${stage.id}`, method: 'DELETE' }), close)
  const deleteLine = usePlanMutation(plan.project.id, (id: number) => ({ path: `/budget-lines/${id}`, method: 'DELETE' }), close)
  const deleteMilestone = usePlanMutation(plan.project.id, (id: number) => ({ path: `/milestones/${id}`, method: 'DELETE' }), close)
  const reopen = usePlanMutation(plan.project.id, (id: number) => ({ path: `/milestones/${id}/reopen`, method: 'POST' }))

  const weightsOk = stage.milestoneWeightTotal === 10000

  return (
    <Accordion defaultExpanded={permissions.edit} disableGutters variant="outlined">
      <AccordionSummary expandIcon={<ExpandMoreIcon />}>
        <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', md: '2fr 1fr 1fr' }, gap: 2, width: '100%', alignItems: 'center', pr: 1 }}>
          <Box sx={{ minWidth: 0 }}>
            <Stack direction="row" spacing={1} sx={{ alignItems: 'center' }}>
              <Typography sx={{ fontWeight: 600 }}>
                {index + 1}. {stage.name}
              </Typography>
              <Chip size="small" label={t(`stageStatus.${stage.status}`)} color={STAGE_COLORS[stage.status]} />
            </Stack>
            <Typography variant="caption" color="text.secondary">
              {formatDate(stage.plannedStart)} – {formatDate(stage.plannedEnd)}
              {stage.actualStart && ` · ${t('plan.actualStart')}: ${formatDate(stage.actualStart)}`}
            </Typography>
          </Box>
          {stage.budgetTotal !== undefined ? (
            <Box>
              <Typography sx={{ fontWeight: 600 }}>{formatMoney(stage.budgetTotal, currency)}</Typography>
              <Typography variant="caption" color="text.secondary">
                {t('plan.shareOfBudget', { percent: formatPercent(stage.weight ?? 0) })}
              </Typography>
            </Box>
          ) : (
            <span />
          )}
          <ProgressBar value={stage.progress} />
        </Box>
      </AccordionSummary>

      <AccordionDetails>
        {(permissions.edit || permissions.track) && (
          <Stack direction="row" spacing={1} sx={{ mb: 2, flexWrap: 'wrap', gap: 1 }}>
            {permissions.edit && (
              <>
                <Button size="small" startIcon={<EditIcon />} onClick={() => setDialog({ kind: 'editStage' })}>
                  {t('plan.editStage')}
                </Button>
                <IconButton size="small" aria-label={t('plan.moveUp')} disabled={index === 0} onClick={() => onMove(index, index - 1)}>
                  <ArrowUpIcon fontSize="small" />
                </IconButton>
                <IconButton size="small" aria-label={t('plan.moveDown')} disabled={index === plan.stages.length - 1} onClick={() => onMove(index, index + 1)}>
                  <ArrowDownIcon fontSize="small" />
                </IconButton>
                <Button size="small" color="error" startIcon={<DeleteIcon />} onClick={() => setDialog({ kind: 'deleteStage' })}>
                  {t('plan.deleteStage')}
                </Button>
              </>
            )}
            {permissions.track && (
              <Button size="small" startIcon={<EditIcon />} onClick={() => setDialog({ kind: 'editStage' })}>
                {t('plan.editStage')}
              </Button>
            )}
            {permissions.track && stage.status === 'PENDING' && (
              <Button size="small" variant="outlined" startIcon={<PlayIcon />} onClick={() => setDialog({ kind: 'startStage' })}>
                {t('plan.startStage')}
              </Button>
            )}
          </Stack>
        )}

        {stage.lines && (
          <Box sx={{ mb: 3 }}>
            <SectionTitle
              title={t('plan.lines')}
              action={
                permissions.edit && (
                  <Button size="small" onClick={() => setDialog({ kind: 'addLine' })} disabled={plan.categories.length === 0}>
                    {t('plan.addLine')}
                  </Button>
                )
              }
            />
            {permissions.edit && plan.categories.length === 0 && (
              <Typography variant="body2" color="warning.main">
                {t('plan.noCategories')}
              </Typography>
            )}
            {stage.lines.length === 0 ? (
              <Typography variant="body2" color="text.secondary">
                {t('plan.noLines')}
              </Typography>
            ) : (
              <TableContainer>
                <Table size="small" sx={{ minWidth: 640 }}>
                  <TableHead>
                    <TableRow>
                      <TableCell>{t('plan.category')}</TableCell>
                      <TableCell>{t('plan.description')}</TableCell>
                      <TableCell align="right">{t('plan.quantity')}</TableCell>
                      <TableCell align="right">{t('plan.unitPrice')}</TableCell>
                      <TableCell align="right">{t('plan.lineTotal')}</TableCell>
                      {permissions.edit && <TableCell />}
                    </TableRow>
                  </TableHead>
                  <TableBody>
                    {stage.lines.map((line) => (
                      <TableRow key={line.id} hover>
                        <TableCell>{categoryName(line.categoryId)}</TableCell>
                        <TableCell>{line.description}</TableCell>
                        <TableCell align="right" sx={{ whiteSpace: 'nowrap' }}>
                          {formatQuantity(line.quantity)} {line.unit}
                        </TableCell>
                        <TableCell align="right">{formatMoney(line.unitPrice, currency)}</TableCell>
                        <TableCell align="right">{formatMoney(line.total, currency)}</TableCell>
                        {permissions.edit && (
                          <TableCell align="right" sx={{ whiteSpace: 'nowrap' }}>
                            <IconButton size="small" aria-label={t('plan.editLine')} onClick={() => setDialog({ kind: 'editLine', line })}>
                              <EditIcon fontSize="small" />
                            </IconButton>
                            <IconButton size="small" aria-label={t('plan.deleteLine')} onClick={() => setDialog({ kind: 'deleteLine', line })}>
                              <DeleteIcon fontSize="small" />
                            </IconButton>
                          </TableCell>
                        )}
                      </TableRow>
                    ))}
                  </TableBody>
                  <TableFooter>
                    <TableRow>
                      <TableCell colSpan={4} sx={{ fontWeight: 600 }}>
                        {t('plan.stageTotal')}
                      </TableCell>
                      <TableCell align="right" sx={{ fontWeight: 600 }}>
                        {formatMoney(stage.budgetTotal ?? 0, currency)}
                      </TableCell>
                      {permissions.edit && <TableCell />}
                    </TableRow>
                  </TableFooter>
                </Table>
              </TableContainer>
            )}
          </Box>
        )}

        <SectionTitle
          title={t('plan.milestones')}
          extra={
            <Typography variant="caption" color={weightsOk ? 'text.secondary' : 'warning.main'}>
              {t('plan.weightTotal', { total: formatPercent(stage.milestoneWeightTotal) })}
            </Typography>
          }
          action={
            permissions.edit && (
              <Button size="small" onClick={() => setDialog({ kind: 'addMilestone' })} disabled={stage.milestoneWeightTotal >= 10000}>
                {t('plan.addMilestone')}
              </Button>
            )
          }
        />
        {stage.milestones.length === 0 ? (
          <Typography variant="body2" color="text.secondary">
            {t('plan.noMilestones')}
          </Typography>
        ) : (
          <List dense disablePadding>
            {stage.milestones.map((m) => (
              <ListItem
                key={m.id}
                disableGutters
                secondaryAction={
                  <Stack direction="row" spacing={0.5}>
                    {permissions.track && !m.completedAt && (
                      <Button size="small" color="success" onClick={() => setDialog({ kind: 'completeMilestone', milestone: m })}>
                        {t('plan.complete')}
                      </Button>
                    )}
                    {permissions.reopenMilestones && m.completedAt && (
                      <Button size="small" onClick={() => reopen.mutate(m.id)}>
                        {t('plan.reopen')}
                      </Button>
                    )}
                    {permissions.edit && (
                      <>
                        <IconButton size="small" aria-label={t('plan.editMilestone')} onClick={() => setDialog({ kind: 'editMilestone', milestone: m })}>
                          <EditIcon fontSize="small" />
                        </IconButton>
                        <IconButton size="small" aria-label={t('plan.deleteMilestone')} onClick={() => setDialog({ kind: 'deleteMilestone', milestone: m })}>
                          <DeleteIcon fontSize="small" />
                        </IconButton>
                      </>
                    )}
                  </Stack>
                }
                sx={{ pr: 22 }}
              >
                <ListItemIcon sx={{ minWidth: 36 }}>
                  {m.completedAt ? <CheckIcon color="success" /> : <RadioIcon color="disabled" />}
                </ListItemIcon>
                <ListItemText
                  primary={
                    <Stack direction="row" spacing={1} sx={{ alignItems: 'center' }}>
                      <span>{m.name}</span>
                      <Chip size="small" variant="outlined" label={formatPercent(m.weight)} />
                      {m.overdue && <Chip size="small" color="error" label={t('plan.overdue')} />}
                    </Stack>
                  }
                  secondary={
                    m.completedAt ? (
                      <Tooltip title={m.completionNotes ?? ''}>
                        <span>{t('plan.completedBy', { date: formatDate(m.completedAt), user: m.completedBy?.fullName })}</span>
                      </Tooltip>
                    ) : (
                      m.plannedDate && `${t('plan.plannedDate')}: ${formatDate(m.plannedDate)}`
                    )
                  }
                />
              </ListItem>
            ))}
          </List>
        )}
      </AccordionDetails>

      {dialog?.kind === 'editStage' && <StageDialog plan={plan} stage={stage} onClose={close} />}
      {dialog?.kind === 'startStage' && <StartStageDialog plan={plan} stage={stage} onClose={close} />}
      {dialog?.kind === 'addLine' && <LineDialog plan={plan} stage={stage} onClose={close} />}
      {dialog?.kind === 'editLine' && <LineDialog plan={plan} stage={stage} line={dialog.line} onClose={close} />}
      {dialog?.kind === 'addMilestone' && <MilestoneDialog plan={plan} stage={stage} onClose={close} />}
      {dialog?.kind === 'editMilestone' && <MilestoneDialog plan={plan} stage={stage} milestone={dialog.milestone} onClose={close} />}
      {dialog?.kind === 'completeMilestone' && <CompleteMilestoneDialog plan={plan} milestone={dialog.milestone} onClose={close} />}
      {dialog?.kind === 'deleteStage' && (
        <ConfirmDialog
          title={t('plan.deleteStage')}
          message={t('plan.deleteStageConfirm', { name: stage.name })}
          error={deleteStage.error}
          pending={deleteStage.isPending}
          onConfirm={() => deleteStage.mutate()}
          onClose={close}
        />
      )}
      {dialog?.kind === 'deleteLine' && (
        <ConfirmDialog
          title={t('plan.deleteLine')}
          message={t('plan.deleteLineConfirm', { name: dialog.line.description })}
          error={deleteLine.error}
          pending={deleteLine.isPending}
          onConfirm={() => deleteLine.mutate(dialog.line.id)}
          onClose={close}
        />
      )}
      {dialog?.kind === 'deleteMilestone' && (
        <ConfirmDialog
          title={t('plan.deleteMilestone')}
          message={t('plan.deleteMilestoneConfirm', { name: dialog.milestone.name })}
          error={deleteMilestone.error}
          pending={deleteMilestone.isPending}
          onConfirm={() => deleteMilestone.mutate(dialog.milestone.id)}
          onClose={close}
        />
      )}
    </Accordion>
  )
}

function SectionTitle({ title, extra, action }: { title: string; extra?: ReactNode; action?: ReactNode }) {
  return (
    <Stack direction="row" spacing={1} sx={{ alignItems: 'center', mb: 1 }}>
      <Typography variant="subtitle2">{title}</Typography>
      {extra}
      <Box sx={{ flexGrow: 1 }} />
      {action}
    </Stack>
  )
}
