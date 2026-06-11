const API_BASE = import.meta.env.VITE_API_BASE_URL || '';
const TOKEN_KEY = 'bufete_jwt_token';

function getAuthHeaders(): Record<string, string> {
  const token = localStorage.getItem(TOKEN_KEY);
  if (!token) return {};
  return { Authorization: `Bearer ${token}` };
}

async function uploadRequest<T>(path: string, file: File): Promise<T> {
  const formData = new FormData();
  formData.append('file', file);

  const res = await fetch(API_BASE + path, {
    method: 'POST',
    headers: {
      ...getAuthHeaders(),
    },
    body: formData,
  });

  if (res.status === 401) {
    localStorage.removeItem(TOKEN_KEY);
    window.location.href = '/login';
    throw new Error('Sesión expirada. Por favor, inicie sesión de nuevo.');
  }

  if (!res.ok) {
    const err = await res.json().catch(() => ({ error: res.statusText }));
    throw new Error(
      (err as { error?: string; message?: string }).message ||
        (err as { error?: string }).error ||
        'Error',
    );
  }

  return res.json() as Promise<T>;
}

export async function fetchAuthenticatedAsset(path: string, cacheKey?: string): Promise<string | null> {
  const url =
    cacheKey != null && cacheKey !== ''
      ? `${API_BASE}${path}${path.includes('?') ? '&' : '?'}v=${encodeURIComponent(cacheKey)}`
      : API_BASE + path;

  const res = await fetch(url, {
    headers: {
      ...getAuthHeaders(),
    },
    cache: 'no-store',
  });

  if (!res.ok) {
    return null;
  }

  const blob = await res.blob();
  return URL.createObjectURL(blob);
}

export function getDespachoAssetPath(tipo: 'logo' | 'sello'): string {
  return `/api/config/despacho/assets/${tipo}`;
}

export async function fetchAccesoPdf(previewUrl: string): Promise<string> {
  const res = await fetch((import.meta.env.VITE_API_BASE_URL || '') + previewUrl, { cache: 'no-store' });
  if (!res.ok) {
    const err = await res.json().catch(() => ({ message: res.statusText }));
    throw new Error((err as { message?: string }).message ?? 'No se pudo cargar el documento');
  }
  const blob = await res.blob();
  return URL.createObjectURL(blob);
}

async function publicRequest<T>(path: string, options: RequestInit = {}): Promise<T> {
  const res = await fetch(API_BASE + path, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      ...options.headers,
    },
  });

  if (!res.ok) {
    const err = await res.json().catch(() => ({ error: res.statusText }));
    throw new Error(
      (err as { error?: string; message?: string }).message ||
        (err as { error?: string }).error ||
        'Error',
    );
  }

  return res.json() as Promise<T>;
}

async function request<T>(path: string, options: RequestInit = {}): Promise<T> {
  const res = await fetch(API_BASE + path, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      ...getAuthHeaders(),
      ...options.headers,
    },
  });

  if (res.status === 401) {
    localStorage.removeItem(TOKEN_KEY);
    window.location.href = '/login';
    throw new Error('Sesión expirada. Por favor, inicie sesión de nuevo.');
  }

  if (!res.ok) {
    const err = await res.json().catch(() => ({ error: res.statusText }));
    throw new Error(
      (err as { error?: string; message?: string }).message ||
        (err as { error?: string }).error ||
        'Error',
    );
  }
  if (res.status === 204) {
    return undefined as T;
  }
  const text = await res.text();
  if (text === '') {
    return undefined as T;
  }
  return JSON.parse(text) as T;
}

export async function loginRequest(email: string, password: string): Promise<{ token: string }> {
  const res = await fetch(API_BASE + '/api/auth/login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, password }),
  });

  if (!res.ok) {
    throw new Error('Credenciales incorrectas. Verifique su email y contraseña.');
  }

  return res.json() as Promise<{ token: string }>;
}

export interface PaymentResponse {
  id: string;
  expedienteId: string;
  status: string;
  type: string;
  amount: string;
  pdfUrl: string | null;
  createdAt: string;
}

export const api = {
  getExpedientes: () => request<ExpedienteResponse[]>('/api/expedientes'),

  getExpedientePayments: (expedienteId: string) =>
    request<PaymentResponse[]>('/api/expedientes/' + expedienteId + '/payments'),

  postPaymentManual: (body: {
    expedienteId: string;
    amount: string;
    clientName?: string;
    caseReference?: string;
  }) =>
    request<{
      success: boolean;
      paymentId?: string;
      pdfUrl?: string;
      error?: string;
      message?: string;
    }>('/api/payments/manual', {
      method: 'POST',
      body: JSON.stringify(body),
    }),

  postPaymentGenerateLink: (body: { expedienteId: string; amount: string; phone: string }) =>
    request<{ success: boolean; url?: string; sessionId?: string; error?: string }>(
      '/api/payments/generate-link',
      {
        method: 'POST',
        body: JSON.stringify(body),
      },
    ),

  postPaymentSubscription3: (body: { expedienteId: string; amount: string }) =>
    request<{ success: boolean; url?: string; sessionId?: string; error?: string }>(
      '/api/payments/subscription-3',
      {
        method: 'POST',
        body: JSON.stringify(body),
      },
    ),

  getPdfUrl: (expedienteId: string, paymentId: string) =>
    `${API_BASE}/api/expedientes/${expedienteId}/invoices/${paymentId}/pdf`,

  getInvoices: (expedienteId: string) =>
    request<InvoiceResponse[]>('/api/expedientes/' + expedienteId + '/invoices'),

  postInvoiceHolded: (body: { expedienteId: string; concepto: string; amount: string }) =>
    request<{ success: boolean; invoiceId?: string; holdedId?: string; error?: string }>(
      '/api/invoices/holded',
      { method: 'POST', body: JSON.stringify(body) },
    ),

  getContacts: () => request<MockContact[]>('/api/contacts'),

  postQuickInvoice: (body: {
    contactId: string;
    concepto: string;
    importe: number;
    telefono?: string;
  }) =>
    request<QuickInvoiceResponse>('/api/invoices/quick', {
      method: 'POST',
      body: JSON.stringify(body),
    }),

  postInvoiceWhatsapp: (invoiceId: string) =>
    request<{ success: boolean; message?: string }>(`/api/invoices/${invoiceId}/whatsapp`, {
      method: 'POST',
    }),

  getServicios: (params?: { incluirInactivos?: boolean }) => {
    const qs = params?.incluirInactivos ? '?incluir_inactivos=1' : '';
    return request<ServicioResponse[]>('/api/servicios' + qs);
  },

  getServicio: (id: string) => request<ServicioResponse>('/api/servicios/' + encodeURIComponent(id)),

  postServicio: (body: { nombre: string; tipo: string }) =>
    request<ServicioResponse>('/api/servicios', {
      method: 'POST',
      body: JSON.stringify(body),
    }),

  putServicio: (id: string, body: { nombre: string; tipo: string }) =>
    request<ServicioResponse>('/api/servicios/' + encodeURIComponent(id), {
      method: 'PUT',
      body: JSON.stringify(body),
    }),

  patchServicioEstado: (id: string, activo: boolean) =>
    request<ServicioResponse>('/api/servicios/' + encodeURIComponent(id) + '/estado', {
      method: 'PATCH',
      body: JSON.stringify({ activo }),
    }),

  getTramites: (params?: { incluirInactivos?: boolean; servicioId?: string }) => {
    const search = new URLSearchParams();
    if (params?.incluirInactivos) search.set('incluir_inactivos', '1');
    if (params?.servicioId) search.set('servicio_id', params.servicioId);
    const qs = search.toString();
    return request<TramiteResponse[]>('/api/tramites' + (qs ? '?' + qs : ''));
  },

  getTramite: (id: string) => request<TramiteResponse>('/api/tramites/' + encodeURIComponent(id)),

  postTramite: (body: {
    servicioId: string;
    nombre: string;
    honorarios: number;
    plataforma: string;
    requiereProcurador: boolean;
  }) =>
    request<TramiteResponse>('/api/tramites', {
      method: 'POST',
      body: JSON.stringify(body),
    }),

  putTramite: (
    id: string,
    body: {
      servicioId: string;
      nombre: string;
      honorarios: number;
      plataforma: string;
      requiereProcurador: boolean;
    },
  ) =>
    request<TramiteResponse>('/api/tramites/' + encodeURIComponent(id), {
      method: 'PUT',
      body: JSON.stringify(body),
    }),

  patchTramiteEstado: (id: string, activo: boolean) =>
    request<TramiteResponse>('/api/tramites/' + encodeURIComponent(id) + '/estado', {
      method: 'PATCH',
      body: JSON.stringify({ activo }),
    }),

  getDespachoConfig: () => request<DespachoConfigResponse>('/api/config/despacho'),

  putDespachoConfig: (body: {
    nombreFirma: string;
    nombreLetrada: string;
    numColegiado: string;
    direccion: string;
    ciudad: string;
    subtituloProfesional?: string;
    telefono?: string;
    email?: string;
    web?: string;
    nif?: string;
    colegioAbogados?: string;
    iban?: string;
    entidadBancaria?: string;
    titularCuenta?: string;
    cabeceraHtml?: string | null;
    pieHtml?: string | null;
  }) =>
    request<DespachoConfigResponse>('/api/config/despacho', {
      method: 'PUT',
      body: JSON.stringify(body),
    }),

  uploadDespachoLogo: (file: File) =>
    uploadRequest<DespachoConfigResponse>('/api/config/despacho/logo', file),

  uploadDespachoSello: (file: File) =>
    uploadRequest<DespachoConfigResponse>('/api/config/despacho/sello', file),

  getHojaEncargoVariables: () => request<EscritoVariableCategory[]>('/api/hoja-encargo/variables'),

  getEscritoVariables: () => request<EscritoVariableCategory[]>('/api/escritos/variables'),

  getHojaEncargoPlantilla: (tramiteId: string) =>
    request<EscritoPlantillaResponse>(
      '/api/tramites/' + encodeURIComponent(tramiteId) + '/hoja-encargo',
    ),

  getEscritoPlantilla: (tramiteId: string, tipo: string) =>
    request<EscritoPlantillaResponse>(
      '/api/tramites/' + encodeURIComponent(tramiteId) + '/escritos/' + encodeURIComponent(tipo),
    ),

  putHojaEncargoPlantilla: (tramiteId: string, bloques: unknown[]) =>
    request<EscritoPlantillaResponse>(
      '/api/tramites/' + encodeURIComponent(tramiteId) + '/hoja-encargo',
      {
        method: 'PUT',
        body: JSON.stringify({ bloques }),
      },
    ),

  putEscritoPlantilla: (tramiteId: string, tipo: string, bloques: unknown[]) =>
    request<EscritoPlantillaResponse>(
      '/api/tramites/' + encodeURIComponent(tramiteId) + '/escritos/' + encodeURIComponent(tipo),
      {
        method: 'PUT',
        body: JSON.stringify({ bloques }),
      },
    ),

  downloadTramiteEscritoPdf: async (
    tramiteId: string,
    tipo: string,
    bloques: unknown[],
    incluirMembrete: boolean,
  ): Promise<Blob> => {
    const res = await fetch(
      API_BASE +
        '/api/tramites/' +
        encodeURIComponent(tramiteId) +
        '/escritos/' +
        encodeURIComponent(tipo) +
        '/pdf-preview',
      {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          ...getAuthHeaders(),
        },
        body: JSON.stringify({ bloques, incluirMembrete }),
      },
    );

    if (!res.ok) {
      const err = await res.json().catch(() => ({ message: res.statusText }));
      throw new Error((err as { message?: string }).message ?? 'Error al generar PDF');
    }

    return res.blob();
  },

  getClientes: () => request<ClienteResponse[]>('/api/clientes'),

  buscarClientes: (query: string) =>
    request<BuscarClientesResponse>(
      '/api/clientes/buscar?q=' + encodeURIComponent(query),
    ),

  altaExpediente: (body: AltaExpedienteInput) =>
    request<AltaExpedienteResponse>('/api/expedientes/alta', {
      method: 'POST',
      body: JSON.stringify(body),
    }),

  getAccesoExpediente: (token: string) => publicRequest<AccesoExpedienteResponse>('/api/acceso/' + encodeURIComponent(token)),

  completarPasoCliente: (token: string, paso: string) =>
    publicRequest<AccesoExpedienteResponse>('/api/acceso/' + encodeURIComponent(token) + '/completar-paso', {
      method: 'POST',
      body: JSON.stringify({ paso }),
    }),

  getMercureToken: (expedienteId: string) =>
    request<MercureTokenResponse>('/api/realtime/mercure-token/' + encodeURIComponent(expedienteId)),

  getContratacion: (expedienteId: string) =>
    request<ContratacionResponse>('/api/expedientes/' + encodeURIComponent(expedienteId) + '/contratacion'),

  validarPasoContratacion: (expedienteId: string, paso: string) =>
    request<ContratacionResponse>(
      '/api/expedientes/' + encodeURIComponent(expedienteId) + '/contratacion/validar/' + encodeURIComponent(paso),
      { method: 'POST' },
    ),

  postCliente: (body: ClienteInput) =>
    request<ClienteResponse>('/api/clientes', {
      method: 'POST',
      body: JSON.stringify(body),
    }),

  putCliente: (id: string, body: ClienteInput) =>
    request<ClienteResponse>('/api/clientes/' + encodeURIComponent(id), {
      method: 'PUT',
      body: JSON.stringify(body),
    }),

  vincularExpediente: (expedienteId: string, body: { clienteId?: string | null; tramiteId?: string | null }) =>
    request<{ success: boolean }>('/api/expedientes/' + encodeURIComponent(expedienteId) + '/vincular', {
      method: 'PUT',
      body: JSON.stringify(body),
    }),

  previewEscrito: (expedienteId: string, tipo: string, incluirMembrete: boolean) =>
    request<{ html: string }>(
      '/api/expedientes/' + encodeURIComponent(expedienteId) + '/escritos/' + encodeURIComponent(tipo) + '/preview',
      {
        method: 'POST',
        body: JSON.stringify({ incluirMembrete }),
      },
    ),

  downloadEscritoPdf: async (expedienteId: string, tipo: string, incluirMembrete: boolean): Promise<Blob> => {
    const res = await fetch(
      API_BASE +
        '/api/expedientes/' +
        encodeURIComponent(expedienteId) +
        '/escritos/' +
        encodeURIComponent(tipo) +
        '/pdf',
      {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          ...getAuthHeaders(),
        },
        body: JSON.stringify({ incluirMembrete }),
      },
    );

    if (!res.ok) {
      const err = await res.json().catch(() => ({ message: res.statusText }));
      throw new Error((err as { message?: string }).message ?? 'Error al generar PDF');
    }

    return res.blob();
  },

  getDocumentosRequeridos: (tramiteId: string) =>
    request<DocumentosRequeridosResponse>(
      '/api/tramites/' + encodeURIComponent(tramiteId) + '/documentos-requeridos',
    ),

  putDocumentosRequeridos: (tramiteId: string, documentos: DocumentoRequeridoInput[]) =>
    request<DocumentosRequeridosResponse>(
      '/api/tramites/' + encodeURIComponent(tramiteId) + '/documentos-requeridos',
      {
        method: 'PUT',
        body: JSON.stringify({ documentos }),
      },
    ),
};

export interface InvoiceResponse {
  id: string;
  expedienteId: string;
  numero: string;
  concepto: string;
  modalidad: string;
  fecha: string;
  importe: string;
  estadoHolded: 'draft' | 'outstanding' | 'paid' | 'overdue' | 'void';
  holdedId: string | null;
}

export interface MockContact {
  id: string;
  name: string;
  email: string;
  code: string;
}

export interface QuickInvoiceResponse {
  success: boolean;
  invoiceId?: string;
  holdedId?: string;
  numero?: string;
  importe?: number;
  pdfUrl?: string;
  error?: string;
}

export interface ServicioResponse {
  id: string;
  nombre: string;
  tipo: string;
  tipoLabel: string;
  activo: boolean;
}

export interface TramiteResponse {
  id: string;
  servicioId: string;
  servicioNombre: string | null;
  nombre: string;
  honorarios: number;
  plataforma: string;
  plataformaLabel: string;
  requiereProcurador: boolean;
  activo: boolean;
}

export type FaseNegocio = 'contratacion' | 'requerimientos' | 'tramitacion' | 'resolucion';
export type MetodoPago = 'manual' | 'digital';
export type PlanPago = 'unico' | 'fraccionado';

export interface ExpedienteResponse {
  id: string;
  numero: string;
  titulo: string;
  estado: string;
  fechaApertura: string;
  clientName: string;
  caseReference: string;
  folderPath: string;
  paymentStatus: string;
  clienteId?: string | null;
  tramiteId?: string | null;
  servicioId?: string | null;
  faseNegocio?: FaseNegocio;
  estadoFase?: string;
  honorariosAcordados?: number;
  metodoPago?: MetodoPago;
  planPago?: PlanPago;
  numCuotas?: number;
  accessUrl?: string | null;
}

export interface AltaExpedienteInput {
  clienteId?: string | null;
  telefono?: string | null;
  email?: string | null;
  tramiteId: string;
  honorariosAcordados: number;
  metodoPago: MetodoPago;
  planPago: PlanPago;
  numCuotas: number;
  notificar?: boolean;
  canalesNotificacion?: ('whatsapp' | 'email')[];
}

export interface AltaExpedienteResponse {
  expediente: ExpedienteResponse;
  accessUrl: string;
  canalesNotificados: string[];
}

export interface ClienteBusquedaItem {
  id: string;
  nombre: string;
  telefono: string;
  email: string;
  tipoDocumento: string;
  numDocumento: string;
}

export interface BuscarClientesResponse {
  clientes: ClienteBusquedaItem[];
}

export interface AccesoPasoResponse {
  paso: string;
  label: string;
  estado: string;
  estadoLabel: string;
  esActivo?: boolean;
}

export interface AccesoDocumentoFirmaResponse {
  tipo: string;
  label: string;
  previewUrl: string;
}

export interface AccesoResumenPagoResponse {
  honorariosAcordados: number;
  metodoPago: MetodoPago;
  metodoPagoLabel: string;
  planPago: PlanPago;
  numCuotas: number;
  importeCuota: number;
  iban: string;
  titularCuenta: string;
  entidadBancaria: string;
}

export interface AccesoClienteDatosResponse {
  nombre: string;
  tipoDocumento: string;
  numDocumento: string;
  telefono: string;
  email: string;
  domicilio: string;
  ciudad: string;
}

export interface AccesoExpedienteResponse {
  expedienteNumero: string;
  tramiteNombre: string;
  faseNegocio: FaseNegocio;
  faseNegocioLabel: string;
  estadoFase: string;
  estadoFaseLabel: string;
  honorariosAcordados: number;
  metodoPago?: MetodoPago;
  planPago?: PlanPago;
  numCuotas?: number;
  pasoActivo?: string | null;
  pasos?: AccesoPasoResponse[];
  documentosRequeridos?: DocumentoRequerido[];
  documentosFirma?: AccesoDocumentoFirmaResponse[];
  resumenPago?: AccesoResumenPagoResponse;
  clienteDatos?: AccesoClienteDatosResponse | null;
}

export interface MercureTokenResponse {
  token: string;
  hubUrl: string;
  topic: string;
}

export interface ContratacionPasoResponse {
  paso: string;
  label: string;
  descripcion: string;
  orden: number;
  estado: string;
  estadoLabel: string;
  realizadoAt: string | null;
  validadoAt: string | null;
  requiereValidacionAbogado: boolean;
}

export interface ContratacionHitoResponse {
  id: string;
  paso: string | null;
  tipo: string;
  descripcion: string;
  actor: string;
  createdAt: string;
}

export interface ContratacionResponse {
  expedienteId: string;
  numero: string;
  faseNegocio: FaseNegocio;
  faseNegocioLabel: string;
  estadoFase: string;
  estadoFaseLabel: string;
  metodoPago: MetodoPago;
  metodoPagoLabel: string;
  planPago: PlanPago;
  honorariosAcordados: number;
  numCuotas: number;
  accessUrl: string | null;
  pasoActivo: string | null;
  contratacionCompletada: boolean;
  pasos: ContratacionPasoResponse[];
  hitos: ContratacionHitoResponse[];
}

export interface DespachoConfigResponse {
  id: string;
  nombreFirma: string;
  nombreLetrada: string;
  numColegiado: string;
  direccion: string;
  ciudad: string;
  subtituloProfesional: string;
  telefono: string;
  email: string;
  web: string;
  nif: string;
  colegioAbogados: string;
  iban: string;
  entidadBancaria: string;
  titularCuenta: string;
  cabeceraHtml: string | null;
  pieHtml: string | null;
  logoUrl: string | null;
  selloUrl: string | null;
  updatedAt: string | null;
}

export interface EscritoVariableCategory {
  categoria: string;
  variables: Array<{ key: string; label: string }>;
}

/** @deprecated Use EscritoVariableCategory */
export type HojaEncargoVariableCategory = EscritoVariableCategory;

export interface EscritoPlantillaResponse {
  tramiteId: string;
  tipo?: string;
  esDefault: boolean;
  esPlantillaGlobal?: boolean;
  bloques: unknown[];
}

/** @deprecated Use EscritoPlantillaResponse */
export type HojaEncargoPlantillaResponse = EscritoPlantillaResponse;

export interface ClienteResponse {
  id: string;
  nombre: string;
  nacionalidad: string;
  tipoDocumento: string;
  numDocumento: string;
  fechaNacimiento: string | null;
  lugarNacimiento: string;
  domicilio: string;
  codigoPostal: string;
  ciudad: string;
  telefono: string;
  email: string;
}

export interface ClienteInput {
  nombre: string;
  nacionalidad?: string;
  tipoDocumento?: string;
  numDocumento?: string;
  fechaNacimiento?: string | null;
  lugarNacimiento?: string;
  domicilio?: string;
  codigoPostal?: string;
  ciudad?: string;
  telefono?: string;
  email?: string;
}

export type FaseExpediente = 1 | 2 | 3 | 4;

/** Documentación que aporta el cliente (etapa 2 del expediente). */
export const FASE_DOCUMENTOS_CLIENTE = 2 as const;

export type TipoDocumentoRequerido = 'individual' | 'conjunto';

export interface DocumentoRequerido {
  id: string;
  fase: FaseExpediente;
  nombre: string;
  descripcion: string;
  obligatorio: boolean;
  tipo: TipoDocumentoRequerido;
  maxImagenes: number;
  orden: number;
  formatoEntrega?: 'pdf';
}

export type DocumentoRequeridoInput = Pick<
  DocumentoRequerido,
  'fase' | 'nombre' | 'descripcion' | 'obligatorio' | 'tipo' | 'maxImagenes' | 'orden'
> & { id?: string };

export interface DocumentosRequeridosResponse {
  documentos: DocumentoRequerido[];
  conversionPdfAutomatica: boolean;
}
