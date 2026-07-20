import { documentoUploadLimiteLabel } from '@/lib/documento-upload-limite';
import { Badge } from '@/components/ui/badge';

interface DocumentoLimiteBadgeProps {
  tipo: string;
  maxImagenes: number;
  className?: string;
}

export function DocumentoLimiteBadge({ tipo, maxImagenes, className }: DocumentoLimiteBadgeProps) {
  return (
    <Badge variant="outline" className={className}>
      {documentoUploadLimiteLabel(tipo, maxImagenes)}
    </Badge>
  );
}
