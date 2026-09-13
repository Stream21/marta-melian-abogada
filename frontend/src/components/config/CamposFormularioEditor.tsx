import { useEffect, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Plus, Trash2 } from 'lucide-react';
import {
  api,
  type CampoFormularioConfig,
  type TipoCampoFormularioValue,
} from '@/api/client';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export type CamposFormularioScope = 'tramite' | 'servicio';

interface CamposFormularioEditorProps {
  scope: CamposFormularioScope;
  entityId: string;
  title?: string;
}

function queryKey(scope: CamposFormularioScope, entityId: string) {
  return scope === 'servicio'
    ? ['campos-formulario-servicio', entityId]
    : ['campos-formulario-tramite', entityId];
}

export function CamposFormularioEditor({
  scope,
  entityId,
  title = 'Campos de formulario (requerimientos Mercurio)',
}: CamposFormularioEditorProps) {
  const queryClient = useQueryClient();
  const [campos, setCampos] = useState<CampoFormularioConfig[]>([]);
  const [initialized, setInitialized] = useState(false);

  const { data, isLoading } = useQuery({
    queryKey: queryKey(scope, entityId),
    queryFn: () =>
      scope === 'servicio'
        ? api.getCamposFormularioServicio(entityId)
        : api.getCamposFormularioTramite(entityId),
  });

  useEffect(() => {
    if (!data || initialized) return;
    setCampos(data.campos ?? []);
    setInitialized(true);
  }, [data, initialized]);

  const saveMutation = useMutation({
    mutationFn: () => {
      const payload = campos.map((c) =>
        c.tipo === 'select'
          ? { ...c, opciones: (c.opciones ?? []).map((s) => s.trim()).filter(Boolean) }
          : c,
      );
      return scope === 'servicio'
        ? api.putCamposFormularioServicio(entityId, payload)
        : api.putCamposFormularioTramite(entityId, payload);
    },
    onSuccess: (res) => {
      setCampos(res.campos);
      void queryClient.invalidateQueries({ queryKey: queryKey(scope, entityId) });
    },
  });

  const addCampo = () => {
    setCampos((c) => [
      ...c,
      { clave: '', etiqueta: '', tipo: 'text', obligatorio: true, orden: c.length },
    ]);
  };

  return (
    <section className="panel space-y-4 p-5">
      <div>
        <h3 className="font-semibold">{title}</h3>
        <p className="mt-1 text-sm text-muted-foreground">
          Plantilla reutilizable al crear requerimientos Mercurio (plantillaFrom).
        </p>
      </div>
      {isLoading && <p className="text-sm text-muted-foreground">Cargando…</p>}
      <ul className="space-y-3">
        {campos.map((campo, i) => (
          <li key={campo.id ?? i} className="grid gap-2 rounded-lg border border-border p-3 sm:grid-cols-4">
            <div className="space-y-1">
              <Label>Clave</Label>
              <Input
                value={campo.clave}
                onChange={(e) =>
                  setCampos((list) =>
                    list.map((item, j) => (j === i ? { ...item, clave: e.target.value } : item)),
                  )
                }
              />
            </div>
            <div className="space-y-1">
              <Label>Etiqueta</Label>
              <Input
                value={campo.etiqueta}
                onChange={(e) =>
                  setCampos((list) =>
                    list.map((item, j) => (j === i ? { ...item, etiqueta: e.target.value } : item)),
                  )
                }
              />
            </div>
            <div className="space-y-1">
              <Label>Tipo</Label>
              <select
                className="input-field w-full"
                value={campo.tipo}
                onChange={(e) => {
                  const tipo = e.target.value as TipoCampoFormularioValue;
                  setCampos((list) =>
                    list.map((item, j) =>
                      j === i
                        ? {
                            ...item,
                            tipo,
                            opciones:
                              tipo === 'select'
                                ? item.opciones?.length
                                  ? item.opciones
                                  : ['']
                                : undefined,
                          }
                        : item,
                    ),
                  );
                }}
              >
                <option value="text">Texto</option>
                <option value="textarea">Texto largo</option>
                <option value="number">Número</option>
                <option value="date">Fecha</option>
                <option value="select">Selección</option>
                <option value="checkbox">Casilla</option>
              </select>
            </div>
            {campo.tipo === 'select' && (
              <div className="space-y-2 sm:col-span-4">
                <Label>Opciones</Label>
                {(campo.opciones?.length ? campo.opciones : ['']).map((opt, oi) => (
                  <div key={oi} className="flex gap-2">
                    <Input
                      value={opt}
                      placeholder={`Opción ${oi + 1}`}
                      onChange={(e) => {
                        const next = [...(campo.opciones?.length ? campo.opciones : [''])];
                        next[oi] = e.target.value;
                        setCampos((list) =>
                          list.map((item, j) => (j === i ? { ...item, opciones: next } : item)),
                        );
                      }}
                    />
                    {(campo.opciones?.length ?? 1) > 1 && (
                      <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        aria-label="Quitar opción"
                        onClick={() => {
                          const base = campo.opciones?.length ? campo.opciones : [''];
                          const next = base.filter((_, k) => k !== oi);
                          setCampos((list) =>
                            list.map((item, j) =>
                              j === i ? { ...item, opciones: next.length ? next : [''] } : item,
                            ),
                          );
                        }}
                      >
                        <Trash2 className="h-4 w-4" />
                      </Button>
                    )}
                  </div>
                ))}
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  onClick={() =>
                    setCampos((list) =>
                      list.map((item, j) =>
                        j === i
                          ? {
                              ...item,
                              opciones: [...(item.opciones?.length ? item.opciones : ['']), ''],
                            }
                          : item,
                      ),
                    )
                  }
                >
                  <Plus className="mr-1 h-3 w-3" /> Añadir opción
                </Button>
              </div>
            )}
            <div className="flex items-end gap-2">
              <Button
                type="button"
                variant="ghost"
                size="icon"
                aria-label="Eliminar"
                onClick={() => setCampos((list) => list.filter((_, j) => j !== i))}
              >
                <Trash2 className="h-4 w-4" />
              </Button>
            </div>
          </li>
        ))}
      </ul>
      <div className="flex flex-wrap gap-2">
        <Button type="button" variant="outline" size="sm" onClick={addCampo}>
          <Plus className="mr-1.5 h-4 w-4" /> Añadir campo
        </Button>
        <Button
          size="sm"
          disabled={saveMutation.isPending}
          onClick={() => saveMutation.mutate()}
        >
          Guardar plantilla
        </Button>
      </div>
      {saveMutation.error && (
        <p className="text-sm text-destructive">
          {saveMutation.error instanceof Error ? saveMutation.error.message : 'Error al guardar'}
        </p>
      )}
    </section>
  );
}
