import { useEffect, useState } from 'react';
import { useMutation } from '@tanstack/react-query';
import { Loader2, ShieldCheck, Smartphone } from 'lucide-react';
import { api } from '@/api/client';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface FirmaOtpPanelProps {
  token: string;
  requiereOtp: boolean;
  otpVerificado: boolean;
  telefonoMascara?: string | null;
  onVerificado: () => void;
}

export function FirmaOtpPanel({
  token,
  requiereOtp,
  otpVerificado: otpVerificadoInicial,
  telefonoMascara,
  onVerificado,
}: FirmaOtpPanelProps) {
  const [otpVerificado, setOtpVerificado] = useState(otpVerificadoInicial);
  const [codigo, setCodigo] = useState('');
  const [telefonoDestino, setTelefonoDestino] = useState(telefonoMascara ?? '');
  const [codigoEnviado, setCodigoEnviado] = useState(false);

  useEffect(() => {
    setOtpVerificado(otpVerificadoInicial);
  }, [otpVerificadoInicial]);

  const enviarMutation = useMutation({
    mutationFn: () => api.enviarOtpFirma(token),
    onSuccess: (data) => {
      setCodigoEnviado(true);
      if (data.telefonoMascara) setTelefonoDestino(data.telefonoMascara);
      if (data.otpVerificado) {
        setOtpVerificado(true);
        onVerificado();
      }
    },
  });

  const verificarMutation = useMutation({
    mutationFn: () => api.verificarOtpFirma(token, codigo),
    onSuccess: () => {
      setOtpVerificado(true);
      setCodigo('');
      onVerificado();
    },
  });

  if (!requiereOtp) {
    return null;
  }

  if (otpVerificado) {
    return (
      <div className="rounded-lg border border-emerald-200 bg-emerald-50/60 p-4 text-sm text-emerald-900">
        <div className="flex items-center gap-2 font-medium">
          <ShieldCheck className="h-4 w-4" />
          Identidad verificada por SMS
        </div>
        <p className="mt-1 text-emerald-800/90">
          Puede firmar los documentos. La verificación es válida durante esta sesión de firmas.
        </p>
      </div>
    );
  }

  return (
    <div className="rounded-lg border border-amber-200 bg-amber-50/50 p-4 space-y-4">
      <div className="flex items-start gap-3">
        <Smartphone className="mt-0.5 h-5 w-5 shrink-0 text-amber-800" />
        <div className="space-y-1 text-sm">
          <p className="font-medium text-amber-950">Verificación por SMS</p>
          <p className="text-amber-900/90">
            Antes de firmar, le enviaremos un código de 6 dígitos al móvil registrado
            {telefonoDestino ? ` (${telefonoDestino})` : ''}. Puede introducirlo en el ordenador o en el móvil.
          </p>
        </div>
      </div>

      {!codigoEnviado ? (
        <Button
          type="button"
          onClick={() => enviarMutation.mutate()}
          disabled={enviarMutation.isPending}
          className="w-full sm:w-auto"
        >
          {enviarMutation.isPending ? (
            <>
              <Loader2 className="mr-2 h-4 w-4 animate-spin" />
              Enviando código…
            </>
          ) : (
            'Enviar código SMS'
          )}
        </Button>
      ) : (
        <div className="space-y-3 max-w-xs">
          <div className="space-y-1.5">
            <Label htmlFor="otp-codigo">Código de 6 dígitos</Label>
            <Input
              id="otp-codigo"
              inputMode="numeric"
              maxLength={6}
              value={codigo}
              onChange={(e) => setCodigo(e.target.value.replace(/\D/g, '').slice(0, 6))}
              placeholder="000000"
              className="font-mono tracking-widest"
            />
          </div>
          <div className="flex flex-wrap gap-2">
            <Button
              type="button"
              onClick={() => verificarMutation.mutate()}
              disabled={codigo.length !== 6 || verificarMutation.isPending}
            >
              {verificarMutation.isPending ? 'Verificando…' : 'Verificar código'}
            </Button>
            <Button
              type="button"
              variant="outline"
              onClick={() => enviarMutation.mutate()}
              disabled={enviarMutation.isPending}
            >
              Reenviar
            </Button>
          </div>
        </div>
      )}

      {(enviarMutation.error || verificarMutation.error) && (
        <p className="text-sm text-destructive" role="alert">
          {(enviarMutation.error ?? verificarMutation.error) instanceof Error
            ? (enviarMutation.error ?? verificarMutation.error)?.message
            : 'Error en la verificación SMS.'}
        </p>
      )}
    </div>
  );
}
