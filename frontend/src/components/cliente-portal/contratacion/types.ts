import type {
  AccesoExpedienteResponse,
  AccesoPasoResponse,
  DocumentoRequerido,
} from '@/api/client';

export type MicroItemKind = 'identidad' | 'doc' | 'otp' | 'firma' | 'pago';

export type MicroItemStatus = 'pending' | 'done' | 'locked';

export interface ContratacionMicroItem {
  id: string;
  kind: MicroItemKind;
  label: string;
  description?: string;
  status: MicroItemStatus;
  /** Documento requerido id or firma tipo */
  refId?: string;
}

export type ContratacionView =
  | { type: 'hub' }
  | { type: 'identidad' }
  | { type: 'doc'; docId: string }
  | { type: 'otp' }
  | { type: 'firma'; tipo: string }
  | { type: 'pago' }
  | { type: 'resumen_final' }
  | { type: 'waiting' };

export function docEntregado(doc: DocumentoRequerido): boolean {
  return doc.estado === 'entregado' || doc.estado === 'validado';
}

export function buildMicroItems(
  data: AccesoExpedienteResponse,
  pasoActivo: AccesoPasoResponse | null,
): ContratacionMicroItem[] {
  if (!pasoActivo) return [];

  const items: ContratacionMicroItem[] = [];

  if (pasoActivo.paso === 'datos_cliente') {
    items.push({
      id: 'identidad',
      kind: 'identidad',
      label: 'Identidad y datos personales',
      description: 'Documento de identidad y verificación de sus datos',
      status: 'pending',
    });

    const docs = (data.documentosRequeridos ?? []).filter((d) => d.obligatorio);
    for (const doc of docs) {
      items.push({
        id: `doc:${doc.id}`,
        kind: 'doc',
        label: doc.nombre,
        description: doc.descripcion || undefined,
        status: docEntregado(doc) ? 'done' : 'pending',
        refId: doc.id,
      });
    }
  }

  if (pasoActivo.paso === 'firmas') {
    const requiereOtp = data.firmas?.requiereOtp ?? true;
    const otpOk = !requiereOtp || !!data.firmas?.otpVerificado;

    if (requiereOtp) {
      items.push({
        id: 'otp',
        kind: 'otp',
        label: 'Verificar móvil',
        description: data.firmas?.telefonoMascara
          ? `Enviaremos un código a ${data.firmas.telefonoMascara}`
          : 'Confirmación por SMS antes de firmar',
        status: otpOk ? 'done' : 'pending',
      });
    }

    for (const doc of data.documentosFirma ?? []) {
      items.push({
        id: `firma:${doc.tipo}`,
        kind: 'firma',
        label: doc.label,
        description: doc.firmado ? 'Documento firmado' : 'Leer y firmar electrónicamente',
        status: doc.firmado ? 'done' : otpOk ? 'pending' : 'locked',
        refId: doc.tipo,
      });
    }
  }

  if (pasoActivo.paso === 'pago') {
    items.push({
      id: 'pago',
      kind: 'pago',
      label: 'Pago inicial',
      description: data.resumenPago?.metodoPagoLabel ?? 'Complete el pago acordado',
      status: 'pending',
    });
  }

  return items;
}

export function viewFromItem(item: ContratacionMicroItem): ContratacionView {
  switch (item.kind) {
    case 'identidad':
      return { type: 'identidad' };
    case 'doc':
      return { type: 'doc', docId: item.refId! };
    case 'otp':
      return { type: 'otp' };
    case 'firma':
      return { type: 'firma', tipo: item.refId! };
    case 'pago':
      return { type: 'pago' };
  }
}

export function itemLabelForView(
  view: ContratacionView,
  items: ContratacionMicroItem[],
): string {
  if (view.type === 'hub') return 'Resumen';
  if (view.type === 'resumen_final') return 'Resumen de contratación';
  if (view.type === 'waiting') return 'En revisión';
  if (view.type === 'identidad') {
    return items.find((i) => i.kind === 'identidad')?.label ?? 'Identidad';
  }
  if (view.type === 'doc') {
    return items.find((i) => i.id === `doc:${view.docId}`)?.label ?? 'Documento';
  }
  if (view.type === 'otp') {
    return items.find((i) => i.kind === 'otp')?.label ?? 'Verificar móvil';
  }
  if (view.type === 'firma') {
    return items.find((i) => i.id === `firma:${view.tipo}`)?.label ?? 'Firma';
  }
  if (view.type === 'pago') {
    return items.find((i) => i.kind === 'pago')?.label ?? 'Pago';
  }
  return '';
}

export function nextPendingItem(
  items: ContratacionMicroItem[],
  afterId?: string | null,
): ContratacionMicroItem | null {
  if (!afterId) {
    return items.find((i) => i.status === 'pending') ?? null;
  }
  const idx = items.findIndex((i) => i.id === afterId);
  for (let i = idx + 1; i < items.length; i++) {
    if (items[i].status === 'pending') return items[i];
  }
  return null;
}

export function canConfirmPaso(
  data: AccesoExpedienteResponse,
  paso: AccesoPasoResponse,
): boolean {
  if (paso.paso === 'datos_cliente') return false;
  if (paso.paso === 'firmas') {
    return (data.documentosFirma ?? []).every((d) => d.firmado);
  }
  if (paso.paso === 'pago') return false;
  return false;
}

export function focusHint(
  view: ContratacionView,
  items: ContratacionMicroItem[],
  currentId: string | null,
): string {
  const next = nextPendingItem(items, currentId);
  if (view.type === 'hub') {
    return next
      ? `Continúe con: ${next.label}`
      : 'Revise el resumen y confirme cuando esté listo';
  }
  if (next && next.id !== currentId) {
    return `Al terminar podrá volver al resumen o continuar con: ${next.label}`;
  }
  return 'Al terminar volverá al resumen de su contratación';
}
