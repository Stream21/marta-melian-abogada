import { useEffect, useRef, useState } from 'react';
import { Search, UserPlus, Users } from 'lucide-react';
import { api, type ClienteBusquedaItem } from '@/api/client';
import { ClienteExistenteDetectadoDialog } from '@/components/cliente/ClienteExistenteDetectadoDialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { TelefonoInput } from '@/components/ui/TelefonoInput';
import { sanitizarTelefono } from '@/lib/telefono';
import { isValidEmail, isValidTelefono } from '@/lib/validators';
import { cn } from '@/lib/utils';
import type { ExpedienteAltaState } from './types';

interface PasoClientePanelProps {
  state: ExpedienteAltaState;
  onChange: (patch: Partial<ExpedienteAltaState>) => void;
}

function digitosTelefono(valor: string): string {
  return sanitizarTelefono(valor).replace(/\D/g, '');
}

/** Compara móviles aunque uno esté en E.164 (+34…) y el otro en formato local. */
function telefonosCoinciden(a: string, b: string): boolean {
  const da = digitosTelefono(a);
  const db = digitosTelefono(b);
  if (da === '' || db === '') return false;
  if (da === db) return true;

  const nacional = (d: string) => (d.startsWith('34') && d.length === 11 ? d.slice(2) : d);
  return nacional(da) === nacional(db);
}

function emailsCoinciden(a: string, b: string): boolean {
  const ea = a.trim().toLowerCase();
  const eb = b.trim().toLowerCase();
  return ea !== '' && ea === eb;
}

function nombreClienteVisible(cliente: Pick<ClienteBusquedaItem, 'nombre'> & { provisional?: boolean }): string {
  if (cliente.provisional || cliente.nombre === 'Cliente pendiente') {
    return 'Cliente pendiente de identidad';
  }
  return cliente.nombre;
}

export function PasoClientePanel({ state, onChange }: PasoClientePanelProps) {
  const [buscando, setBuscando] = useState(false);
  const [verificando, setVerificando] = useState<'telefono' | 'email' | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [resultados, setResultados] = useState<ClienteBusquedaItem[]>([]);
  const [haBusado, setHaBusado] = useState(false);
  const [emailError, setEmailError] = useState<string | null>(null);
  const [telefonoError, setTelefonoError] = useState<string | null>(null);
  const [modalDetectadoOpen, setModalDetectadoOpen] = useState(false);
  const telefonoDebounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const verificarTelefonoRef = useRef<(telefono: string) => Promise<void>>(async () => {});

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
      modoCliente: 'existente',
      clienteId: cliente.id,
      clienteNombre: nombreClienteVisible(cliente),
      telefono: cliente.telefono,
      email: cliente.email ?? '',
      busquedaCliente: nombreClienteVisible(cliente),
      clienteDetectado: null,
      permitirDuplicado: false,
    });
    setResultados([]);
    setHaBusado(false);
    setModalDetectadoOpen(false);
  };

  const mostrarDetectado = (cliente: ClienteBusquedaItem, campo: 'telefono' | 'email') => {
    onChange({
      clienteDetectado: {
        id: cliente.id,
        nombre: nombreClienteVisible(cliente),
        telefono: cliente.telefono,
        email: cliente.email ?? '',
        campo,
      },
      permitirDuplicado: false,
    });
    setModalDetectadoOpen(true);
  };

  const verificarTelefonoDuplicado = async (telefono: string) => {
    const trimmed = telefono.trim();
    if (!trimmed || state.modoCliente !== 'nuevo') return;
    if (state.permitirDuplicado) return;

    if (!isValidTelefono(trimmed)) {
      setTelefonoError('El teléfono no tiene un formato válido.');
      onChange({ clienteDetectado: null });
      return;
    }
    setTelefonoError(null);

    setVerificando('telefono');
    onChange({ contactoVerificando: true });
    setError(null);
    try {
      const result = await api.buscarClientes(trimmed);
      const coincidenciaExacta = result.clientes.find((c) => telefonosCoinciden(c.telefono, trimmed));
      if (coincidenciaExacta) {
        mostrarDetectado(coincidenciaExacta, 'telefono');
      } else {
        onChange({ clienteDetectado: null });
        setModalDetectadoOpen(false);
      }
    } catch {
      setError('No se pudo verificar el teléfono.');
    } finally {
      setVerificando(null);
      onChange({ contactoVerificando: false });
    }
  };

  verificarTelefonoRef.current = verificarTelefonoDuplicado;

  useEffect(() => {
    return () => {
      if (telefonoDebounceRef.current) clearTimeout(telefonoDebounceRef.current);
    };
  }, []);

  const programarVerificacionTelefono = (telefono: string) => {
    if (telefonoDebounceRef.current) clearTimeout(telefonoDebounceRef.current);
    if (!telefono.trim() || state.modoCliente !== 'nuevo') return;
    telefonoDebounceRef.current = setTimeout(() => {
      void verificarTelefonoRef.current(telefono);
    }, 450);
  };

  const verificarEmailDuplicado = async (email: string) => {
    const trimmed = email.trim();
    if (!trimmed || state.modoCliente !== 'nuevo') return;
    if (state.permitirDuplicado) return;

    if (!isValidEmail(trimmed)) {
      setEmailError('El email no tiene un formato válido.');
      return;
    }
    setEmailError(null);

    setVerificando('email');
    onChange({ contactoVerificando: true });
    setError(null);
    try {
      const result = await api.buscarClientes(trimmed);
      const coincidenciaExacta = result.clientes.find((c) => emailsCoinciden(c.email, trimmed));
      if (coincidenciaExacta) {
        mostrarDetectado(coincidenciaExacta, 'email');
      } else if (state.clienteDetectado?.campo === 'email') {
        onChange({ clienteDetectado: null });
        setModalDetectadoOpen(false);
      }
    } catch {
      setError('No se pudo verificar el email.');
    } finally {
      setVerificando(null);
      onChange({ contactoVerificando: false });
    }
  };

  const validarEmail = (email: string) => {
    if (!isValidEmail(email)) {
      setEmailError('El email no tiene un formato válido.');
      return false;
    }
    setEmailError(null);
    return true;
  };

  const usarClienteDetectado = () => {
    const detectado = state.clienteDetectado;
    if (!detectado) return;
    seleccionarCliente({
      id: detectado.id,
      nombre: detectado.nombre,
      telefono: detectado.telefono,
      email: detectado.email,
      tipoDocumento: '',
      numDocumento: '',
    });
  };

  const continuarComoNuevo = () => {
    onChange({ permitirDuplicado: true, clienteDetectado: null, contactoVerificando: false });
    setModalDetectadoOpen(false);
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
            El teléfono identifica al cliente de forma unívoca en el despacho. Si ya existe, podrá
            vincularlo al instante.
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
              clienteDetectado: null,
              permitirDuplicado: false,
              busquedaCliente: '',
            });
            setResultados([]);
            setHaBusado(false);
            setModalDetectadoOpen(false);
          }}
          className={cn(
            'flex flex-1 items-center gap-3 rounded-lg border-2 p-4 text-left transition-colors',
            state.modoCliente === 'nuevo'
              ? 'border-primary bg-primary/5'
              : 'border-border hover:border-primary/30',
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
            onChange({
              modoCliente: 'existente',
              clienteDetectado: null,
              permitirDuplicado: false,
              clienteId: null,
              clienteNombre: '',
            });
            setResultados([]);
            setHaBusado(false);
            setModalDetectadoOpen(false);
          }}
          className={cn(
            'flex flex-1 items-center gap-3 rounded-lg border-2 p-4 text-left transition-colors',
            state.modoCliente === 'existente'
              ? 'border-primary bg-primary/5'
              : 'border-border hover:border-primary/30',
          )}
        >
          <Search className="h-5 w-5 text-primary" />
          <div>
            <p className="font-semibold text-foreground">Cliente Existente</p>
            <p className="text-xs text-muted-foreground">
              Buscar por nombre, documento, teléfono o email
            </p>
          </div>
        </button>
      </div>

      {state.modoCliente === 'nuevo' && (
        <div className="grid max-w-md gap-4">
          <div>
            <Label htmlFor="telefono">Teléfono *</Label>
            <TelefonoInput
              id="telefono"
              value={state.telefono}
              onChange={(value) => {
                onChange({
                  telefono: value,
                  clienteDetectado: null,
                  permitirDuplicado: false,
                });
                setTelefonoError(null);
                programarVerificacionTelefono(value);
              }}
              onBlur={(value) => {
                if (telefonoDebounceRef.current) clearTimeout(telefonoDebounceRef.current);
                void verificarTelefonoDuplicado(value);
              }}
              required
              className="mt-1"
            />
            {verificando === 'telefono' && (
              <p className="mt-1 text-xs text-muted-foreground">Verificando teléfono…</p>
            )}
            {telefonoError && (
              <p className="mt-1 text-sm text-destructive" role="alert">
                {telefonoError}
              </p>
            )}
            {state.permitirDuplicado && (
              <p className="mt-1 text-xs font-medium text-emerald-700">
                Confirmado: se creará un cliente nuevo aunque el dato ya exista.
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
                onChange({
                  email: e.target.value,
                  permitirDuplicado: false,
                  ...(state.clienteDetectado?.campo === 'email' ? { clienteDetectado: null } : {}),
                });
                if (emailError) setEmailError(null);
              }}
              onBlur={() => {
                if (!validarEmail(state.email)) return;
                void verificarEmailDuplicado(state.email);
              }}
              className="mt-1"
              aria-invalid={!!emailError}
            />
            {verificando === 'email' && (
              <p className="mt-1 text-xs text-muted-foreground">Verificando email…</p>
            )}
            {emailError && (
              <p className="mt-1 text-sm text-destructive" role="alert">
                {emailError}
              </p>
            )}
          </div>

          {state.clienteDetectado && !state.permitirDuplicado && !modalDetectadoOpen && (
            <p className="text-sm text-amber-800">
              Cliente detectado: <strong>{state.clienteDetectado.nombre}</strong>.{' '}
              <button
                type="button"
                className="font-medium underline underline-offset-2 hover:text-amber-950"
                onClick={() => setModalDetectadoOpen(true)}
              >
                Abrir de nuevo
              </button>
            </p>
          )}
        </div>
      )}

      {state.modoCliente === 'existente' && (
        <div className="grid max-w-xl gap-4">
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
              <Button
                type="button"
                onClick={() => void buscarClientes()}
                disabled={buscando || !state.busquedaCliente.trim()}
              >
                <Search className="mr-2 h-4 w-4" />
                {buscando ? 'Buscando…' : 'Buscar'}
              </Button>
            </div>
          </div>

          {resultados.length > 0 && (
            <ul className="divide-y divide-border overflow-hidden rounded-lg border border-border">
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
                      {[cliente.numDocumento, cliente.telefono, cliente.email]
                        .filter(Boolean)
                        .join(' · ')}
                    </span>
                  </button>
                </li>
              ))}
            </ul>
          )}

          {haBusado && !buscando && resultados.length === 0 && state.clienteId === null && (
            <div className="rounded-lg border border-amber-200 bg-amber-50 p-4">
              <p className="text-sm text-amber-800">
                No se encontró ningún cliente con esos criterios.
              </p>
            </div>
          )}

          {state.clienteId && (
            <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
              <p className="text-sm font-medium text-emerald-800">Cliente seleccionado</p>
              <p className="text-sm text-emerald-700">{state.clienteNombre}</p>
              {state.telefono && (
                <p className="mt-1 text-xs text-emerald-600">{state.telefono}</p>
              )}
              {state.email && <p className="text-xs text-emerald-600">{state.email}</p>}
            </div>
          )}
        </div>
      )}

      {error && (
        <p className="mt-4 text-sm text-destructive" role="alert">
          {error}
        </p>
      )}

      <ClienteExistenteDetectadoDialog
        open={modalDetectadoOpen && !!state.clienteDetectado}
        clienteNombre={state.clienteDetectado?.nombre ?? ''}
        campo={state.clienteDetectado?.campo ?? 'telefono'}
        detalle={
          state.clienteDetectado?.campo === 'email'
            ? state.clienteDetectado.email
            : state.clienteDetectado?.telefono
        }
        onUsarExistente={usarClienteDetectado}
        onContinuarNuevo={continuarComoNuevo}
        onCancel={() => setModalDetectadoOpen(false)}
      />
    </div>
  );
}
