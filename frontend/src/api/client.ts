import { apiAbsoluteUrl, getApiBase } from './apiBase';
import { mergeFetchHeaders } from '@/lib/ngrok-headers';

export { apiAbsoluteUrl, getApiBase };

const API_BASE = getApiBase();

function appendArchivosToFormData(formData: FormData, files: File[]): void {
  for (const file of files) {
    formData.append('archivos[]', file);
  }
}
const TOKEN_KEY = 'bufete_jwt_token';

function getAuthHeaders(): Record<string, string> {
  const token = localStorage.getItem(TOKEN_KEY);
  if (!token) return {};
  return { Authorization: `Bearer ${token}` };
}

function parseApiError(status: number, err: unknown): string {
  const payload = err as {
    error?: string;
    message?: string;
    clienteExistenteNombre?: string;
  };
  let message = payload.message || payload.error || 'Error';
  if (status === 409 && payload.clienteExistenteNombre) {
    message = `${message} (${payload.clienteExistenteNombre})`;
  }
  return message;
}

async function uploadRequest<T>(path: string, file: File): Promise<T> {
  const formData = new FormData();
  formData.append('file', file);

  const res = await fetch(API_BASE + path, {
    method: 'POST',
    headers: mergeFetchHeaders(getAuthHeaders()),
    body: formData,
  });

  if (res.status === 401) {
    localStorage.removeItem(TOKEN_KEY);
    window.location.href = '/login';
    throw new Error('Sesión expirada. Por favor, inicie sesión de nuevo.');
  }

  if (!res.ok) {
    const err = await res.json().catch(() => ({ error: res.statusText }));
    throw new Error(parseApiError(res.status, err));
  }

  return res.json() as Promise<T>;
}

export async function fetchAuthenticatedAsset(path: string, cacheKey?: string): Promise<string | null> {
  try {
    const blob = await fetchAuthenticatedBlob(path, cacheKey);
    return URL.createObjectURL(blob);
  } catch {
    return null;
  }
}

export async function fetchAuthenticatedBlob(path: string, cacheKey?: string): Promise<Blob> {
  const basePath =
    path.startsWith('http') ? path : API_BASE + path;
  const url =
    cacheKey != null && cacheKey !== ''
      ? `${basePath}${basePath.includes('?') ? '&' : '?'}v=${encodeURIComponent(cacheKey)}`
      : basePath;

  const res = await fetch(url, {
    headers: mergeFetchHeaders(getAuthHeaders()),
    cache: 'no-store',
  });

  if (res.status === 401) {
    localStorage.removeItem(TOKEN_KEY);
    window.location.href = '/login';
    throw new Error('Sesión expirada. Por favor, inicie sesión de nuevo.');
  }

  if (!res.ok) {
    const err = await res.json().catch(() => ({ message: res.statusText }));
    throw new Error(
      (err as { error?: string; message?: string }).message ||
        (err as { error?: string }).error ||
        'No se pudo cargar el documento.',
    );
  }

  return res.blob();
}

/** Abre un PDF o imagen protegido por JWT en una pestaña nueva. */
export async function openAuthenticatedDocument(path: string): Promise<void> {
  const blob = await fetchAuthenticatedBlob(path);
  const blobUrl = URL.createObjectURL(blob);
  const opened = window.open(blobUrl, '_blank', 'noopener,noreferrer');
  if (!opened) {
    URL.revokeObjectURL(blobUrl);
    throw new Error('Permita ventanas emergentes para ver el documento.');
  }
  window.setTimeout(() => URL.revokeObjectURL(blobUrl), 60_000);
}

export function getDespachoAssetPath(tipo: 'logo' | 'sello'): string {
  return `/api/config/despacho/assets/${tipo}`;
}

export async function fetchAccesoPdf(previewUrl: string): Promise<string> {
  const url = previewUrl.startsWith('http') ? previewUrl : `${API_BASE}${previewUrl}`;
  const res = await fetch(url, { cache: 'no-store', headers: mergeFetchHeaders() });
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
    headers: mergeFetchHeaders({
      'Content-Type': 'application/json',
      ...options.headers,
    }),
  });

  if (!res.ok) {
    const err = await res.json().catch(() => ({ error: res.statusText }));
    throw new Error(parseApiError(res.status, err));
  }

  return res.json() as Promise<T>;
}

async function publicMultipartRequest<T>(path: string, formData: FormData, method = 'POST'): Promise<T> {
  const res = await fetch(API_BASE + path, {
    method,
    headers: mergeFetchHeaders(),
    body: formData,
  });

  if (!res.ok) {
    const err = await res.json().catch(() => ({ error: res.statusText }));
    throw new Error(parseApiError(res.status, err));
  }

  return res.json() as Promise<T>;
}

async function multipartRequest<T>(path: string, formData: FormData, method = 'POST'): Promise<T> {
  const res = await fetch(API_BASE + path, {
    method,
    headers: mergeFetchHeaders(getAuthHeaders()),
    body: formData,
  });

  if (res.status === 401) {
    localStorage.removeItem(TOKEN_KEY);
    window.location.href = '/login';
    throw new Error('Sesión expirada. Por favor, inicie sesión de nuevo.');
  }

  if (!res.ok) {
    const err = await res.json().catch(() => ({ error: res.statusText }));
    throw new Error(parseApiError(res.status, err));
  }

  return res.json() as Promise<T>;
}

async function request<T>(path: string, options: RequestInit = {}): Promise<T> {
  const res = await fetch(API_BASE + path, {
    ...options,
    headers: mergeFetchHeaders({
      'Content-Type': 'application/json',
      ...getAuthHeaders(),
      ...options.headers,
    }),
  });

  if (res.status === 401) {
    localStorage.removeItem(TOKEN_KEY);
    window.location.href = '/login';
    throw new Error('Sesión expirada. Por favor, inicie sesión de nuevo.');
  }

  if (!res.ok) {
    const err = await res.json().catch(() => ({ error: res.statusText }));
    throw new Error(parseApiError(res.status, err));
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
    headers: mergeFetchHeaders({ 'Content-Type': 'application/json' }),
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
  holdedEstado?: string;
  holdedEstadoLabel?: string;
  holdedSyncError?: string | null;
  holdedInvoiceId?: string | null;
  pdfUrl: string | null;
  cuotaNumero?: number | null;
  createdAt: string;
}

export type PaymentHoldedEstado = 'no_aplica' | 'pendiente_sync' | 'sincronizado' | 'error';

export interface CobroGlobalItem {
  id: string;
  expedienteId: string;
  expedienteNumero: string;
  clienteNombre: string;
  tramiteNombre: string;
  amount: string;
  status: 'pending' | 'paid' | 'failed';
  statusLabel: string;
  type: string;
  typeLabel: string;
  holdedEstado: PaymentHoldedEstado;
  holdedEstadoLabel: string;
  holdedSyncError: string | null;
  holdedInvoiceId: string | null;
  holdedSyncedAt: string | null;
  stripeSessionId: string | null;
  pdfUrl: string | null;
  createdAt: string;
  updatedAt: string;
}

export interface CobrosGlobalesResponse {
  items: CobroGlobalItem[];
  kpis: {
    cobradoMes: number;
    pendienteSyncHolded: number;
    stripePendientes: number;
  };
}

export interface CobrosGlobalesFilters {
  estadoCobro?: string[];
  holdedEstado?: string[];
  tipo?: string[];
  desde?: string;
  hasta?: string;
  q?: string;
}

export const api = {
  getExpedientes: () => request<ExpedienteResponse[]>('/api/expedientes'),

  getExpediente: (id: string) => request<ExpedienteResponse>('/api/expedientes/' + encodeURIComponent(id)),

  getExpedienteAuditoria: (expedienteId: string) =>
    request<ExpedienteAuditoriaResponse>(
      '/api/expedientes/' + encodeURIComponent(expedienteId) + '/auditoria',
    ),

  getExpedientePayments: (expedienteId: string) =>
    request<PaymentResponse[]>('/api/expedientes/' + expedienteId + '/payments'),

  getCobrosGlobales: (filters: CobrosGlobalesFilters = {}) => {
    const params = new URLSearchParams();
    if (filters.estadoCobro?.length) params.set('estadoCobro', filters.estadoCobro.join(','));
    if (filters.holdedEstado?.length) params.set('holdedEstado', filters.holdedEstado.join(','));
    if (filters.tipo?.length) params.set('tipo', filters.tipo.join(','));
    if (filters.desde) params.set('desde', filters.desde);
    if (filters.hasta) params.set('hasta', filters.hasta);
    if (filters.q) params.set('q', filters.q);
    const qs = params.toString();
    return request<CobrosGlobalesResponse>('/api/cobros' + (qs ? '?' + qs : ''));
  },

  sincronizarPagoHolded: (paymentId: string) =>
    request<{ success: boolean; holdedInvoiceId?: string; error?: string }>(
      '/api/cobros/' + encodeURIComponent(paymentId) + '/sincronizar-holded',
      { method: 'POST' },
    ),

  sincronizarCobrosExpedienteHolded: (expedienteId: string) =>
    request<{
      success: boolean;
      total: number;
      sincronizados: number;
      fallidos: number;
      errores: Array<{ paymentId: string; cuotaNumero: number | null; error: string }>;
      error?: string;
    }>('/api/expedientes/' + encodeURIComponent(expedienteId) + '/sincronizar-holded', {
      method: 'POST',
    }),

  postPaymentManual: (body: {
    expedienteId: string;
    amount: string;
    clientName?: string;
    caseReference?: string;
    cuotaNumero?: number;
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

  postPaymentGenerateLink: (body: {
    expedienteId: string;
    amount: string;
    phone?: string;
    email?: string;
    cuotaNumero?: number;
  }) =>
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

  confirmarPagoStripeSesion: (sessionId: string) =>
    publicRequest<{ success: boolean; message?: string }>('/api/payments/confirm-session', {
      method: 'POST',
      body: JSON.stringify({ sessionId }),
    }),

  getPdfUrl: (expedienteId: string, paymentId: string) =>
    `/api/expedientes/${encodeURIComponent(expedienteId)}/invoices/${encodeURIComponent(paymentId)}/pdf`,

  getInvoices: (expedienteId: string) =>
    request<InvoiceResponse[]>('/api/expedientes/' + expedienteId + '/invoices'),

  postInvoiceHolded: (body: { expedienteId: string; concepto: string; importe: string }) =>
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
    requiereOtpFirma?: boolean;
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
      requiereOtpFirma?: boolean;
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
        headers: mergeFetchHeaders({
          'Content-Type': 'application/json',
          ...getAuthHeaders(),
        }),
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

  getCliente: (id: string) => request<ClienteDetalleResponse>('/api/clientes/' + encodeURIComponent(id)),

  sincronizarClienteHolded: (id: string, forzar = true) =>
    request<SincronizarHoldedResponse>('/api/clientes/' + encodeURIComponent(id) + '/sincronizar-holded', {
      method: 'POST',
      body: JSON.stringify({ forzar }),
    }),

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

  extraerDocumentoIdentidadAcceso: (
    token: string,
    input: {
      tipoEscaneo: TipoEscaneoDocumentoIdentidad;
      anverso: File;
      reverso: File | null;
    },
  ) => {
    const formData = new FormData();
    formData.append('tipoEscaneo', input.tipoEscaneo);
    formData.append('anverso', input.anverso);
    if (input.reverso) {
      formData.append('reverso', input.reverso);
    }
    return publicMultipartRequest<ExtraerDocumentoIdentidadResponse>(
      '/api/acceso/' + encodeURIComponent(token) + '/extraer-documento',
      formData,
    );
  },

  guardarDatosIdentidadAcceso: (
    token: string,
    input: {
      tipoEscaneo: TipoEscaneoDocumentoIdentidad;
      anverso: File | null;
      reverso: File | null;
      datos: ClienteInput;
      soloDatos?: boolean;
    },
  ) => {
    const formData = new FormData();
    if (input.soloDatos) {
      formData.append('soloDatos', '1');
    }
    formData.append('tipoEscaneo', input.tipoEscaneo);
    if (input.anverso) {
      formData.append('anverso', input.anverso);
    }
    if (input.reverso) {
      formData.append('reverso', input.reverso);
    }
    Object.entries(input.datos).forEach(([key, value]) => {
      if (value != null && value !== '') {
        formData.append(`datos[${key}]`, String(value));
      }
    });
    return publicMultipartRequest<AccesoExpedienteResponse>(
      '/api/acceso/' + encodeURIComponent(token) + '/datos-identidad',
      formData,
    );
  },

  reutilizarDocumentoIdentidadAcceso: (token: string) =>
    publicRequest<AccesoExpedienteResponse>(
      '/api/acceso/' + encodeURIComponent(token) + '/reutilizar-documento-identidad',
      { method: 'POST' },
    ),

  getMercureToken: (expedienteId: string) =>
    request<MercureTokenResponse>('/api/realtime/mercure-token/' + encodeURIComponent(expedienteId)),

  getMercureTokenAcceso: (token: string) =>
    publicRequest<MercureTokenResponse>('/api/acceso/' + encodeURIComponent(token) + '/mercure-token'),

  getMercureTokenAbogado: () => request<MercureTokenResponse>('/api/realtime/mercure-token-abogado'),

  getNotificacionesRecientes: () =>
    request<NotificacionesRecientesResponse>('/api/notificaciones/recientes'),

  marcarNotificacionLeida: (hitoId: string) =>
    request<{ ok: boolean }>('/api/notificaciones/' + encodeURIComponent(hitoId) + '/leida', {
      method: 'POST',
    }),

  marcarTodasNotificacionesLeidas: () =>
    request<{ ok: boolean; marcadas: number }>('/api/notificaciones/leidas', {
      method: 'POST',
    }),

  getContratacion: (expedienteId: string) =>
    request<ContratacionResponse>('/api/expedientes/' + encodeURIComponent(expedienteId) + '/contratacion'),

  getDocumentacionExpediente: (expedienteId: string) =>
    request<DocumentacionExpedienteItemResponse[]>(
      '/api/expedientes/' + encodeURIComponent(expedienteId) + '/documentacion',
    ),

  documentacionArchivoUrl: (expedienteId: string, docId: string) =>
    `/api/expedientes/${encodeURIComponent(expedienteId)}/documentacion/${encodeURIComponent(docId)}/archivo`,

  documentacionIdentidadUrl: (expedienteId: string, lado: 'anverso' | 'reverso') =>
    `/api/expedientes/${encodeURIComponent(expedienteId)}/documentacion/identidad/${lado}`,

  getTwilioEstado: () => request<TwilioEstadoResponse>('/api/integraciones/twilio/estado'),

  getEmailEstado: () => request<EmailEstadoResponse>('/api/integraciones/email/estado'),

  probarEmail: (body: { email: string; asunto?: string; mensaje?: string }) =>
    request<{ enviado: boolean }>('/api/integraciones/email/probar', {
      method: 'POST',
      body: JSON.stringify(body),
    }),

  probarTwilio: (body: { canal: 'sms' | 'whatsapp'; telefono: string; mensaje?: string }) =>
    request<{ canal: string; enviado: boolean }>('/api/integraciones/twilio/probar', {
      method: 'POST',
      body: JSON.stringify(body),
    }),

  enviarEnlaceExpediente: (expedienteId: string, canales: ('whatsapp' | 'email')[]) =>
    request<{ canalesEnviados: string[] }>(
      '/api/integraciones/twilio/expedientes/' + encodeURIComponent(expedienteId) + '/enviar-enlace',
      { method: 'POST', body: JSON.stringify({ canales }) },
    ),

  getContratacionDocumentos: (expedienteId: string) =>
    request<ContratacionDocumentoResponse[]>(
      '/api/expedientes/' + encodeURIComponent(expedienteId) + '/contratacion/documentos',
    ),

  subirDocumentoIdentidadContratacion: (
    expedienteId: string,
    input: {
      tipoEscaneo: TipoEscaneoDocumentoIdentidad;
      anverso: File;
      reverso: File | null;
      datos: ClienteInput;
    },
  ) => {
    const formData = new FormData();
    formData.append('tipoEscaneo', input.tipoEscaneo);
    formData.append('anverso', input.anverso);
    if (input.reverso) {
      formData.append('reverso', input.reverso);
    }
    Object.entries(input.datos).forEach(([key, value]) => {
      if (value != null && value !== '') {
        formData.append(`datos[${key}]`, String(value));
      }
    });
    return multipartRequest<ContratacionResponse>(
      '/api/expedientes/' + encodeURIComponent(expedienteId) + '/contratacion/documento-identidad',
      formData,
    );
  },

  contratacionDocumentoArchivoUrl: (expedienteId: string, docId: string) =>
    `/api/expedientes/${encodeURIComponent(expedienteId)}/contratacion/documentos/${encodeURIComponent(docId)}/archivo`,

  contratacionFirmaPdfUrl: (expedienteId: string, tipo: string) =>
    `/api/expedientes/${encodeURIComponent(expedienteId)}/contratacion/firmas/${encodeURIComponent(tipo)}/pdf`,

  subirDocumentoContratacion: (token: string, docId: string, files: File[]) => {
    const formData = new FormData();
    appendArchivosToFormData(formData, files);
    return publicMultipartRequest<AccesoExpedienteResponse>(
      '/api/acceso/' + encodeURIComponent(token) + '/documentos/' + encodeURIComponent(docId),
      formData,
    );
  },

  registrarFirmaDocumento: (token: string, tipo: string, file: File) => {
    const formData = new FormData();
    formData.append('firma', file);
    return publicMultipartRequest<AccesoExpedienteResponse>(
      '/api/acceso/' + encodeURIComponent(token) + '/firma/' + encodeURIComponent(tipo),
      formData,
    );
  },

  enviarOtpFirma: (token: string) =>
    publicRequest<OtpFirmaEnviarResponse>(
      '/api/acceso/' + encodeURIComponent(token) + '/firma/otp/enviar',
      { method: 'POST' },
    ),

  verificarOtpFirma: (token: string, codigo: string) =>
    publicRequest<AccesoExpedienteResponse>(
      '/api/acceso/' + encodeURIComponent(token) + '/firma/otp/verificar',
      { method: 'POST', body: JSON.stringify({ codigo }) },
    ),

  iniciarPagoAcceso: (token: string) =>
    publicRequest<{ checkoutUrl: string }>('/api/acceso/' + encodeURIComponent(token) + '/iniciar-pago', {
      method: 'POST',
    }),

  validarPasoContratacion: (expedienteId: string, paso: string) =>
    request<ContratacionResponse>(
      '/api/expedientes/' + encodeURIComponent(expedienteId) + '/contratacion/validar/' + encodeURIComponent(paso),
      { method: 'POST' },
    ),

  devolverPasoContratacion: (
    expedienteId: string,
    paso: string,
    nota: string,
    motivos?: MotivoDevolucionIdentidad[],
  ) =>
    request<ContratacionResponse>(
      '/api/expedientes/' + encodeURIComponent(expedienteId) + '/contratacion/devolver/' + encodeURIComponent(paso),
      { method: 'POST', body: JSON.stringify({ nota, motivos: motivos ?? [] }) },
    ),

  getRequerimientos: (expedienteId: string) =>
    request<RequerimientosResponse>(
      '/api/expedientes/' + encodeURIComponent(expedienteId) + '/requerimientos',
    ),

  agregarDocumentoRequerimientos: (
    expedienteId: string,
    body: {
      nombre: string;
      descripcion?: string;
      obligatorio?: boolean;
      tipo?: 'individual' | 'conjunto';
      maxImagenes?: number;
    },
  ) =>
    request<{ id: string }>(
      '/api/expedientes/' + encodeURIComponent(expedienteId) + '/requerimientos/documentos',
      { method: 'POST', body: JSON.stringify(body) },
    ),

  validarDocumentoRequerimientos: (expedienteId: string, docId: string) =>
    request<RequerimientosResponse>(
      '/api/expedientes/' +
        encodeURIComponent(expedienteId) +
        '/requerimientos/documentos/' +
        encodeURIComponent(docId) +
        '/validar',
      { method: 'POST' },
    ),

  devolverDocumentoRequerimientos: (expedienteId: string, docId: string, nota: string) =>
    request<RequerimientosResponse>(
      '/api/expedientes/' +
        encodeURIComponent(expedienteId) +
        '/requerimientos/documentos/' +
        encodeURIComponent(docId) +
        '/devolver',
      { method: 'POST', body: JSON.stringify({ nota }) },
    ),

  asignarDocumentoRequerimientoAbogado: (expedienteId: string, docId: string) =>
    request<RequerimientosResponse>(
      '/api/expedientes/' +
        encodeURIComponent(expedienteId) +
        '/requerimientos/documentos/' +
        encodeURIComponent(docId) +
        '/asignar-abogado',
      { method: 'POST' },
    ),

  derivarDocumentoRequerimientoCliente: (
    expedienteId: string,
    docId: string,
    nota?: string,
  ) =>
    request<RequerimientosResponse>(
      '/api/expedientes/' +
        encodeURIComponent(expedienteId) +
        '/requerimientos/documentos/' +
        encodeURIComponent(docId) +
        '/derivar-cliente',
      { method: 'POST', body: JSON.stringify({ nota: nota ?? '' }) },
    ),

  requerimientosDocumentoArchivoUrl: (expedienteId: string, docId: string, archivoId?: string) => {
    const base = `/api/expedientes/${encodeURIComponent(expedienteId)}/requerimientos/documentos/${encodeURIComponent(docId)}/archivo`;
    return archivoId ? `${base}?archivoId=${encodeURIComponent(archivoId)}` : base;
  },

  guardarEscritoRequerimientos: (
    expedienteId: string,
    body: { titulo: string; contenidoHtml: string },
  ) =>
    request<{ id: string; titulo: string; pdfPath: string }>(
      '/api/expedientes/' + encodeURIComponent(expedienteId) + '/requerimientos/escritos',
      { method: 'POST', body: JSON.stringify(body) },
    ),

  requerimientosEscritoPdfUrl: (expedienteId: string, escritoId: string) =>
    `/api/expedientes/${encodeURIComponent(expedienteId)}/requerimientos/escritos/${encodeURIComponent(escritoId)}/pdf`,

  getEscritosExpediente: (expedienteId: string) =>
    request<ExpedienteEscritoListItem[]>(
      '/api/expedientes/' + encodeURIComponent(expedienteId) + '/escritos',
    ),

  getEscritoExpediente: (expedienteId: string, escritoId: string) =>
    request<ExpedienteEscritoDetail>(
      '/api/expedientes/' +
        encodeURIComponent(expedienteId) +
        '/escritos/' +
        encodeURIComponent(escritoId),
    ),

  crearEscritoExpediente: (expedienteId: string, body: { titulo: string; contenidoHtml: string }) =>
    request<{ id: string; titulo: string; pdfPath: string }>(
      '/api/expedientes/' + encodeURIComponent(expedienteId) + '/escritos',
      { method: 'POST', body: JSON.stringify(body) },
    ),

  actualizarEscritoExpediente: (
    expedienteId: string,
    escritoId: string,
    body: { titulo: string; contenidoHtml: string },
  ) =>
    request<{ id: string; titulo: string; pdfPath: string }>(
      '/api/expedientes/' +
        encodeURIComponent(expedienteId) +
        '/escritos/' +
        encodeURIComponent(escritoId),
      { method: 'PUT', body: JSON.stringify(body) },
    ),

  escritoExpedientePdfUrl: (expedienteId: string, escritoId: string) =>
    `/api/expedientes/${encodeURIComponent(expedienteId)}/escritos/${encodeURIComponent(escritoId)}/pdf`,

  getFacturacionExpediente: (expedienteId: string) =>
    request<FacturacionExpedienteResponse>(
      '/api/expedientes/' + encodeURIComponent(expedienteId) + '/facturacion',
    ),

  avanzarTramitacion: (expedienteId: string) =>
    request<{ message: string }>(
      '/api/expedientes/' + encodeURIComponent(expedienteId) + '/requerimientos/avanzar-tramitacion',
      { method: 'POST' },
    ),

  generarPdfConjuntoRequerimientos: async (
    expedienteId: string,
    archivoIds: string[],
  ): Promise<{ blob: Blob; filename: string }> => {
    const res = await fetch(
      API_BASE +
        '/api/expedientes/' +
        encodeURIComponent(expedienteId) +
        '/requerimientos/pdf-conjunto',
      {
        method: 'POST',
        headers: mergeFetchHeaders({
          'Content-Type': 'application/json',
          ...getAuthHeaders(),
        }),
        body: JSON.stringify({ archivoIds }),
      },
    );

    if (res.status === 401) {
      localStorage.removeItem(TOKEN_KEY);
      window.location.href = '/login';
      throw new Error('Sesión expirada. Por favor, inicie sesión de nuevo.');
    }

    if (!res.ok) {
      const err = await res.json().catch(() => ({ message: res.statusText }));
      throw new Error(
        (err as { error?: string; message?: string }).message ||
          (err as { error?: string }).error ||
          'No se pudo generar el PDF conjunto.',
      );
    }

    const disposition = res.headers.get('Content-Disposition');
    const filenameMatch = disposition ? /filename="([^"]+)"/i.exec(disposition) : null;
    const filename = filenameMatch?.[1] ?? `mercurio-${expedienteId}.pdf`;

    return { blob: await res.blob(), filename };
  },

  subirDocumentoRequerimientos: (token: string, docId: string, files: File[]) => {
    const formData = new FormData();
    appendArchivosToFormData(formData, files);
    return publicMultipartRequest<AccesoExpedienteResponse>(
      '/api/acceso/' +
        encodeURIComponent(token) +
        '/requerimientos/documentos/' +
        encodeURIComponent(docId),
      formData,
    );
  },

  subirDocumentoRequerimientosAbogado: (
    expedienteId: string,
    docId: string,
    files: File[],
    modo: 'validar' | 'aportar' = 'validar',
  ) => {
    const formData = new FormData();
    appendArchivosToFormData(formData, files);
    const query = modo === 'aportar' ? '?modo=aportar' : '';
    return multipartRequest<RequerimientosResponse>(
      '/api/expedientes/' +
        encodeURIComponent(expedienteId) +
        '/requerimientos/documentos/' +
        encodeURIComponent(docId) +
        '/subir' +
        query,
      formData,
    );
  },

  actualizarCondicionesPagoContratacion: (
    expedienteId: string,
    body: {
      metodoPago: MetodoPago;
      planPago: PlanPago;
      numCuotas: number;
      honorariosAcordados?: number;
    },
  ) =>
    request<ActualizarCondicionesPagoResponse>(
      '/api/expedientes/' + encodeURIComponent(expedienteId) + '/contratacion/condiciones-pago',
      { method: 'PUT', body: JSON.stringify(body) },
    ),

  extraerDocumentoIdentidad: (input: {
    tipoEscaneo: TipoEscaneoDocumentoIdentidad;
    anverso: File;
    reverso: File | null;
  }) => {
    const formData = new FormData();
    formData.append('tipoEscaneo', input.tipoEscaneo);
    formData.append('anverso', input.anverso);
    if (input.reverso) {
      formData.append('reverso', input.reverso);
    }
    return multipartRequest<ExtraerDocumentoIdentidadResponse>(
      '/api/clientes/extraer-documento',
      formData,
    );
  },

  postClienteConDocumento: (input: {
    tipoEscaneo: TipoEscaneoDocumentoIdentidad;
    anverso: File;
    reverso: File | null;
    datos: ClienteInput;
  }) => {
    const formData = new FormData();
    formData.append('tipoEscaneo', input.tipoEscaneo);
    formData.append('anverso', input.anverso);
    if (input.reverso) {
      formData.append('reverso', input.reverso);
    }
    Object.entries(input.datos).forEach(([key, value]) => {
      if (value != null && value !== '') {
        formData.append(`datos[${key}]`, String(value));
      }
    });
    return multipartRequest<ClienteResponse>('/api/clientes', formData);
  },

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

  deleteCliente: (id: string) =>
    request<void>('/api/clientes/' + encodeURIComponent(id), {
      method: 'DELETE',
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
        headers: mergeFetchHeaders({
          'Content-Type': 'application/json',
          ...getAuthHeaders(),
        }),
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

  getDocumentosRequeridosServicio: (servicioId: string) =>
    request<DocumentosRequeridosResponse>(
      '/api/servicios/' + encodeURIComponent(servicioId) + '/documentos-requeridos',
    ),

  putDocumentosRequeridosServicio: (servicioId: string, documentos: DocumentoRequeridoInput[]) =>
    request<DocumentosRequeridosResponse>(
      '/api/servicios/' + encodeURIComponent(servicioId) + '/documentos-requeridos',
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
  requiereOtpFirma: boolean;
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
  avisosPendientes?: number;
  avisosDetalle?: {
    contratacion: number;
    requerimientos: number;
  };
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
  fechaVencimientoFase?: string | null;
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

export type MotivoDevolucionIdentidad =
  | 'datos_personales'
  | 'documento_anverso'
  | 'documento_reverso'
  | 'documento_completo'
  | 'documentacion_adicional';

export interface AccesoIdentidadEdicionResponse {
  modoCorreccion: boolean;
  tieneDocumentoPrevio?: boolean;
  tipoEscaneo?: TipoEscaneoDocumentoIdentidad | string | null;
  anversoUrl?: string | null;
  reversoUrl?: string | null;
  motivosDevolucion?: MotivoDevolucionIdentidad[];
}

export interface AccesoPasoResponse {
  paso: string;
  label: string;
  estado: string;
  estadoLabel: string;
  esActivo?: boolean;
  notaDevolucion?: string | null;
  motivosDevolucion?: MotivoDevolucionIdentidad[];
}

export interface AccesoDocumentoFirmaResponse {
  tipo: string;
  label: string;
  previewUrl: string;
  firmado?: boolean;
  firmadoPdfUrl?: string | null;
}

export interface CalendarioCuotaResponse {
  numero: number;
  importe: number;
  fechaVencimiento: string;
  estado: string;
}

export interface AccesoResumenPagoResponse {
  honorariosAcordados: number;
  metodoPago: MetodoPago;
  metodoPagoLabel: string;
  planPago: PlanPago;
  planPagoLabel?: string;
  numCuotas: number;
  importeCuota: number;
  importePagoInicial?: number;
  iban: string;
  titularCuenta: string;
  entidadBancaria: string;
  calendarioPago?: CalendarioCuotaResponse[] | null;
  calendarioProyectado?: CalendarioCuotaResponse[] | null;
  fechaFirmaContrato?: string | null;
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
  tipoServicio?: string | null;
  faseNegocio: FaseNegocio;
  faseNegocioLabel: string;
  estadoFase: string;
  estadoFaseLabel: string;
  fechaVencimientoFase?: string | null;
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
  datosClienteEditables?: ClienteInput | null;
  identidadEdicion?: AccesoIdentidadEdicionResponse | null;
  clienteNombre?: string | null;
  despachoLogoUrl?: string | null;
  despachoNombreFirma?: string | null;
  despachoSubtitulo?: string | null;
  firmas?: AccesoFirmasConfigResponse;
  requerimientos?: AccesoRequerimientosResponse | null;
}

export interface RequerimientoDocumentoArchivoResponse {
  id: string;
  nombre: string;
  orden: number;
}

export interface AccesoRequerimientosDocumentoResponse {
  id: string;
  nombre: string;
  descripcion: string;
  obligatorio: boolean;
  tipo: string;
  maxImagenes: number;
  estado: string;
  estadoLabel?: string;
  subidoPor?: 'cliente' | 'abogado';
  notaRechazo?: string | null;
  entregadoAt?: string | null;
  archivos?: RequerimientoDocumentoArchivoResponse[];
  puedeSubir: boolean;
  responsableActual?: 'cliente' | 'abogado';
  puedeTomarAbogado?: boolean;
  puedeDerivarCliente?: boolean;
  parcialConArchivos?: boolean;
}

export interface AccesoRequerimientosResponse {
  documentos: AccesoRequerimientosDocumentoResponse[];
  pendientesSubida: number;
  enRevision: number;
  esperandoAbogado: boolean;
  agenteResponsableExpediente?: 'cliente' | 'abogado' | null;
}

export interface AccesoFirmasConfigResponse {
  requiereOtp: boolean;
  otpVerificado: boolean;
  telefonoMascara: string | null;
}

export interface OtpFirmaEnviarResponse {
  telefonoMascara: string;
  expiraEnSegundos: number;
  otpVerificado: boolean;
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
  notaDevolucion?: string | null;
  motivosDevolucion?: MotivoDevolucionIdentidad[];
}

export interface ContratacionHitoResponse {
  id: string;
  tipo: string;
  descripcion: string;
  actor: string;
  paso: string | null;
  createdAt: string;
}

export interface ContratacionFirmaDocumentoResponse {
  tipo: string;
  label: string;
  firmado: boolean;
  firmadoAt: string | null;
  pdfSha256: string | null;
  integridadOk: boolean | null;
}

export interface ActualizarCondicionesPagoResponse {
  metodoPago: MetodoPago;
  metodoPagoLabel: string;
  planPago: PlanPago;
  planPagoLabel: string;
  numCuotas: number;
  honorariosAcordados: number;
  calendarioProyectado: CalendarioCuotaResponse[];
  condicionesPagoEditables: boolean;
}

export interface ContratacionResponse {
  expedienteId: string;
  numero: string;
  clienteId?: string | null;
  faseNegocio: FaseNegocio;
  faseNegocioLabel: string;
  estadoFase: string;
  estadoFaseLabel: string;
  metodoPago: MetodoPago;
  metodoPagoLabel: string;
  planPago: PlanPago;
  honorariosAcordados: number;
  importePagoInicial?: number;
  numCuotas: number;
  accessUrl: string | null;
  fechaVencimientoFase?: string | null;
  pasoActivo: string | null;
  contratacionCompletada: boolean;
  pasos: ContratacionPasoResponse[];
  firmasDocumento?: ContratacionFirmaDocumentoResponse[];
  fechaFirmaContrato?: string | null;
  calendarioPago?: CalendarioCuotaResponse[] | null;
  calendarioProyectado?: CalendarioCuotaResponse[] | null;
  condicionesPagoEditables?: boolean;
  hitos?: ContratacionHitoResponse[];
}

export interface RequerimientosDocumentoResponse {
  id: string;
  nombre: string;
  descripcion: string;
  obligatorio: boolean;
  tipo: string;
  maxImagenes: number;
  orden: number;
  origen: string;
  origenLabel: string;
  estado: string;
  estadoLabel: string;
  entregadoAt: string | null;
  notaRechazo?: string | null;
  tieneArchivo: boolean;
  archivos?: RequerimientoDocumentoArchivoResponse[];
  requiereRevision: boolean;
  subidoPor?: 'cliente' | 'abogado';
  responsableActual?: 'cliente' | 'abogado';
  parcialConArchivos?: boolean;
  puedeSubir?: boolean;
  puedeSubirAbogado?: boolean;
  puedeValidarAbogado?: boolean;
  puedeDevolverAbogado?: boolean;
  puedeTomarAbogado?: boolean;
  puedeDerivarCliente?: boolean;
}

export interface RequerimientosEscritoResponse {
  id: string;
  titulo: string;
  createdAt: string;
}

export interface ExpedienteEscritoListItem {
  id: string;
  titulo: string;
  createdAt: string;
  pdfUrl: string;
}

export interface ExpedienteEscritoDetail extends ExpedienteEscritoListItem {
  contenidoHtml: string;
}

export interface CobroExpedienteResponse {
  numero: number;
  importe: number;
  fechaVencimiento: string;
  estado: 'pagado' | 'vencido' | 'enlace_pendiente' | 'pendiente';
  estadoLabel: string;
  paymentId?: string | null;
  paymentType?: string | null;
  paymentStatus?: string | null;
  holdedEstado?: PaymentHoldedEstado | null;
  holdedEstadoLabel?: string | null;
  holdedSyncError?: string | null;
  pdfUrl?: string | null;
}

export interface FacturacionExpedienteResponse {
  expedienteId: string;
  numero: string;
  clientName: string;
  honorariosAcordados: number;
  planPago: string;
  numCuotas: number;
  metodoPago: string;
  contacto: { telefono: string; email: string };
  cobros: CobroExpedienteResponse[];
  resumen: { total: number; cobrado: number; pendiente: number; vencido: number };
  holdedResumen: {
    pendientes: number;
    errores: number;
    requiereAccion: boolean;
  };
  historialPagos: PaymentResponse[];
}

export interface RequerimientosProgresoResponse {
  total: number;
  obligatorios: number;
  validados: number;
  pendientesEntrega: number;
  enRevision: number;
  rechazados: number;
  todosObligatoriosValidados: boolean;
  ningunoEnRevision: boolean;
  requerimientosListo: boolean;
}

export interface RequerimientosResponse {
  expedienteId: string;
  numero: string;
  faseNegocio: FaseNegocio;
  estadoFase: string;
  estadoFaseLabel: string;
  accessUrl: string | null;
  documentos: RequerimientosDocumentoResponse[];
  escritos: RequerimientosEscritoResponse[];
  progreso: RequerimientosProgresoResponse;
  puedeAvanzarFase3: boolean;
  esperandoAbogado?: boolean;
  agenteResponsableExpediente?: 'cliente' | 'abogado' | null;
}

export interface DocumentacionExpedienteItemResponse {
  id: string;
  nombre: string;
  descripcion: string;
  tipo: 'documento' | 'escrito';
  fase: number;
  faseLabel: string;
  faseNegocio: string;
  faseNegocioLabel: string;
  origen: 'requisito_tramite' | 'documento_firmado' | 'identidad_cliente' | 'escrito_firmado';
  origenLabel: string;
  obligatorio: boolean;
  estado: string;
  entregadoAt: string | null;
  descargaUrl: string | null;
  mediaTipo?: 'pdf' | 'imagen';
}

export interface EmailEstadoResponse {
  configurado: boolean;
  capturaLocal?: boolean;
  bandejaUrl?: string | null;
}

export interface TwilioEstadoResponse {
  sms: boolean;
  whatsapp: boolean;
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

export type ClienteHoldedEstado = 'oportunidad' | 'sincronizado' | 'error';

export type TipoEscaneoDocumentoIdentidad = 'dni_nie' | 'pasaporte';

export interface DocumentoIdentidadInfo {
  tipoEscaneo?: TipoEscaneoDocumentoIdentidad | null;
  tipoEscaneoLabel?: string | null;
  tieneAnverso?: boolean;
  tieneReverso?: boolean;
  escaneadoAt?: string | null;
  anversoUrl?: string;
  reversoUrl?: string;
}

export interface DocumentoIdentidadExtraido {
  nombre: string;
  nacionalidad: string;
  tipoDocumento: string;
  numDocumento: string;
  fechaNacimiento: string | null;
  lugarNacimiento: string;
  domicilio?: string;
  codigoPostal?: string;
  ciudad?: string;
  provincia?: string;
  nombrePadre?: string;
  nombreMadre?: string;
  extraccionAutomatica?: boolean;
  /** Campos leídos de la MRZ que el cliente no puede modificar. */
  camposMrz?: string[];
}

export interface ExtraerDocumentoIdentidadResponse {
  tipoEscaneo: TipoEscaneoDocumentoIdentidad;
  tipoEscaneoLabel: string;
  datosExtraidos: DocumentoIdentidadExtraido;
}

export interface ClienteResponse {
  id: string;
  nombre: string;
  nacionalidad: string;
  tipoDocumento: string;
  numDocumento: string;
  fechaNacimiento: string | null;
  lugarNacimiento: string;
  estadoCivil: string;
  domicilio: string;
  codigoPostal: string;
  ciudad: string;
  provincia: string;
  nombrePadre: string;
  nombreMadre: string;
  telefono: string;
  email: string;
  holdedContactId?: string | null;
  holdedEstado?: ClienteHoldedEstado;
  holdedEstadoLabel?: string;
  holdedSyncedAt?: string | null;
  holdedSyncError?: string | null;
  numExpedientes?: number;
  documentoIdentidad?: DocumentoIdentidadInfo;
}

export interface ClienteDetalleResponse {
  cliente: ClienteResponse;
  edicionBloqueada?: boolean;
  motivoEdicionBloqueada?: string | null;
  expedientesAbiertos?: Array<{ numero: string; titulo: string }>;
  expedientes: Array<
    ExpedienteResponse & { faseNegocioLabel: string; estadoFaseLabel: string }
  >;
  tramitesDerivadosPendientes: unknown[];
}

export interface SincronizarHoldedResponse {
  success: boolean;
  holdedContactId?: string;
  error?: string;
}

export interface ClienteInput {
  nombre: string;
  nacionalidad?: string;
  tipoDocumento?: string;
  numDocumento?: string;
  fechaNacimiento?: string | null;
  lugarNacimiento?: string;
  estadoCivil?: string;
  domicilio?: string;
  codigoPostal?: string;
  ciudad?: string;
  provincia?: string;
  nombrePadre?: string;
  nombreMadre?: string;
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
  estado?: string;
  entregadoAt?: string | null;
}

export interface ContratacionDocumentoResponse {
  id: string;
  nombre: string;
  descripcion: string;
  obligatorio: boolean;
  tipo: string;
  maxImagenes: number;
  estado: string;
  entregadoAt?: string | null;
  archivoPath?: string | null;
}

export interface NotificacionesRecientesResponse {
  items: NotificacionResponse[];
  total: number;
}

export interface NotificacionResponse {
  id: string;
  tipo: string;
  descripcion: string;
  actor: string;
  paso?: string | null;
  referenciaId?: string | null;
  expedienteId: string;
  expedienteNumero: string;
  clienteNombre: string;
  createdAt: string;
  destinoTab: string;
  destinoHitoId: string;
  destinoPaso?: string | null;
  destinoReferenciaId?: string | null;
  abrirRevision: boolean;
}

export interface ExpedienteAuditoriaEntryResponse {
  id: string;
  source: 'hito' | 'pago' | 'expediente';
  categoria: string;
  categoriaLabel: string;
  tipo: string;
  tipoLabel: string;
  actor: string;
  actorLabel: string;
  resumen: string;
  detalle: string | null;
  canal: string | null;
  canalLabel: string | null;
  paso: string | null;
  referenciaId?: string | null;
  createdAt: string;
}

export interface ExpedienteAuditoriaResponse {
  items: ExpedienteAuditoriaEntryResponse[];
  total: number;
}

export type DocumentoRequeridoInput = Pick<
  DocumentoRequerido,
  'fase' | 'nombre' | 'descripcion' | 'obligatorio' | 'tipo' | 'maxImagenes' | 'orden'
> & { id?: string };

export interface DocumentosRequeridosResponse {
  documentos: DocumentoRequerido[];
  conversionPdfAutomatica: boolean;
}
