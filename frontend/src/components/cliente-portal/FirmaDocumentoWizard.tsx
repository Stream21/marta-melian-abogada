import { useCallback, useMemo, useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import {
  CheckCircle2,
  ChevronDown,
  FileText,
  PenLine,
} from 'lucide-react';
import { api, type AccesoDocumentoFirmaResponse, type AccesoFirmasConfigResponse } from '@/api/client';
import { DocumentoPdfPreview } from '@/components/cliente-portal/DocumentoPdfPreview';
import { FirmaOtpPanel } from '@/components/cliente-portal/FirmaOtpPanel';
import { SignaturePad } from '@/components/signature/SignaturePad';
import { Badge } from '@/components/ui/badge';
import { extraerTiposFirmaDevolucion } from '@/lib/campos-devolucion';
import { cn } from '@/lib/utils';

interface FirmaDocumentoWizardProps {
  token: string;
  documentos: AccesoDocumentoFirmaResponse[];
  firmasConfig?: AccesoFirmasConfigResponse;
  notaDevolucion?: string | null;
  motivosDevolucion?: string[] | null;
}

export function FirmaDocumentoWizard({
  token,
  documentos,
  firmasConfig,
  notaDevolucion,
  motivosDevolucion,
}: FirmaDocumentoWizardProps) {
  const queryClient = useQueryClient();
  const [docActivo, setDocActivo] = useState<string | null>(null);
  const [documentosLeidos, setDocumentosLeidos] = useState<Record<string, boolean>>({});
  const [aceptaContenido, setAceptaContenido] = useState<Record<string, boolean>>({});
  const [otpVerificado, setOtpVerificado] = useState(firmasConfig?.otpVerificado ?? false);

  const requiereOtp = firmasConfig?.requiereOtp ?? true;
  const firmados = useMemo(() => documentos.filter((d) => d.firmado).length, [documentos]);
  const total = documentos.length;
  const firmasDesbloqueadas = !requiereOtp || otpVerificado;
  const tiposARefirmar = useMemo(
    () => new Set(extraerTiposFirmaDevolucion(motivosDevolucion)),
    [motivosDevolucion],
  );

  const firmarMutation = useMutation({
    mutationFn: ({ tipo, file }: { tipo: string; file: File }) =>
      api.registrarFirmaDocumento(token, tipo, file),
    onSuccess: (_data, variables) => {
      setDocActivo(null);
      setAceptaContenido((prev) => {
        const next = { ...prev };
        delete next[variables.tipo];
        return next;
      });
      void queryClient.invalidateQueries({ queryKey: ['acceso', token] });
    },
  });

  const marcarLeido = useCallback((tipo: string) => {
    setDocumentosLeidos((prev) => (prev[tipo] ? prev : { ...prev, [tipo]: true }));
  }, []);

  const puedeFirmar = (tipo: string) =>
    !!documentosLeidos[tipo] && !!aceptaContenido[tipo] && (!requiereOtp || otpVerificado);

  const toggleDoc = (tipo: string, firmado: boolean) => {
    if (!firmasDesbloqueadas && !firmado) return;
    setDocActivo((actual) => (actual === tipo ? null : tipo));
  };

  return (
    <div className="space-y-5">
      <div className="flex items-center justify-between gap-3 rounded-xl border border-border bg-card px-4 py-3">
        <div>
          <p className="text-sm font-semibold text-foreground">Firma de documentos</p>
          <p className="mt-0.5 text-xs text-muted-foreground">
            {firmados === total
              ? 'Todos los documentos están firmados.'
              : `${firmados} de ${total} firmados`}
          </p>
        </div>
        <span className="rounded-full bg-primary/10 px-3 py-1 text-sm font-semibold text-primary">
          {firmados}/{total}
        </span>
      </div>

      <FirmaOtpPanel
        token={token}
        requiereOtp={requiereOtp}
        otpVerificado={otpVerificado}
        telefonoMascara={firmasConfig?.telefonoMascara}
        onVerificado={() => {
          setOtpVerificado(true);
          void queryClient.invalidateQueries({ queryKey: ['acceso', token] });
        }}
      />

      {(notaDevolucion || tiposARefirmar.size > 0) && (
        <div className="space-y-2 rounded-xl border border-amber-300 bg-amber-50/90 px-4 py-3 text-sm text-amber-950">
          <p className="font-semibold">Su abogado solicita correcciones en las firmas</p>
          {notaDevolucion?.trim() && (
            <p className="whitespace-pre-wrap text-amber-900/95">{notaDevolucion}</p>
          )}
          {documentos.some((d) => !d.firmado) && (
            <ul className="flex flex-wrap gap-1.5 pt-1">
              {documentos
                .filter((d) => !d.firmado)
                .map((d) => (
                  <li
                    key={d.tipo}
                    className="rounded-full border border-amber-400 bg-white px-2.5 py-0.5 text-xs font-medium"
                  >
                    Volver a firmar: {d.label}
                  </li>
                ))}
            </ul>
          )}
        </div>
      )}

      <ol className="space-y-3">
        {documentos.map((doc, index) => {
          const expandido = docActivo === doc.tipo;
          const leido = !!documentosLeidos[doc.tipo] || !!doc.firmado;
          const bloqueado = !firmasDesbloqueadas && !doc.firmado;

          return (
            <li
              key={doc.tipo}
              className={cn(
                'overflow-hidden rounded-xl border transition-colors',
                doc.firmado
                  ? 'border-emerald-200 bg-emerald-50/40'
                  : 'border-border bg-card',
                bloqueado && 'opacity-55',
              )}
            >
              <button
                type="button"
                onClick={() => toggleDoc(doc.tipo, !!doc.firmado)}
                disabled={bloqueado}
                className={cn(
                  'flex w-full items-center gap-3 px-4 py-3.5 text-left transition-colors',
                  !bloqueado && 'hover:bg-muted/40',
                  'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-inset',
                  bloqueado && 'cursor-not-allowed',
                )}
                aria-expanded={expandido}
              >
                <span
                  className={cn(
                    'flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-bold',
                    doc.firmado
                      ? 'bg-emerald-500 text-white'
                      : expandido
                        ? 'bg-primary text-primary-foreground'
                        : 'bg-muted text-muted-foreground',
                  )}
                >
                  {doc.firmado ? <CheckCircle2 className="h-4 w-4" /> : index + 1}
                </span>

                <div className="min-w-0 flex-1">
                  <p className="truncate font-medium text-foreground">{doc.label}</p>
                  <p className="mt-0.5 text-xs text-muted-foreground">
                    {doc.firmado
                      ? 'Firmado · pulse para ver el PDF'
                      : bloqueado
                        ? 'Verifique el SMS para desbloquear'
                        : tiposARefirmar.has(doc.tipo)
                          ? 'Debe volver a firmar este documento'
                          : leido
                            ? 'Documento revisado · pulse para firmar'
                            : 'Pulse para abrir y firmar'}
                  </p>
                </div>

                <div className="flex shrink-0 items-center gap-2">
                  {doc.firmado ? (
                    <Badge variant="success">Firmado</Badge>
                  ) : tiposARefirmar.has(doc.tipo) && !doc.firmado ? (
                    <Badge variant="warning">Refirmar</Badge>
                  ) : leido ? (
                    <Badge variant="info">Revisado</Badge>
                  ) : (
                    <Badge variant="secondary">Pendiente</Badge>
                  )}
                  <ChevronDown
                    className={cn(
                      'h-4 w-4 text-muted-foreground transition-transform',
                      expandido && 'rotate-180',
                    )}
                  />
                </div>
              </button>

              {expandido && (
                <div className="space-y-4 border-t border-border bg-card px-4 py-4">
                  {!doc.firmado && (
                    <>
                      <DocumentoPdfPreview
                        label={doc.label}
                        previewUrl={doc.previewUrl}
                        requireFullRead
                        fullyRead={leido}
                        onFullyRead={() => marcarLeido(doc.tipo)}
                        ctaPrincipal
                      />

                      {leido && (
                        <label className="flex cursor-pointer items-start gap-3 rounded-xl border border-border bg-muted/30 px-3.5 py-3 text-sm">
                          <input
                            type="checkbox"
                            className="mt-1"
                            checked={!!aceptaContenido[doc.tipo]}
                            onChange={(e) =>
                              setAceptaContenido((prev) => ({
                                ...prev,
                                [doc.tipo]: e.target.checked,
                              }))
                            }
                          />
                          <span className="leading-snug text-foreground">
                            He leído <strong>{doc.label}</strong> y acepto firmarlo
                            electrónicamente.
                          </span>
                        </label>
                      )}

                      {puedeFirmar(doc.tipo) && (
                        <div className="space-y-2">
                          <div className="flex items-center gap-2 text-sm font-medium text-foreground">
                            <PenLine className="h-4 w-4 text-primary" />
                            Dibuje su firma
                          </div>
                          <SignaturePad
                            title={`Firma: ${doc.label}`}
                            description="Use el dedo o el ratón. Se incorporará al PDF."
                            filename={`firma-${doc.tipo}.png`}
                            isSaving={firmarMutation.isPending}
                            onSave={(file) => firmarMutation.mutate({ tipo: doc.tipo, file })}
                          />
                        </div>
                      )}

                      {firmarMutation.isError && (
                        <p className="text-sm text-destructive" role="alert">
                          {firmarMutation.error instanceof Error
                            ? firmarMutation.error.message
                            : 'No se pudo registrar la firma.'}
                        </p>
                      )}
                    </>
                  )}

                  {doc.firmado && doc.firmadoPdfUrl && (
                    <DocumentoPdfPreview
                      label={doc.label}
                      previewUrl={doc.firmadoPdfUrl}
                      fullyRead
                      requireFullRead={false}
                      ctaPrincipal
                      ctaLabel="Ver documento firmado"
                    />
                  )}
                </div>
              )}
            </li>
          );
        })}
      </ol>

      {!firmasDesbloqueadas && (
        <p className="flex items-center justify-center gap-2 text-center text-xs text-muted-foreground">
          <FileText className="h-3.5 w-3.5" />
          Complete la verificación SMS para poder firmar.
        </p>
      )}
    </div>
  );
}
