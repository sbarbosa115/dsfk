import type { ProjectRole } from '../../api/types'
import auditoriaImg from './images/auditoria.jpg'
import cajaMenorImg from './images/caja-menor.jpg'
import configuracionImg from './images/configuracion.jpg'
import finanzasImg from './images/finanzas.jpg'
import gastosLiderImg from './images/gastos-lider.jpg'
import gastosRevisionImg from './images/gastos-revision.jpg'
import iniciarSesionImg from './images/iniciar-sesion.jpg'
import partidasHitosImg from './images/partidas-hitos.jpg'
import presupuestoPlanImg from './images/presupuesto-plan.jpg'
import registrarGastoImg from './images/registrar-gasto.jpg'
import tableroImg from './images/tablero.jpg'
import usuariosImg from './images/usuarios.jpg'
import verComoImg from './images/ver-como.jpg'

/**
 * The user manual. Unlike the rest of the UI this text is not in i18n/es.ts: it is long-form
 * prose rather than interface labels, and keeping it here lets each topic carry its own
 * audience, keywords and screenshots. Labels quoted below must match i18n/es.ts exactly,
 * otherwise the instructions stop matching what the user sees on screen.
 */
export type HelpAudience = ProjectRole | 'ADMIN' | 'SUPER_ADMIN'

export interface HelpSection {
  heading?: string
  body?: string[]
  steps?: string[]
  note?: string
  image?: { src: string; caption: string }
}

export interface HelpTopic {
  id: string
  title: string
  summary: string
  /** Who this topic is for. A user sees it when one of these matches a role they hold. */
  audiences: HelpAudience[]
  /** Extra search words, including wording users may try that is not in the text. */
  keywords: string[]
  sections: HelpSection[]
  related?: string[]
}

const ALL: HelpAudience[] = ['ADMIN', 'PROJECT_MANAGER', 'TEAM_LEAD']

export const helpTopics: HelpTopic[] = [
  {
    id: 'primeros-pasos',
    title: 'Entrar a la aplicación y moverte por ella',
    summary: 'Cómo iniciar sesión, qué hay en cada parte de la pantalla y cómo cambiar tu contraseña.',
    audiences: ALL,
    keywords: ['login', 'entrar', 'ingresar', 'contrasena', 'clave', 'menu', 'salir', 'cerrar sesion', 'olvide'],
    sections: [
      {
        heading: 'Iniciar sesión',
        steps: [
          'Abre la dirección de la aplicación que te compartió el administrador.',
          'Escribe tu Correo electrónico y tu Contraseña.',
          'Presiona Ingresar.',
        ],
        note: 'Si ves “Correo o contraseña incorrectos”, revisa mayúsculas y espacios. Después de varios intentos fallidos la aplicación espera unos minutos antes de dejarte reintentar. Si aparece “Tu cuenta está desactivada”, pide al administrador que la vuelva a activar.',
        image: { src: iniciarSesionImg, caption: 'Pantalla de inicio de sesión.' },
      },
      {
        heading: 'Las partes de la pantalla',
        body: [
          'A la izquierda está el menú con las secciones disponibles para tu rol. En pantallas pequeñas se abre con el botón de las tres líneas.',
          'Arriba a la derecha aparecen tu nombre, el botón de la llave para cambiar la contraseña y el botón para Cerrar sesión.',
          'Al entrar, los administradores y gerentes llegan al Tablero; los líderes de equipo llegan a Proyectos.',
        ],
      },
      {
        heading: 'Cambiar tu contraseña',
        steps: [
          'Presiona el icono de la llave (Cambiar contraseña) en la barra superior.',
          'Escribe tu Contraseña actual y la Nueva contraseña (mínimo 8 caracteres).',
          'Presiona Guardar. Verás el mensaje “Contraseña actualizada”.',
        ],
        note: 'La aplicación no envía correos para recuperar contraseñas. Si la olvidaste, el administrador debe asignarte una nueva desde Usuarios.',
      },
    ],
    related: ['roles-y-permisos'],
  },
  {
    id: 'roles-y-permisos',
    title: 'Qué puede hacer cada rol',
    summary: 'Diferencias entre administrador, gerente de proyecto y líder de equipo, y por qué no todos ven lo mismo.',
    audiences: ALL,
    keywords: ['rol', 'permiso', 'acceso', 'no veo', 'no aparece', 'no tengo permiso', 'administrador', 'gerente', 'lider'],
    sections: [
      {
        body: [
          'Lo que ves en el menú y en cada proyecto depende de tu rol. Por eso esta ayuda solo te muestra las guías de las tareas que puedes realizar.',
        ],
      },
      {
        heading: 'Administrador',
        body: [
          'Ve todos los proyectos sin necesidad de estar asignado. Crea usuarios, define la Configuración general y consulta la Auditoría.',
          'Aprueba o devuelve los presupuestos, aprueba los gastos que superan el límite y aprueba el cierre de los ciclos de caja menor.',
        ],
        note: 'Un super administrador es un administrador que además puede usar “Ver como otro usuario”. Es el único que puede otorgar ese nivel.',
      },
      {
        heading: 'Gerente de proyecto',
        body: [
          'Es el responsable de un proyecto: arma el presupuesto y el plan, registra los depósitos, controla las etapas y los hitos, aprueba los gastos del equipo y reembolsa a los líderes desde la caja menor.',
          'Cada proyecto tiene un solo gerente de proyecto.',
        ],
      },
      {
        heading: 'Líder de equipo',
        body: [
          'Registra los gastos del proyecto, sobre todo los que paga de su propio dinero, y hace seguimiento a lo que le deben.',
          'No ve el Tablero general ni la configuración, y no aprueba gastos.',
        ],
        note: 'Si crees que te falta acceso a un proyecto, pide al gerente o al administrador que te agregue al equipo del proyecto con el rol correspondiente.',
      },
    ],
  },
  {
    id: 'tablero',
    title: 'Leer el tablero y sus indicadores',
    summary: 'Cómo interpretar presupuesto, ejecución, avance, CPI, SPI, pronóstico y alertas.',
    audiences: ['ADMIN', 'PROJECT_MANAGER'],
    keywords: ['tablero', 'dashboard', 'indicadores', 'cpi', 'spi', 'pronostico', 'alertas', 'grafico', 'avance'],
    sections: [
      {
        body: [
          'El Tablero resume todos los proyectos que puedes ver. Cada tarjeta muestra el estado financiero y de avance de un proyecto, y se completa cuando el presupuesto está aprobado y hay movimientos registrados.',
        ],
      },
      {
        heading: 'Las cifras principales',
        body: [
          'Presupuesto: el total aprobado, que es la línea base con la que se compara todo lo demás.',
          'Ejecutado: cuánto se ha gastado, con el porcentaje del presupuesto que representa.',
          'Depositado y Disponible: cuánto dinero ha entrado al proyecto y cuánto queda sin usar.',
          'Avance real frente a Avance planeado: el avance se calcula con los hitos cumplidos y su peso dentro de cada etapa.',
        ],
        image: { src: tableroImg, caption: 'Tablero con la tarjeta de cada proyecto.' },
      },
      {
        heading: 'Los tres indicadores',
        body: [
          'Eficiencia del costo (CPI): valor del avance logrado por cada peso gastado. Menor a 1 significa que se gasta más de lo que avanza la obra.',
          'Cumplimiento del cronograma (SPI): avance logrado frente al planeado a la fecha. Menor a 1 significa que la obra va atrasada.',
          'Pronóstico al cierre: costo final estimado si la eficiencia actual se mantiene. La línea de Diferencia compara ese pronóstico con el presupuesto.',
        ],
        note: 'El color de cada tarjeta resume la situación: En control, Atención o Crítico.',
      },
      {
        heading: 'Alertas y gráficos',
        body: [
          'En “Requiere atención” aparecen las etapas que superaron o se acercan a su presupuesto, los hitos atrasados, los gastos por aprobar o por reembolsar y los ciclos de caja menor pendientes de aprobación.',
          'Los gráficos “Avance vs. ejecución por etapa” y “Depósitos y gastos por mes” tienen un botón Ver tabla para consultar los mismos datos como lista.',
        ],
      },
    ],
    related: ['aprobar-gastos', 'avance-etapas'],
  },
  {
    id: 'proyectos',
    title: 'Crear un proyecto y armar el equipo',
    summary: 'Datos del proyecto, moneda y cómo asignar al gerente y a los líderes de equipo.',
    audiences: ['ADMIN', 'PROJECT_MANAGER'],
    keywords: ['proyecto', 'nuevo proyecto', 'equipo', 'integrante', 'miembro', 'moneda', 'asignar'],
    sections: [
      {
        heading: 'Crear el proyecto',
        steps: [
          'Entra a Proyectos y presiona Nuevo proyecto.',
          'Completa Nombre, Descripción, Moneda y las fechas de Inicio planeado y Fin planeado.',
          'Presiona Crear.',
        ],
        note: 'La moneda no se puede cambiar después de crear el proyecto, porque todos los montos registrados quedan expresados en ella.',
      },
      {
        heading: 'Agregar integrantes',
        steps: [
          'Abre el proyecto y ve a la pestaña Resumen, sección Equipo.',
          'Presiona Agregar integrante, elige el Usuario y su Rol.',
          'Presiona Guardar.',
        ],
        note: 'Cada proyecto admite un solo gerente de proyecto: si ya hay uno, verás “El proyecto ya tiene un gerente de proyecto”. Los administradores ven todos los proyectos y no necesitan ser asignados.',
      },
    ],
    related: ['categorias-y-etapas', 'usuarios'],
  },
  {
    id: 'categorias-y-etapas',
    title: 'Crear las categorías y las etapas',
    summary: 'El primer paso del presupuesto: agrupar los costos y dividir la obra en etapas.',
    audiences: ['PROJECT_MANAGER'],
    keywords: ['categoria', 'etapa', 'fase', 'presupuesto', 'plan', 'ordenar', 'nomina', 'materiales'],
    sections: [
      {
        body: [
          'El presupuesto se arma en la pestaña Presupuesto y plan del proyecto, en este orden: primero las categorías, después las etapas, dentro de cada etapa las partidas y por último los hitos.',
        ],
      },
      {
        heading: 'Categorías',
        steps: [
          'En la sección Categorías presiona Nueva categoría.',
          'Escribe el nombre (por ejemplo Nómina, Materiales o Equipos) y presiona Guardar.',
        ],
        note: 'Las categorías agrupan las partidas y los gastos, y se pueden agregar en cualquier momento. Una categoría que ya tiene partidas asociadas no se puede eliminar.',
      },
      {
        heading: 'Etapas',
        steps: [
          'En la sección Etapas presiona Agregar etapa.',
          'Escribe el Nombre de la etapa y guarda.',
          'Usa Subir y Bajar para dejarlas en el orden en que se ejecutará la obra.',
        ],
        note: 'El orden importa: cuando finalizas una etapa, el saldo que le sobre se traslada a la siguiente.',
        image: { src: presupuestoPlanImg, caption: 'Pestaña Presupuesto y plan con las categorías y las etapas.' },
      },
    ],
    related: ['partidas', 'hitos'],
  },
  {
    id: 'partidas',
    title: 'Agregar las partidas del presupuesto',
    summary: 'Cargar los costos de cada etapa con unidad, cantidad y valor unitario.',
    audiences: ['PROJECT_MANAGER'],
    keywords: ['partida', 'presupuesto', 'costo', 'cantidad', 'valor unitario', 'total', 'contingencia'],
    sections: [
      {
        heading: 'Cargar una partida',
        steps: [
          'En la etapa que corresponda, presiona Agregar partida.',
          'Elige la Categoría y escribe la Descripción.',
          'Completa Unidad, Cantidad y Valor unitario. El Total se calcula solo.',
          'Presiona Guardar.',
        ],
        note: 'Debes crear al menos una categoría antes de poder agregar partidas.',
        image: { src: partidasHitosImg, caption: 'Etapa desplegada: sus partidas y sus hitos.' },
      },
      {
        heading: 'Contingencia y totales',
        body: [
          'Arriba ves el Total etapas, la Contingencia y el Presupuesto total. Junto a cada etapa aparece el porcentaje que representa del presupuesto.',
          'La contingencia es el dinero reservado para imprevistos. Se edita con Editar contingencia mientras el presupuesto esté en Borrador.',
        ],
      },
    ],
    related: ['hitos', 'enviar-presupuesto', 'contingencia'],
  },
  {
    id: 'hitos',
    title: 'Definir los hitos de cada etapa',
    summary: 'Los hitos miden el avance real de la obra; sus pesos deben sumar 100% en cada etapa.',
    audiences: ['PROJECT_MANAGER'],
    keywords: ['hito', 'milestone', 'peso', 'avance', 'porcentaje', '100', 'cronograma'],
    sections: [
      {
        body: [
          'El avance del proyecto no se calcula con el dinero gastado sino con los hitos cumplidos. Por eso cada etapa necesita hitos antes de enviar el presupuesto.',
        ],
      },
      {
        heading: 'Agregar un hito',
        steps: [
          'Dentro de la etapa presiona Agregar hito.',
          'Escribe el Nombre del hito y su Peso en la etapa (%).',
          'Indica la Fecha planeada y presiona Guardar.',
        ],
        note: 'Los hitos de cada etapa deben sumar 100%. La aplicación muestra la Suma de pesos y no deja enviar el presupuesto hasta que cuadre.',
        image: { src: partidasHitosImg, caption: 'Hitos de la etapa con la suma de pesos en 100%.' },
      },
    ],
    related: ['avance-etapas', 'enviar-presupuesto'],
  },
  {
    id: 'enviar-presupuesto',
    title: 'Enviar el presupuesto a aprobación',
    summary: 'Qué revisa la aplicación antes de dejarte enviar, y qué pasa después.',
    audiences: ['PROJECT_MANAGER'],
    keywords: ['enviar', 'aprobacion', 'presupuesto', 'bloqueado', 'devuelto', 'observaciones', 'borrador'],
    sections: [
      {
        heading: 'Antes de enviar',
        body: [
          'Si falta algo, la aplicación lo lista en “Para enviar el presupuesto falta:”. Los casos más comunes son: no hay etapas, una etapa no tiene partidas, o los hitos de una etapa no suman 100%.',
        ],
      },
      {
        heading: 'Enviar',
        steps: [
          'Revisa que el Presupuesto total sea el correcto.',
          'Presiona Enviar a aprobación y confirma.',
        ],
        note: 'Después de enviarlo no podrás modificarlo, a menos que el administrador lo devuelva. Mientras tanto el estado es “Enviado a aprobación”.',
      },
      {
        heading: 'Si te lo devuelven',
        body: [
          'El administrador puede devolverlo con observaciones. Verás “Devuelto por … ” con el comentario, el presupuesto vuelve a quedar editable y puedes corregirlo y enviarlo de nuevo.',
          'Una vez aprobado se habilitan los depósitos y el registro de gastos.',
        ],
      },
    ],
    related: ['aprobar-presupuesto', 'depositos'],
  },
  {
    id: 'aprobar-presupuesto',
    title: 'Aprobar o devolver un presupuesto',
    summary: 'La revisión que hace el administrador antes de que el proyecto pueda mover dinero.',
    audiences: ['ADMIN'],
    keywords: ['aprobar', 'devolver', 'observaciones', 'presupuesto', 'linea base', 'bloquear'],
    sections: [
      {
        heading: 'Revisar',
        steps: [
          'Abre el proyecto y entra a la pestaña Presupuesto y plan.',
          'Revisa las etapas, sus partidas, la contingencia y el Presupuesto total.',
        ],
      },
      {
        heading: 'Aprobar',
        steps: ['Presiona Aprobar presupuesto y confirma.'],
        note: 'Una vez aprobado, el presupuesto queda bloqueado y será la línea base para comparar los gastos. Solo entonces se habilitan los depósitos y el registro de gastos.',
      },
      {
        heading: 'Devolver con observaciones',
        steps: [
          'Presiona Devolver con observaciones.',
          'Escribe las Observaciones para el gerente de proyecto: es lo único que verá para saber qué corregir.',
          'Guarda. El presupuesto vuelve a quedar editable para el gerente.',
        ],
      },
    ],
    related: ['enviar-presupuesto'],
  },
  {
    id: 'depositos',
    title: 'Registrar un depósito y distribuirlo',
    summary: 'Cómo ingresa el dinero al proyecto y cómo se reparte entre etapas, caja menor y contingencia.',
    audiences: ['PROJECT_MANAGER'],
    keywords: ['deposito', 'dinero', 'consignacion', 'transferencia', 'distribucion', 'destino', 'comprobante', 'finanzas'],
    sections: [
      {
        body: [
          'Los depósitos se registran en la pestaña Finanzas y solo se habilitan cuando el presupuesto está aprobado.',
        ],
      },
      {
        heading: 'Registrar el depósito',
        steps: [
          'En Finanzas presiona Registrar depósito.',
          'Indica la Fecha, el Medio de pago (Transferencia, Efectivo, Cheque u Otro) y la Referencia.',
          'En Distribución agrega un destino por cada parte del dinero: una Etapa, la Caja menor o la Contingencia, con su Monto. Usa Agregar destino para repartirlo entre varios.',
          'Si quieres, adjunta el Comprobante (PDF o imagen) y escribe una Nota.',
          'Revisa el Total del depósito y presiona Guardar.',
        ],
        note: 'En cada etapa la aplicación indica cuánto Falta recibir para cubrir su presupuesto, lo que ayuda a repartir el dinero.',
        image: { src: finanzasImg, caption: 'Pestaña Finanzas: saldos, fondos por etapa y movimientos.' },
      },
      {
        heading: 'Consultar y corregir',
        body: [
          'La sección Movimientos lista todo lo registrado, con quién lo hizo y cuándo.',
          'Un movimiento equivocado se corrige con Anular: deja de contar en los saldos pero queda registrado. Si parte de ese dinero ya se usó o se trasladó, la aplicación no permite anularlo.',
        ],
      },
    ],
    related: ['contingencia', 'caja-menor'],
  },
  {
    id: 'contingencia',
    title: 'Usar la contingencia',
    summary: 'Cómo pasar dinero de la reserva para imprevistos a una etapa que lo necesita.',
    audiences: ['PROJECT_MANAGER'],
    keywords: ['contingencia', 'imprevisto', 'reserva', 'sobrecosto', 'usar contingencia'],
    sections: [
      {
        body: [
          'La contingencia es la reserva del proyecto. Se alimenta de lo presupuestado, de los depósitos que le asignes y de los saldos que sobran al finalizar las etapas.',
        ],
      },
      {
        heading: 'Pasar dinero a una etapa',
        steps: [
          'En Finanzas presiona Usar contingencia.',
          'Elige la Etapa que recibe y el Monto, sin superar el Disponible en contingencia.',
          'Escribe el Motivo y guarda.',
        ],
        note: 'Queda registrado como “Uso de contingencia” en los movimientos, con su motivo, para que la auditoría muestre por qué se usó la reserva.',
      },
    ],
    related: ['depositos', 'avance-etapas'],
  },
  {
    id: 'avance-etapas',
    title: 'Iniciar etapas, cumplir hitos y finalizar',
    summary: 'El ciclo de vida de una etapa y qué pasa con el dinero que le sobra.',
    audiences: ['PROJECT_MANAGER'],
    keywords: ['etapa', 'iniciar', 'finalizar', 'hito', 'cumplido', 'traslado', 'saldo', 'atrasada', 'avance'],
    sections: [
      {
        heading: 'Iniciar la etapa',
        steps: [
          'En Presupuesto y plan, en la etapa que empieza, presiona Iniciar etapa.',
          'Indica la Fecha de inicio real y guarda. La etapa pasa de Pendiente a En curso.',
        ],
      },
      {
        heading: 'Marcar hitos cumplidos',
        steps: [
          'En el hito terminado presiona Marcar como cumplido.',
          'Indica la Fecha de cumplimiento y agrega Notas si hace falta.',
        ],
        note: 'Cada hito cumplido aumenta el avance real del proyecto según su peso. Si te equivocaste puedes usar Reabrir. Los hitos vencidos sin cumplir aparecen como Atrasado y generan alerta en el tablero.',
      },
      {
        heading: 'Finalizar la etapa',
        steps: [
          'En Finanzas presiona Finalizar etapa.',
          'Indica la Fecha de finalización y confirma.',
        ],
        note: 'Todos los hitos de la etapa deben estar cumplidos antes de finalizarla. El saldo disponible que quede se traslada automáticamente a la siguiente etapa; si es la última, pasa a la contingencia.',
      },
    ],
    related: ['hitos', 'contingencia', 'tablero'],
  },
  {
    id: 'registrar-gasto',
    title: 'Registrar un gasto',
    summary: 'Cómo cargar un gasto del proyecto y adjuntar la factura o el recibo.',
    audiences: ['PROJECT_MANAGER', 'TEAM_LEAD'],
    keywords: ['gasto', 'registrar', 'factura', 'recibo', 'soporte', 'proveedor', 'comprar', 'pagar', 'bolsillo'],
    sections: [
      {
        body: [
          'Los gastos se registran en la pestaña Gastos del proyecto y se habilitan cuando el presupuesto está aprobado.',
        ],
      },
      {
        heading: 'Cargar el gasto',
        steps: [
          'Presiona Registrar gasto.',
          'Elige la Etapa y la Categoría a la que corresponde.',
          'Indica la Fecha, el Monto y la Descripción.',
          'Completa el Proveedor y el N.º de factura cuando los tengas.',
          'En Pagado con indica de dónde salió el dinero: Fondos de la etapa, Caja menor o tu propio dinero.',
          'Adjunta el Soporte (factura o recibo) y presiona Guardar.',
        ],
        note: 'El soporte es obligatorio para que el gasto pueda aprobarse: sin él verás “Adjunta el soporte (factura o recibo) antes de aprobar”. Puedes agregarlo después con Adjuntar soporte.',
        image: { src: registrarGastoImg, caption: 'Formulario Registrar gasto.' },
      },
      {
        heading: 'Si eres líder de equipo',
        body: [
          'Registra lo que pagaste con tu dinero. El gerente de proyecto lo revisará y te lo reembolsará de la caja menor.',
          'Los gastos que superan el límite configurado también requieren aprobación del administrador; la aplicación te avisa del límite al registrarlos.',
        ],
      },
    ],
    related: ['gasto-rechazado', 'aprobar-gastos'],
  },
  {
    id: 'gasto-rechazado',
    title: 'Seguir tus gastos y cobrar lo que te deben',
    summary: 'Cómo saber en qué va cada gasto que registraste y qué hacer si te lo rechazan.',
    audiences: ['TEAM_LEAD'],
    keywords: ['rechazado', 'corregir', 'te deben', 'pendiente', 'reembolso', 'estado', 'mis gastos'],
    sections: [
      {
        heading: 'Dónde mirar',
        body: [
          'Arriba de la lista de Gastos verás Te deben, con el total de tus gastos aprobados que aún no te han reembolsado, y Pendientes de aprobación con los que siguen en revisión.',
          'El filtro Estado te deja ver solo los gastos en una situación concreta.',
        ],
        image: { src: gastosLiderImg, caption: 'Pestaña Gastos vista por un líder de equipo.' },
      },
      {
        heading: 'Qué significa cada estado',
        body: [
          'Por aprobar: el gerente todavía no lo revisa.',
          'Espera al administrador: el gerente ya lo aprobó, pero supera el límite y falta el visto bueno del administrador.',
          'Aprobado: aceptado y, si lo pagaste tú, pendiente de reembolso.',
          'Reembolsado: ya te devolvieron el dinero.',
          'Rechazado: hay algo que corregir.',
        ],
      },
      {
        heading: 'Si te lo rechazan',
        steps: [
          'Abre el gasto: verás “Rechazado” con el motivo que escribió el gerente.',
          'Presiona Corregir gasto, ajusta lo que corresponda o adjunta el soporte que faltaba.',
          'Guarda para que vuelva a quedar por aprobar.',
        ],
        note: 'En el Historial de cada gasto queda quién lo registró, quién lo corrigió, quién lo aprobó y cuándo.',
      },
    ],
    related: ['registrar-gasto'],
  },
  {
    id: 'aprobar-gastos',
    title: 'Revisar, aprobar o rechazar gastos',
    summary: 'La revisión de los gastos del equipo antes de que cuenten en el presupuesto.',
    audiences: ['ADMIN', 'PROJECT_MANAGER'],
    keywords: ['aprobar', 'rechazar', 'anular', 'gasto', 'revisar', 'limite', 'soporte', 'pendiente'],
    sections: [
      {
        heading: 'Encontrar lo pendiente',
        body: [
          'En la pestaña Gastos, el contador Por aprobar muestra lo que falta revisar. También puedes usar el filtro Estado.',
        ],
        image: { src: gastosRevisionImg, caption: 'Pestaña Gastos vista por el gerente, con las acciones de revisión.' },
      },
      {
        heading: 'Aprobar',
        steps: [
          'Abre el gasto y revisa el monto, la etapa, la categoría y el soporte adjunto.',
          'Presiona Aprobar.',
        ],
        note: 'Sin soporte adjunto la aprobación se bloquea. Si el gasto supera el límite configurado, al aprobarlo queda en “Espera al administrador” hasta que el administrador lo apruebe también.',
      },
      {
        heading: 'Rechazar o anular',
        body: [
          'Rechazar devuelve el gasto al líder para que lo corrija. El Motivo del rechazo es lo único que él verá, así que conviene ser concreto.',
          'Anular se usa cuando el gasto no debió registrarse: deja de contar en el presupuesto y el dinero vuelve a su cuenta, pero el registro se conserva.',
        ],
      },
    ],
    related: ['reembolsos', 'registrar-gasto'],
  },
  {
    id: 'reembolsos',
    title: 'Reembolsar a los líderes de equipo',
    summary: 'Cómo devolver desde la caja menor el dinero que un líder puso de su bolsillo.',
    audiences: ['PROJECT_MANAGER'],
    keywords: ['reembolso', 'reembolsar', 'devolver', 'caja menor', 'pagar al lider', 'seleccionar'],
    sections: [
      {
        heading: 'Pagar los reembolsos',
        steps: [
          'En la pestaña Gastos revisa el contador Por reembolsar.',
          'Marca la casilla Seleccionar para reembolso en cada gasto que vas a pagar.',
          'Presiona Reembolsar seleccionados y revisa el Total a reembolsar.',
          'Indica el Medio de pago y la Referencia, y confirma.',
        ],
        note: 'Solo se reembolsan gastos aprobados que fueron pagados por líderes de equipo. El dinero sale de la caja menor, así que debe haber saldo suficiente.',
      },
    ],
    related: ['caja-menor', 'aprobar-gastos'],
  },
  {
    id: 'caja-menor',
    title: 'Manejar la caja menor',
    summary: 'El saldo del día a día: recargas, gastos, reembolsos y cierre de ciclo.',
    audiences: ['PROJECT_MANAGER'],
    keywords: ['caja menor', 'ciclo', 'cerrar', 'saldo', 'recarga', 'efectivo', 'arqueo'],
    sections: [
      {
        body: [
          'La caja menor es el dinero disponible para gastos pequeños y para reembolsar a los líderes. Se maneja por ciclos: mientras un ciclo está Abierto se le suman las recargas y se le restan los gastos y reembolsos.',
        ],
      },
      {
        heading: 'Ver el estado',
        body: [
          'La pestaña Caja menor muestra el Saldo actual y el resumen del ciclo: Saldo inicial, Recargas, Gastos, Reembolsos y Saldo final.',
          'Para recargarla, registra un depósito en Finanzas con destino Caja menor.',
        ],
        image: { src: cajaMenorImg, caption: 'Pestaña Caja menor con el ciclo abierto y los anteriores.' },
      },
      {
        heading: 'Cerrar el ciclo',
        steps: [
          'Revisa que todos los movimientos del ciclo estén registrados y con su soporte.',
          'Presiona Cerrar ciclo.',
          'Agrega una Nota para el administrador si hace falta y confirma.',
        ],
        note: 'Al cerrar, el resumen queda pendiente de aprobación del administrador y se abre un ciclo nuevo con el saldo final. Los movimientos de un ciclo cerrado ya no se pueden modificar.',
      },
    ],
    related: ['aprobar-cierre-caja', 'reembolsos', 'depositos'],
  },
  {
    id: 'aprobar-cierre-caja',
    title: 'Aprobar el cierre de un ciclo de caja menor',
    summary: 'El visto bueno del administrador sobre el manejo del efectivo del proyecto.',
    audiences: ['ADMIN'],
    keywords: ['aprobar cierre', 'ciclo', 'caja menor', 'firmar', 'visto bueno', 'pendiente'],
    sections: [
      {
        heading: 'Revisar y aprobar',
        steps: [
          'Entra a la pestaña Caja menor del proyecto. Los ciclos pendientes aparecen como “Cerrado, por aprobar”.',
          'Presiona Ver movimientos y revisa las recargas, los gastos y los reembolsos con sus soportes.',
          'Presiona Aprobar cierre y confirma.',
        ],
        note: 'Al aprobar confirmas que revisaste los movimientos y soportes del ciclo. El tablero avisa cuando hay ciclos cerrados esperando aprobación.',
      },
    ],
    related: ['caja-menor'],
  },
  {
    id: 'usuarios',
    title: 'Crear y administrar usuarios',
    summary: 'Altas, cambios de contraseña y cómo desactivar a quien ya no debe entrar.',
    audiences: ['ADMIN'],
    keywords: ['usuario', 'crear', 'desactivar', 'contrasena', 'administrador', 'acceso', 'nuevo usuario'],
    sections: [
      {
        heading: 'Crear un usuario',
        steps: [
          'Entra a Usuarios y presiona Nuevo usuario.',
          'Escribe el Nombre completo, el Correo electrónico y una Contraseña inicial.',
          'Marca Administrador solo si la persona debe ver todos los proyectos y la configuración.',
          'Marca Super administrador solo si además debe poder usar “Ver como otro usuario”. Esta casilla únicamente aparece si tú eres super administrador.',
          'Presiona Guardar y entrega la contraseña a la persona para que la cambie al entrar.',
        ],
        note: 'No pueden existir dos usuarios con el mismo correo. Crear el usuario no le da acceso a ningún proyecto: además hay que agregarlo al equipo del proyecto con su rol.',
        image: { src: usuariosImg, caption: 'Listado de usuarios.' },
      },
      {
        heading: 'Cambiar o desactivar',
        body: [
          'Editar usuario permite corregir el nombre o asignar una Nueva contraseña cuando alguien la olvida; dejar el campo vacío mantiene la actual.',
          'Para quitarle el acceso a alguien, cámbialo a Inactivo en lugar de eliminarlo: así se conserva su historial de gastos y aprobaciones. Al intentar entrar verá “Tu cuenta está desactivada”.',
        ],
      },
    ],
    related: ['proyectos', 'ver-como'],
  },
  {
    id: 'configuracion',
    title: 'Configurar los valores generales',
    summary: 'Moneda por defecto, límite de gasto de los líderes y los porcentajes que disparan alertas.',
    audiences: ['ADMIN'],
    keywords: ['configuracion', 'moneda', 'limite', 'alerta', 'porcentaje', 'ajustes', 'parametros'],
    sections: [
      {
        heading: 'Qué puedes ajustar',
        body: [
          'Moneda por defecto: se aplica a los proyectos nuevos; los existentes mantienen la suya.',
          'Límite por gasto del líder de equipo: los gastos por encima de ese monto requieren además tu aprobación.',
          'Alerta de caja menor baja (%): porcentaje de la última recarga por debajo del cual se envía una alerta.',
          'Alertas de presupuesto (%): porcentajes de ejecución que generan alerta, separados por coma. Por ejemplo 80, 100.',
        ],
        note: 'Los cambios afectan de inmediato a todos los proyectos, así que conviene avisar al equipo cuando cambies el límite de gasto.',
        image: { src: configuracionImg, caption: 'Pantalla de Configuración.' },
      },
    ],
    related: ['aprobar-gastos', 'auditoria'],
  },
  {
    id: 'auditoria',
    title: 'Consultar la auditoría',
    summary: 'El registro de todos los cambios en presupuestos, dinero y configuración.',
    audiences: ['ADMIN'],
    keywords: ['auditoria', 'historial', 'quien cambio', 'registro', 'log', 'rastro', 'control'],
    sections: [
      {
        heading: 'Buscar un cambio',
        steps: [
          'Entra a Auditoría.',
          'Filtra por Proyecto y por Tipo de registro (presupuesto, gasto, movimiento de dinero, usuario, configuración…).',
          'Recorre las páginas con Anterior y Siguiente.',
        ],
        image: { src: auditoriaImg, caption: 'Auditoría con sus filtros y el detalle de cada cambio.' },
      },
      {
        heading: 'Qué muestra cada línea',
        body: [
          'La Fecha, el Usuario que hizo el cambio, la Acción (Creó, Modificó o Eliminó), el Registro afectado y los Cambios con los valores anteriores y nuevos.',
          'Cuando el cambio lo hizo un proceso automático, el usuario aparece como Sistema.',
        ],
        note: 'Es el lugar para resolver dudas del tipo “quién cambió este monto” o “cuándo se aprobó esto”.',
      },
    ],
    related: ['ver-como'],
  },
  {
    id: 'ver-como',
    title: 'Ver la aplicación como otro usuario',
    summary: 'Revisar qué ve una persona en su pantalla, para dar soporte o comprobar permisos.',
    audiences: ['SUPER_ADMIN'],
    keywords: ['ver como', 'suplantar', 'soporte', 'permisos', 'que ve', 'impersonar'],
    sections: [
      {
        heading: 'Cómo usarlo',
        steps: [
          'Presiona Ver como otro usuario en la barra superior.',
          'Elige a la persona en la lista.',
          'Cuando termines, usa Volver a mi usuario.',
        ],
        note: 'Mientras lo usas, una franja te recuerda que estás viendo la aplicación como esa persona. Lo que hagas quedará registrado a su nombre, indicando que fuiste tú, así que sirve para revisar pantallas, no para trabajar en su lugar.',
      },
      {
        heading: 'Quién puede usarlo',
        body: [
          'Solo los super administradores. Un administrador normal no ve el botón en la barra superior.',
          'El nivel de super administrador se otorga desde Usuarios y solo lo puede dar otro super administrador.',
        ],
        image: { src: verComoImg, caption: 'Menú “Ver la aplicación como…”.' },
      },
    ],
    related: ['roles-y-permisos', 'auditoria'],
  },
]

export function topicById(id: string): HelpTopic | undefined {
  return helpTopics.find((topic) => topic.id === id)
}
