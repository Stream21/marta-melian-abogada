import { type FormEvent, useEffect, useState } from 'react';
import { Save, User } from 'lucide-react';
import type { ClienteInput, ClienteResponse } from '@/api/client';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { TelefonoInput } from '@/components/ui/TelefonoInput';
import { ESTADOS_CIVILES } from '@/lib/cliente-datos';

const TIPOS_DOCUMENTO_DEFAULT = ['DNI', 'NIE', 'PASAPORTE', 'OTRO'];

interface ClienteDatosFormProps {
  cliente?: ClienteResponse | null;
  initialValues?: ClienteInput | null;
  onSubmit: (body: ClienteInput) => void;
  isSaving?: boolean;
  readOnly?: boolean;
  /** Campos concretos en solo lectura (p. ej. datos MRZ del documento). */
  camposSoloLectura?: (keyof ClienteInput)[];
  /** Restringe opciones del selector de tipo de documento. */
  tiposDocumentoPermitidos?: string[];
  etiquetaNumDocumento?: string;
  submitLabel?: string;
  /** Layout del portal cliente: botones a ancho completo y textos más breves. */
  portalCliente?: boolean;
  onVolver?: () => void;
  volverLabel?: string;
}

export function ClienteDatosForm({
  cliente,
  initialValues,
  onSubmit,
  isSaving,
  readOnly = false,
  camposSoloLectura = [],
  tiposDocumentoPermitidos = TIPOS_DOCUMENTO_DEFAULT,
  etiquetaNumDocumento = 'Número de documento',
  submitLabel = 'Guardar datos',
  portalCliente = false,
  onVolver,
  volverLabel = 'Volver',
}: ClienteDatosFormProps) {
  const [nombre, setNombre] = useState('');
  const [nacionalidad, setNacionalidad] = useState('');
  const [tipoDocumento, setTipoDocumento] = useState('');
  const [numDocumento, setNumDocumento] = useState('');
  const [fechaNacimiento, setFechaNacimiento] = useState('');
  const [lugarNacimiento, setLugarNacimiento] = useState('');
  const [estadoCivil, setEstadoCivil] = useState('');
  const [domicilio, setDomicilio] = useState('');
  const [codigoPostal, setCodigoPostal] = useState('');
  const [ciudad, setCiudad] = useState('');
  const [provincia, setProvincia] = useState('');
  const [nombrePadre, setNombrePadre] = useState('');
  const [nombreMadre, setNombreMadre] = useState('');
  const [telefono, setTelefono] = useState('');
  const [email, setEmail] = useState('');

  const applyValues = (values: ClienteInput) => {
    setNombre(values.nombre ?? '');
    setNacionalidad(values.nacionalidad ?? '');
    setTipoDocumento(values.tipoDocumento ?? '');
    setNumDocumento(values.numDocumento ?? '');
    setFechaNacimiento(values.fechaNacimiento ?? '');
    setLugarNacimiento(values.lugarNacimiento ?? '');
    setEstadoCivil(values.estadoCivil ?? '');
    setDomicilio(values.domicilio ?? '');
    setCodigoPostal(values.codigoPostal ?? '');
    setCiudad(values.ciudad ?? '');
    setProvincia(values.provincia ?? '');
    setNombrePadre(values.nombrePadre ?? '');
    setNombreMadre(values.nombreMadre ?? '');
    setTelefono(values.telefono ?? '');
    setEmail(values.email ?? '');
  };

  useEffect(() => {
    if (cliente) {
      applyValues({
        nombre: cliente.nombre,
        nacionalidad: cliente.nacionalidad,
        tipoDocumento: cliente.tipoDocumento,
        numDocumento: cliente.numDocumento,
        fechaNacimiento: cliente.fechaNacimiento,
        lugarNacimiento: cliente.lugarNacimiento,
        estadoCivil: cliente.estadoCivil ?? '',
        domicilio: cliente.domicilio,
        codigoPostal: cliente.codigoPostal,
        ciudad: cliente.ciudad,
        provincia: cliente.provincia ?? '',
        nombrePadre: cliente.nombrePadre ?? '',
        nombreMadre: cliente.nombreMadre ?? '',
        telefono: cliente.telefono,
        email: cliente.email,
      });
    } else if (initialValues) {
      applyValues(initialValues);
    }
  }, [cliente, initialValues]);

  const bloqueado = (campo: keyof ClienteInput) =>
    readOnly || camposSoloLectura.includes(campo);

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    if (readOnly) return;
    onSubmit({
      nombre,
      nacionalidad,
      tipoDocumento,
      numDocumento,
      fechaNacimiento: fechaNacimiento || null,
      lugarNacimiento,
      estadoCivil,
      domicilio,
      codigoPostal,
      ciudad,
      provincia,
      nombrePadre,
      nombreMadre,
      telefono,
      email,
    });
  };

  return (
    <form onSubmit={handleSubmit} className="panel">
      <div className="panel-header">
        <div className="panel-header-icon">
          <User className="h-5 w-5" />
        </div>
        <div>
          <h2 className="panel-title">Datos del cliente</h2>
          {!portalCliente && (
            <p className="text-sm text-muted-foreground">
              {readOnly
                ? 'Solo lectura. Los datos no se pueden modificar hasta que cierre el proceso de contratación.'
                : camposSoloLectura.length > 0
                  ? 'Los datos leídos de la banda MRZ del documento no se pueden modificar. Complete el resto de campos (contacto, domicilio, etc.).'
                  : 'Complete la ficha mínima del cliente. Los campos del documento se rellenan al escanear; el resto debe indicarlo el cliente.'}
            </p>
          )}
          {portalCliente && (
            <p className="text-sm text-muted-foreground">
              Revise sus datos y complete contacto y domicilio.
            </p>
          )}
        </div>
      </div>

      <div className={portalCliente ? 'space-y-6 p-4 sm:p-6' : 'space-y-6 p-6'}>
        <section>
          <h3 className="section-label mb-3">Identificación</h3>
          <div className="grid gap-3 md:grid-cols-2">
            <Field label="Nombre completo" id="nombre" value={nombre} onChange={setNombre} required disabled={bloqueado('nombre')} className="md:col-span-2" />
            <Field label="Nacionalidad" id="nacionalidad" value={nacionalidad} onChange={setNacionalidad} disabled={bloqueado('nacionalidad')} />
            <div className="space-y-1">
              <Label htmlFor="tipoDocumento">Tipo de documento</Label>
              <select
                id="tipoDocumento"
                className="input-field h-9 w-full disabled:cursor-not-allowed disabled:opacity-60"
                value={tipoDocumento}
                onChange={(e) => setTipoDocumento(e.target.value)}
                disabled={bloqueado('tipoDocumento')}
              >
                <option value="">Seleccionar…</option>
                {tiposDocumentoPermitidos.map((t) => (
                  <option key={t} value={t}>{t}</option>
                ))}
              </select>
            </div>
            <Field label={etiquetaNumDocumento} id="numDocumento" value={numDocumento} onChange={setNumDocumento} disabled={bloqueado('numDocumento')} />
          </div>
        </section>

        <section>
          <h3 className="section-label mb-3">Datos personales</h3>
          <div className="grid gap-3 md:grid-cols-2">
            <Field label="Fecha de nacimiento" id="fechaNacimiento" type="date" value={fechaNacimiento} onChange={setFechaNacimiento} disabled={bloqueado('fechaNacimiento')} />
            <Field label="Lugar de nacimiento" id="lugarNacimiento" value={lugarNacimiento} onChange={setLugarNacimiento} disabled={bloqueado('lugarNacimiento')} />
            <div className="space-y-1 md:col-span-2">
              <Label htmlFor="estadoCivil">Estado civil</Label>
              <select
                id="estadoCivil"
                className="input-field h-9 w-full disabled:cursor-not-allowed disabled:opacity-60"
                value={estadoCivil}
                onChange={(e) => setEstadoCivil(e.target.value)}
                disabled={bloqueado('estadoCivil')}
              >
                <option value="">Seleccionar…</option>
                {ESTADOS_CIVILES.map((e) => (
                  <option key={e.value} value={e.value}>{e.label}</option>
                ))}
              </select>
            </div>
          </div>
        </section>

        <section>
          <h3 className="section-label mb-3">Filiación</h3>
          <div className="grid gap-3 md:grid-cols-2">
            <Field label="Nombre del padre" id="nombrePadre" value={nombrePadre} onChange={setNombrePadre} disabled={readOnly} />
            <Field label="Nombre de la madre" id="nombreMadre" value={nombreMadre} onChange={setNombreMadre} disabled={readOnly} />
          </div>
        </section>

        <section>
          <h3 className="section-label mb-3">Domicilio</h3>
          <div className="grid gap-3 md:grid-cols-2">
            <Field label="Domicilio" id="domicilio" value={domicilio} onChange={setDomicilio} disabled={readOnly} className="md:col-span-2" />
            <Field label="Código postal" id="codigoPostal" value={codigoPostal} onChange={setCodigoPostal} disabled={readOnly} />
            <Field label="Ciudad / municipio" id="ciudad" value={ciudad} onChange={setCiudad} disabled={readOnly} />
            <Field label="Provincia" id="provincia" value={provincia} onChange={setProvincia} disabled={readOnly} className="md:col-span-2" />
          </div>
        </section>

        <section>
          <h3 className="section-label mb-3">Contacto</h3>
          <div className={portalCliente ? 'grid gap-3' : 'grid max-w-xl gap-3'}>
            <div className="space-y-1">
              <Label htmlFor="telefono">Teléfono</Label>
              <TelefonoInput
                id="telefono"
                value={telefono}
                onChange={setTelefono}
                disabled={bloqueado('telefono')}
              />
            </div>
            <Field label="Email" id="email" type="email" value={email} onChange={setEmail} disabled={bloqueado('email')} />
          </div>
        </section>
      </div>

      {!readOnly && (
        <div
          className={
            portalCliente
              ? 'flex flex-col items-stretch gap-3 border-t px-4 py-4 sm:px-6'
              : 'flex justify-end border-t px-6 py-4'
          }
        >
          <Button
            type="submit"
            disabled={isSaving}
            className={portalCliente ? 'w-full' : undefined}
            size={portalCliente ? 'lg' : 'default'}
          >
            <Save className="mr-2 h-4 w-4" />
            {submitLabel}
          </Button>
          {portalCliente && onVolver && (
            <Button
              type="button"
              variant="outline"
              className="w-full"
              size="lg"
              onClick={onVolver}
              disabled={isSaving}
            >
              {volverLabel}
            </Button>
          )}
        </div>
      )}
    </form>
  );
}

function Field({
  label,
  id,
  value,
  onChange,
  type = 'text',
  required,
  disabled,
  className,
}: {
  label: string;
  id: string;
  value: string;
  onChange: (v: string) => void;
  type?: string;
  required?: boolean;
  disabled?: boolean;
  className?: string;
}) {
  return (
    <div className={`space-y-1 ${className ?? ''}`}>
      <Label htmlFor={id}>{label}</Label>
      <Input
        id={id}
        type={type}
        value={value}
        onChange={(e) => onChange(e.target.value)}
        required={required}
        disabled={disabled}
      />
    </div>
  );
}
