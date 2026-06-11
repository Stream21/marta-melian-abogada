import { Badge } from '@/components/ui/badge';

interface EscritoPlantillaStatusProps {
  esDefault: boolean;
  esPlantillaGlobal?: boolean;
  hasUnsavedChanges: boolean;
}

export function EscritoPlantillaStatus({
  esDefault,
  esPlantillaGlobal,
  hasUnsavedChanges,
}: EscritoPlantillaStatusProps) {
  const baseVariant = esDefault ? 'secondary' : esPlantillaGlobal ? 'info' : 'success';
  const baseLabel = esDefault
    ? 'Plantilla por defecto'
    : esPlantillaGlobal
      ? 'Plantilla global'
      : 'Plantilla personalizada';

  return (
    <div className="flex flex-wrap items-center justify-end gap-1.5">
      <Badge variant={baseVariant}>{baseLabel}</Badge>
      {hasUnsavedChanges && <Badge variant="warning">Sin guardar</Badge>}
    </div>
  );
}
