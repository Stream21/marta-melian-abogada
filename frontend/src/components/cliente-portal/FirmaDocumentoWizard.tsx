import { useCallback, useMemo, useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { CheckCircle2, ChevronRight, FileText, ShieldCheck } from 'lucide-react';
import { api, type AccesoDocumentoFirmaResponse, type AccesoFirmasConfigResponse } from '@/api/client';
import { DocumentoPdfPreview } from '@/components/cliente-portal/DocumentoPdfPreview';
import { FirmaOtpPanel } from '@/components/cliente-portal/FirmaOtpPanel';
import { SignaturePad } from '@/components/signature/SignaturePad';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

interface FirmaDocumentoWizardProps {
  token: string;
  documentos: AccesoDocumentoFirmaResponse[];
  firmasConfig?: AccesoFirmasConfigResponse;
}

export function FirmaDocumentoWizard({ token, documentos, firmasConfig }: FirmaDocumentoWizardProps) {
  const queryClient = useQueryClient();
  const [docActivo, setDocActivo] = useState<string | null>(null);
  const [documentosLeidos, setDocumentosLeidos] = useState<Record<string, boolean>>({});
  const [aceptaContenido, setAceptaContenido] = useState<Record<string, boolean>>({});
  const [otpVerificado, setOtpVerificado] = useState(firmasConfig?.otpVerificado ?? false);

  const requiereOtp = firmasConfig?.requiereOtp ?? true;
  const firmados = useMemo(() => documentos.filter((d) => d.firmado).length, [documentos]);
  const total = documentos.length;

  const firmarMutation = useMutation({
    mutationFn: ({ tipo, file }: { tipo: string; file: File }) => api.registrarFirmaDocumento(token, tipo, file),
    onSuccess: () => {
      setDocActivo(null);
      void queryClient.invalidateQueries({ queryKey: ['acceso', token] });
    },
  });

  const marcarLeido = useCallback((tipo: string) => {
    setDocumentosLeidos((prev) => (prev[tipo] ? prev : { ...prev, [tipo]: true }));
  }, []);

  const puedeFirmar = (tipo: string) =>
    documentosLeidos[tipo] && aceptaContenido[tipo] && (!requiereOtp || otpVerificado);

  const firmasDesbloqueadas = !requiereOtp || otpVerificado;

  return (
    <div className="space-y-5">
      <div className="flex items-center justify-between gap-2 rounded-lg bg-muted/40 px-3 py-2 text-sm">
        <span className="text-muted-foreground">Documentos firmados</span>
        <span className="font-semibold text-primary">
          {firmados} / {total}
        </span>
      </div>

      <div className="rounded-lg border border-blue-200 bg-blue-50/60 px-4 py-3 text-sm text-blue-950">
        <div className="flex items-start gap-2">
          <ShieldCheck className="mt-0.5 h-4 w-4 shrink-0 text-blue-700" />
          <p>
            Lea cada documento, verifique su identidad por SMS si aplica, y firme con el dedo o el ratón.
          </p>
        </div>
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

      {requiereOtp && !otpVerificado && (
        <p className="text-center text-xs text-muted-foreground">
          Verifique su móvil por SMS para desbloquear la firma.
        </p>
      )}

      <ol className="space-y-3">
        {documentos.map((doc, index) => (
          <li
            key={doc.tipo}
            className={cn(
              'rounded-xl border p-4',
              doc.firmado ? 'border-emerald-200 bg-emerald-50/40' : 'border-border bg-card',
              !firmasDesbloqueadas && !doc.firmado && 'opacity-60',
            )}
          >
            <div className="flex items-start justify-between gap-3">
              <div className="flex min-w-0 items-start gap-3">
                <span
                  className={cn(
                    'flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold',
                    doc.firmado
                      ? 'bg-emerald-500 text-white'
                      : 'bg-muted text-muted-foreground',
                  )}
                >
                  {doc.firmado ? <CheckCircle2 className="h-4 w-4" /> : index + 1}
                </span>
                <div className="min-w-0">
                  <div className="flex items-center gap-2">
                    <FileText className="h-4 w-4 shrink-0 text-muted-foreground" />
                    <span className="font-medium">{doc.label}</span>
                  </div>
                  {!doc.firmado && firmasDesbloqueadas && docActivo !== doc.tipo && (
                    <p className="mt-1 text-xs text-muted-foreground">
                      Pulse para leer y firmar
                    </p>
                  )}
                </div>
              </div>
              {doc.firmado ? (
                <Badge variant="success" className="shrink-0">
                  Firmado
                </Badge>
              ) : (
                <Badge variant="secondary" className="shrink-0">
                  Pendiente
                </Badge>
              )}
            </div>

            {docActivo === doc.tipo ? (
              <div className="mt-4 space-y-4 border-t border-border pt-4">
                <DocumentoPdfPreview
                  label={doc.label}
                  previewUrl={doc.previewUrl}
                  requireFullRead
                  fullyRead={documentosLeidos[doc.tipo]}
                  onFullyRead={() => marcarLeido(doc.tipo)}
                />

                {!documentosLeidos[doc.tipo] && (
                  <p className="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                    Desplácese hasta el final del documento para poder firmarlo.
                  </p>
                )}

                {documentosLeidos[doc.tipo] && (
                  <label className="flex cursor-pointer items-start gap-2 rounded-md border border-border bg-muted/30 px-3 py-2.5 text-sm">
                    <input
                      type="checkbox"
                      className="mt-0.5"
                      checked={!!aceptaContenido[doc.tipo]}
                      onChange={(e) =>
                        setAceptaContenido((prev) => ({ ...prev, [doc.tipo]: e.target.checked }))
                      }
                    />
                    <span>
                      He leído <strong>{doc.label}</strong> y acepto su contenido para firmarlo
                      electrónicamente.
                    </span>
                  </label>
                )}

                {puedeFirmar(doc.tipo) && (
                  <SignaturePad
                    title={`Firma: ${doc.label}`}
                    description="Dibuje su firma. Se guardará en el PDF del expediente."
                    filename={`firma-${doc.tipo}.png`}
                    isSaving={firmarMutation.isPending}
                    onSave={(file) => firmarMutation.mutate({ tipo: doc.tipo, file })}
                  />
                )}

                {firmarMutation.isError && (
                  <p className="text-sm text-destructive" role="alert">
                    {firmarMutation.error instanceof Error
                      ? firmarMutation.error.message
                      : 'No se pudo registrar la firma.'}
                  </p>
                )}

                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => setDocActivo(null)}
                  disabled={firmarMutation.isPending}
                >
                  Cerrar
                </Button>
              </div>
            ) : (
              <div className="mt-3">
                {!doc.firmado && (
                  <Button
                    className="w-full sm:w-auto"
                    size="lg"
                    onClick={() => firmasDesbloqueadas && setDocActivo(doc.tipo)}
                    disabled={!firmasDesbloqueadas}
                  >
                    Revisar y firmar
                    <ChevronRight className="ml-2 h-4 w-4" />
                  </Button>
                )}
                {doc.firmado && doc.firmadoPdfUrl && (
                  <DocumentoPdfPreview
                    label="Ver documento firmado"
                    previewUrl={doc.firmadoPdfUrl}
                    fullyRead
                    requireFullRead={false}
                  />
                )}
              </div>
            )}
          </li>
        ))}
      </ol>
    </div>
  );
}
