import ArrowBackIcon from '@mui/icons-material/ArrowBackOutlined'
import { Alert, Box, Button, Divider, Paper, Stack, Typography } from '@mui/material'
import { useMemo } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useParams } from 'react-router'
import { useAuth } from '../../auth/AuthContext'
import { PageHeader } from '../../layout/PageHeader'
import { topicById, type HelpSection, type HelpTopic } from './content'
import { audiencesOf, visibleTo } from './search'
import { AudienceChips } from './HelpPage'

function Section({ section }: { section: HelpSection }) {
  const { t } = useTranslation()

  return (
    <Box component="section" sx={{ '& + &': { mt: 4 } }}>
      {section.heading && (
        <Typography variant="h6" component="h2" sx={{ mb: 1 }}>
          {section.heading}
        </Typography>
      )}

      {section.body?.map((paragraph) => (
        <Typography key={paragraph} variant="body1" sx={{ mb: 1.5 }}>
          {paragraph}
        </Typography>
      ))}

      {section.steps && (
        <>
          <Typography variant="subtitle2" color="text.secondary" sx={{ mb: 0.5 }}>
            {t('help.steps')}
          </Typography>
          <Box component="ol" sx={{ pl: 3, m: 0, mb: 1.5, '& li': { mb: 0.75 } }}>
            {section.steps.map((step) => (
              <Typography key={step} component="li" variant="body1">
                {step}
              </Typography>
            ))}
          </Box>
        </>
      )}

      {section.image && (
        <Box component="figure" sx={{ m: 0, mb: 1.5 }}>
          <Box
            component="img"
            src={section.image.src}
            alt={section.image.caption}
            loading="lazy"
            // No width: a screenshot never scales above its natural size, only down to fit.
            sx={{ maxWidth: '100%', height: 'auto', borderRadius: 1, border: 1, borderColor: 'divider', display: 'block' }}
          />
          <Typography component="figcaption" variant="caption" color="text.secondary" sx={{ mt: 0.5, display: 'block' }}>
            {section.image.caption}
          </Typography>
        </Box>
      )}

      {section.note && (
        <Alert severity="info" sx={{ mt: 1 }}>
          {section.note}
        </Alert>
      )}
    </Box>
  )
}

export function HelpTopicPage() {
  const { t } = useTranslation()
  const { user } = useAuth()
  const { topicId } = useParams()
  const audiences = useMemo(() => audiencesOf(user), [user])

  const topic = topicId ? topicById(topicId) : undefined
  // Admins may open any topic; everyone else only the ones their role covers.
  const allowed = topic && (user?.admin || visibleTo(topic, audiences))

  const back = (
    <Button component={Link} to="/help" startIcon={<ArrowBackIcon />} sx={{ mb: 2 }}>
      {t('help.back')}
    </Button>
  )

  if (!topic || !allowed) {
    return (
      <>
        {back}
        <Alert severity="warning">{t('help.notFound')}</Alert>
      </>
    )
  }

  const related = (topic.related ?? [])
    .map((id) => topicById(id))
    .filter((item): item is HelpTopic => !!item && (!!user?.admin || visibleTo(item, audiences)))

  return (
    <>
      {back}
      <PageHeader title={topic.title} subtitle={topic.summary} />

      <Stack direction="row" spacing={1} sx={{ mb: 3, alignItems: 'center' }}>
        <Typography variant="body2" color="text.secondary">
          {t('help.audience')}:
        </Typography>
        <AudienceChips audiences={topic.audiences} />
      </Stack>

      <Paper variant="outlined" sx={{ p: { xs: 2, md: 3 }, maxWidth: 900 }}>
        {topic.sections.map((section, index) => (
          <Section key={section.heading ?? index} section={section} />
        ))}

        {related.length > 0 && (
          <>
            <Divider sx={{ my: 3 }} />
            <Typography variant="subtitle2" sx={{ mb: 1 }}>
              {t('help.related')}
            </Typography>
            <Stack spacing={0.5}>
              {related.map((item) => (
                <Typography key={item.id} component={Link} to={`/help/${item.id}`} variant="body2">
                  {item.title}
                </Typography>
              ))}
            </Stack>
          </>
        )}
      </Paper>
    </>
  )
}
