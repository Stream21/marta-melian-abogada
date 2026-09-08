import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { CheckCircle2, PenLine } from 'lucide-react';
import {
  api,
  type AccesoDocumentoFirmaResponse,
  type AccesoFirmasConfigResponse,
} from '@/api/client';
import { DocumentoPdfPreview } from '@/components/cliente-portal/DocumentoPdfPreview';
import { FirmaOtpPanel } from '@/components/cliente-portal/FirmaOtpPanel';
import { SignaturePad } from '@/components/signature/SignaturePad';
import { Button } from '@/components/ui/button';
import { extraerTiposFirmaDevolucion } from '@/lib/campos-devolucion';

interface FirmaDocumentoFocusProps {
  token: string;
  documentos: AccesoDocumentoFirmaResponse[];
  firmasConfig?: AccesoFirmasConfigResponse;
  /** When set, show only this document (after OTP). */
  tipoActivo?: string | null;
  mode: 'otp' | 'firma';
  notaDevolucion?: string | null;
  motivosDevolucion?: string[] | null;
  /**
   * Solo true tras cerrar el briefing del paso: evita abrir el PDF encima
   * o en lugar del briefing en la primera entrada.
   */
  autoOpenDocumento?: boolean;
  onOtpDone: () => void;
  onFirmaDone: () => void;
}

export function FirmaDocumentoFocus({
  token,
  documentos,
  firmasConfig,
  tipoActivo,
  mode,
  notaDevolucion,
  motivosDevolucion,
  autoOpenDocumento = false,
  onOtpDone,
  onFirmaDone,
}: FirmaDocumentoFocusProps) {
  const queryClient = useQueryClient();
  const firmaZonaRef = useRef<HTMLDivElement>(null);
  const [documentosLeidos, setDocumentosLeidos] = useState<Record<string, boolean>>({});
  const [aceptaContenido, setAceptaContenido] = useState<Record<string, boolean>>({});
  const [otpVerificado, setOtpVerificado] = useState(firmasConfig?.otpVerificado ?? false);

  useEffect(() => {
    setOtpVerificado(firmasConfig?.otpVerificado ?? false);
  }, [firmasConfig?.otpVerificado]);

  const requiereOtp = firmasConfig?.requiereOtp ?? true;
  const tiposARefirmar = useMemo(
    () => new Set(extraerTiposFirmaDevolucion(motivosDevolucion)),
    [motivosDevolucion],
  );

  const doc = useMemo(
    () => documentos.find((d) => d.tipo === tipoActivo) ?? null,
    [documentos, tipoActivo],
  );

  const firmarMutation = useMutation({
    mutationFn: ({ tipo, file }: { tipo: string; file: File }) =>
      api.registrarFirmaDocumento(token, tipo, file),
    onSuccess: (_data, variables) => {
      setAceptaContenido((prev) => {
        const next = { ...prev };
        delete next[variables.tipo];
        return next;
      });
      void queryClient.invalidateQueries({ queryKey: ['acceso', token] }).then(() => {
        onFirmaDone();
      });
    },
  });

  const marcarLeido = useCallback((tipo: string) => {
    setDocumentosLeidos((prev) => (prev[tipo] ? prev : { ...prev, [tipo]: true }));
  }, []);

  const leido = !!doc && (!!documentosLeidos[doc.tipo] || !!doc.firmado);
  const acepta = !!doc && !!aceptaContenido[doc.tipo];
  const puedeFirmar = !!doc && leido && acepta && (!requiereOtp || otpVerificado);

  useEffect(() => {
    if (!puedeFirmar) return;
    const id = window.setTimeout(() => {
      firmaZonaRef.current?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }, 180);
    return () => window.clearTimeout(id);
  }, [puedeFirmar, doc?.tipo]);

  if (mode === 'otp') {
    return (
      <div className="space-y-4">
        {(notaDevolucion || tiposARefirmar.size > 0) && (
          <div className="space-y-2 rounded-xl border border-amber-300 bg-amber-50/90 px-4 py-3 text-sm text-amber-950">
            <p className="font-semibold">Su abogado solicita correcciones en las firmas</p>
            {notaDevolucion?.trim() && (
              <p className="whitespace-pre-wrap text-amber-900/95">{notaDevolucion}</p>
            )}
          </div>
        )}
        <FirmaOtpPanel
          token={token}
          requiereOtp={requiereOtp}
          otpVerificado={otpVerificado}
          telefonoMascara={firmasConfig?.telefonoMascara}
          onVerificado={() => {
            setOtpVerificado(true);
            void queryClient.invalidateQueries({ queryKey: ['acceso', token] }).then(() => {
              onOtpDone();
            });
          }}
        />
        {otpVerificado && (
          <Button className="min-h-[48px] w-full" size="lg" onClick={onOtpDone}>
            Continuar a las firmas
          </Button>
        )}
      </div>
    );
  }

  if (!doc) {
    return (
      <div className="space-y-3 text-center">
        <p className="text-sm text-muted-foreground">Documento no encontrado.</p>
      </div>
    );
  }

  if (doc.firmado) {
    return <FirmaYaCompletada label={doc.label} firmadoPdfUrl={doc.firmadoPdfUrl} />;
  }

  return (
    <div className="space-y-4">
      {tiposARefirmar.has(doc.tipo) && (
        <p className="rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-950">
          Debe volver a firmar este documento.
        </p>
      )}

      <DocumentoPdfPreview
        key={`${doc.tipo}-${doc.previewUrl}`}
        label={doc.label}
        previewUrl={doc.previewUrl}
        requireFullRead
        fullyRead={leido}
        onFullyRead={() => marcarLeido(doc.tipo)}
        ctaPrincipal
        autoOpen={autoOpenDocumento && !leido}
      />

      {leido && (
        <label className="flex min-h-[48px] cursor-pointer items-start gap-3 rounded-xl border border-border bg-muted/30 px-3.5 py-3 text-sm">
          <input
            type="checkbox"
            className="mt-1 h-4 w-4"
            checked={acepta}
            onChange={(e) =>
              setAceptaContenido((prev) => ({
                ...prev,
                [doc.tipo]: e.target.checked,
              }))
            }
          />
          <span className="leading-snug text-foreground">
            He leído <strong>{doc.label}</strong> y acepto firmarlo electrónicamente.
          </span>
        </label>
      )}

      {puedeFirmar && (
        <div ref={firmaZonaRef} className="space-y-2 rounded-xl border border-border bg-card p-3">
          <div className="flex items-center gap-2 text-sm font-medium text-foreground">
            <PenLine className="h-4 w-4 text-primary" />
            Dibuje su firma
          </div>
          <SignaturePad
            filename={`firma-${doc.tipo}.png`}
            isSaving={firmarMutation.isPending}
            onSave={async (file) => {
              await firmarMutation.mutateAsync({ tipo: doc.tipo, file });
            }}
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
    </div>
  );
}

/** Feedback breve mientras el flujo avanza solo tras registrar la firma. */
function FirmaYaCompletada({
  label,
  firmadoPdfUrl,
}: {
  label: string;
  firmadoPdfUrl?: string | null;
}) {
  return (
    <div className="space-y-4">
      <div className="flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50/60 px-3 py-2.5 text-sm text-emerald-900">
        <CheckCircle2 className="h-4 w-4 shrink-0" />
        Documento firmado correctamente
      </div>
      {firmadoPdfUrl && (
        <DocumentoPdfPreview
          label={label}
          previewUrl={firmadoPdfUrl}
          fullyRead
          requireFullRead={false}
          ctaPrincipal
          ctaLabel="Ver documento firmado"
        />
      )}
      <p className="text-center text-sm text-muted-foreground">Continuando…</p>
    </div>
  );
}
