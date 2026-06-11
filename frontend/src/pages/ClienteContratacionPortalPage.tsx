import { useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { CheckCircle2, Clock, CreditCard, FileText, PenLine, Scale, Upload, User } from 'lucide-react';
import { api, type AccesoExpedienteResponse, type AccesoPasoResponse } from '@/api/client';
import { DocumentoPdfPreview } from '@/components/cliente-portal/DocumentoPdfPreview';
import { ClienteIdentidadOnboarding } from '@/components/documento-identidad/ClienteIdentidadOnboarding';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

interface ClienteContratacionPortalPageProps {
  token: string;
}

const PASO_ICONS: Record<string, typeof Upload> = {
  documentacion: Upload,
  datos_cliente: User,
  firmas: PenLine,
  pago: CreditCard,
};

export function ClienteContratacionPortalPage({ token }: ClienteContratacionPortalPageProps) {
  const queryClient = useQueryClient();

  const { data, isLoading, error } = useQuery({
    queryKey: ['acceso', token],
    queryFn: () => api.getAccesoExpediente(token),
    retry: false,
  });

  const completarMutation = useMutation({
    mutationFn: (paso: string) => api.completarPasoCliente(token, paso),
    onSuccess: () => void queryClient.invalidateQueries({ queryKey: ['acceso', token] }),
  });

  const esperandoAbogado = useMemo(() => {
    if (!data?.pasos?.length) return false;
    return data.pasos.some((p) => p.estado === 'realizado_cliente');
  }, [data?.pasos]);

  return (
    <div className="min-h-screen bg-muted/30 py-8 px-4">
      <div className="mx-auto w-full max-w-2xl">
        <div className="mb-8 flex flex-col items-center gap-3">
          <img src="/logo.png" alt="Bufete Melián" className="h-12" />
          <div className="flex items-center gap-2 text-primary">
            <Scale className="h-5 w-5" />
            <span className="font-semibold">Marta Melián Abogados</span>
          </div>
        </div>

        <div className="panel p-6 sm:p-8">
          {isLoading && <p className="text-center text-muted-foreground">Cargando su expediente…</p>}

          {error && (
            <div className="text-center">
              <p className="font-medium text-destructive">Enlace no válido</p>
              <p className="mt-2 text-sm text-muted-foreground">
                Este enlace de acceso no es válido o ha expirado. Contacte con su abogado.
              </p>
            </div>
          )}

          {data && (
            <>
              <div className="mb-6 text-center">
                <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-primary/10">
                  <FileText className="h-7 w-7 text-primary" />
                </div>
                <h1 className="text-xl font-bold">Portal del cliente</h1>
                <p className="mt-2 text-sm text-muted-foreground">
                  Expediente <strong>{data.expedienteNumero}</strong> — {data.tramiteNombre}
                </p>
                <Badge variant="info" className="mt-3">
                  {data.faseNegocioLabel}
                </Badge>
              </div>

              {esperandoAbogado && (
                <div className="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                  <div className="flex items-start gap-2">
                    <Clock className="mt-0.5 h-4 w-4 shrink-0" />
                    <p>
                      Su abogado está revisando la información enviada. Recibirá acceso al siguiente paso cuando
                      valide el actual.
                    </p>
                  </div>
                </div>
              )}

              <div className="space-y-4">
                {(data.pasos ?? []).map((paso) => (
                  <PasoClienteCard
                    key={paso.paso}
                    token={token}
                    paso={paso}
                    data={data}
                    onCompletar={(p) => completarMutation.mutate(p)}
                    onIdentidadCompletada={() =>
                      void queryClient.invalidateQueries({ queryKey: ['acceso', token] })
                    }
                    completando={completarMutation.isPending}
                  />
                ))}
              </div>
            </>
          )}
        </div>
      </div>
    </div>
  );
}

function PasoClienteCard({
  token,
  paso,
  data,
  onCompletar,
  onIdentidadCompletada,
  completando,
}: {
  token: string;
  paso: AccesoPasoResponse;
  data: AccesoExpedienteResponse;
  onCompletar: (paso: string) => void;
  onIdentidadCompletada: () => void;
  completando: boolean;
}) {
  const [confirmado, setConfirmado] = useState(false);
  const [docsVistos, setDocsVistos] = useState<Set<string>>(new Set());

  const Icon = PASO_ICONS[paso.paso] ?? FileText;
  const esActivo = paso.esActivo === true;
  const completado = paso.estado !== 'pendiente';
  const enRevision = paso.estado === 'realizado_cliente';

  const todosDocsVistos =
    paso.paso !== 'firmas' ||
    (data.documentosFirma ?? []).every((d) => docsVistos.has(d.tipo));

  const puedeConfirmar = esActivo && confirmado && (paso.paso !== 'firmas' || todosDocsVistos);
  const usaOnboardingIdentidad = paso.paso === 'datos_cliente' && esActivo && !completado;

  return (
    <div
      className={cn(
        'rounded-xl border p-5 transition-colors',
        completado && paso.estado === 'validado_abogado' && 'border-emerald-200 bg-emerald-50/50',
        enRevision && 'border-amber-200 bg-amber-50/30',
        esActivo && !completado && 'border-primary bg-primary/5',
        !esActivo && !completado && 'border-border opacity-70',
      )}
    >
      <div className="flex items-start gap-3">
        <div
          className={cn(
            'flex h-10 w-10 shrink-0 items-center justify-center rounded-lg',
            paso.estado === 'validado_abogado' && 'bg-emerald-100 text-emerald-700',
            enRevision && 'bg-amber-100 text-amber-700',
            esActivo && !completado && 'bg-primary/10 text-primary',
            !esActivo && !completado && 'bg-muted text-muted-foreground',
          )}
        >
          {paso.estado === 'validado_abogado' ? (
            <CheckCircle2 className="h-5 w-5" />
          ) : (
            <Icon className="h-5 w-5" />
          )}
        </div>

        <div className="min-w-0 flex-1">
          <div className="flex flex-wrap items-center justify-between gap-2">
            <h3 className="font-semibold">{paso.label}</h3>
            <Badge
              variant={
                paso.estado === 'validado_abogado'
                  ? 'success'
                  : paso.estado === 'realizado_cliente'
                    ? 'warning'
                    : 'secondary'
              }
            >
              {paso.estadoLabel}
            </Badge>
          </div>

          {esActivo && !completado && (
            <div className="mt-4 space-y-4">
              {usaOnboardingIdentidad ? (
                <ClienteIdentidadOnboarding
                  token={token}
                  onCompletado={onIdentidadCompletada}
                />
              ) : (
                <>
                  <PasoContenido
                    paso={paso.paso}
                    data={data}
                    docsVistos={docsVistos}
                    onDocVisto={(tipo) => setDocsVistos((prev) => new Set(prev).add(tipo))}
                  />

                  <label className="flex items-start gap-2 text-sm cursor-pointer">
                    <input
                      type="checkbox"
                      className="mt-1"
                      checked={confirmado}
                      onChange={(e) => setConfirmado(e.target.checked)}
                    />
                    <span>He revisado la información y confirmo que todo es correcto.</span>
                  </label>

                  {paso.paso === 'firmas' && !todosDocsVistos && (
                    <p className="text-xs text-muted-foreground">
                      Debe abrir y revisar todos los documentos antes de confirmar.
                    </p>
                  )}

                  <Button
                    className="w-full"
                    onClick={() => onCompletar(paso.paso)}
                    disabled={!puedeConfirmar || completando}
                  >
                    Confirmar y continuar
                  </Button>
                </>
              )}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

function PasoContenido({
  paso,
  data,
  docsVistos,
  onDocVisto,
}: {
  paso: string;
  data: AccesoExpedienteResponse;
  docsVistos: Set<string>;
  onDocVisto: (tipo: string) => void;
}) {
  if (paso === 'documentacion') {
    const docs = data.documentosRequeridos ?? [];
    return (
      <div className="space-y-3">
        <p className="text-sm text-muted-foreground">
          Documentación que deberá aportar para su expediente. Revise la lista y confirme que la entenderá
          correctamente.
        </p>
        {docs.length === 0 ? (
          <p className="text-sm text-muted-foreground italic">No hay documentación adicional configurada.</p>
        ) : (
          <ul className="space-y-2">
            {docs.map((doc) => (
              <li key={doc.id} className="rounded-lg border bg-card p-3 text-sm">
                <div className="flex items-center justify-between gap-2">
                  <span className="font-medium">{doc.nombre}</span>
                  {doc.obligatorio && <Badge variant="secondary">Obligatorio</Badge>}
                </div>
                {doc.descripcion && <p className="mt-1 text-muted-foreground">{doc.descripcion}</p>}
              </li>
            ))}
          </ul>
        )}
      </div>
    );
  }

  if (paso === 'firmas') {
    return (
      <div className="space-y-2">
        <p className="text-sm text-muted-foreground">
          Abra y revise cada documento legal antes de confirmar su conformidad.
        </p>
        {(data.documentosFirma ?? []).map((doc) => (
          <DocumentoPdfPreview
            key={doc.tipo}
            label={doc.label}
            previewUrl={doc.previewUrl}
            viewed={docsVistos.has(doc.tipo)}
            onViewed={() => onDocVisto(doc.tipo)}
          />
        ))}
      </div>
    );
  }

  if (paso === 'pago' && data.resumenPago) {
    const r = data.resumenPago;
    return (
      <div className="rounded-lg border bg-card p-4 text-sm space-y-3">
        <p className="text-muted-foreground">
          Resumen del pago acordado con el despacho. Verifique importes y método antes de confirmar.
        </p>
        <DatoFila
          label="Honorarios totales"
          value={`${r.honorariosAcordados.toLocaleString('es-ES', { minimumFractionDigits: 2 })} €`}
          destacado
        />
        <DatoFila label="Método" value={r.metodoPagoLabel} />
        <DatoFila
          label="Plan"
          value={
            r.planPago === 'fraccionado'
              ? `${r.numCuotas} cuotas de ${r.importeCuota.toLocaleString('es-ES', { minimumFractionDigits: 2 })} €`
              : 'Pago único'
          }
        />
        {r.metodoPago === 'manual' && r.iban && (
          <>
            <DatoFila label="Titular" value={r.titularCuenta} />
            <DatoFila label="IBAN" value={r.iban} />
            {r.entidadBancaria && <DatoFila label="Entidad" value={r.entidadBancaria} />}
          </>
        )}
        {r.metodoPago === 'digital' && (
          <p className="text-xs text-muted-foreground">
            Tras confirmar, su abogado le enviará el enlace de pago digital correspondiente.
          </p>
        )}
      </div>
    );
  }

  return null;
}

function DatoFila({ label, value, destacado }: { label: string; value: string; destacado?: boolean }) {
  return (
    <div className="flex justify-between gap-4">
      <span className="text-muted-foreground">{label}</span>
      <span className={cn('text-right', destacado && 'font-bold text-primary')}>{value}</span>
    </div>
  );
}
