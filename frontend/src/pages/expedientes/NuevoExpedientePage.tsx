import { useState } from 'react';
import { Link, useNavigate } from '@tanstack/react-router';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { ArrowLeft, ArrowRight } from 'lucide-react';
import { api } from '@/api/client';
import { Button } from '@/components/ui/button';
import { ExpedienteAltaStepper } from '@/components/expedientes/alta/ExpedienteAltaStepper';
import { PasoClientePanel } from '@/components/expedientes/alta/PasoClientePanel';
import { PasoFinalizarPanel } from '@/components/expedientes/alta/PasoFinalizarPanel';
import { PasoPagoPanel } from '@/components/expedientes/alta/PasoPagoPanel';
import { PasoResumenPanel } from '@/components/expedientes/alta/PasoResumenPanel';
import { PasoTramitePanel } from '@/components/expedientes/alta/PasoTramitePanel';
import {
  canalesNotificacionPorDefecto,
  initialAltaState,
  type ExpedienteAltaState,
} from '@/components/expedientes/alta/types';
import { isValidEmail, isValidTelefono } from '@/lib/validators';

export function NuevoExpedientePage() {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [state, setState] = useState<ExpedienteAltaState>(initialAltaState);
  const [error, setError] = useState<string | null>(null);

  const patch = (p: Partial<ExpedienteAltaState>) => setState((s) => ({ ...s, ...p }));

  const altaMutation = useMutation({
    mutationFn: () =>
      api.altaExpediente({
        clienteId: state.modoCliente === 'existente' ? state.clienteId : null,
        telefono: state.modoCliente === 'nuevo' ? state.telefono : null,
        email: state.modoCliente === 'nuevo' ? state.email || null : null,
        tramiteId: state.tramiteId,
        honorariosAcordados: state.honorarios,
        metodoPago: state.metodoPago,
        planPago: state.planPago,
        numCuotas: state.planPago === 'unico' ? 1 : state.numCuotas,
        notificar: true,
        canalesNotificacion: [
          ...(state.canalesNotificacion.whatsapp && state.telefono.trim() ? ['whatsapp' as const] : []),
          ...(state.canalesNotificacion.email && state.email.trim() ? ['email' as const] : []),
        ],
      }),
    onSuccess: (result) => {
      void queryClient.invalidateQueries({ queryKey: ['expedientes'] });
      void navigate({ to: '/expedientes/$expedienteId', params: { expedienteId: result.expediente.id } });
    },
    onError: (err: Error) => setError(err.message),
  });

  const canContinue = (): boolean => {
    switch (state.step) {
      case 1:
        if (state.modoCliente === 'nuevo') {
          if (!state.telefono.trim() || !isValidTelefono(state.telefono)) return false;
          if (state.telefonoDuplicado) return false;
          if (state.email.trim() && !isValidEmail(state.email)) return false;
          return true;
        }
        return !!state.clienteId;
      case 2:
        return !!state.tramiteId && state.honorarios > 0;
      case 3:
        return true;
      case 4:
        return true;
      case 5: {
        const tieneTelefono = !!state.telefono.trim();
        const tieneEmail = !!state.email.trim();
        const n =
          (state.canalesNotificacion.whatsapp && tieneTelefono ? 1 : 0) +
          (state.canalesNotificacion.email && tieneEmail ? 1 : 0);
        return n >= 1;
      }
      default:
        return false;
    }
  };

  const handleNext = () => {
    setError(null);
    if (state.step < 5) {
      const nextStep = state.step + 1;
      if (nextStep === 5) {
        patch({
          step: nextStep,
          canalesNotificacion: canalesNotificacionPorDefecto(state.telefono, state.email),
        });
      } else {
        patch({ step: nextStep });
      }
    }
  };

  const handleBack = () => {
    setError(null);
    if (state.step > 1) patch({ step: state.step - 1 });
  };

  const handleFinalizar = () => {
    setError(null);
    altaMutation.mutate();
  };

  const renderStep = () => {
    switch (state.step) {
      case 1:
        return <PasoClientePanel state={state} onChange={patch} />;
      case 2:
        return <PasoTramitePanel state={state} onChange={patch} />;
      case 3:
        return <PasoPagoPanel state={state} onChange={patch} />;
      case 4:
        return <PasoResumenPanel state={state} />;
      case 5:
        return <PasoFinalizarPanel state={state} onChange={patch} />;
      default:
        return null;
    }
  };

  return (
    <div className="p-6 max-w-4xl">
      <div className="mb-6">
        <p className="section-label">
          <Link to="/dashboard" className="hover:text-primary">
            Dashboard
          </Link>
          {' / '}
          <Link to="/expedientes" className="hover:text-primary">
            Expedientes
          </Link>
          {' / Nuevo'}
        </p>
        <h1 className="mt-1 page-title">Alta de Expediente</h1>
        <p className="page-subtitle">Proceso de apertura y contratación con el cliente</p>
      </div>

      <ExpedienteAltaStepper currentStep={state.step} />

      <div>
        {renderStep()}

        {error && (
          <p className="mt-4 text-sm text-destructive" role="alert">
            {error}
          </p>
        )}

        <div className="mt-6 flex justify-between">
          <Button variant="outline" onClick={handleBack} disabled={state.step === 1}>
            <ArrowLeft className="mr-2 h-4 w-4" />
            Anterior
          </Button>

          {state.step < 5 ? (
            <Button onClick={handleNext} disabled={!canContinue()}>
              Continuar
              <ArrowRight className="ml-2 h-4 w-4" />
            </Button>
          ) : (
            <Button onClick={handleFinalizar} disabled={!canContinue() || altaMutation.isPending}>
              {altaMutation.isPending ? 'Finalizando…' : 'Finalizar y notificar al cliente'}
            </Button>
          )}
        </div>
      </div>
    </div>
  );
}
