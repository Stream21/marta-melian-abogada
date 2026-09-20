import type { MetodoPago, PlanPago } from '@/api/client';
import type { TipoServicioValue } from '@/lib/servicio-tipos';

/** Plazo por defecto de la fase 1 (contratación): 2 semanas desde hoy. */
const DIAS_VENCIMIENTO_FASE_1 = 14;

function defaultFechaVencimientoFase(): string {
  const d = new Date();
  d.setHours(12, 0, 0, 0);
  d.setDate(d.getDate() + DIAS_VENCIMIENTO_FASE_1);
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}

export interface ExpedienteAltaState {
  step: number;
  modoCliente: 'existente' | 'nuevo';
  busquedaCliente: string;
  telefono: string;
  email: string;
  clienteId: string | null;
  clienteNombre: string;
  /** Cliente detectado al introducir teléfono o email en modo «nuevo». */
  clienteDetectado: {
    id: string;
    nombre: string;
    telefono: string;
    email: string;
    campo: 'telefono' | 'email';
  } | null;
  /** True mientras se consulta si el teléfono/email ya existen. */
  contactoVerificando: boolean;
  /** El abogado confirma crear cliente nuevo aunque el teléfono/email ya exista. */
  permitirDuplicado: boolean;
  areaTipo: TipoServicioValue | '';
  servicioId: string;
  servicioNombre: string;
  tramiteId: string;
  tramiteNombre: string;
  honorarios: number;
  metodoPago: MetodoPago;
  planPago: PlanPago;
  numCuotas: number;
  canalesNotificacion: {
    whatsapp: boolean;
    email: boolean;
  };
  fechaVencimientoFase: string;
}

export const initialAltaState: ExpedienteAltaState = {
  step: 1,
  modoCliente: 'nuevo',
  busquedaCliente: '',
  telefono: '',
  email: '',
  clienteId: null,
  clienteNombre: '',
  clienteDetectado: null,
  contactoVerificando: false,
  permitirDuplicado: false,
  areaTipo: '',
  servicioId: '',
  servicioNombre: '',
  tramiteId: '',
  tramiteNombre: '',
  honorarios: 0,
  metodoPago: 'manual',
  planPago: 'unico',
  numCuotas: 1,
  canalesNotificacion: { whatsapp: false, email: false },
  fechaVencimientoFase: defaultFechaVencimientoFase(),
};

export function canalesNotificacionPorDefecto(telefono: string, email: string): {
  whatsapp: boolean;
  email: boolean;
} {
  const tieneTelefono = !!telefono.trim();
  const tieneEmail = !!email.trim();

  return {
    whatsapp: tieneTelefono,
    email: tieneEmail,
  };
}
