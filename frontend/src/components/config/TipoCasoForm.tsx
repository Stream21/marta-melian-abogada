import { useState, type FormEvent } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate } from '@tanstack/react-router';
import { Save, Tag } from 'lucide-react';
import { api } from '@/api/client';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';

const textareaClass = cn(
  'flex min-h-[120px] w-full rounded-lg border bg-muted/50 px-3 py-2 text-sm',
  'placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring focus-visible:border-primary',
  'disabled:cursor-not-allowed disabled:opacity-50',
);

export type TipoCasoFormMode = 'create' | 'edit';

export interface TipoCasoFormProps {
  mode: TipoCasoFormMode;
  tipoCasoId?: string;
  initialNombre?: string;
  initialDescripcion?: string;
}

export function TipoCasoForm({
  mode,
  tipoCasoId,
  initialNombre = '',
  initialDescripcion = '',
}: TipoCasoFormProps) {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [nombre, setNombre] = useState(initialNombre);
  const [descripcion, setDescripcion] = useState(initialDescripcion);
  const [nombreError, setNombreError] = useState(false);

  const createMutation = useMutation({
    mutationFn: () =>
      api.postTipoCaso({
        nombre: nombre.trim(),
        descripcion: descripcion.trim(),
      }),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['tipos-caso'] });
      navigate({ to: '/config/tipos-caso' } as never);
    },
  });

  const updateMutation = useMutation({
    mutationFn: () => {
      if (!tipoCasoId) throw new Error('Falta el identificador.');
      return api.putTipoCaso(tipoCasoId, {
        nombre: nombre.trim(),
        descripcion: descripcion.trim(),
      });
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['tipos-caso'] });
      void queryClient.invalidateQueries({ queryKey: ['tipo-caso', tipoCasoId] });
      navigate({ to: '/config/tipos-caso' } as never);
    },
  });

  const mutation = mode === 'create' ? createMutation : updateMutation;
  const saveError =
    mutation.error instanceof Error ? mutation.error.message : mutation.isError ? 'No se pudo guardar.' : null;

  const handleCancel = () => {
    navigate({ to: '/config/tipos-caso' } as never);
  };

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    const trimmed = nombre.trim();
    if (!trimmed) {
      setNombreError(true);
      return;
    }
    setNombreError(false);
    mutation.mutate();
  };

  const isPending = mutation.isPending;

  return (
    <form onSubmit={handleSubmit} className="panel">
      <div className="panel-header">
        <div>
          <h2 className="text-lg font-bold text-foreground">Datos del Tipo de Caso</h2>
          <p className="mt-0.5 text-sm text-muted-foreground">
            Complete la información requerida para registrar la nueva categoría.
          </p>
        </div>
      </div>

      <div className="space-y-6 p-6">
        {saveError && (
          <p className="rounded-lg border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive" role="alert">
            {saveError}
          </p>
        )}
        <div className="space-y-2">
          <Label htmlFor="tipo-caso-nombre" className="text-foreground">
            Nombre del Tipo de Caso <span className="text-destructive">*</span>
          </Label>
          <div className="relative">
            <Tag
              className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
              aria-hidden
            />
            <Input
              id="tipo-caso-nombre"
              value={nombre}
              onChange={(e) => {
                setNombre(e.target.value);
                if (nombreError && e.target.value.trim()) setNombreError(false);
              }}
              placeholder="Ej. Derecho Laboral, Propiedad Intelectual..."
              className="pl-10 bg-muted/50 focus-visible:ring-ring focus-visible:border-primary"
              aria-invalid={nombreError}
              aria-describedby={nombreError ? 'tipo-caso-nombre-error' : 'tipo-caso-nombre-hint'}
              disabled={isPending}
            />
          </div>
          {nombreError ? (
            <p id="tipo-caso-nombre-error" className="text-xs text-destructive" role="alert">
              Indique un nombre para el tipo de caso.
            </p>
          ) : (
            <p id="tipo-caso-nombre-hint" className="text-xs text-muted-foreground">
              Nombre visible en los selectores de expedientes.
            </p>
          )}
        </div>

        <div className="space-y-2">
          <Label htmlFor="tipo-caso-descripcion" className="text-foreground">
            Descripción
          </Label>
          <textarea
            id="tipo-caso-descripcion"
            value={descripcion}
            onChange={(e) => setDescripcion(e.target.value)}
            placeholder="Describa el alcance de este tipo de caso..."
            className={textareaClass}
            rows={5}
            disabled={isPending}
          />
        </div>
      </div>

      <Separator />
      <div className="flex justify-end gap-3 px-6 py-4">
        <Button type="button" variant="outline" onClick={handleCancel} disabled={isPending}>
          Cancelar
        </Button>
        <Button type="submit" disabled={isPending}>
          <Save className="h-4 w-4" />
          Guardar Tipo de Caso
        </Button>
      </div>
    </form>
  );
}
