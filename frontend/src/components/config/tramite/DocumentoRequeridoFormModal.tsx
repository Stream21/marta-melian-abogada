import { useEffect, useState, type FormEvent } from 'react';
import { Image, Images } from 'lucide-react';
import type { DocumentoRequerido, TipoDocumentoRequerido } from '@/api/client';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

export interface DocumentoRequeridoFormValues {
  nombre: string;
  descripcion: string;
  obligatorio: boolean;
  tipo: TipoDocumentoRequerido;
  maxImagenes: number;
}

interface DocumentoRequeridoFormModalProps {
  open: boolean;
  mode: 'create' | 'edit';
  initial?: DocumentoRequerido | null;
  isPending?: boolean;
  onOpenChange: (open: boolean) => void;
  onSubmit: (values: DocumentoRequeridoFormValues) => void;
}

const MAX_IMAGENES_CONJUNTO = 50;
const DEFAULT_MAX_CONJUNTO = 5;

const emptyValues: DocumentoRequeridoFormValues = {
  nombre: '',
  descripcion: '',
  obligatorio: true,
  tipo: 'individual',
  maxImagenes: 1,
};

const tipoOptions: Array<{
  value: TipoDocumentoRequerido;
  title: string;
  description: string;
  icon: typeof Image;
}> = [
  {
    value: 'individual',
    title: 'Documento individual',
    description: 'El cliente sube una sola imagen o archivo (p. ej. DNI, contrato).',
    icon: Image,
  },
  {
    value: 'conjunto',
    title: 'Conjunto de archivos',
    description: 'Varios archivos dentro del mismo requisito (p. ej. páginas de nóminas).',
    icon: Images,
  },
];

export function DocumentoRequeridoFormModal({
  open,
  mode,
  initial,
  isPending,
  onOpenChange,
  onSubmit,
}: DocumentoRequeridoFormModalProps) {
  const [values, setValues] = useState<DocumentoRequeridoFormValues>(emptyValues);
  const [nombreError, setNombreError] = useState(false);
  const [maxImagenesError, setMaxImagenesError] = useState<string | null>(null);

  useEffect(() => {
    if (!open) return;
    if (mode === 'edit' && initial) {
      setValues({
        nombre: initial.nombre,
        descripcion: initial.descripcion,
        obligatorio: initial.obligatorio,
        tipo: initial.tipo ?? 'individual',
        maxImagenes: initial.maxImagenes ?? 1,
      });
    } else {
      setValues(emptyValues);
    }
    setNombreError(false);
    setMaxImagenesError(null);
  }, [open, mode, initial]);

  const handleTipoChange = (tipo: TipoDocumentoRequerido) => {
    setValues((v) => ({
      ...v,
      tipo,
      maxImagenes:
        tipo === 'individual'
          ? 1
          : v.tipo === 'conjunto' && v.maxImagenes >= 2
            ? v.maxImagenes
            : DEFAULT_MAX_CONJUNTO,
    }));
    setMaxImagenesError(null);
  };

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    if (!values.nombre.trim()) {
      setNombreError(true);
      return;
    }

    if (values.tipo === 'conjunto') {
      if (values.maxImagenes < 2) {
        setMaxImagenesError('Indique al menos 2 archivos.');
        return;
      }
      if (values.maxImagenes > MAX_IMAGENES_CONJUNTO) {
        setMaxImagenesError(`El máximo permitido es ${MAX_IMAGENES_CONJUNTO} archivos.`);
        return;
      }
    }

    onSubmit({
      nombre: values.nombre.trim(),
      descripcion: values.descripcion.trim(),
      obligatorio: values.obligatorio,
      tipo: values.tipo,
      maxImagenes: values.tipo === 'individual' ? 1 : values.maxImagenes,
    });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-lg">
        <form onSubmit={handleSubmit}>
          <DialogHeader>
            <DialogTitle>
              {mode === 'create' ? 'Añadir documento' : 'Editar documento'}
            </DialogTitle>
            <DialogDescription>
              Defina qué debe aportar el cliente. Los archivos se convertirán automáticamente a
              PDF para las plataformas.
            </DialogDescription>
          </DialogHeader>

          <div className="grid gap-4 py-4">
            <div className="space-y-2">
              <Label htmlFor="doc-nombre">
                Nombre <span className="text-destructive">*</span>
              </Label>
              <Input
                id="doc-nombre"
                value={values.nombre}
                onChange={(e) => {
                  setValues((v) => ({ ...v, nombre: e.target.value }));
                  if (nombreError && e.target.value.trim()) setNombreError(false);
                }}
                placeholder="Ej. Nóminas de los últimos 6 meses"
                disabled={isPending}
                aria-invalid={nombreError}
              />
              {nombreError && (
                <p className="text-xs text-destructive">Indique un nombre para el documento.</p>
              )}
            </div>

            <div className="space-y-2">
              <Label htmlFor="doc-descripcion">Descripción</Label>
              <textarea
                id="doc-descripcion"
                value={values.descripcion}
                onChange={(e) => setValues((v) => ({ ...v, descripcion: e.target.value }))}
                placeholder="Indicaciones para el cliente (opcional)"
                disabled={isPending}
                rows={3}
                className="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
              />
            </div>

            <fieldset className="space-y-2">
              <legend className="text-sm font-medium text-foreground">Tipo de aportación</legend>
              <div className="grid gap-2 sm:grid-cols-2">
                {tipoOptions.map(({ value, title, description, icon: Icon }) => {
                  const selected = values.tipo === value;
                  return (
                    <button
                      key={value}
                      type="button"
                      disabled={isPending}
                      onClick={() => handleTipoChange(value)}
                      className={cn(
                        'flex flex-col items-start gap-2 rounded-lg border px-4 py-3 text-left transition-colors',
                        selected
                          ? 'border-primary bg-primary/5 ring-1 ring-primary/30'
                          : 'border-border bg-muted/20 hover:bg-muted/40',
                      )}
                    >
                      <span className="flex items-center gap-2 text-sm font-medium text-foreground">
                        <Icon className="h-4 w-4 text-primary" />
                        {title}
                      </span>
                      <span className="text-xs leading-snug text-muted-foreground">{description}</span>
                    </button>
                  );
                })}
              </div>
            </fieldset>

            {values.tipo === 'conjunto' && (
              <div className="space-y-2">
                <Label htmlFor="doc-max-imagenes">
                  Máximo de archivos que puede anexar el cliente
                </Label>
                <Input
                  id="doc-max-imagenes"
                  type="number"
                  min={2}
                  max={MAX_IMAGENES_CONJUNTO}
                  value={values.maxImagenes}
                  onChange={(e) => {
                    const parsed = Number.parseInt(e.target.value, 10);
                    setValues((v) => ({
                      ...v,
                      maxImagenes: Number.isNaN(parsed) ? 2 : parsed,
                    }));
                    setMaxImagenesError(null);
                  }}
                  disabled={isPending}
                  aria-invalid={maxImagenesError !== null}
                />
                <p className="text-xs text-muted-foreground">
                  El cliente podrá subir entre 1 y {values.maxImagenes} archivos en este requisito.
                  Todos se convertirán y unirán en un PDF.
                </p>
                {maxImagenesError && (
                  <p className="text-xs text-destructive">{maxImagenesError}</p>
                )}
              </div>
            )}

            <label className="flex cursor-pointer items-start gap-3 rounded-lg border border-border bg-muted/20 px-4 py-3">
              <input
                type="checkbox"
                checked={values.obligatorio}
                onChange={(e) => setValues((v) => ({ ...v, obligatorio: e.target.checked }))}
                disabled={isPending}
                className="mt-0.5 h-4 w-4 rounded border-input text-primary focus-visible:ring-1 focus-visible:ring-ring"
              />
              <span className="text-sm leading-snug">
                <span className="font-medium text-foreground">Documento obligatorio</span>
                <span className="mt-0.5 block text-muted-foreground">
                  El cliente deberá aportarlo para continuar el trámite.
                </span>
              </span>
            </label>
          </div>

          <DialogFooter>
            <Button
              type="button"
              variant="outline"
              onClick={() => onOpenChange(false)}
              disabled={isPending}
            >
              Cancelar
            </Button>
            <Button type="submit" disabled={isPending}>
              {mode === 'create' ? 'Añadir' : 'Guardar cambios'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
