/** Etiquetas legibles para migas de pan (evitar UUIDs crudos). */

import { capitalizeDisplay } from '@/lib/capitalize-display';

export function formatExpedienteBreadcrumbLabel(expediente: {
  numero?: string | null;
  titulo?: string | null;
  clientName?: string | null;
}): string {
  const numero = expediente.numero?.trim();
  if (!numero) return 'Expediente';

  const clienteRaw = expediente.clientName?.trim() ?? '';
  const cliente =
    clienteRaw && clienteRaw !== 'Cliente pendiente'
      ? /^\+?[\d\s\-().]{9,}$/.test(clienteRaw)
        ? clienteRaw
        : capitalizeDisplay(clienteRaw)
      : '';
  if (cliente) return `${numero} · ${cliente}`;

  const titulo = capitalizeDisplay(expediente.titulo);
  if (titulo && titulo !== numero) return `${numero} · ${titulo}`;

  return numero;
}

export function formatClienteBreadcrumbLabel(cliente: {
  nombre?: string | null;
  numDocumento?: string | null;
  tipoDocumento?: string | null;
}): string {
  const nombreRaw = cliente.nombre?.trim() ?? '';
  if (nombreRaw && nombreRaw !== 'Cliente pendiente') {
    return capitalizeDisplay(nombreRaw);
  }

  const doc = [cliente.tipoDocumento, cliente.numDocumento].filter(Boolean).join(' ').trim();
  return doc || 'Cliente';
}

export function formatServicioBreadcrumbLabel(servicio: { nombre?: string | null }): string {
  return capitalizeDisplay(servicio.nombre) || 'Servicio';
}

export function formatTramiteBreadcrumbLabel(tramite: { nombre?: string | null }): string {
  return capitalizeDisplay(tramite.nombre) || 'Trámite';
}
