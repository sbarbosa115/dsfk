/** Every string the user sees, in Spanish (es-CO). Keys are grouped by screen; `errors` holds API codes. */
export const es = {
  app: {
    name: 'Control de Proyectos',
  },
  common: {
    notFound: 'Página no encontrada.',
    loading: 'Cargando…',
    retry: 'Reintentar',
  },
  errors: {
    generic: 'Algo salió mal. Intenta de nuevo.',
    unknown_error: 'Algo salió mal. Intenta de nuevo.',
    server_error:
      'El servidor tuvo un problema. Intenta de nuevo en un momento.',
    not_found: 'No encontrado.',
    forbidden: 'No tienes permiso para hacer esto.',
    unauthorized: 'Tu sesión terminó. Inicia sesión de nuevo.',
    csrf_header_missing:
      'Solicitud rechazada por seguridad. Recarga la página.',
    validation_failed: 'Revisa los campos marcados.',
    invalid_request: 'La solicitud no es válida.',
    too_many_attempts: 'Demasiados intentos. Espera unos minutos.',
  },
};
