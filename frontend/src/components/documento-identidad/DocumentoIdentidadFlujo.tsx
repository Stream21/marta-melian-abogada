import { useCallback, useId, useRef, useState } from 'react';
import {
  CreditCard,
  IdCard,
  Loader2,
  ScanLine,
  CheckCircle2,
} from 'lucide-react';
import { api, type ExtraerDocumentoIdentidadResponse, type TipoEscaneoDocumentoIdentidad } from '@/api/client';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { DocumentoIdentidadResultado, ModoDocumentoIdentidad } from './types';
import { DocumentoLadoGuia } from './DocumentoLadoGuia';
import { ImagenDocumentoCaptura } from './ImagenDocumentoCaptura';

type PasoFlujo = 'tipo' | 'captura' | 'extraccion';
type LadoCaptura = 'anverso' | 'reverso';

type ExtraerDocumentoFn = (input: {
  tipoEscaneo: TipoEscaneoDocumentoIdentidad;
  anverso: File;
  reverso: File | null;
}) => Promise<ExtraerDocumentoIdentidadResponse>;

interface DocumentoIdentidadFlujoProps {
  modo: ModoDocumentoIdentidad;
  onCompletado: (resultado: DocumentoIdentidadResultado) => void;
  extraerDocumento?: ExtraerDocumentoFn;
}

export function DocumentoIdentidadFlujo({
  modo,
  onCompletado,
  extraerDocumento,
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

  const esCliente = modo === 'cliente';
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

  const capturaCompleta = anverso != null && (!requiereReverso || reverso != null);

  const abrirCaptura = () => {
    if (ladoActivo === 'anverso') anversoInputRef.current?.click();
    else reversoInputRef.current?.click();
  };

  const extraerDatos = async () => {
    if (!tipoEscaneo || !anverso) return;
    setError(null);
    setPaso('extraccion');
    try {
      const extraer = extraerDocumento ?? api.extraerDocumentoIdentidad.bind(api);
      const resultado = await extraer({
        tipoEscaneo,
        anverso,
        reverso: requiereReverso ? reverso : null,
      });
      onCompletado({
        archivos: {
          tipoEscaneo,
          anverso,
          reverso: requiereReverso ? reverso : null,
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
  };

  const ladoGuia =
    tipoEscaneo === 'pasaporte' ? 'pasaporte' : ladoActivo;

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
              ? 'Indique su documento y escanéelo con la cámara en horizontal. Después revisará los datos extraídos.'
              : 'Indique el documento y suba una imagen en horizontal. Puede girarla si la ha capturado de lado.'}
          </p>
        </div>
      </div>

      <div className="space-y-6 p-6">
        <IndicadorPasos pasoActual={paso} />

        {paso === 'tipo' && (
          <section>
            <p className="section-label mb-3">¿Qué documento de identidad tiene?</p>
            <div className="grid gap-3 sm:grid-cols-2">
              <TipoCard
                icon={IdCard}
                title="DNI / NIE"
                description="Anverso (foto) y reverso (MRZ)"
                onClick={() => seleccionarTipo('dni_nie')}
              />
              <TipoCard
                icon={CreditCard}
                title="Pasaporte"
                description="Página interior con sus datos"
                onClick={() => seleccionarTipo('pasaporte')}
              />
            </div>
          </section>
        )}

        {paso === 'captura' && tipoEscaneo && (
          <section className="space-y-4">
            <div className="flex flex-wrap items-center justify-between gap-2">
              <div>
                <p className="section-label">
                  {tipoEscaneo === 'pasaporte' ? 'Pasaporte' : 'DNI / NIE'}
                </p>
                <p className="text-sm text-muted-foreground">
                  {requiereReverso
                    ? `Paso ${indiceLado} de ${totalLados}: ${etiquetaLado(ladoActivo)}`
                    : etiquetaLado('anverso')}
                </p>
              </div>
              <Button type="button" variant="ghost" size="sm" onClick={volverATipo}>
                Cambiar tipo
              </Button>
            </div>

            {requiereReverso && (
              <div className="flex flex-wrap gap-2">
                <LadoChip
                  label="1 · Anverso (foto)"
                  done={anverso != null}
                  active={ladoActivo === 'anverso'}
                  onClick={() => setLadoActivo('anverso')}
                />
                <LadoChip
                  label="2 · Reverso (MRZ)"
                  done={reverso != null}
                  active={ladoActivo === 'reverso'}
                  onClick={() => setLadoActivo('reverso')}
                />
              </div>
            )}

            <DocumentoLadoGuia lado={ladoGuia} />

            <div className={ladoActivo === 'anverso' ? '' : 'hidden'}>
              <ImagenDocumentoCaptura
                label={etiquetaLado('anverso')}
                modo={modo}
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
            </div>
            {requiereReverso && (
              <div className={ladoActivo === 'reverso' ? '' : 'hidden'}>
                <ImagenDocumentoCaptura
                  label={etiquetaLado('reverso')}
                  modo={modo}
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
              </div>
            )}

            {capturaCompleta && (
              <div className="rounded-lg border border-border bg-muted/20 p-4">
                <p className="mb-3 text-sm font-medium">Imágenes listas</p>
                <div className="flex flex-wrap gap-3">
                  <Miniatura label="Anverso" url={anversoPreview} />
                  {requiereReverso && <Miniatura label="Reverso" url={reversoPreview} />}
                </div>
              </div>
            )}

            {error && <p className="text-sm text-destructive">{error}</p>}

            <div className="flex justify-end">
              <Button type="button" onClick={() => void extraerDatos()} disabled={!capturaCompleta}>
                <ScanLine className="mr-2 h-4 w-4" />
                Extraer datos y continuar
              </Button>
            </div>
          </section>
        )}

        {paso === 'extraccion' && (
          <div className="flex flex-col items-center gap-3 py-10 text-center">
            <Loader2 className="h-8 w-8 animate-spin text-primary" />
            <p className="text-sm text-muted-foreground">Leyendo el documento…</p>
          </div>
        )}
      </div>
    </div>
  );
}

function IndicadorPasos({ pasoActual }: { pasoActual: PasoFlujo }) {
  const pasos = [
    { id: 'tipo', label: 'Tipo de documento' },
    { id: 'captura', label: 'Escaneo' },
    { id: 'extraccion', label: 'Revisión de datos' },
  ] as const;

  const indiceActual =
    pasoActual === 'tipo' ? 0 : pasoActual === 'captura' ? 1 : 2;

  return (
    <ol className="flex flex-wrap gap-2 sm:gap-4">
      {pasos.map((paso, index) => (
        <li
          key={paso.id}
          className={cn(
            'flex items-center gap-2 text-xs sm:text-sm',
            index <= indiceActual ? 'text-foreground' : 'text-muted-foreground',
          )}
        >
          <span
            className={cn(
              'flex h-6 w-6 items-center justify-center rounded-full text-xs font-semibold',
              index < indiceActual
                ? 'bg-primary text-primary-foreground'
                : index === indiceActual
                  ? 'border-2 border-primary text-primary'
                  : 'border border-border',
            )}
          >
            {index < indiceActual ? <CheckCircle2 className="h-4 w-4" /> : index + 1}
          </span>
          {paso.label}
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
