import { useState } from 'react';
import { CheckCircle2, Circle, Eye, FileText, Loader2 } from 'lucide-react';
import { api, type ContratacionFirmaDocumentoResponse } from '@/api/client';
import { ContratacionPdfEmbed } from '@/components/expedientes/contratacion/ContratacionPdfEmbed';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { cn } from '@/lib/utils';

const TOTAL_FIRMAS = 3;

export function contarFirmasCompletadas(firmas: ContratacionFirmaDocumentoResponse[] | undefined): number {
  return (firmas ?? []).filter((f) => f.firmado).length;
}

interface FirmasProgresoResumenProps {
  firmas: ContratacionFirmaDocumentoResponse[] | undefined;
  className?: string;
  compact?: boolean;
}

export function FirmasProgresoResumen({ firmas, className, compact = false }: FirmasProgresoResumenProps) {
  const lista = firmas ?? [];
  const completadas = contarFirmasCompletadas(lista);
  const pct = Math.round((completadas / TOTAL_FIRMAS) * 100);
  const todoCompleto = completadas === TOTAL_FIRMAS;

  return (
    <div
      className={cn(
        'rounded-lg border p-4',
        todoCompleto ? 'border-emerald-200 bg-emerald-50/50' : 'border-border bg-muted/20',
        className,
      )}
    >
      <div className="flex flex-wrap items-center justify-between gap-3 mb-3">
        <div>
          <p className="text-sm font-semibold">
            {completadas} de {TOTAL_FIRMAS} documentos firmados
          </p>
          {!compact && (
            <p className="text-xs text-muted-foreground mt-0.5">
              {todoCompleto
                ? 'El cliente ha firmado toda la documentación legal.'
                : 'El cliente está firmando documentos en su portal.'}
            </p>
          )}
        </div>
        <span className="text-lg font-bold tabular-nums">{pct}%</span>
      </div>
      <div className="h-2 overflow-hidden rounded-full bg-muted mb-3">
        <div
          className={cn('h-full rounded-full transition-all', todoCompleto ? 'bg-emerald-500' : 'bg-primary')}
          style={{ width: `${pct}%` }}
        />
      </div>
      <ul className={cn('space-y-1.5', compact ? 'text-xs' : 'text-sm')}>
        {lista.map((doc) => (
          <li key={doc.tipo} className="flex items-center gap-2">
            {doc.firmado ? (
              <CheckCircle2 className="h-4 w-4 shrink-0 text-emerald-600" />
            ) : (
              <Circle className="h-4 w-4 shrink-0 text-muted-foreground" />
            )}
            <span className={cn(doc.firmado ? 'text-foreground' : 'text-muted-foreground')}>{doc.label}</span>
            {doc.firmado && doc.firmadoAt && !compact && (
              <span className="ml-auto text-[10px] text-muted-foreground">
                {new Date(doc.firmadoAt).toLocaleString('es-ES', { dateStyle: 'short', timeStyle: 'short' })}
              </span>
            )}
          </li>
        ))}
      </ul>
    </div>
  );
}

interface FirmasRevisionPanelProps {
  expedienteId: string;
  firmas: ContratacionFirmaDocumentoResponse[];
}

export function FirmasRevisionPanel({ expedienteId, firmas }: FirmasRevisionPanelProps) {
  const [previewTipo, setPreviewTipo] = useState<string | null>(null);
  const previewDoc = firmas.find((f) => f.tipo === previewTipo);

  return (
    <div className="space-y-4">
      <FirmasProgresoResumen firmas={firmas} />

      <div className="grid gap-3 sm:grid-cols-3">
        {firmas.map((doc) => (
          <div
            key={doc.tipo}
            className={cn(
              'flex flex-col rounded-xl border p-4 transition-colors',
              doc.firmado ? 'border-emerald-200 bg-emerald-50/30' : 'border-border bg-card',
            )}
          >
            <div className="flex items-start gap-2 mb-3">
              <FileText className="h-4 w-4 shrink-0 text-muted-foreground mt-0.5" />
              <div className="min-w-0 flex-1">
                <p className="text-sm font-medium leading-snug">{doc.label}</p>
                {doc.firmado ? (
                  <Badge variant="success" className="mt-2 gap-1 text-[10px]">
                    <CheckCircle2 className="h-3 w-3" />
                    Firmado
                  </Badge>
                ) : (
                  <Badge variant="secondary" className="mt-2 text-[10px]">
                    Pendiente
                  </Badge>
                )}
              </div>
            </div>

            {doc.firmado && doc.integridadOk === false && (
              <p className="mb-2 text-[11px] text-destructive">Integridad del archivo comprometida</p>
            )}

            <Button
              type="button"
              variant="outline"
              size="sm"
              className="mt-auto w-full"
              disabled={!doc.firmado}
              onClick={() => setPreviewTipo(doc.tipo)}
            >
              <Eye className="mr-2 h-4 w-4" />
              Previsualizar
            </Button>
          </div>
        ))}
      </div>

      <Dialog open={previewTipo !== null} onOpenChange={(open) => !open && setPreviewTipo(null)}>
        <DialogContent className="max-w-4xl h-[85vh] flex flex-col">
          <DialogHeader>
            <DialogTitle>{previewDoc?.label ?? 'Documento'}</DialogTitle>
            <DialogDescription>Documento firmado por el cliente. Revise el contenido antes de validar el paso.</DialogDescription>
          </DialogHeader>
          <div className="flex-1 min-h-0">
            {previewTipo && previewDoc?.firmado ? (
              <ContratacionPdfEmbed
                url={api.contratacionFirmaPdfUrl(expedienteId, previewTipo)}
                title={previewDoc.label}
                className="h-full min-h-[480px]"
              />
            ) : (
              <div className="flex h-full items-center justify-center text-muted-foreground">
                <Loader2 className="h-5 w-5 animate-spin" />
              </div>
            )}
          </div>
        </DialogContent>
      </Dialog>
    </div>
  );
}
