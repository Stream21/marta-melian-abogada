import { useQuery } from '@tanstack/react-query';
import { api } from '@/api/client';
import { Badge } from '@/components/ui/badge';
import { useMercureContratacion } from '@/hooks/useMercureContratacion';

interface TramitacionEnConstruccionPanelProps {
  expedienteId: string;
  numero: string;
}

export function TramitacionEnConstruccionPanel({
  expedienteId,
  numero,
}: TramitacionEnConstruccionPanelProps) {
  useMercureContratacion(expedienteId);

  const { data } = useQuery({
    queryKey: ['expediente', expedienteId],
    queryFn: () => api.getExpediente(expedienteId),
  });

  return (
    <div className="panel p-8 text-center space-y-4">
      <Badge variant="info">Fase 3 — Tramitación</Badge>
      <h2 className="text-xl font-semibold">Tramitación en construcción</h2>
      <p className="text-muted-foreground max-w-lg mx-auto">
        El expediente <strong>{numero}</strong> ha pasado a la fase de tramitación.
        La gestión con la plataforma judicial estará disponible próximamente.
      </p>
      {data?.estadoFase && (
        <p className="text-sm text-muted-foreground">
          Estado actual: {data.estadoFase.replace(/_/g, ' ')}
        </p>
      )}
    </div>
  );
}
