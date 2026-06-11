import type { MetodoPago, PlanPago } from '@/api/client';
import type { TipoServicioValue } from '@/lib/servicio-tipos';

export interface ExpedienteAltaState {
  step: number;
  modoCliente: 'existente' | 'nuevo';
  busquedaCliente: string;
  telefono: string;
  email: string;
  clienteId: string | null;
  clienteNombre: string;
  telefonoDuplicado: { id: string; nombre: string } | null;
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
}

export const initialAltaState: ExpedienteAltaState = {
  step: 1,
  modoCliente: 'nuevo',
  busquedaCliente: '',
  telefono: '',
  email: '',
  clienteId: null,
  clienteNombre: '',
  telefonoDuplicado: null,
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
