import { PenLine } from 'lucide-react';
import { SignatureAreaSurface } from '@/components/hoja-encargo/SignatureAreaSurface';
import { VariableChip } from '@/components/hoja-encargo/VariableChip';
import { cn } from '@/lib/utils';

interface SignatureZoneBlockProps {
  variant: 'client' | 'lawyer';
  selloUrl?: string | null;
  logoUrl?: string | null;
  documentPreview?: boolean;
  compact?: boolean;
}

const SLOT_META = {
  lawyer: {
    title: 'Firma de la abogada',
    roleLabel: 'POR LA ABOGADA',
    variableKey: 'NOMBRE_LETRADA',
  },
  client: {
    title: 'Firma del cliente',
    roleLabel: 'POR EL CLIENTE',
    variableKey: 'NOMBRE_CLIENTE',
  },
} as const;

export function SignatureZoneBlock({
  variant,
  selloUrl,
  logoUrl,
  documentPreview = false,
  compact = false,
}: SignatureZoneBlockProps) {
  const meta = SLOT_META[variant];

  return (
    <div className={cn('flex h-full flex-col', compact ? 'gap-2' : 'gap-3')}>
      <p className="section-label text-center">{meta.title}</p>

      <SignatureAreaSurface
        logoUrl={logoUrl}
        className={cn(!documentPreview && 'border-dashed')}
      >
        {variant === 'client' ? (
          documentPreview ? (
            <p className="text-[10px] italic text-muted-foreground">Firma del cliente</p>
          ) : (
            <>
              <PenLine className="mb-2 h-5 w-5 text-primary" aria-hidden />
              <p className="text-[10px] font-medium text-muted-foreground">Firma en el portal del cliente</p>
            </>
          )
        ) : selloUrl ? (
          <img src={selloUrl} alt="" className="max-h-16 max-w-full object-contain" />
        ) : documentPreview ? (
          <p className="text-[10px] italic text-muted-foreground">Firma de la letrada</p>
        ) : (
          <>
            <PenLine className="mb-2 h-5 w-5 text-muted-foreground" aria-hidden />
            <p className="text-[10px] text-muted-foreground">Configuración del despacho</p>
          </>
        )}
      </SignatureAreaSurface>

      <div className="space-y-1 text-center">
        <p className="escrito-signature-caption">{meta.roleLabel}</p>
        <VariableChip variableKey={meta.variableKey} />
      </div>
    </div>
  );
}
