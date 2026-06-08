import { useState, type FormEvent } from 'react';

import { useMutation, useQueryClient } from '@tanstack/react-query';

import { useNavigate } from '@tanstack/react-router';

import { Save, Tag } from 'lucide-react';

import { api } from '@/api/client';

import { Button } from '@/components/ui/button';

import { Input } from '@/components/ui/input';

import { Label } from '@/components/ui/label';

import { Separator } from '@/components/ui/separator';

import { TIPOS_SERVICIO, type TipoServicioValue } from '@/lib/servicio-tipos';

import { cn } from '@/lib/utils';



export type ServicioFormMode = 'create' | 'edit';



export interface ServicioFormProps {

  mode: ServicioFormMode;

  servicioId?: string;

  initialNombre?: string;

  initialTipo?: TipoServicioValue;

}



export function ServicioForm({

  mode,

  servicioId,

  initialNombre = '',

  initialTipo,

}: ServicioFormProps) {

  const navigate = useNavigate();

  const queryClient = useQueryClient();

  const [nombre, setNombre] = useState(initialNombre);

  const [tipo, setTipo] = useState<TipoServicioValue | ''>(initialTipo ?? '');

  const [nombreError, setNombreError] = useState(false);

  const [tipoError, setTipoError] = useState(false);



  const createMutation = useMutation({

    mutationFn: () =>

      api.postServicio({

        nombre: nombre.trim(),

        tipo,

      }),

    onSuccess: () => {

      void queryClient.invalidateQueries({ queryKey: ['servicios'] });

      navigate({ to: '/config/servicios' } as never);

    },

  });



  const updateMutation = useMutation({

    mutationFn: () => {

      if (!servicioId) throw new Error('Falta el identificador.');

      return api.putServicio(servicioId, {

        nombre: nombre.trim(),

        tipo,

      });

    },

    onSuccess: () => {

      void queryClient.invalidateQueries({ queryKey: ['servicios'] });

      void queryClient.invalidateQueries({ queryKey: ['servicio', servicioId] });

      navigate({ to: '/config/servicios' } as never);

    },

  });



  const mutation = mode === 'create' ? createMutation : updateMutation;

  const saveError =

    mutation.error instanceof Error ? mutation.error.message : mutation.isError ? 'No se pudo guardar.' : null;



  const handleCancel = () => {

    navigate({ to: '/config/servicios' } as never);

  };



  const handleSubmit = (e: FormEvent) => {

    e.preventDefault();

    const trimmed = nombre.trim();

    let valid = true;

    if (!trimmed) {

      setNombreError(true);

      valid = false;

    } else {

      setNombreError(false);

    }

    if (!tipo) {

      setTipoError(true);

      valid = false;

    } else {

      setTipoError(false);

    }

    if (!valid) return;

    mutation.mutate();

  };



  const isPending = mutation.isPending;



  return (

    <form onSubmit={handleSubmit} className="panel">




      <div className="space-y-6 p-6">

        {saveError && (

          <p className="rounded-lg border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive" role="alert">

            {saveError}

          </p>

        )}



        <div className="space-y-3">

          <Label className="text-foreground">

            Área jurídica <span className="text-destructive">*</span>

          </Label>

          <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">

            {TIPOS_SERVICIO.map((option) => {

              const Icon = option.icon;

              const selected = tipo === option.value;

              return (

                <button

                  key={option.value}

                  type="button"

                  disabled={isPending}

                  onClick={() => {

                    setTipo(option.value);

                    if (tipoError) setTipoError(false);

                  }}

                  className={cn(

                    'flex items-start gap-3 rounded-lg border p-4 text-left transition-colors',

                    'hover:border-primary/40 hover:bg-muted/50',

                    selected ? 'border-primary bg-primary/5 ring-1 ring-primary/30' : 'border-border bg-card',

                    tipoError && !tipo && 'border-destructive/40',

                  )}

                  aria-pressed={selected}

                >

                  <span

                    className={cn(

                      'flex h-10 w-10 shrink-0 items-center justify-center rounded-lg',

                      option.iconClass,

                    )}

                  >

                    <Icon className="h-5 w-5" aria-hidden />

                  </span>

                  <span className="min-w-0">

                    <span className="block text-sm font-semibold text-foreground">{option.shortLabel}</span>

                    <span className="mt-0.5 block text-xs text-muted-foreground leading-snug">{option.label}</span>

                  </span>

                </button>

              );

            })}

          </div>

          {tipoError && (

            <p className="text-xs text-destructive" role="alert">

              Seleccione el área jurídica del servicio.

            </p>

          )}

        </div>



        <div className="space-y-2">

          <Label htmlFor="servicio-nombre" className="text-foreground">

            Nombre del servicio <span className="text-destructive">*</span>

          </Label>

          <div className="relative">

            <Tag

              className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"

              aria-hidden

            />

            <Input

              id="servicio-nombre"

              value={nombre}

              onChange={(e) => {

                setNombre(e.target.value);

                if (nombreError && e.target.value.trim()) setNombreError(false);

              }}

              placeholder="Ej. Residencia y arraigo, Despidos..."

              className="pl-10 bg-muted/50 focus-visible:ring-ring focus-visible:border-primary"

              aria-invalid={nombreError}

              disabled={isPending}

            />

          </div>

          {nombreError && (

            <p className="text-xs text-destructive" role="alert">

              Indique un nombre para el servicio.

            </p>

          )}

        </div>

      </div>



      <Separator />

      <div className="flex justify-end gap-3 px-6 py-4">

        <Button type="button" variant="outline" onClick={handleCancel} disabled={isPending}>

          Cancelar

        </Button>

        <Button type="submit" disabled={isPending}>

          <Save className="h-4 w-4" />

          Guardar servicio

        </Button>

      </div>

    </form>

  );

}

