import { useCallback, useEffect, useId, useRef, useState } from 'react';
import {
  CreditCard,
  IdCard,
  Loader2,
  ScanLine,
  CheckCircle2,
  ArrowLeft,
} from 'lucide-react';
import { api, type ExtraerDocumentoIdentidadResponse, type TipoEscaneoDocumentoIdentidad } from '@/api/client';
import { labelsDocumentoIdentidad } from '@/lib/documento-identidad-labels';
import { mergeFetchHeaders } from '@/lib/ngrok-headers';
import { Button } from '@/components/ui/button';
import { esDispositivoMovil } from '@/lib/device';
import { cn } from '@/lib/utils';
import type { DocumentoIdentidadResultado, ModoDocumentoIdentidad } from './types';
import { ImagenDocumentoCaptura } from './ImagenDocumentoCaptura';
import type { LadoCapturaCamara } from './CapturaCamaraDocumento';

type PasoFlujo = 'tipo' | 'captura' | 'extraccion';
type LadoCaptura = 'anverso' | 'reverso';

type ExtraerDocumentoFn = (input: {
  tipoEscaneo: TipoEscaneoDocumentoIdentidad;
  anverso: File;
  reverso: File | null;
}) => Promise<ExtraerDocumentoIdentidadResponse>;

interface DocumentoIdentidadFlujoProps {
  modo: ModoDocumentoIdentidad;
  /** Tipo de servicio del expediente (p. ej. extranjeria_nacionalidad). */
  tipoServicio?: string | null;
  onCompletado: (resultado: DocumentoIdentidadResultado) => void;
  extraerDocumento?: ExtraerDocumentoFn;
  /** Vuelve al paso anterior del onboarding (elección, corrección, etc.). */
  onVolver?: () => void;
  /** Limpia el inicio rápido del padre al reiniciar la selección de tipo. */
  onReiniciarTipo?: () => void;
  inicioRapido?: {
    tipoEscaneo: TipoEscaneoDocumentoIdentidad;
    ladoInicial?: LadoCaptura;
    conservarLado?: LadoCaptura;
    anversoUrl?: string | null;
    reversoUrl?: string | null;
  };
}

export function DocumentoIdentidadFlujo({
  modo,
  tipoServicio,
  onCompletado,
  extraerDocumento,
  onVolver,
  onReiniciarTipo,
  inicioRapido,
}: DocumentoIdentidadFlujoProps) {
  const inputId = useId();
  const anversoInputRef = useRef<HTMLInputElement>(null);
  const reversoInputRef = useRef<HTMLInputElement>(null);
  const [paso, setPaso] = useState<PasoFlujo>('tipo');
  const [tipoEscaneo, setTipoEscaneo] = useState<TipoEscaneoDocumentoIdentidad | null>(null);
  const [ladoActivo, setLadoActivo] = useState<LadoCaptura>('anverso');
  const [anverso, setAnverso] = useState<File | null>(null);
  const [reverso, setReverso] = useState<File | null>(null);
  const [anversoPreview, setAnversoPreview] = useState<string | null>(null);
  const [reversoPreview, setReversoPreview] = useState<string | null>(null);
  const [rotacionAnverso, setRotacionAnverso] = useState(0);
  const [rotacionReverso, setRotacionReverso] = useState(0);
  const [error, setError] = useState<string | null>(null);
  const [isDragging, setIsDragging] = useState(false);
  const [ladoConservado, setLadoConservado] = useState<LadoCaptura | null>(null);
  const [esMovil, setEsMovil] = useState(false);

  useEffect(() => {
    setEsMovil(esDispositivoMovil());
  }, []);

  useEffect(() => {
    if (!inicioRapido) return;
    setTipoEscaneo(inicioRapido.tipoEscaneo);
    setLadoConservado(inicioRapido.conservarLado ?? null);
    setLadoActivo(inicioRapido.ladoInicial ?? 'anverso');
    setPaso('captura');
  }, [inicioRapido]);

  const esCliente = modo === 'cliente';
  const labels = labelsDocumentoIdentidad(tipoServicio);
  const soloExtranjeria = labels.tipoDocumentoSelect.length === 2;
  const requiereReverso = tipoEscaneo === 'dni_nie';
  const totalLados = requiereReverso ? 2 : 1;
  const indiceLado = ladoActivo === 'anverso' ? 1 : 2;

  const revocarPreview = (url: string | null) => {
    if (url) URL.revokeObjectURL(url);
  };

  const resetCaptura = () => {
    revocarPreview(anversoPreview);
    revocarPreview(reversoPreview);
    setAnverso(null);
    setReverso(null);
    setAnversoPreview(null);
    setReversoPreview(null);
    setRotacionAnverso(0);
    setRotacionReverso(0);
    setLadoActivo('anverso');
    setLadoConservado(null);
    setError(null);
  };

  const seleccionarTipo = (tipo: TipoEscaneoDocumentoIdentidad) => {
    setTipoEscaneo(tipo);
    resetCaptura();
    setPaso('captura');
  };

  const etiquetaLado = (lado: LadoCaptura): string => {
    if (tipoEscaneo === 'pasaporte') return 'Página de identificación';
    return lado === 'anverso' ? 'Anverso (cara con foto)' : 'Reverso (banda MRZ)';
  };

  const handleAnversoReady = useCallback((file: File, previewUrl: string) => {
    setAnversoPreview((prev) => {
      revocarPreview(prev);
      return previewUrl;
    });
    setAnverso(file);
    if (requiereReverso) setLadoActivo('reverso');
  }, [requiereReverso]);

  const handleReversoReady = useCallback((file: File, previewUrl: string) => {
    setReversoPreview((prev) => {
      revocarPreview(prev);
      return previewUrl;
    });
    setReverso(file);
  }, []);

  const capturaCompleta =
    (anverso != null || ladoConservado === 'anverso') &&
    (!requiereReverso || reverso != null || ladoConservado === 'reverso');

  const urlABlobFile = async (url: string, nombre: string): Promise<File> => {
    const res = await fetch(url, { headers: mergeFetchHeaders() });
    if (!res.ok) throw new Error('No se pudo cargar la imagen del documento guardado.');
    const blob = await res.blob();
    return new File([blob], nombre, { type: blob.type || 'image/jpeg' });
  };

  const abrirCaptura = () => {
    if (ladoActivo === 'anverso') anversoInputRef.current?.click();
    else reversoInputRef.current?.click();
  };

  const extraerDatos = async () => {
    if (!tipoEscaneo) return;
    setError(null);
    setPaso('extraccion');
    try {
      let fileAnverso = anverso;
      let fileReverso = requiereReverso ? reverso : null;

      if (!fileAnverso && ladoConservado === 'anverso' && inicioRapido?.anversoUrl) {
        fileAnverso = await urlABlobFile(inicioRapido.anversoUrl, 'anverso-existente.jpg');
      }
      if (requiereReverso && !fileReverso && ladoConservado === 'reverso' && inicioRapido?.reversoUrl) {
        fileReverso = await urlABlobFile(inicioRapido.reversoUrl, 'reverso-existente.jpg');
      }

      if (!fileAnverso) {
        throw new Error('Falta la imagen del anverso del documento.');
      }

      const extraer = extraerDocumento ?? api.extraerDocumentoIdentidad.bind(api);
      const resultado = await extraer({
        tipoEscaneo,
        anverso: fileAnverso,
        reverso: fileReverso,
      });
      onCompletado({
        archivos: {
          tipoEscaneo,
          anverso: fileAnverso,
          reverso: fileReverso,
        },
        datosExtraidos: resultado.datosExtraidos,
      });
    } catch (e) {
      setPaso('captura');
      setError((e as Error).message);
    }
  };

  const volverATipo = () => {
    setTipoEscaneo(null);
    resetCaptura();
    setPaso('tipo');
    onReiniciarTipo?.();
  };

  const ladoCamara = (lado: LadoCaptura): LadoCapturaCamara =>
    tipoEscaneo === 'pasaporte' ? 'pasaporte' : lado;

  const layoutMovilCliente = esCliente && esMovil;

  const contenido = (
    <>
      <IndicadorPasos pasoActual={paso} compacto={layoutMovilCliente} />

      {paso === 'tipo' && (
        <section className={layoutMovilCliente ? 'space-y-3' : undefined}>
          <p className="section-label mb-3">¿Qué documento de identidad tiene?</p>
          {soloExtranjeria && (
            <p className="mb-3 text-sm text-muted-foreground">
              Para trámites de extranjería y nacionalidad solo puede usar su <strong>NIE</strong> o su{' '}
              <strong>pasaporte</strong>.
            </p>
          )}
          <div className={cn('grid gap-3', soloExtranjeria ? 'sm:grid-cols-2' : 'sm:grid-cols-2')}>
            <TipoCard
              icon={IdCard}
              title={labels.tarjetaIdentidad}
              description={labels.tarjetaIdentidadDescripcion}
              onClick={() => seleccionarTipo('dni_nie')}
            />
            <TipoCard
              icon={CreditCard}
              title="Pasaporte"
              description="Página interior con sus datos"
              onClick={() => seleccionarTipo('pasaporte')}
            />
          </div>
          {onVolver && (
            <Button
              type="button"
              variant="outline"
              className={cn('mt-2', layoutMovilCliente && 'w-full')}
              size={layoutMovilCliente ? 'lg' : 'default'}
              onClick={onVolver}
            >
              <ArrowLeft className="mr-2 h-4 w-4" />
              Volver
            </Button>
          )}
        </section>
      )}

      {paso === 'captura' && tipoEscaneo && (
        <section className="space-y-5">
          <div className="rounded-xl border border-border bg-muted/20 p-4">
            <div>
              <p className="section-label">
                {tipoEscaneo === 'pasaporte' ? 'Pasaporte' : labels.tipoDocumentoCorto}
              </p>
              <p className="mt-1 text-sm font-medium text-foreground">
                {requiereReverso
                  ? `Paso ${indiceLado} de ${totalLados}: ${etiquetaLado(ladoActivo)}`
                  : etiquetaLado('anverso')}
              </p>
            </div>

            {requiereReverso && (
              <div className="mt-4 flex flex-wrap gap-2 border-t border-border pt-4">
                <LadoChip
                  label="1 · Anverso (foto)"
                  done={anverso != null || ladoConservado === 'anverso'}
                  active={ladoActivo === 'anverso'}
                  onClick={() => setLadoActivo('anverso')}
                />
                <LadoChip
                  label="2 · Reverso (MRZ)"
                  done={reverso != null || ladoConservado === 'reverso'}
                  active={ladoActivo === 'reverso'}
                  onClick={() => setLadoActivo('reverso')}
                />
              </div>
            )}
          </div>

          <div className={cn('flex flex-col gap-2', !layoutMovilCliente && 'sm:flex-row')}>
            <Button
              type="button"
              variant="outline"
              className={layoutMovilCliente ? 'w-full' : 'sm:w-auto'}
              size={layoutMovilCliente ? 'lg' : 'default'}
              onClick={volverATipo}
            >
              <ArrowLeft className="mr-2 h-4 w-4" />
              Cambiar tipo de documento
            </Button>
            {onVolver && (
              <Button
                type="button"
                variant="ghost"
                className={layoutMovilCliente ? 'w-full' : 'sm:w-auto'}
                size={layoutMovilCliente ? 'lg' : 'default'}
                onClick={onVolver}
              >
                Volver
              </Button>
            )}
          </div>

          <div className="space-y-3">
            <p className="section-label px-1">Fotografía</p>
            <div className={ladoActivo === 'anverso' ? '' : 'hidden'}>
              {ladoConservado === 'anverso' ? (
                <p className="rounded-lg border border-emerald-200 bg-emerald-50/50 px-3 py-2 text-sm text-emerald-800">
                  Se conserva el anverso ya enviado. Solo necesita actualizar el reverso.
                </p>
              ) : (
                <ImagenDocumentoCaptura
                  label={etiquetaLado('anverso')}
                  modo={modo}
                  varianteCaptura={esCliente ? 'identidad' : 'general'}
                  ladoCamara={ladoCamara('anverso')}
                  etiquetaDocumento={tipoEscaneo === 'pasaporte' ? 'pasaporte' : labels.tipoDocumentoCorto}
                  preview={anversoPreview}
                  inputId={`${inputId}-anverso`}
                  inputRef={anversoInputRef}
                  isDragging={!esCliente ? isDragging : undefined}
                  rotation={rotacionAnverso}
                  onRotationChange={setRotacionAnverso}
                  onFileReady={handleAnversoReady}
                  onActivar={abrirCaptura}
                  onDragEnter={!esCliente ? () => setIsDragging(true) : undefined}
                  onDragLeave={!esCliente ? () => setIsDragging(false) : undefined}
                  onDrop={!esCliente ? () => setIsDragging(false) : undefined}
                />
              )}
            </div>
            {requiereReverso && (
              <div className={ladoActivo === 'reverso' ? '' : 'hidden'}>
                {ladoConservado === 'reverso' ? (
                  <p className="rounded-lg border border-emerald-200 bg-emerald-50/50 px-3 py-2 text-sm text-emerald-800">
                    Se conserva el reverso ya enviado. Solo necesita actualizar el anverso.
                  </p>
                ) : (
                  <ImagenDocumentoCaptura
                    label={etiquetaLado('reverso')}
                    modo={modo}
                    varianteCaptura={esCliente ? 'identidad' : 'general'}
                    ladoCamara={ladoCamara('reverso')}
                    etiquetaDocumento={labels.tipoDocumentoCorto}
                    preview={reversoPreview}
                    inputId={`${inputId}-reverso`}
                    inputRef={reversoInputRef}
                    isDragging={!esCliente ? isDragging : undefined}
                    rotation={rotacionReverso}
                    onRotationChange={setRotacionReverso}
                    onFileReady={handleReversoReady}
                    onActivar={abrirCaptura}
                    onDragEnter={!esCliente ? () => setIsDragging(true) : undefined}
                    onDragLeave={!esCliente ? () => setIsDragging(false) : undefined}
                    onDrop={!esCliente ? () => setIsDragging(false) : undefined}
                  />
                )}
              </div>
            )}
          </div>

          {capturaCompleta && (
            <div className="rounded-xl border border-border bg-muted/20 p-4">
              <p className="mb-3 text-sm font-medium">Imágenes listas</p>
              <div className="flex flex-wrap gap-3">
                <Miniatura label="Anverso" url={anversoPreview} />
                {requiereReverso && <Miniatura label="Reverso" url={reversoPreview} />}
              </div>
            </div>
          )}

          {error && <p className="text-sm text-destructive">{error}</p>}

          <Button
            type="button"
            className="w-full sm:ml-auto sm:w-auto"
            size={layoutMovilCliente ? 'lg' : 'default'}
            onClick={() => void extraerDatos()}
            disabled={!capturaCompleta}
          >
            <ScanLine className="mr-2 h-4 w-4" />
            Extraer datos y continuar
          </Button>
        </section>
      )}

      {paso === 'extraccion' && (
        <div className="flex flex-col items-center gap-3 py-10 text-center">
          <Loader2 className="h-8 w-8 animate-spin text-primary" />
          <p className="text-sm text-muted-foreground">Leyendo el documento…</p>
        </div>
      )}
    </>
  );

  if (layoutMovilCliente) {
    return (
      <div className="space-y-5">
        <p className="rounded-lg border border-primary/20 bg-primary/5 px-4 py-3 text-sm text-muted-foreground">
          Fotografíe su documento con la cámara. El escaneo guiado con OCR es el único método permitido en el
          portal del cliente.
        </p>
        {contenido}
      </div>
    );
  }

  return (
    <div className="panel">
      <div className="panel-header">
        <div className="panel-header-icon">
          <ScanLine className="h-5 w-5" />
        </div>
        <div>
          <h2 className="panel-title">Documento de identidad</h2>
          <p className="text-sm text-muted-foreground">
            {esCliente
              ? 'Fotografíe su documento con la cámara del móvil. No puede subir archivos sueltos: use el escaneo guiado con OCR.'
              : 'Suba las imágenes del documento desde su equipo (JPG/PNG). Puede girarlas si es necesario.'}
          </p>
        </div>
      </div>
      <div className="space-y-6 p-6">{contenido}</div>
    </div>
  );
}

function IndicadorPasos({ pasoActual, compacto = false }: { pasoActual: PasoFlujo; compacto?: boolean }) {
  const pasos = [
    { id: 'tipo', label: 'Tipo', labelLargo: 'Tipo de documento' },
    { id: 'captura', label: 'Escaneo', labelLargo: 'Escaneo' },
    { id: 'extraccion', label: 'Revisión', labelLargo: 'Revisión de datos' },
  ] as const;

  const indiceActual =
    pasoActual === 'tipo' ? 0 : pasoActual === 'captura' ? 1 : 2;

  return (
    <ol
      className={cn(
        'rounded-xl border border-border bg-card',
        compacto ? 'grid grid-cols-3 divide-x divide-border p-0' : 'flex flex-wrap gap-2 p-3 sm:gap-4',
      )}
    >
      {pasos.map((paso, index) => (
        <li
          key={paso.id}
          className={cn(
            'flex items-center gap-2',
            compacto ? 'flex-col justify-center px-2 py-3 text-center' : 'text-xs sm:text-sm',
            index <= indiceActual ? 'text-foreground' : 'text-muted-foreground',
          )}
        >
          <span
            className={cn(
              'flex shrink-0 items-center justify-center rounded-full text-xs font-semibold',
              compacto ? 'h-7 w-7' : 'h-6 w-6',
              index < indiceActual
                ? 'bg-primary text-primary-foreground'
                : index === indiceActual
                  ? 'border-2 border-primary text-primary'
                  : 'border border-border',
            )}
          >
            {index < indiceActual ? <CheckCircle2 className="h-4 w-4" /> : index + 1}
          </span>
          <span className={cn(compacto && 'text-[10px] leading-tight font-medium')}>
            {compacto ? paso.label : paso.labelLargo}
          </span>
        </li>
      ))}
    </ol>
  );
}

function TipoCard({
  icon: Icon,
  title,
  description,
  onClick,
}: {
  icon: typeof IdCard;
  title: string;
  description: string;
  onClick: () => void;
}) {
  return (
    <button
      type="button"
      onClick={onClick}
      className="flex items-start gap-3 rounded-lg border border-border p-4 text-left transition-colors hover:border-primary hover:bg-primary/5"
    >
      <Icon className="mt-0.5 h-5 w-5 text-primary" />
      <div>
        <p className="font-medium">{title}</p>
        <p className="text-xs text-muted-foreground">{description}</p>
      </div>
    </button>
  );
}

function LadoChip({
  label,
  done,
  active,
  onClick,
}: {
  label: string;
  done: boolean;
  active: boolean;
  onClick: () => void;
}) {
  return (
    <button
      type="button"
      onClick={onClick}
      className={cn(
        'rounded-full border px-3 py-1.5 text-xs font-medium transition-colors',
        active ? 'border-primary bg-primary/10 text-primary' : 'border-border text-muted-foreground',
        done && !active && 'border-emerald-300 bg-emerald-50 text-emerald-800',
      )}
    >
      {done ? '✓ ' : ''}
      {label}
    </button>
  );
}

function Miniatura({ label, url }: { label: string; url: string | null }) {
  return (
    <div className="space-y-1 text-center">
      {url ? (
        <img src={url} alt={label} className="h-20 w-32 rounded border object-contain bg-muted/30" />
      ) : (
        <div className="h-20 w-32 rounded border bg-muted" />
      )}
      <p className="text-[10px] text-muted-foreground">{label}</p>
    </div>
  );
}
