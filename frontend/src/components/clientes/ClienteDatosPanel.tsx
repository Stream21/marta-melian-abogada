import { useState, type FormEvent } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Plus, Save, Users } from 'lucide-react';
import { api, type ClienteResponse } from '@/api/client';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export function ClienteDatosPanel() {
  const queryClient = useQueryClient();
  const [selectedId, setSelectedId] = useState<string | null>(null);
  const [nombre, setNombre] = useState('');
  const [nacionalidad, setNacionalidad] = useState('');
  const [tipoDocumento, setTipoDocumento] = useState('');
  const [numDocumento, setNumDocumento] = useState('');
  const [fechaNacimiento, setFechaNacimiento] = useState('');
  const [lugarNacimiento, setLugarNacimiento] = useState('');
  const [domicilio, setDomicilio] = useState('');
  const [codigoPostal, setCodigoPostal] = useState('');
  const [ciudad, setCiudad] = useState('');
  const [telefono, setTelefono] = useState('');
  const [email, setEmail] = useState('');

  const { data: clientes = [], isLoading } = useQuery({
    queryKey: ['clientes'],
    queryFn: () => api.getClientes(),
  });

  const resetForm = () => {
    setSelectedId(null);
    setNombre('');
    setNacionalidad('');
    setTipoDocumento('');
    setNumDocumento('');
    setFechaNacimiento('');
    setLugarNacimiento('');
    setDomicilio('');
    setCodigoPostal('');
    setCiudad('');
    setTelefono('');
    setEmail('');
  };

  const loadCliente = (cliente: ClienteResponse) => {
    setSelectedId(cliente.id);
    setNombre(cliente.nombre);
    setNacionalidad(cliente.nacionalidad);
    setTipoDocumento(cliente.tipoDocumento);
    setNumDocumento(cliente.numDocumento);
    setFechaNacimiento(cliente.fechaNacimiento ?? '');
    setLugarNacimiento(cliente.lugarNacimiento);
    setDomicilio(cliente.domicilio);
    setCodigoPostal(cliente.codigoPostal);
    setCiudad(cliente.ciudad);
    setTelefono(cliente.telefono);
    setEmail(cliente.email);
  };

  const saveMutation = useMutation({
    mutationFn: () => {
      const body = {
        nombre,
        nacionalidad,
        tipoDocumento,
        numDocumento,
        fechaNacimiento: fechaNacimiento || null,
        lugarNacimiento,
        domicilio,
        codigoPostal,
        ciudad,
        telefono,
        email,
      };
      return selectedId ? api.putCliente(selectedId, body) : api.postCliente(body);
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['clientes'] });
    },
  });

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    saveMutation.mutate();
  };

  return (
    <div className="grid gap-6 lg:grid-cols-2">
      <div className="panel">
        <div className="panel-header">
          <div className="panel-header-icon">
            <Users className="h-5 w-5" />
          </div>
          <div>
            <h2 className="panel-title">Clientes del bufete</h2>
            <p className="text-sm text-muted-foreground">Datos para escritos y hojas de encargo.</p>
          </div>
        </div>
        <div className="p-4">
          {isLoading ? (
            <p className="text-sm text-muted-foreground">Cargando…</p>
          ) : clientes.length === 0 ? (
            <p className="text-sm text-muted-foreground">No hay clientes registrados.</p>
          ) : (
            <ul className="divide-y divide-border">
              {clientes.map((c) => (
                <li key={c.id}>
                  <button
                    type="button"
                    className="flex w-full flex-col px-2 py-3 text-left hover:bg-muted/50"
                    onClick={() => loadCliente(c)}
                  >
                    <span className="font-medium">{c.nombre}</span>
                    <span className="text-xs text-muted-foreground">
                      {c.tipoDocumento} {c.numDocumento}
                    </span>
                  </button>
                </li>
              ))}
            </ul>
          )}
        </div>
      </div>

      <form onSubmit={handleSubmit} className="panel">
        <div className="panel-header">
          <div className="panel-header-icon">
            {selectedId ? <Save className="h-5 w-5" /> : <Plus className="h-5 w-5" />}
          </div>
          <div>
            <h2 className="panel-title">{selectedId ? 'Editar cliente' : 'Nuevo cliente'}</h2>
          </div>
        </div>
        <div className="grid gap-3 p-4 md:grid-cols-2">
          <div className="space-y-1 md:col-span-2">
            <Label htmlFor="cli-nombre">Nombre completo</Label>
            <Input id="cli-nombre" value={nombre} onChange={(e) => setNombre(e.target.value)} required />
          </div>
          <div className="space-y-1">
            <Label htmlFor="cli-nacionalidad">Nacionalidad</Label>
            <Input id="cli-nacionalidad" value={nacionalidad} onChange={(e) => setNacionalidad(e.target.value)} />
          </div>
          <div className="space-y-1">
            <Label htmlFor="cli-tipo-doc">Tipo documento</Label>
            <Input id="cli-tipo-doc" value={tipoDocumento} onChange={(e) => setTipoDocumento(e.target.value)} placeholder="DNI / PASAPORTE" />
          </div>
          <div className="space-y-1">
            <Label htmlFor="cli-num-doc">Número documento</Label>
            <Input id="cli-num-doc" value={numDocumento} onChange={(e) => setNumDocumento(e.target.value)} />
          </div>
          <div className="space-y-1">
            <Label htmlFor="cli-fecha-nac">Fecha nacimiento</Label>
            <Input id="cli-fecha-nac" type="date" value={fechaNacimiento} onChange={(e) => setFechaNacimiento(e.target.value)} />
          </div>
          <div className="space-y-1 md:col-span-2">
            <Label htmlFor="cli-lugar-nac">Lugar de nacimiento</Label>
            <Input id="cli-lugar-nac" value={lugarNacimiento} onChange={(e) => setLugarNacimiento(e.target.value)} />
          </div>
          <div className="space-y-1 md:col-span-2">
            <Label htmlFor="cli-domicilio">Domicilio</Label>
            <Input id="cli-domicilio" value={domicilio} onChange={(e) => setDomicilio(e.target.value)} />
          </div>
          <div className="space-y-1">
            <Label htmlFor="cli-cp">Código postal</Label>
            <Input id="cli-cp" value={codigoPostal} onChange={(e) => setCodigoPostal(e.target.value)} />
          </div>
          <div className="space-y-1">
            <Label htmlFor="cli-ciudad">Ciudad</Label>
            <Input id="cli-ciudad" value={ciudad} onChange={(e) => setCiudad(e.target.value)} />
          </div>
          <div className="space-y-1">
            <Label htmlFor="cli-telefono">Teléfono</Label>
            <Input id="cli-telefono" value={telefono} onChange={(e) => setTelefono(e.target.value)} />
          </div>
          <div className="space-y-1">
            <Label htmlFor="cli-email">Email</Label>
            <Input id="cli-email" type="email" value={email} onChange={(e) => setEmail(e.target.value)} />
          </div>
        </div>
        <div className="flex justify-end gap-2 px-4 pb-4">
          {selectedId && (
            <Button type="button" variant="outline" onClick={resetForm}>
              Cancelar
            </Button>
          )}
          <Button type="submit" disabled={saveMutation.isPending}>
            <Save className="h-4 w-4" />
            Guardar
          </Button>
        </div>
      </form>
    </div>
  );
}
