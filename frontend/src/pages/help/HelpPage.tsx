import ClearIcon from '@mui/icons-material/Close'
import SearchIcon from '@mui/icons-material/Search'
import {
  Alert,
  Box,
  Chip,
  FormControlLabel,
  IconButton,
  InputAdornment,
  List,
  ListItemButton,
  ListItemText,
  Paper,
  Stack,
  Switch,
  TextField,
  Typography,
} from '@mui/material'
import { useMemo, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { useAuth } from '../../auth/AuthContext'
import { PageHeader } from '../../layout/PageHeader'
import { helpTopics, type HelpAudience, type HelpTopic } from './content'
import { audiencesOf, searchTopics, topicsFor } from './search'

export function AudienceChips({ audiences }: { audiences: HelpAudience[] }) {
  const { t } = useTranslation()

  return (
    <Stack direction="row" spacing={0.5} sx={{ flexWrap: 'wrap', gap: 0.5 }}>
      {audiences.map((audience) => (
        <Chip key={audience} size="small" variant="outlined" label={t(`roles.${audience}`)} />
      ))}
    </Stack>
  )
}

export function HelpPage() {
  const { t } = useTranslation()
  const { user } = useAuth()
  const [query, setQuery] = useState('')
  const [showAll, setShowAll] = useState(false)

  const audiences = useMemo(() => audiencesOf(user), [user])
  // Admins support the other roles, so they may opt into the whole manual.
  const available: HelpTopic[] = showAll && user?.admin ? helpTopics : topicsFor(helpTopics, audiences)
  const results = useMemo(() => searchTopics(available, query), [available, query])
  const searching = query.trim().length > 0

  return (
    <>
      <PageHeader title={t('help.title')} subtitle={t('help.subtitle')} />

      <TextField
        fullWidth
        value={query}
        onChange={(e) => setQuery(e.target.value)}
        label={t('help.search')}
        helperText={t('help.searchHint')}
        sx={{ maxWidth: 640, mb: 3 }}
        slotProps={{
          input: {
            startAdornment: (
              <InputAdornment position="start">
                <SearchIcon color="action" />
              </InputAdornment>
            ),
            endAdornment: searching ? (
              <InputAdornment position="end">
                <IconButton size="small" onClick={() => setQuery('')} aria-label={t('help.clear')}>
                  <ClearIcon fontSize="small" />
                </IconButton>
              </InputAdornment>
            ) : null,
          },
        }}
      />

      {user?.admin && (
        <Box sx={{ mb: 2 }}>
          <FormControlLabel
            control={<Switch checked={showAll} onChange={(e) => setShowAll(e.target.checked)} />}
            label={t('help.showAll')}
          />
          <Typography variant="body2" color="text.secondary">
            {t('help.showAllHint')}
          </Typography>
        </Box>
      )}

      <Typography variant="subtitle2" color="text.secondary" sx={{ mb: 1 }}>
        {searching ? t('help.results', { count: results.length, query: query.trim() }) : t('help.forYou')}
      </Typography>

      {!searching && !showAll && (
        <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
          {t('help.forYouHint')}
        </Typography>
      )}

      {available.length === 0 && <Alert severity="info">{t('help.empty')}</Alert>}

      {available.length > 0 && results.length === 0 && (
        <Alert severity="info">
          {t('help.noResults', { query: query.trim() })} {t('help.noResultsHint')}
        </Alert>
      )}

      {results.length > 0 && (
        <Paper variant="outlined">
          <List disablePadding>
            {results.map(({ topic, snippet }) => (
              <ListItemButton key={topic.id} component={Link} to={`/help/${topic.id}`} divider>
                <ListItemText
                  disableTypography
                  primary={
                    <Typography variant="body1" sx={{ fontWeight: 500 }}>
                      {topic.title}
                    </Typography>
                  }
                  secondary={
                    <Box>
                      <Typography variant="body2" color="text.secondary">
                        {snippet}
                      </Typography>
                      <Box sx={{ display: 'flex', mt: 0.5 }}>
                        <AudienceChips audiences={topic.audiences} />
                      </Box>
                    </Box>
                  }
                />
              </ListItemButton>
            ))}
          </List>
        </Paper>
      )}
    </>
  )
}
