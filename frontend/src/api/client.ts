const API_BASE = import.meta.env.VITE_API_BASE_URL || '';
const TOKEN_KEY = 'bufete_jwt_token';

function getAuthHeaders(): Record<string, string> {
  const token = localStorage.getItem(TOKEN_KEY);
  if (!token) return {};
  return { Authorization: `Bearer ${token}` };
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
}
