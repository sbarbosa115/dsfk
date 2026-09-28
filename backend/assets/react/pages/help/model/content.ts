import auditoriaImg from '../assets/auditoria.png';
import cajaMenorImg from '../assets/caja-menor.png';
import configuracionImg from '../assets/configuracion.png';
import fondosImg from '../assets/fondos.png';
import gastosLiderImg from '../assets/gastos-lider.png';
import gastosRevisionImg from '../assets/gastos-revision.png';
import iniciarSesionImg from '../assets/iniciar-sesion.png';
import partidasHitosImg from '../assets/partidas-hitos.png';
import presupuestoPlanImg from '../assets/presupuesto-plan.png';
import registrarGastoImg from '../assets/registrar-gasto.png';
import tableroImg from '../assets/tablero.png';
import usuariosImg from '../assets/usuarios.png';
import verComoImg from '../assets/ver-como.png';

/**
 * The user manual. Unlike the rest of the UI this text is not in i18n/es.ts: it is long-form prose rather than
 * interface labels, and each topic carries its own audience, keywords and screenshots. Labels quoted below must
 * match i18n/es.ts exactly, or the instructions stop matching what people see.
 */
export type HelpAudience =
  'ADMIN' | 'SUPER_ADMIN' | 'PROJECT_MANAGER' | 'TEAM_LEAD';

export interface HelpSection {
  heading?: string;
  body?: string[];
  steps?: string[];
  note?: string;
  image?: {src: string; caption: string};
}

export interface HelpTopic {
  id: string;
  title: string;
  summary: string;
  /** Who it is for: a person sees it when one of these matches a role they hold. */
  audiences: HelpAudience[];
  /** Extra search words, including wording people may try that is not in the text. */
  keywords: string[];
  sections: HelpSection[];
  related?: string[];
}

const ALL: HelpAudience[] = ['ADMIN', 'PROJECT_MANAGER', 'TEAM_LEAD'];

export const helpTopics: HelpTopic[] = [
  {
    id: 'primeros-pasos',
    title: 'Entrar a la aplicación y moverte por ella',
    summary:
      'Cómo iniciar sesión, qué hay en cada parte de la pantalla y cómo cambiar tu contraseña.',
    audiences: ALL,
    keywords: [
      'login',
      'entrar',
      'ingresar',
      'contrasena',
      'clave',
      'menu',
      'salir',
      'cerrar sesion',
      'olvide',
      'tema',
      'oscuro',
    ],
    sections: [
      {
        heading: 'Iniciar sesión',
        steps: [
          'Abre la dirección de la aplicación que te compartió el administrador.',
          'Escribe tu Correo electrónico y tu Contraseña.',
          'Presiona Ingresar.',
        ],
        note: 'Si ves “Correo o contraseña incorrectos.”, revisa mayúsculas y espacios. Después de varios intentos fallidos la aplicación espera unos minutos antes de dejarte reintentar. Si aparece “Tu cuenta está desactivada”, pide al administrador que la vuelva a activar.',
        image: {
          src: iniciarSesionImg,
          caption: 'Pantalla de inicio de sesión.',
        },
      },
      {
        heading: 'Las partes de la pantalla',
        body: [
          'A la izquierda está el menú con las secciones de tu rol. En pantallas pequeñas se abre con el botón Menú.',
          'Abajo, en el mismo menú, están tu nombre, el Tema (Claro, Oscuro o Según el dispositivo), Cambiar contraseña y Cerrar sesión.',
          'Al entrar, los administradores y gerentes llegan al Tablero; los líderes de equipo, a Proyectos.',
          'Dentro de un proyecto, las pestañas separan el trabajo: Presupuesto y plan, Tablero, Gastos, Fondos, Caja menor y Resumen. Los líderes de equipo ven Presupuesto y plan, Gastos y Resumen.',
        ],
      },
      {
        heading: 'Cambiar tu contraseña',
        steps: [
          'Presiona Cambiar contraseña, abajo en el menú.',
          'Escribe tu Contraseña actual y la Nueva contraseña (mínimo 8 caracteres).',
          'Presiona Guardar. Verás “Contraseña actualizada.” y sigues con la sesión abierta.',
        ],
        note: 'La aplicación no envía correos para recuperar contraseñas. Si la olvidaste, el administrador te asigna una nueva desde Usuarios.',
      },
    ],
    related: ['roles-y-permisos'],
  },
  {
    id: 'roles-y-permisos',
    title: 'Qué puede hacer cada rol',
    summary:
      'Diferencias entre administrador, gerente de proyecto y líder de equipo, y por qué no todos ven lo mismo.',
    audiences: ALL,
    keywords: [
      'rol',
      'permiso',
      'acceso',
      'no veo',
      'no aparece',
      'no tengo permiso',
      'administrador',
      'gerente',
      'lider',
    ],
    sections: [
      {
        body: [
          'Lo que ves en el menú y en cada proyecto depende de tu rol. Por eso esta ayuda solo te muestra las guías de lo que puedes hacer.',
        ],
      },
      {
        heading: 'Administrador',
        body: [
          'Ve todos los proyectos sin estar en su equipo. Crea usuarios y proyectos, define la Configuración y consulta la Auditoría.',
          'Aprueba o devuelve los presupuestos, registra los depósitos y los usos de la contingencia, finaliza las etapas, aprueba los gastos que superan el límite, anula gastos y firma los ciclos de caja menor.',
        ],
        note: 'Un super administrador es un administrador que además puede usar “Ver como”. Solo otro super administrador otorga ese nivel.',
      },
      {
        heading: 'Gerente de proyecto',
        body: [
          'Es el responsable de un proyecto: arma el presupuesto y el plan, inicia las etapas y marca los hitos, registra gastos desde la etapa o la caja menor, aprueba los gastos de los líderes, les reembolsa desde la caja menor y cierra sus ciclos.',
          'Cada proyecto tiene un solo gerente de proyecto.',
        ],
      },
      {
        heading: 'Líder de equipo',
        body: [
          'Registra lo que paga con su dinero, adjunta la factura o el recibo y sigue en qué va cada gasto hasta que se lo reembolsan.',
          'Ve las etapas y los hitos del plan, pero no los montos del proyecto: no ve el Tablero, Fondos ni Caja menor.',
        ],
        note: 'Si crees que te falta acceso a un proyecto, pide al administrador que te agregue a su equipo con el rol que corresponde.',
      },
    ],
  },
  {
    id: 'tablero',
    title: 'Leer el tablero y sus indicadores',
    summary:
      'Cómo interpretar gasto, avance, CPI, SPI, costo final estimado y alertas.',
    audiences: ['ADMIN', 'PROJECT_MANAGER'],
    keywords: [
      'tablero',
      'dashboard',
      'indicadores',
      'cpi',
      'spi',
      'pronostico',
      'costo final',
      'alertas',
      'grafico',
      'avance',
    ],
    sections: [
      {
        body: [
          'El Tablero del menú muestra una tarjeta por cada proyecto cuyo dinero puedes ver. La tarjeta abre el Tablero del proyecto, que tiene el detalle.',
        ],
        image: {
          src: tableroImg,
          caption: 'Tablero con la tarjeta de cada proyecto.',
        },
      },
      {
        heading: 'Las cifras principales',
        body: [
          'Gastado: lo aprobado como gasto, con el porcentaje del presupuesto de las etapas (la contingencia va aparte).',
          'Avance: sale de los hitos cumplidos y su peso en cada etapa; debajo, cuánto debería llevar según las fechas planeadas.',
        ],
      },
      {
        heading: 'Los indicadores',
        body: [
          'CPI (costo): el avance logrado frente a lo gastado. Menos de 1 significa que se gasta más de lo que avanza la obra.',
          'SPI (plazo): el avance logrado frente al planeado a la fecha. Menos de 1 significa que va atrasada.',
          'Costo final estimado: lo que costaría la obra si sigue gastando como hasta ahora, y su diferencia con el presupuesto.',
        ],
        note: 'Cada indicador dice su lectura en palabras: Bien, Atención o Crítico. “Sin datos” aparece mientras no hay avance, gasto o plan a la fecha.',
      },
      {
        heading: 'Alertas y gráficos',
        body: [
          '“Lo que necesita atención” lista las etapas que superaron o se acercan a su presupuesto, las atrasadas, los hitos vencidos, los gastos por aprobar o reembolsar y los ciclos de caja menor por firmar.',
          'Las gráficas “Avance y gasto por etapa” y “Dinero por mes” tienen el botón Ver como tabla para leer las cifras exactas.',
        ],
      },
    ],
    related: ['aprobar-gastos', 'avance-etapas'],
  },
  {
    id: 'proyectos',
    title: 'Crear un proyecto y armar el equipo',
    summary:
      'Datos del proyecto, moneda y cómo asignar al gerente y a los líderes de equipo.',
    audiences: ['ADMIN'],
    keywords: [
      'proyecto',
      'nuevo proyecto',
      'equipo',
      'integrante',
      'miembro',
      'moneda',
      'asignar',
    ],
    sections: [
      {
        heading: 'Crear el proyecto',
        steps: [
          'Entra a Proyectos y presiona Nuevo proyecto.',
          'Completa Nombre, Descripción, Moneda y las fechas de Inicio planeado y Fin planeado.',
          'Presiona Crear.',
        ],
        note: 'La moneda no se puede cambiar después de crear el proyecto: todos sus montos quedan en ella.',
      },
      {
        heading: 'Armar el equipo',
        steps: [
          'Abre el proyecto y ve a la pestaña Resumen, sección Equipo del proyecto.',
          'Elige la Persona y su Rol, y presiona Agregar.',
        ],
        note: 'Cada proyecto tiene un solo gerente: si ya hay uno verás “El proyecto ya tiene un gerente. Cámbiale el rol primero.”. Los administradores ven todos los proyectos y no se agregan como miembros.',
      },
    ],
    related: ['categorias-y-etapas', 'usuarios'],
  },
  {
    id: 'categorias-y-etapas',
    title: 'Crear las categorías y las etapas',
    summary:
      'El primer paso del presupuesto: agrupar los costos y dividir la obra en etapas.',
    audiences: ['PROJECT_MANAGER', 'ADMIN'],
    keywords: [
      'categoria',
      'etapa',
      'fase',
      'presupuesto',
      'plan',
      'ordenar',
      'nomina',
      'materiales',
    ],
    sections: [
      {
        body: [
          'El presupuesto se arma en la pestaña Presupuesto y plan, en este orden: las categorías, las etapas, dentro de cada etapa sus partidas y sus hitos.',
        ],
      },
      {
        heading: 'Categorías',
        steps: [
          'En Categorías escribe el nombre en Nueva categoría (por ejemplo Nómina, Materiales o Equipos).',
          'Presiona Agregar.',
        ],
        note: 'Las categorías agrupan partidas y gastos, y se pueden agregar en cualquier momento, también después de aprobado el presupuesto. Una categoría que usan partidas, gastos o dinero depositado no se puede eliminar.',
      },
      {
        heading: 'Etapas',
        steps: [
          'Presiona Agregar etapa.',
          'Escribe el Nombre y las fechas planeadas, y guarda.',
          'Usa Subir y Bajar para dejarlas en el orden en que se ejecutará la obra.',
        ],
        note: 'El orden importa: al finalizar una etapa, el saldo que le sobre pasa a la siguiente.',
        image: {
          src: presupuestoPlanImg,
          caption:
            'Pestaña Presupuesto y plan: el presupuesto, las categorías y las etapas.',
        },
      },
    ],
    related: ['partidas', 'hitos'],
  },
  {
    id: 'partidas',
    title: 'Agregar las partidas del presupuesto',
    summary:
      'Cargar los costos de cada etapa con unidad, cantidad y valor unitario.',
    audiences: ['PROJECT_MANAGER', 'ADMIN'],
    keywords: [
      'partida',
      'presupuesto',
      'costo',
      'cantidad',
      'valor unitario',
      'total',
      'contingencia',
    ],
    sections: [
      {
        heading: 'Cargar una partida',
        steps: [
          'En la etapa que corresponda, presiona Agregar partida.',
          'Elige la Categoría y escribe la Descripción.',
          'Completa Unidad, Cantidad y Valor unitario: la ventana muestra el Total de la partida.',
          'Presiona Agregar partida.',
        ],
        note: 'Necesitas al menos una categoría para agregar partidas. Los valores con centavos se guardan exactos.',
        image: {
          src: partidasHitosImg,
          caption: 'Una etapa con sus partidas y sus hitos.',
        },
      },
      {
        heading: 'Contingencia y totales',
        body: [
          'Arriba ves Total etapas, Contingencia y Presupuesto total; en cada etapa, el porcentaje que representa del presupuesto.',
          'La contingencia es la reserva para imprevistos. Se edita con el lápiz junto a Contingencia mientras el presupuesto es un borrador.',
        ],
      },
    ],
    related: ['hitos', 'enviar-presupuesto', 'contingencia'],
  },
  {
    id: 'hitos',
    title: 'Definir los hitos de cada etapa',
    summary:
      'Los hitos miden el avance real; sus pesos deben sumar 100 % en cada etapa.',
    audiences: ['PROJECT_MANAGER', 'ADMIN'],
    keywords: [
      'hito',
      'milestone',
      'peso',
      'avance',
      'porcentaje',
      '100',
      'cronograma',
    ],
    sections: [
      {
        body: [
          'El avance no se mide con el dinero gastado sino con los hitos cumplidos. Cada etapa necesita hitos antes de enviar el presupuesto.',
        ],
      },
      {
        heading: 'Agregar un hito',
        steps: [
          'En la etapa presiona Agregar hito.',
          'Escribe el Nombre del hito, su Peso en la etapa (%) y, si la tiene, la Fecha planeada.',
          'Presiona Agregar hito.',
        ],
        note: 'Los pesos de cada etapa deben sumar 100 %: la etapa muestra cuánto suman y un peso que pase de 100 % se rechaza.',
      },
    ],
    related: ['avance-etapas', 'enviar-presupuesto'],
  },
  {
    id: 'enviar-presupuesto',
    title: 'Enviar el presupuesto a aprobación',
    summary:
      'Qué revisa la aplicación antes de dejarte enviar, y qué pasa después.',
    audiences: ['PROJECT_MANAGER'],
    keywords: [
      'enviar',
      'aprobacion',
      'presupuesto',
      'bloqueado',
      'devuelto',
      'observaciones',
      'borrador',
    ],
    sections: [
      {
        heading: 'Antes de enviar',
        body: [
          'Si falta algo, la aplicación lo lista en “Para enviar el presupuesto falta:” y deja Enviar a aprobación deshabilitado: no hay etapas, una etapa no tiene partidas, o los hitos de una etapa no suman 100 %.',
        ],
      },
      {
        heading: 'Enviar',
        steps: [
          'Revisa el Presupuesto total.',
          'Presiona Enviar a aprobación y confirma.',
        ],
        note: 'Mientras está “Enviado a aprobación” nadie lo modifica. El administrador recibe un correo.',
      },
      {
        heading: 'Si te lo devuelven',
        body: [
          'El administrador puede devolverlo: verás “Devuelto por …” con su comentario, el presupuesto vuelve a ser editable y lo puedes enviar de nuevo. Te llega un correo en ambos casos.',
          'Una vez aprobado, el proyecto pasa a Activo y se habilitan los depósitos y los gastos.',
        ],
      },
    ],
    related: ['aprobar-presupuesto'],
  },
  {
    id: 'aprobar-presupuesto',
    title: 'Aprobar o devolver un presupuesto',
    summary:
      'La revisión del administrador antes de que el proyecto mueva dinero.',
    audiences: ['ADMIN'],
    keywords: [
      'aprobar',
      'devolver',
      'observaciones',
      'presupuesto',
      'linea base',
      'bloquear',
    ],
    sections: [
      {
        heading: 'Revisar',
        steps: [
          'Abre el proyecto en la pestaña Presupuesto y plan.',
          'Revisa las etapas, sus partidas, la contingencia y el Presupuesto total.',
        ],
      },
      {
        heading: 'Aprobar',
        steps: ['Presiona Aprobar presupuesto y confirma.'],
        note: 'Aprobado, queda bloqueado para siempre: es la línea base contra la que se comparan los gastos. El proyecto pasa a Activo.',
      },
      {
        heading: 'Devolver',
        steps: [
          'Presiona Devolver.',
          'Escribe Qué debe cambiar el gerente: es lo que leerá para corregir.',
          'Presiona Devolver.',
        ],
      },
    ],
    related: ['enviar-presupuesto', 'depositos'],
  },
  {
    id: 'depositos',
    title: 'Registrar un depósito y repartirlo',
    summary:
      'Cómo entra el dinero al proyecto y cómo se reparte entre etapas, caja menor y contingencia.',
    audiences: ['ADMIN'],
    keywords: [
      'deposito',
      'dinero',
      'consignacion',
      'transferencia',
      'distribucion',
      'destino',
      'comprobante',
      'fondos',
      'anular',
    ],
    sections: [
      {
        body: [
          'Los depósitos se registran en la pestaña Fondos cuando el presupuesto está aprobado.',
        ],
      },
      {
        heading: 'Registrar el depósito',
        steps: [
          'En Fondos presiona Registrar depósito.',
          'Indica la Fecha, el Medio de pago y la Referencia.',
          'En Distribución indica el destino de cada parte: una etapa (y, si quieres, su categoría), la Caja menor o la Contingencia, con su monto. Agregar destino suma otra parte.',
          'Revisa el Total del depósito y presiona Registrar.',
        ],
        note: 'Después puedes Adjuntar el comprobante (PDF o imagen, hasta 10 MB) desde la lista de Movimientos.',
        image: {
          src: fondosImg,
          caption: 'Pestaña Fondos: saldos, fondos por etapa y movimientos.',
        },
      },
      {
        heading: 'Corregir',
        body: [
          'Un depósito equivocado se Anula con un motivo: deja de contar en los saldos pero sigue en la lista, en gris. Si parte de ese dinero ya se usó o se trasladó, la aplicación no deja anularlo.',
        ],
      },
    ],
    related: ['contingencia', 'caja-menor'],
  },
  {
    id: 'contingencia',
    title: 'Usar la contingencia',
    summary:
      'Pasar dinero de la reserva para imprevistos a una etapa que lo necesita.',
    audiences: ['ADMIN'],
    keywords: [
      'contingencia',
      'imprevisto',
      'reserva',
      'sobrecosto',
      'usar contingencia',
    ],
    sections: [
      {
        body: [
          'La contingencia recibe lo que le asignas en los depósitos y el saldo que sobra al finalizar la última etapa.',
        ],
      },
      {
        heading: 'Pasar dinero a una etapa',
        steps: [
          'En Fondos presiona Usar contingencia.',
          'Elige la Etapa y el Monto, sin superar lo disponible en la contingencia.',
          'Escribe el Motivo y presiona Pasar a la etapa.',
        ],
        note: 'Queda como “Uso de contingencia” en los movimientos, con su motivo.',
      },
    ],
    related: ['depositos', 'avance-etapas'],
  },
  {
    id: 'avance-etapas',
    title: 'Iniciar etapas, cumplir hitos y finalizar',
    summary: 'La vida de una etapa y qué pasa con el dinero que le sobra.',
    audiences: ['PROJECT_MANAGER', 'ADMIN'],
    keywords: [
      'etapa',
      'iniciar',
      'finalizar',
      'hito',
      'cumplido',
      'traslado',
      'saldo',
      'atrasada',
      'avance',
      'reabrir',
    ],
    sections: [
      {
        heading: 'Iniciar la etapa',
        steps: [
          'En Presupuesto y plan, en la etapa que empieza, presiona Iniciar etapa.',
          'Indica la fecha real de inicio. La etapa pasa de Pendiente a En curso.',
        ],
      },
      {
        heading: 'Marcar hitos cumplidos',
        steps: [
          'En el hito terminado presiona Marcar cumplido.',
          'Indica la fecha y, si quieres, unas Notas.',
        ],
        note: 'Cada hito cumplido suma su peso al avance. Solo el administrador puede Reabrir un hito. Los hitos vencidos sin cumplir aparecen en rojo (“Atrasado”).',
      },
      {
        heading: 'Finalizar la etapa (administrador)',
        steps: [
          'En Fondos, en la etapa en curso, presiona Finalizar etapa.',
          'Indica la Fecha de finalización y confirma.',
        ],
        note: 'Todos sus hitos deben estar cumplidos. El saldo que le quede pasa a la siguiente etapa abierta; después de la última, a la contingencia. Una etapa finalizada no recibe más dinero.',
      },
    ],
    related: ['hitos', 'contingencia', 'tablero'],
  },
  {
    id: 'registrar-gasto',
    title: 'Registrar un gasto',
    summary:
      'Cómo cargar un gasto del proyecto y adjuntar la factura o el recibo.',
    audiences: ['PROJECT_MANAGER', 'TEAM_LEAD', 'ADMIN'],
    keywords: [
      'gasto',
      'registrar',
      'factura',
      'recibo',
      'soporte',
      'proveedor',
      'comprar',
      'pagar',
      'bolsillo',
    ],
    sections: [
      {
        body: [
          'Los gastos se registran en la pestaña Gastos cuando el presupuesto está aprobado.',
        ],
      },
      {
        heading: 'Cargar el gasto',
        steps: [
          'Presiona Registrar gasto.',
          'Escribe la Descripción y elige la Etapa y la Categoría.',
          'Indica la Fecha y el Monto; el Proveedor y el Número de factura si los tienes.',
          'Si eres gerente o administrador, elige de dónde sale el dinero en Se paga desde: Fondos de la etapa o Caja menor.',
          'Presiona Registrar.',
          'En la fila del gasto presiona Adjuntar recibo y elige la factura o el recibo (PDF o imagen, hasta 10 MB).',
        ],
        note: 'Sin recibo el gasto no se puede aprobar (“Adjunta la factura o el recibo antes de aprobar el gasto.”). Lo que paga el gerente desde la etapa o la caja menor cuenta de inmediato, y debe haber saldo suficiente.',
        image: {src: registrarGastoImg, caption: 'La ventana Registrar gasto.'},
      },
      {
        heading: 'Si eres líder de equipo',
        body: [
          'Registras lo que pagaste con tu dinero. El gerente lo revisa y te lo reembolsa desde la caja menor; si supera el límite de líder de equipo, el administrador también lo aprueba.',
        ],
      },
    ],
    related: ['seguir-gastos', 'aprobar-gastos'],
  },
  {
    id: 'seguir-gastos',
    title: 'Seguir tus gastos y cobrar lo que te deben',
    summary:
      'En qué va cada gasto que registraste y qué hacer si te lo rechazan.',
    audiences: ['TEAM_LEAD'],
    keywords: [
      'rechazado',
      'corregir',
      'te deben',
      'pendiente',
      'reembolso',
      'estado',
      'mis gastos',
    ],
    sections: [
      {
        heading: 'Dónde mirar',
        body: [
          'En Gastos solo ves tus propios gastos. Arriba, Por aprobar y Por reembolsar dicen cuántos y cuánto; el color de cada fila es su estado (la leyenda está encima de la lista) y el filtro Estado deja ver solo unos.',
        ],
        image: {
          src: gastosLiderImg,
          caption: 'Pestaña Gastos vista por un líder de equipo.',
        },
      },
      {
        heading: 'Qué significa cada estado',
        body: [
          'Pendiente: el gerente todavía no lo revisa.',
          'Espera al administrador: el gerente lo aprobó, pero supera el límite y falta el administrador.',
          'Aprobado: aceptado; falta reembolsártelo.',
          'Reembolsado: ya te devolvieron el dinero.',
          'Rechazado: hay algo que corregir; la fila dice el motivo.',
        ],
      },
      {
        heading: 'Si te lo rechazan',
        steps: [
          'Presiona Corregir gasto (el lápiz) en la fila.',
          'Ajusta lo que haga falta o adjunta el recibo que faltaba.',
          'Presiona Guardar y enviar de nuevo: vuelve a quedar Pendiente.',
        ],
        note: 'Ver gasto (el ojo) muestra su historial: quién lo registró, lo corrigió (con los valores de antes), lo aprobó o lo rechazó, y cuándo. Te llega un correo cuando te lo rechazan y cuando te lo reembolsan.',
      },
    ],
    related: ['registrar-gasto'],
  },
  {
    id: 'aprobar-gastos',
    title: 'Revisar, aprobar o rechazar gastos',
    summary:
      'La revisión de los gastos de los líderes antes de que cuenten en el presupuesto.',
    audiences: ['ADMIN', 'PROJECT_MANAGER'],
    keywords: [
      'aprobar',
      'rechazar',
      'anular',
      'gasto',
      'revisar',
      'limite',
      'soporte',
      'recibo',
      'pendiente',
    ],
    sections: [
      {
        heading: 'Encontrar lo pendiente',
        body: [
          'En Gastos, Por aprobar dice cuántos esperan revisión; el filtro Estado › Pendiente los deja solos. Te llega un correo con cada gasto nuevo.',
        ],
        image: {
          src: gastosRevisionImg,
          caption:
            'Pestaña Gastos vista por el gerente, con las acciones de revisión.',
        },
      },
      {
        heading: 'Aprobar',
        steps: [
          'Abre el Recibo y revisa monto, etapa y categoría (Ver gasto muestra todo).',
          'Presiona Aprobar (el visto bueno) y confirma.',
        ],
        note: 'Si el gasto supera el límite de líder de equipo, la ventana lo avisa y queda en “Espera al administrador” hasta que el administrador lo apruebe.',
      },
      {
        heading: 'Rechazar o anular',
        body: [
          'Rechazar devuelve el gasto al líder para que lo corrija; el Motivo del rechazo es lo que leerá.',
          'Anular (administrador) es para un gasto aprobado que no debió registrarse: deja de contar y el dinero vuelve a la etapa o a la caja menor. Un gasto ya reembolsado no se anula.',
        ],
      },
    ],
    related: ['reembolsos', 'registrar-gasto'],
  },
  {
    id: 'reembolsos',
    title: 'Reembolsar a los líderes de equipo',
    summary:
      'Devolver desde la caja menor el dinero que un líder puso de su bolsillo.',
    audiences: ['PROJECT_MANAGER', 'ADMIN'],
    keywords: [
      'reembolso',
      'reembolsar',
      'devolver',
      'caja menor',
      'pagar al lider',
      'seleccionar',
    ],
    sections: [
      {
        heading: 'Pagar los reembolsos',
        steps: [
          'En Gastos presiona Reembolsar (aparece cuando hay gastos por reembolsar).',
          'Marca los gastos que vas a pagar: el botón dice el total.',
          'Indica la Fecha, el Medio de pago y la Referencia, y presiona Reembolsar.',
        ],
        note: 'Se pagan desde la caja menor en un solo movimiento, así que debe tener saldo: si no alcanza, la ventana dice cuánto hay disponible.',
      },
    ],
    related: ['caja-menor', 'aprobar-gastos'],
  },
  {
    id: 'caja-menor',
    title: 'Manejar la caja menor',
    summary:
      'El efectivo del día a día: entradas, gastos, reembolsos y cierre de ciclo.',
    audiences: ['PROJECT_MANAGER', 'ADMIN'],
    keywords: [
      'caja menor',
      'ciclo',
      'cerrar',
      'saldo',
      'recarga',
      'efectivo',
      'arqueo',
    ],
    sections: [
      {
        body: [
          'La caja menor es el dinero para gastos pequeños y para reembolsar a los líderes. Se lleva por ciclos: mientras un ciclo está abierto se le suman las entradas y se le restan los gastos y reembolsos.',
        ],
      },
      {
        heading: 'Ver el estado',
        body: [
          'La pestaña Caja menor muestra el Saldo, lo que entró y salió en el ciclo actual, sus movimientos con sus soportes, y los ciclos anteriores.',
          'Para recargarla, el administrador registra un depósito en Fondos con destino Caja menor.',
        ],
        image: {
          src: cajaMenorImg,
          caption: 'Pestaña Caja menor con el ciclo actual y los anteriores.',
        },
      },
      {
        heading: 'Cerrar el ciclo',
        steps: [
          'Revisa que estén todos los movimientos, con sus soportes.',
          'Presiona Cerrar ciclo, agrega una Nota de cierre si hace falta y confirma.',
        ],
        note: 'El ciclo queda “Cerrado, por firmar” y el administrador recibe un correo. El siguiente ciclo empieza con el saldo final. Los movimientos de un ciclo cerrado ya no se anulan.',
      },
    ],
    related: ['firmar-ciclo', 'reembolsos', 'depositos'],
  },
  {
    id: 'firmar-ciclo',
    title: 'Firmar un ciclo de caja menor',
    summary: 'El visto bueno del administrador sobre el manejo del efectivo.',
    audiences: ['ADMIN'],
    keywords: [
      'firmar',
      'aprobar cierre',
      'ciclo',
      'caja menor',
      'visto bueno',
      'pendiente',
    ],
    sections: [
      {
        heading: 'Revisar y firmar',
        steps: [
          'En la pestaña Caja menor, los ciclos por revisar aparecen en Ciclos anteriores como “Cerrado, por firmar”.',
          'Presiona Ver (el ojo) y revisa sus entradas, gastos y reembolsos con sus soportes.',
          'Presiona Firmar y confirma.',
        ],
        note: 'El Tablero avisa cuando hay ciclos esperando la firma.',
      },
    ],
    related: ['caja-menor'],
  },
  {
    id: 'usuarios',
    title: 'Crear y administrar usuarios',
    summary: 'Altas, contraseñas y cómo desactivar a quien ya no debe entrar.',
    audiences: ['ADMIN'],
    keywords: [
      'usuario',
      'crear',
      'desactivar',
      'contrasena',
      'administrador',
      'acceso',
      'nuevo usuario',
    ],
    sections: [
      {
        heading: 'Crear un usuario',
        steps: [
          'Entra a Usuarios y presiona Nuevo usuario.',
          'Escribe el Nombre completo, el Correo electrónico y una Contraseña (mínimo 8 caracteres).',
          'Marca Administrador solo si la persona debe ver y hacer todo en todos los proyectos.',
          'Marca Super administrador solo si además debe poder usar “Ver como”. Esa casilla solo la ve un super administrador.',
          'Presiona Crear y comparte la contraseña con la persona.',
        ],
        note: 'No puede haber dos usuarios con el mismo correo. Crear el usuario no le da acceso a ningún proyecto: hay que agregarlo al equipo del proyecto.',
        image: {src: usuariosImg, caption: 'Lista de usuarios.'},
      },
      {
        heading: 'Cambiar o desactivar',
        body: [
          'Editar usuario (el lápiz) corrige el nombre o asigna una Nueva contraseña cuando alguien la olvida; vacía, no cambia.',
          'Para quitarle el acceso a alguien, Desactivar (en lugar de borrar) conserva su historial. Al intentar entrar verá que su cuenta está desactivada.',
        ],
      },
    ],
    related: ['proyectos', 'ver-como'],
  },
  {
    id: 'configuracion',
    title: 'Configurar los valores generales',
    summary:
      'Moneda por defecto, límite de gasto de los líderes y los porcentajes que avisan.',
    audiences: ['ADMIN'],
    keywords: [
      'configuracion',
      'moneda',
      'limite',
      'alerta',
      'porcentaje',
      'ajustes',
      'parametros',
    ],
    sections: [
      {
        heading: 'Qué puedes ajustar',
        body: [
          'Moneda por defecto: para los proyectos nuevos; los existentes mantienen la suya.',
          'Límite por gasto de líder de equipo: por encima, el administrador también aprueba.',
          'Alerta de caja menor baja (%): avisa por correo cuando el saldo baja de ese porcentaje de la última recarga.',
          'Alertas de presupuesto (%): porcentajes de ejecución que avisan por correo, separados por comas (por ejemplo 80, 100).',
        ],
        note: 'Los cambios valen de inmediato para todos los proyectos.',
        image: {src: configuracionImg, caption: 'Pantalla de Configuración.'},
      },
    ],
    related: ['aprobar-gastos', 'auditoria'],
  },
  {
    id: 'auditoria',
    title: 'Consultar la auditoría',
    summary:
      'El registro de quién cambió qué y cuándo en presupuestos, dinero, gastos y configuración.',
    audiences: ['ADMIN'],
    keywords: [
      'auditoria',
      'historial',
      'quien cambio',
      'registro',
      'log',
      'rastro',
      'control',
    ],
    sections: [
      {
        heading: 'Buscar un cambio',
        steps: [
          'Entra a Auditoría.',
          'Filtra por Proyecto y por Registro (gasto, movimiento de dinero, etapa, usuario…), o busca por el nombre de la persona.',
          'Presiona Ver el cambio (el ojo) para ver cada campo antes y después.',
        ],
        image: {src: auditoriaImg, caption: 'Auditoría con sus filtros.'},
      },
      {
        heading: 'Qué muestra cada línea',
        body: [
          'La Fecha, la Persona, el Proyecto, el Registro afectado y el Cambio (Creó, Modificó o Eliminó).',
          'Lo que hizo un administrador usando “Ver como” aparece con los dos nombres: “Laura Gómez (vía Sofía Restrepo)”. Lo que hace un proceso automático aparece como Sistema. Las contraseñas nunca se muestran.',
        ],
      },
    ],
    related: ['ver-como'],
  },
  {
    id: 'ver-como',
    title: 'Ver la aplicación como otro usuario',
    summary:
      'Revisar qué ve una persona, para dar soporte o comprobar permisos.',
    audiences: ['SUPER_ADMIN'],
    keywords: [
      'ver como',
      'suplantar',
      'soporte',
      'permisos',
      'que ve',
      'impersonar',
    ],
    sections: [
      {
        heading: 'Cómo usarlo',
        steps: [
          'En el menú, en Ver como, elige a la persona.',
          'Navega: ves la aplicación con sus permisos.',
          'Cuando termines, presiona Volver a … en la franja de arriba, o elige “Nadie (mi cuenta)”.',
        ],
        note: 'Mientras lo usas, una franja te lo recuerda. Lo que hagas queda a nombre de esa persona, indicando que fuiste tú: sirve para revisar pantallas, no para trabajar en su lugar.',
        image: {
          src: verComoImg,
          caption: 'El selector Ver como y la franja que lo recuerda.',
        },
      },
      {
        heading: 'Quién puede usarlo',
        body: [
          'Solo los super administradores. El nivel lo otorga otro super administrador desde Usuarios.',
        ],
      },
    ],
    related: ['roles-y-permisos', 'auditoria'],
  },
];

export function topicById(id: string): HelpTopic | undefined {
  return helpTopics.find((topic) => topic.id === id);
}
