/**
 * Every string the user sees, in Spanish (es-CO), tuteo. Keys are grouped by screen; `errors` holds API codes.
 * One word, one meaning across the app: grep here before naming something new.
 */
export const es = {
  app: {
    name: 'Control de Proyectos',
    tagline: 'Obras, dinero y avance',
  },
  nav: {
    label: 'Menú principal',
    section: {
      work: 'Obras',
      admin: 'Administración',
      help: 'Ayuda',
    },
    dashboard: 'Tablero',
    projects: 'Proyectos',
    users: 'Usuarios',
    settings: 'Configuración',
    audit: 'Auditoría',
    help: 'Documentación',
  },
  common: {
    working: 'Procesando…',
    optional: 'opcional',
    close: 'Cerrar',
    retry: 'Reintentar',
    loading: 'Cargando…',
    search: 'Buscar…',
    searchLabel: 'Buscar',
    rowLegend: 'Color de la fila:',
    showAll: 'Ver todos',
    pickDate: 'Elegir en el calendario',
    datePart: {day: 'dd', month: 'mm', year: 'aaaa'},
    previous: 'Anterior',
    next: 'Siguiente',
    pageOf: 'Página {{page}} de {{pages}}',
    cancel: 'Cancelar',
    save: 'Guardar',
    create: 'Crear',
    menu: 'Menú',
    closeMenu: 'Cerrar menú',
    logout: 'Cerrar sesión',
    all: 'Todos',
    choose: 'Selecciona…',
    edit: 'Editar',
    disable: 'Desactivar',
    enable: 'Activar',
    active: 'Activo',
    inactive: 'Inactivo',
    onlyActive: 'Solo activos',
    status: 'Estado',
    actions: 'Acciones',
    saved: 'Cambios guardados.',
    home: 'Ir al inicio',
    notFound: 'Esta página no existe.',
    confirm: 'Confirmar',
  },
  money: {
    hint: 'Ejemplo: {{example}}',
  },
  theme: {
    label: 'Tema',
    light: 'Claro',
    dark: 'Oscuro',
    system: 'Según el dispositivo',
  },
  roles: {
    SUPER_ADMIN: 'Super administrador',
    ADMIN: 'Administrador',
    PROJECT_MANAGER: 'Gerente de proyecto',
    TEAM_LEAD: 'Líder de equipo',
    USER: 'Sin rol global',
  },
  login: {
    title: 'Iniciar sesión',
    subtitle:
      'Entra con tu correo y la contraseña que te dio el administrador.',
    email: 'Correo electrónico',
    password: 'Contraseña',
    submit: 'Ingresar',
    noAccount: '¿No tienes cuenta? Pídesela al administrador de la obra.',
  },
  account: {
    changePassword: 'Cambiar contraseña',
    current: 'Contraseña actual',
    new: 'Nueva contraseña',
    newHint: 'Mínimo 8 caracteres.',
    changed: 'Contraseña actualizada.',
  },
  impersonation: {
    label: 'Ver como',
    none: 'Nadie (mi cuenta)',
    hint: 'Para pruebas: navegas con los permisos de esa persona y lo que hagas queda a su nombre, indicando que fuiste tú.',
    noRole: 'sin proyectos',
    banner:
      'Estás viendo la aplicación como {{name}}. Lo que hagas quedará registrado a su nombre (vía {{admin}}).',
    back: 'Volver a {{admin}}',
    failed: 'No se pudo cambiar de usuario.',
  },
  users: {
    title: 'Usuarios',
    subtitle:
      'Las personas que entran a la aplicación. Su rol en cada obra se asigna desde el proyecto.',
    searchPlaceholder: 'Buscar por nombre o correo…',
    new: 'Nuevo usuario',
    edit: 'Editar usuario',
    fullName: 'Nombre completo',
    email: 'Correo electrónico',
    access: 'Acceso',
    projects: 'Proyectos',
    password: 'Contraseña',
    passwordHint:
      'Mínimo 8 caracteres. Compártela con la persona para su primer ingreso.',
    newPassword: 'Nueva contraseña',
    newPasswordHint: 'Déjala vacía para no cambiarla.',
    admin: 'Administrador',
    adminHint:
      'Ve y hace todo en todos los proyectos: aprueba presupuestos, deposita dinero, gestiona usuarios.',
    superAdmin: 'Super administrador',
    superAdminHint:
      'Además puede usar "Ver como" y dar o quitar este nivel a otros administradores.',
    empty: 'Ningún usuario coincide con la búsqueda.',
    emptyAll:
      'Aún no hay usuarios. Crea las cuentas de gerentes y líderes de equipo.',
    noProjects: 'Sin proyectos',
    lockedSuperAdmin:
      'Solo un super administrador puede editar a otro super administrador.',
    confirmDisable:
      '{{name}} no podrá entrar a la aplicación hasta que vuelvas a activar su cuenta.',
    disabled: 'La cuenta de {{name}} quedó desactivada.',
    enabled: '{{name}} ya puede entrar de nuevo.',
  },
  settings: {
    title: 'Configuración',
    subtitle: 'Valores generales que usan todos los proyectos.',
    defaultCurrency: 'Moneda por defecto',
    defaultCurrencyHint:
      'Código ISO (COP, USD…) para los proyectos nuevos. Un proyecto no cambia de moneda.',
    teamLeadExpenseLimit: 'Límite por gasto de líder de equipo',
    teamLeadExpenseLimitHint:
      'Por encima de este valor, el administrador también debe aprobar el gasto.',
    pettyCashLowBalancePercent: 'Alerta de caja menor baja (%)',
    pettyCashLowBalancePercentHint:
      'Avisa cuando el saldo baja de este porcentaje de la última recarga.',
    budgetWarningPercents: 'Alertas de presupuesto (%)',
    budgetWarningPercentsHint:
      'Porcentajes de ejecución que generan alerta, separados por comas. Ej.: 80, 100',
  },
  errors: {
    generic: 'Ocurrió un error. Intenta de nuevo.',
    unknown_error: 'Ocurrió un error. Intenta de nuevo.',
    server_error: 'Error del servidor. Si continúa, avisa al administrador.',
    not_found: 'No se encontró el registro. Puede que haya sido eliminado.',
    forbidden: 'No tienes permiso para realizar esta acción.',
    authentication_required: 'Tu sesión terminó. Inicia sesión de nuevo.',
    csrf_header_missing:
      'Solicitud rechazada por seguridad. Recarga la página.',
    validation_failed: 'Revisa los campos marcados.',
    invalid_request: 'La solicitud no es válida.',
    bad_request: 'La solicitud no es válida.',
    too_many_attempts: 'Demasiados intentos. Intenta de nuevo en unos minutos.',
    invalid_credentials: 'Correo o contraseña incorrectos.',
    account_disabled: 'Tu cuenta está desactivada. Contacta al administrador.',
    email_taken: 'Ya existe un usuario con ese correo.',
    user_not_found: 'El usuario no existe.',
    cannot_change_own_access:
      'No puedes quitarte el acceso de administrador ni desactivar tu propia cuenta.',
    super_admin_required:
      'Solo un super administrador puede hacer este cambio.',
    switch_user_not_allowed: 'No se puede cambiar de usuario de esta forma.',
    invalid_amount: 'El monto no es válido para la moneda del proyecto.',
  },
};
