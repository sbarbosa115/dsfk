import AddIcon from '@mui/icons-material/Add'
import EditIcon from '@mui/icons-material/EditOutlined'
import {
  Alert,
  Button,
  Chip,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  FormControlLabel,
  IconButton,
  Paper,
  Stack,
  Switch,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  TextField,
  Typography,
} from '@mui/material'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { api } from '../api/client'
import type { User } from '../api/types'
import { useAuth } from '../auth/AuthContext'
import { PageHeader } from '../layout/PageHeader'
import { errorMessage, fieldError } from '../lib/errors'

export function UsersPage() {
  const { t } = useTranslation()
  const users = useQuery({ queryKey: ['users'], queryFn: () => api<User[]>('/users') })
  // undefined = closed, null = creating, User = editing
  const [dialogUser, setDialogUser] = useState<User | null | undefined>(undefined)

  return (
    <>
      <PageHeader
        title={t('users.title')}
        action={
          <Button variant="contained" startIcon={<AddIcon />} onClick={() => setDialogUser(null)}>
            {t('users.new')}
          </Button>
        }
      />
      {users.isPending && <Typography color="text.secondary">{t('common.loading')}</Typography>}
      {users.error && <Alert severity="error">{errorMessage(t, users.error)}</Alert>}
      {users.data && (
        <TableContainer component={Paper}>
          <Table size="small">
            <TableHead>
              <TableRow>
                <TableCell>{t('users.fullName')}</TableCell>
                <TableCell>{t('users.email')}</TableCell>
                <TableCell>{t('users.status')}</TableCell>
                <TableCell />
              </TableRow>
            </TableHead>
            <TableBody>
              {users.data.map((u) => (
                <TableRow key={u.id} hover>
                  <TableCell>
                    {u.fullName}{' '}
                    {u.admin && (
                      <Chip
                        size="small"
                        color="primary"
                        label={u.superAdmin ? t('roles.SUPER_ADMIN') : t('roles.ADMIN')}
                        sx={{ ml: 1 }}
                      />
                    )}
                  </TableCell>
                  <TableCell>{u.email}</TableCell>
                  <TableCell>
                    <Chip size="small" label={u.active ? t('users.active') : t('users.inactive')} color={u.active ? 'success' : 'default'} />
                  </TableCell>
                  <TableCell align="right">
                    <IconButton aria-label={t('common.edit')} onClick={() => setDialogUser(u)}>
                      <EditIcon />
                    </IconButton>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </TableContainer>
      )}
      {dialogUser !== undefined && <UserDialog user={dialogUser} onClose={() => setDialogUser(undefined)} />}
    </>
  )
}

function UserDialog({ user, onClose }: { user: User | null; onClose: () => void }) {
  const { t } = useTranslation()
  const { user: me } = useAuth()
  const queryClient = useQueryClient()
  const isSelf = user?.id === me?.id
  const [form, setForm] = useState({
    fullName: user?.fullName ?? '',
    email: user?.email ?? '',
    password: '',
    admin: user?.admin ?? false,
    superAdmin: user?.superAdmin ?? false,
    active: user?.active ?? true,
  })

  const save = useMutation({
    mutationFn: () => {
      const body = { ...form, password: form.password || undefined }

      return user ? api<User>(`/users/${user.id}`, { method: 'PATCH', body }) : api<User>('/users', { method: 'POST', body })
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['users'] })
      onClose()
    },
  })

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault()
    save.mutate()
  }

  const text = (field: 'fullName' | 'email' | 'password') => ({
    value: form[field],
    onChange: (e: { target: { value: string } }) => setForm((f) => ({ ...f, [field]: e.target.value })),
    error: !!fieldError(save.error, field),
    helperText: fieldError(save.error, field),
  })

  return (
    <Dialog open onClose={onClose} fullWidth maxWidth="xs">
      <form onSubmit={handleSubmit} noValidate>
        <DialogTitle>{user ? t('users.edit') : t('users.new')}</DialogTitle>
        <DialogContent>
          <Stack spacing={2} sx={{ pt: 1 }}>
            {save.error && <Alert severity="error">{errorMessage(t, save.error)}</Alert>}
            <TextField label={t('users.fullName')} required autoFocus {...text('fullName')} />
            <TextField label={t('users.email')} type="email" required {...text('email')} />
            <TextField
              label={user ? t('users.newPassword') : t('users.password')}
              type="password"
              autoComplete="new-password"
              required={!user}
              {...text('password')}
            />
            <FormControlLabel
              control={
                <Switch
                  checked={form.admin}
                  disabled={isSelf}
                  onChange={(e) => setForm((f) => ({ ...f, admin: e.target.checked, superAdmin: e.target.checked && f.superAdmin }))}
                />
              }
              label={t('users.admin')}
            />
            {/* Granting "Ver como" is itself a super admin power, so ordinary admins never see this. */}
            {me?.superAdmin && (
              <FormControlLabel
                sx={{ alignItems: 'flex-start' }}
                control={
                  <Switch
                    checked={form.superAdmin}
                    disabled={isSelf}
                    onChange={(e) => setForm((f) => ({ ...f, superAdmin: e.target.checked, admin: e.target.checked || f.admin }))}
                  />
                }
                label={
                  <>
                    {t('users.superAdmin')}
                    <Typography variant="caption" color="text.secondary" sx={{ display: 'block' }}>
                      {t('users.superAdminHint')}
                    </Typography>
                  </>
                }
              />
            )}
            {user && (
              <FormControlLabel
                control={<Switch checked={form.active} disabled={isSelf} onChange={(e) => setForm((f) => ({ ...f, active: e.target.checked }))} />}
                label={t('users.active')}
              />
            )}
          </Stack>
        </DialogContent>
        <DialogActions>
          <Button onClick={onClose}>{t('common.cancel')}</Button>
          <Button type="submit" variant="contained" disabled={save.isPending}>
            {user ? t('common.save') : t('common.create')}
          </Button>
        </DialogActions>
      </form>
    </Dialog>
  )
}
