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

const MIN_IMAGENES_CONJUNTO = 1;
const MAX_IMAGENES_CONJUNTO = 100;
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
  const [maxImagenesText, setMaxImagenesText] = useState(String(DEFAULT_MAX_CONJUNTO));
  const [nombreError, setNombreError] = useState(false);
  const [maxImagenesError, setMaxImagenesError] = useState<string | null>(null);

  useEffect(() => {
    if (!open) return;
    if (mode === 'edit' && initial) {
      const tipo = initial.tipo ?? 'individual';
      const maxImagenes = initial.maxImagenes ?? 1;
      setValues({
        nombre: initial.nombre,
        descripcion: initial.descripcion,
        obligatorio: initial.obligatorio,
        tipo,
        maxImagenes,
      });
      setMaxImagenesText(
        tipo === 'conjunto' ? String(maxImagenes) : String(DEFAULT_MAX_CONJUNTO),
      );
    } else {
      setValues(emptyValues);
      setMaxImagenesText(String(DEFAULT_MAX_CONJUNTO));
    }
    setNombreError(false);
    setMaxImagenesError(null);
  }, [open, mode, initial]);

  const handleTipoChange = (tipo: TipoDocumentoRequerido) => {
    const nextMax =
      tipo === 'individual'
        ? 1
        : values.tipo === 'conjunto' && values.maxImagenes >= MIN_IMAGENES_CONJUNTO
          ? values.maxImagenes
          : DEFAULT_MAX_CONJUNTO;
    setValues((v) => ({ ...v, tipo, maxImagenes: nextMax }));
    if (tipo === 'conjunto') {
      setMaxImagenesText(String(nextMax));
    }
    setMaxImagenesError(null);
  };

  const handleMaxImagenesChange = (raw: string) => {
    // Solo dígitos; permite vacío mientras escribe.
    const digits = raw.replace(/\D/g, '').slice(0, 3);
    setMaxImagenesText(digits);
    setMaxImagenesError(null);

    if (digits === '') {
      return;
    }

    const parsed = Number.parseInt(digits, 10);
    if (!Number.isNaN(parsed)) {
      setValues((v) => ({ ...v, maxImagenes: parsed }));
    }
  };

  const parseMaxImagenesConjunto = (): number | null => {
    if (maxImagenesText.trim() === '') {
      return null;
    }
    const parsed = Number.parseInt(maxImagenesText, 10);
    if (Number.isNaN(parsed)) {
      return null;
    }
    return parsed;
  };

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    if (!values.nombre.trim()) {
      setNombreError(true);
      return;
    }

    if (values.tipo === 'conjunto') {
      const maxImagenes = parseMaxImagenesConjunto();
      if (maxImagenes === null) {
        setMaxImagenesError('Indique un número entero de archivos.');
        return;
      }
      if (maxImagenes < MIN_IMAGENES_CONJUNTO || maxImagenes > MAX_IMAGENES_CONJUNTO) {
        setMaxImagenesError(
          `Indique un número entre ${MIN_IMAGENES_CONJUNTO} y ${MAX_IMAGENES_CONJUNTO}.`,
        );
        return;
      }
      onSubmit({
        nombre: values.nombre.trim(),
        descripcion: values.descripcion.trim(),
        obligatorio: values.obligatorio,
        tipo: values.tipo,
        maxImagenes,
      });
      return;
    }

    onSubmit({
      nombre: values.nombre.trim(),
      descripcion: values.descripcion.trim(),
      obligatorio: values.obligatorio,
      tipo: values.tipo,
      maxImagenes: 1,
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
                  type="text"
                  inputMode="numeric"
                  autoComplete="off"
                  pattern="[0-9]*"
                  placeholder={`${MIN_IMAGENES_CONJUNTO}–${MAX_IMAGENES_CONJUNTO}`}
                  value={maxImagenesText}
                  onChange={(e) => handleMaxImagenesChange(e.target.value)}
                  disabled={isPending}
                  aria-invalid={maxImagenesError !== null}
                  className="max-w-[8rem]"
                />
                <p className="text-xs text-muted-foreground">
                  Número entero entre {MIN_IMAGENES_CONJUNTO} y {MAX_IMAGENES_CONJUNTO}. El cliente
                  podrá subir hasta{' '}
                  {maxImagenesText === '' ? '…' : maxImagenesText} archivo(s); todos se convertirán
                  a PDF.
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
