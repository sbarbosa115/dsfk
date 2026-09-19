import { createTheme } from '@mui/material'
import { esES } from '@mui/material/locale'

export const theme = createTheme(
  {
    palette: {
      primary: { main: '#1f4e79' },
      secondary: { main: '#c77c02' },
      background: { default: '#f4f6f8' },
    },
    shape: { borderRadius: 8 },
    components: {
      MuiButton: { defaultProps: { disableElevation: true } },
      MuiPaper: { defaultProps: { variant: 'outlined' } },
      MuiTextField: { defaultProps: { fullWidth: true, size: 'small' } },
    },
  },
  esES,
)
