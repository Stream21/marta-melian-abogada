import { Badge } from '@/components/ui/badge';
import type { ClienteHoldedEstado } from '@/api/client';

interface ClienteHoldedBadgeProps {
  estado?: ClienteHoldedEstado;
  label?: string;
}

export function ClienteHoldedBadge({ estado = 'oportunidad', label }: ClienteHoldedBadgeProps) {
  const text = label ?? (
    estado === 'sincronizado'
      ? 'Holded'
      : estado === 'error'
        ? 'Error Holded'
        : 'Oportunidad'
  );

  return (
    <Badge
      variant={
        estado === 'sincronizado' ? 'success' : estado === 'error' ? 'destructive' : 'secondary'
      }
    >
      {text}
    </Badge>
  );
}
