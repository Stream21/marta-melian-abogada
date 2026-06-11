import { useState } from 'react';
import { AlertTriangle, Search, UserPlus, Users } from 'lucide-react';
import { api, type ClienteBusquedaItem } from '@/api/client';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { isValidEmail, isValidTelefono } from '@/lib/validators';
import { cn } from '@/lib/utils';
import type { ExpedienteAltaState } from './types';

interface PasoClientePanelProps {
  state: ExpedienteAltaState;
  onChange: (patch: Partial<ExpedienteAltaState>) => void;
}

export function PasoClientePanel({ state, onChange }: PasoClientePanelProps) {
  const [buscando, setBuscando] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [resultados, setResultados] = useState<ClienteBusquedaItem[]>([]);
  const [haBusado, setHaBusado] = useState(false);
  const [emailError, setEmailError] = useState<string | null>(null);
  const [telefonoError, setTelefonoError] = useState<string | null>(null);

  const verificarTelefonoDuplicado = async (telefono: string) => {
    const trimmed = telefono.trim();
    if (!trimmed || state.modoCliente !== 'nuevo') return;

    if (!isValidTelefono(trimmed)) {
      setTelefonoError('El teléfono no tiene un formato válido.');
      onChange({ telefonoDuplicado: null });
      return;
    }
    setTelefonoError(null);

    setBuscando(true);
    setError(null);
    try {
      const result = await api.buscarClientes(trimmed);
      const coincidenciaExacta = result.clientes.find(
        (c) => c.telefono.replace(/\s+/g, '') === trimmed.replace(/\s+/g, ''),
      );
      if (coincidenciaExacta) {
        onChange({
          telefonoDuplicado: { id: coincidenciaExacta.id, nombre: coincidenciaExacta.nombre },
        });
      } else {
        onChange({ telefonoDuplicado: null });
      }
    } catch {
      setError('No se pudo verificar el teléfono.');
    } finally {
      setBuscando(false);
    }
  };

  const buscarConQuery = async (query: string) => {
    const trimmed = query.trim();
    if (!trimmed) return;

    setBuscando(true);
    setError(null);
    setHaBusado(true);
    try {
      const result = await api.buscarClientes(trimmed);
      setResultados(result.clientes);
      if (result.clientes.length === 0) {
        onChange({ clienteId: null, clienteNombre: '', telefono: '', email: '' });
      }
    } catch {
      setError('No se pudo realizar la búsqueda.');
      setResultados([]);
    } finally {
      setBuscando(false);
    }
  };

  const buscarClientes = () => void buscarConQuery(state.busquedaCliente);

  const seleccionarCliente = (cliente: ClienteBusquedaItem) => {
    onChange({
      clienteId: cliente.id,
      clienteNombre: cliente.nombre,
      telefono: cliente.telefono,
      email: cliente.email ?? '',
      telefonoDuplicado: null,
    });
    setResultados([]);
  };

  const validarEmail = (email: string) => {
    if (!isValidEmail(email)) {
      setEmailError('El email no tiene un formato válido.');
      return false;
    }
    setEmailError(null);
    return true;
  };

  return (
    <div className="panel p-6">
      <div className="panel-header border-0 p-0 mb-6">
        <div className="panel-header-icon">
          <Users className="h-5 w-5" />
        </div>
        <div>
          <h2 className="panel-title">Identificación del Cliente</h2>
          <p className="text-sm text-muted-foreground">
            El teléfono identifica al cliente de forma unívoca en el despacho.
          </p>
        </div>
      </div>

      <div className="mb-6 flex gap-3">
        <button
          type="button"
          onClick={() => {
            onChange({
              modoCliente: 'nuevo',
              clienteId: null,
              clienteNombre: '',
              telefonoDuplicado: null,
              busquedaCliente: '',
            });
            setResultados([]);
            setHaBusado(false);
          }}
          className={cn(
            'flex flex-1 items-center gap-3 rounded-lg border-2 p-4 text-left transition-colors',
            state.modoCliente === 'nuevo' ? 'border-primary bg-primary/5' : 'border-border hover:border-primary/30',
          )}
        >
          <UserPlus className="h-5 w-5 text-primary" />
          <div>
            <p className="font-semibold text-foreground">Nuevo Cliente</p>
            <p className="text-xs text-muted-foreground">Registrar teléfono y email opcional</p>
          </div>
        </button>
        <button
          type="button"
          onClick={() => {
            onChange({ modoCliente: 'existente', telefonoDuplicado: null, clienteId: null, clienteNombre: '' });
            setResultados([]);
            setHaBusado(false);
          }}
          className={cn(
            'flex flex-1 items-center gap-3 rounded-lg border-2 p-4 text-left transition-colors',
            state.modoCliente === 'existente' ? 'border-primary bg-primary/5' : 'border-border hover:border-primary/30',
          )}
        >
          <Search className="h-5 w-5 text-primary" />
          <div>
            <p className="font-semibold text-foreground">Cliente Existente</p>
            <p className="text-xs text-muted-foreground">Buscar por nombre, documento, teléfono o email</p>
          </div>
        </button>
      </div>

      {state.modoCliente === 'nuevo' && (
        <div className="grid gap-4 max-w-md">
          <div>
            <Label htmlFor="telefono">Teléfono *</Label>
            <Input
              id="telefono"
              type="tel"
              placeholder="+34600111222"
              value={state.telefono}
              onChange={(e) => {
                onChange({ telefono: e.target.value, telefonoDuplicado: null });
                setTelefonoError(null);
              }}
              onBlur={() => void verificarTelefonoDuplicado(state.telefono)}
              className="mt-1"
              aria-invalid={!!telefonoError}
            />
            {buscando && <p className="mt-1 text-xs text-muted-foreground">Verificando teléfono…</p>}
            {telefonoError && (
              <p className="mt-1 text-sm text-destructive" role="alert">
                {telefonoError}
              </p>
            )}
          </div>

          <div>
            <Label htmlFor="email">Email (opcional)</Label>
            <Input
              id="email"
              type="email"
              placeholder="cliente@email.com"
              value={state.email}
              onChange={(e) => {
                onChange({ email: e.target.value });
                if (emailError) setEmailError(null);
              }}
              onBlur={() => validarEmail(state.email)}
              className="mt-1"
              aria-invalid={!!emailError}
            />
            {emailError && (
              <p className="mt-1 text-sm text-destructive" role="alert">
                {emailError}
              </p>
            )}
          </div>

          {state.telefonoDuplicado && (
            <div className="rounded-lg border border-destructive/30 bg-destructive/5 p-4" role="alert">
              <div className="flex gap-2">
                <AlertTriangle className="h-5 w-5 shrink-0 text-destructive" />
                <div>
                  <p className="text-sm font-medium text-destructive">Teléfono ya registrado</p>
                  <p className="mt-1 text-sm text-muted-foreground">
                    Ya existe el cliente <strong>{state.telefonoDuplicado.nombre}</strong> con este teléfono.
                    Use la opción &quot;Cliente Existente&quot; para vincularlo.
                  </p>
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    className="mt-3"
                    onClick={() => {
                      const q = state.telefono;
                      onChange({
                        modoCliente: 'existente',
                        busquedaCliente: q,
                        telefonoDuplicado: null,
                      });
                      void buscarConQuery(q);
                    }}
                  >
                    Usar cliente existente
                  </Button>
                </div>
              </div>
            </div>
          )}
        </div>
      )}

      {state.modoCliente === 'existente' && (
        <div className="grid gap-4 max-w-xl">
          <div className="flex gap-2">
            <div className="flex-1">
              <Label htmlFor="busqueda">Buscar cliente</Label>
              <Input
                id="busqueda"
                type="search"
                placeholder="Nombre, apellidos, documento, teléfono o email"
                value={state.busquedaCliente}
                onChange={(e) => onChange({ busquedaCliente: e.target.value })}
                onKeyDown={(e) => e.key === 'Enter' && void buscarClientes()}
                className="mt-1"
              />
            </div>
            <div className="flex items-end">
              <Button type="button" onClick={() => void buscarClientes()} disabled={buscando || !state.busquedaCliente.trim()}>
                <Search className="mr-2 h-4 w-4" />
                {buscando ? 'Buscando…' : 'Buscar'}
              </Button>
            </div>
          </div>

          {resultados.length > 0 && (
            <ul className="divide-y divide-border rounded-lg border border-border overflow-hidden">
              {resultados.map((cliente) => (
                <li key={cliente.id}>
                  <button
                    type="button"
                    onClick={() => seleccionarCliente(cliente)}
                    className={cn(
                      'flex w-full flex-col gap-0.5 px-4 py-3 text-left transition-colors hover:bg-primary/5',
                      state.clienteId === cliente.id && 'bg-primary/10',
                    )}
                  >
                    <span className="font-medium">{cliente.nombre}</span>
                    <span className="text-xs text-muted-foreground">
                      {[cliente.numDocumento, cliente.telefono, cliente.email].filter(Boolean).join(' · ')}
                    </span>
                  </button>
                </li>
              ))}
            </ul>
          )}

          {haBusado && !buscando && resultados.length === 0 && state.clienteId === null && (
            <div className="rounded-lg border border-amber-200 bg-amber-50 p-4">
              <p className="text-sm text-amber-800">No se encontró ningún cliente con esos criterios.</p>
            </div>
          )}

          {state.clienteId && (
            <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
              <p className="text-sm font-medium text-emerald-800">Cliente seleccionado</p>
              <p className="text-sm text-emerald-700">{state.clienteNombre}</p>
              {state.telefono && (
                <p className="text-xs text-emerald-600 mt-1">{state.telefono}</p>
              )}
            </div>
          )}
        </div>
      )}

      {error && (
        <p className="mt-4 text-sm text-destructive" role="alert">
          {error}
        </p>
      )}
    </div>
  );
}
