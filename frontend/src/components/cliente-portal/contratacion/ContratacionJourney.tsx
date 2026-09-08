import { useEffect, useMemo, useRef, useState } from 'react';
import type { AccesoExpedienteResponse, AccesoPasoResponse } from '@/api/client';
import { ClienteIdentidadOnboarding } from '@/components/documento-identidad/ClienteIdentidadOnboarding';
import { useWelcomeBriefing } from '@/hooks/useStepBriefing';
import { ContratacionBriefingDialog } from './ContratacionBriefingDialog';
import { ContratacionFocusChrome } from './ContratacionFocusChrome';
import { ContratacionFocusContent, ContratacionFocusFill } from './ContratacionFocusLayout';
import { ContratacionHub } from './ContratacionHub';
import {
  ContratacionResumenFinal,
} from './ContratacionResumenFinal';
import { ContratacionStepBriefing } from './ContratacionStepBriefing';
import { buildContratacionJourneyPlan } from './contratacion-journey-plan';
import { DocumentoEntregaFocus } from './DocumentoEntregaFocus';
import { FirmaDocumentoFocus } from './FirmaDocumentoFocus';
import { briefingForView } from './journey-step-briefings';
import { PagoContratacionPanel } from './PagoContratacionPanel';
import type { ContratacionView } from './types';
import { useContratacionJourney } from './useContratacionJourney';

interface ContratacionJourneyProps {
  token: string;
  data: AccesoExpedienteResponse;
  pasoActivo: AccesoPasoResponse | null;
  esperandoAbogado: boolean;
  onCompletar: (paso: string) => void;
  onIdentidadCompletada: () => void;
  onIniciarPago: () => void;
  completando: boolean;
  iniciandoPago: boolean;
  onFocusChange?: (isFocus: boolean) => void;
}

function viewStepId(view: ContratacionView): string {
  if (view.type === 'firma') return `firma:${view.tipo}`;
  if (view.type === 'doc') return `doc:${view.docId}`;
  return view.type;
}

function pasoNecesitaBriefing(view: ContratacionView): boolean {
  return (
    view.type === 'doc' ||
    view.type === 'otp' ||
    view.type === 'firma' ||
    view.type === 'pago' ||
    view.type === 'resumen_final'
  );
}

export function ContratacionJourney({
  token,
  data,
  pasoActivo,
  esperandoAbogado,
  onCompletar,
  onIdentidadCompletada,
  onIniciarPago,
  completando,
  iniciandoPago,
  onFocusChange,
}: ContratacionJourneyProps) {
  const {
    view,
    isFocus,
    chromeIndex,
    chromeTotal,
    title,
    puedeConfirmar,
    irAlSiguiente,
  } = useContratacionJourney({ data, pasoActivo, esperandoAbogado });

  const journeyPlan = useMemo(() => buildContratacionJourneyPlan(), []);
  const stepBriefing = briefingForView(view, data);
  const stepId = viewStepId(view);

  const showWelcome = !esperandoAbogado && view.type !== 'waiting' && view.type !== 'resumen_final';
  const { open: welcomeOpen, dismiss: dismissWelcome } = useWelcomeBriefing(token, showWelcome);

  /**
   * Briefing en pantalla (no Dialog + sessionStorage): al cambiar de documento/paso
   * siempre se muestra una vez hasta pulsar continuar.
   */
  const [briefingHechoEn, setBriefingHechoEn] = useState<string | null>(null);
  const [abrirPdfTrasBriefing, setAbrirPdfTrasBriefing] = useState(false);

  useEffect(() => {
    setBriefingHechoEn(null);
    setAbrirPdfTrasBriefing(false);
  }, [stepId]);

  useEffect(() => {
    onFocusChange?.(isFocus);
  }, [isFocus, onFocusChange]);

  const mostrarBriefingPaso =
    !welcomeOpen &&
    pasoNecesitaBriefing(view) &&
    !!stepBriefing &&
    briefingHechoEn !== stepId;

  const mostrarContenido = !welcomeOpen && !mostrarBriefingPaso;

  const onStepBriefingContinue = () => {
    setBriefingHechoEn(stepId);
    if (view.type === 'firma') {
      setAbrirPdfTrasBriefing(true);
    }
  };

  /** Tras la última firma: completar el paso sin pedir otro «Continuar» en el hub. */
  const firmasEnviadasRef = useRef(false);

  const onFirmaDocumentoDone = () => {
    if (view.type !== 'firma') {
      irAlSiguiente();
      return;
    }
    const docs = data.documentosFirma ?? [];
    const quedaOtroPendiente = docs.some((d) => !d.firmado && d.tipo !== view.tipo);
    if (!quedaOtroPendiente) {
      if (firmasEnviadasRef.current || completando) return;
      firmasEnviadasRef.current = true;
      onCompletar('firmas');
      return;
    }
    irAlSiguiente(`firma:${view.tipo}`);
  };

  /** Por si se llega al hub de firmas: enviar solo, sin botón extra. */
  useEffect(() => {
    if (view.type !== 'hub') return;
    if (pasoActivo?.paso !== 'firmas') return;
    if (!puedeConfirmar || completando || firmasEnviadasRef.current) return;
    firmasEnviadasRef.current = true;
    onCompletar('firmas');
  }, [view.type, pasoActivo?.paso, puedeConfirmar, completando, onCompletar]);

  useEffect(() => {
    if (pasoActivo?.paso !== 'firmas') {
      firmasEnviadasRef.current = false;
    }
  }, [pasoActivo?.paso]);

  if (view.type === 'waiting') {
    return (
      <ContratacionFocusContent>
        <ContratacionResumenFinal data={data} esperandoAbogado />
      </ContratacionFocusContent>
    );
  }

  return (
    <>
      <ContratacionBriefingDialog
        open={welcomeOpen}
        title="Contratación del servicio"
        description="Vas a completar los datos para contratar con el despacho. La contratación tiene 3 subfases:"
        planSteps={journeyPlan.map((step, index) => ({ ...step, active: index === 0 }))}
        ctaLabel="Empezar"
        onContinue={dismissWelcome}
      />

      {mostrarBriefingPaso && stepBriefing && (
        <ContratacionFocusContent showScrollHint={false}>
          {(view.type === 'firma' || view.type === 'pago') && (
            <ContratacionFocusChrome
              index={chromeIndex >= 0 ? chromeIndex : 0}
              total={chromeTotal}
              title={title}
            />
          )}
          <ContratacionStepBriefing
            title={stepBriefing.title}
            description={stepBriefing.description}
            ctaLabel={stepBriefing.ctaLabel}
            onContinue={onStepBriefingContinue}
          />
        </ContratacionFocusContent>
      )}

      {mostrarContenido && view.type === 'resumen_final' && (
        <ContratacionFocusContent>
          <ContratacionResumenFinal data={data} />
        </ContratacionFocusContent>
      )}

      {mostrarContenido && view.type === 'hub' && pasoActivo && (
        <ContratacionFocusContent>
          {pasoActivo.paso === 'firmas' ? (
            <div className="flex flex-col items-center py-10 text-center">
              <p className="text-sm text-muted-foreground">
                {completando ? 'Enviando las firmas al despacho…' : 'Preparando el resumen…'}
              </p>
            </div>
          ) : (
            <ContratacionHub
              pasoLabel={pasoActivo.label}
              notaDevolucion={pasoActivo.notaDevolucion}
              puedeConfirmar={puedeConfirmar}
              completando={completando}
            />
          )}
        </ContratacionFocusContent>
      )}

      {mostrarContenido && view.type === 'identidad' && (
        <ContratacionFocusFill>
          <ClienteIdentidadOnboarding
            token={token}
            tipoServicio={data.tipoServicio}
            identidadEdicion={data.identidadEdicion}
            datosClienteEditables={data.datosClienteEditables}
            notaDevolucion={pasoActivo?.notaDevolucion}
            onCompletado={() => {
              onIdentidadCompletada();
              irAlSiguiente('identidad');
            }}
          />
        </ContratacionFocusFill>
      )}

      {mostrarContenido && view.type === 'doc' && (() => {
        const documento = (data.documentosRequeridos ?? []).find((d) => d.id === view.docId);
        if (!documento) {
          return <p className="pt-3 text-sm text-muted-foreground">Documento no disponible.</p>;
        }
        return (
          <ContratacionFocusContent>
            <DocumentoEntregaFocus
              token={token}
              documento={documento}
              onDone={() => irAlSiguiente(`doc:${view.docId}`)}
            />
          </ContratacionFocusContent>
        );
      })()}

      {mostrarContenido && view.type === 'otp' && (
        <ContratacionFocusContent>
          <FirmaDocumentoFocus
            token={token}
            documentos={data.documentosFirma ?? []}
            firmasConfig={data.firmas}
            mode="otp"
            notaDevolucion={pasoActivo?.notaDevolucion}
            motivosDevolucion={pasoActivo?.motivosDevolucion}
            onOtpDone={() => irAlSiguiente('otp')}
            onFirmaDone={() => irAlSiguiente('otp')}
          />
        </ContratacionFocusContent>
      )}

      {mostrarContenido && view.type === 'firma' && (
        <ContratacionFocusContent showScrollHint scrollHintLabel="Abajo puedes aceptar y firmar">
          <ContratacionFocusChrome
            index={chromeIndex >= 0 ? chromeIndex : 0}
            total={chromeTotal}
            title={title}
          />
          <FirmaDocumentoFocus
            token={token}
            documentos={data.documentosFirma ?? []}
            firmasConfig={data.firmas}
            tipoActivo={view.tipo}
            mode="firma"
            notaDevolucion={pasoActivo?.notaDevolucion}
            motivosDevolucion={pasoActivo?.motivosDevolucion}
            autoOpenDocumento={abrirPdfTrasBriefing}
            onOtpDone={() => irAlSiguiente(`firma:${view.tipo}`)}
            onFirmaDone={onFirmaDocumentoDone}
          />
        </ContratacionFocusContent>
      )}

      {mostrarContenido && view.type === 'pago' && data.resumenPago && (
        <ContratacionFocusContent>
          <ContratacionFocusChrome
            index={chromeIndex >= 0 ? chromeIndex : 0}
            total={chromeTotal}
            title={title}
          />
          <PagoContratacionPanel
            resumen={data.resumenPago}
            onIniciarPago={onIniciarPago}
            iniciando={iniciandoPago}
          />
        </ContratacionFocusContent>
      )}

      {mostrarContenido &&
        view.type !== 'resumen_final' &&
        view.type !== 'hub' &&
        view.type !== 'identidad' &&
        view.type !== 'doc' &&
        view.type !== 'otp' &&
        view.type !== 'firma' &&
        view.type !== 'pago' && (
          <ContratacionFocusContent>
            <ContratacionResumenFinal data={data} />
          </ContratacionFocusContent>
        )}
    </>
  );
}
