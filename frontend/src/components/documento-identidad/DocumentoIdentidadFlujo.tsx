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
import type { DocumentoIdentidadArchivos, DocumentoIdentidadResultado, ModoDocumentoIdentidad } from './types';
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
  /** Fotos ya capturadas al volver desde revisión (se muestran y se pueden sustituir). */
  capturasPrevias?: DocumentoIdentidadArchivos | null;
  /** Oculta el micro-stepper Tipo / Escaneo / Revisión (p. ej. carga del abogado). */
  ocultarIndicadorPasos?: boolean;
}

export function DocumentoIdentidadFlujo({
  modo,
  tipoServicio,
  onCompletado,
  extraerDocumento,
  onVolver,
  onReiniciarTipo,
  inicioRapido,
  capturasPrevias,
  ocultarIndicadorPasos = false,
}: DocumentoIdentidadFlujoProps) {
  const inputId = useId();
  const anversoInputRef = useRef<HTMLInputElement>(null);
  const reversoInputRef = useRef<HTMLInputElement>(null);
  const [paso, setPaso] = useState<PasoFlujo>(() =>
    capturasPrevias || inicioRapido ? 'captura' : 'tipo',
  );
  const [tipoEscaneo, setTipoEscaneo] = useState<TipoEscaneoDocumentoIdentidad | null>(
    () => capturasPrevias?.tipoEscaneo ?? inicioRapido?.tipoEscaneo ?? null,
  );
  const [ladoActivo, setLadoActivo] = useState<LadoCaptura>(
    () => inicioRapido?.ladoInicial ?? 'anverso',
  );
  const [anverso, setAnverso] = useState<File | null>(() => capturasPrevias?.anverso ?? null);
  const [reverso, setReverso] = useState<File | null>(() => capturasPrevias?.reverso ?? null);
  const [anversoPreview, setAnversoPreview] = useState<string | null>(null);
  const [reversoPreview, setReversoPreview] = useState<string | null>(null);
  const [rotacionAnverso, setRotacionAnverso] = useState(0);
  const [rotacionReverso, setRotacionReverso] = useState(0);
  const [error, setError] = useState<string | null>(null);
  const [isDragging, setIsDragging] = useState(false);
  const [ladoConservado, setLadoConservado] = useState<LadoCaptura | null>(
    () => inicioRapido?.conservarLado ?? null,
  );
  const [esMovil, setEsMovil] = useState(false);
  /** Tras volver desde revisión: no auto-extraer; el usuario confirma o sustituye fotos. */
  const [revisandoCapturas, setRevisandoCapturas] = useState(!!capturasPrevias);
  const autoExtractDoneRef = useRef(false);
  const previaCompletaRef = useRef(false);

  useEffect(() => {
    setEsMovil(esDispositivoMovil());
  }, []);

  // Restaura previews desde capturas previas o URLs de documento existente.
  useEffect(() => {
    const urlsCreadas: string[] = [];

    if (capturasPrevias?.anverso) {
      const url = URL.createObjectURL(capturasPrevias.anverso);
      urlsCreadas.push(url);
      setAnversoPreview(url);
    } else if (inicioRapido?.anversoUrl && inicioRapido.conservarLado === 'anverso') {
      setAnversoPreview(inicioRapido.anversoUrl);
    }

    if (capturasPrevias?.reverso) {
      const url = URL.createObjectURL(capturasPrevias.reverso);
      urlsCreadas.push(url);
      setReversoPreview(url);
    } else if (inicioRapido?.reversoUrl && inicioRapido.conservarLado === 'reverso') {
      setReversoPreview(inicioRapido.reversoUrl);
    }

    return () => {
      urlsCreadas.forEach((url) => URL.revokeObjectURL(url));
    };
    // Solo al montar / cambiar fuente inicial
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  useEffect(() => {
    if (!inicioRapido || capturasPrevias) return;
    setTipoEscaneo(inicioRapido.tipoEscaneo);
    setLadoConservado(inicioRapido.conservarLado ?? null);
    setLadoActivo(inicioRapido.ladoInicial ?? 'anverso');
    setPaso('captura');
  }, [inicioRapido, capturasPrevias]);

  const esCliente = modo === 'cliente';
  const labels = labelsDocumentoIdentidad(tipoServicio);
  const requiereReverso = tipoEscaneo === 'dni_nie';
  const totalLados = requiereReverso ? 2 : 1;
  const indiceLado = ladoActivo === 'anverso' ? 1 : 2;

  const revocarPreview = (url: string | null) => {
    if (url?.startsWith('blob:')) URL.revokeObjectURL(url);
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
    autoExtractDoneRef.current = false;
    previaCompletaRef.current = false;
  };

  const seleccionarTipo = (tipo: TipoEscaneoDocumentoIdentidad) => {
    if (tipoEscaneo === tipo && (anverso || reverso)) {
      setPaso('captura');
      return;
    }
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
    setRevisandoCapturas(false);
    autoExtractDoneRef.current = false;
    previaCompletaRef.current = false;
    if (requiereReverso) setLadoActivo('reverso');
  }, [requiereReverso]);

  const handleReversoReady = useCallback((file: File, previewUrl: string) => {
    setReversoPreview((prev) => {
      revocarPreview(prev);
      return previewUrl;
    });
    setReverso(file);
    setRevisandoCapturas(false);
    autoExtractDoneRef.current = false;
    previaCompletaRef.current = false;
  }, []);

  const capturaCompleta =
    (anverso != null || ladoConservado === 'anverso') &&
    (!requiereReverso || reverso != null || ladoConservado === 'reverso');

  const anversoListo = anverso != null || ladoConservado === 'anverso' || !!anversoPreview;
  const reversoListo = reverso != null || ladoConservado === 'reverso' || !!reversoPreview;

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
      autoExtractDoneRef.current = false;
      setPaso('captura');
      setError((e as Error).message);
    }
  };

  const volverATipo = () => {
    // Conserva las fotos: solo cambia de pantalla. Se borran al elegir otro tipo.
    setPaso('tipo');
    onReiniciarTipo?.();
  };

  const volverDesdeCaptura = () => {
    if (ladoActivo === 'reverso' && (anversoListo || !requiereReverso)) {
      setLadoActivo('anverso');
      return;
    }
    if (onVolver && !anverso && !reverso) {
      onVolver();
      return;
    }
    volverATipo();
  };

  const ladoCamara = (lado: LadoCaptura): LadoCapturaCamara =>
    tipoEscaneo === 'pasaporte' ? 'pasaporte' : lado;

  const layoutMovilCliente = esCliente && esMovil;

  useEffect(() => {
    if (paso !== 'captura' || !capturaCompleta) {
      if (!capturaCompleta) previaCompletaRef.current = false;
      return;
    }
    if (revisandoCapturas) return;
    if (previaCompletaRef.current || autoExtractDoneRef.current) return;
    autoExtractDoneRef.current = true;
    previaCompletaRef.current = true;
    void extraerDatos();
  }, [paso, capturaCompleta, revisandoCapturas]);

  const mostrarContinuarCliente = esCliente && paso === 'captura' && capturaCompleta && revisandoCapturas;

  const contenido = (
    <>
      {!esCliente && !ocultarIndicadorPasos && <IndicadorPasos pasoActual={paso} />}

      {paso === 'tipo' && (
        <section className="space-y-5">
          <h2 className="text-xl font-semibold text-foreground sm:text-lg">Elija su documento</h2>
          {(anverso || reverso) && (
            <p className="rounded-lg border border-border bg-muted/40 px-3 py-2 text-sm text-muted-foreground">
              Tiene fotos guardadas. Si elige el mismo tipo, las recuperará; si cambia de tipo, se
              sustituirán.
            </p>
          )}
          <div className={cn('grid gap-4', esCliente ? 'grid-cols-1' : 'grid-cols-1 sm:grid-cols-2')}>
            <TipoCard
              icon={IdCard}
              label={labels.tarjetaIdentidad}
              onClick={() => seleccionarTipo('dni_nie')}
              grande={esCliente}
            />
            <TipoCard
              icon={CreditCard}
              label="Pasaporte"
              onClick={() => seleccionarTipo('pasaporte')}
              grande={esCliente}
            />
          </div>
          {onVolver && (
            <Button
              type="button"
              variant="outline"
              className={cn('mt-1', layoutMovilCliente && 'w-full')}
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
          {esCliente ? (
            <>
              <header className="space-y-4">
                <div className="flex items-center justify-between gap-3">
                  <button
                    type="button"
                    onClick={volverDesdeCaptura}
                    className="inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
                  >
                    <ArrowLeft className="h-4 w-4" />
                    Volver
                  </button>
                  {requiereReverso && (
                    <div className="flex items-center gap-2" aria-label={`Cara ${indiceLado} de ${totalLados}`}>
                      {[1, 2].map((n) => (
                        <span
                          key={n}
                          className={cn(
                            'h-2 w-6 rounded-full transition-colors',
                            n <= indiceLado ? 'bg-primary' : 'bg-border',
                          )}
                        />
                      ))}
                      <span className="text-xs font-medium text-muted-foreground">
                        {indiceLado} de {totalLados}
                      </span>
                    </div>
                  )}
                </div>

                <div className="space-y-1.5 text-center sm:text-left">
                  <h2 className="text-2xl font-bold tracking-tight text-foreground">
                    {tipoEscaneo === 'pasaporte' ? 'Pasaporte' : labels.tipoDocumentoCorto}
                  </h2>
                  <p className="text-sm text-muted-foreground">
                    {tipoEscaneo === 'pasaporte'
                      ? 'Página interior con sus datos'
                      : ladoActivo === 'anverso'
                        ? 'Anverso · Cara con foto'
                        : 'Reverso · Banda MRZ'}
                  </p>
                </div>

                {(anversoListo || reversoListo) && (
                  <div className="grid grid-cols-2 gap-3">
                    <MiniaturaLado
                      label="Anverso"
                      url={anversoPreview}
                      done={anversoListo}
                      active={ladoActivo === 'anverso'}
                      onClick={() => setLadoActivo('anverso')}
                    />
                    {requiereReverso && (
                      <MiniaturaLado
                        label="Reverso"
                        url={reversoPreview}
                        done={reversoListo}
                        active={ladoActivo === 'reverso'}
                        onClick={() => setLadoActivo('reverso')}
                      />
                    )}
                  </div>
                )}
              </header>

              <div className={ladoActivo === 'anverso' ? '' : 'hidden'}>
                {ladoConservado === 'anverso' ? (
                  <div className="overflow-hidden rounded-2xl border border-border bg-card shadow-sm">
                    {anversoPreview ? (
                      <div className="bg-muted/20 px-3 py-4">
                        <img
                          src={anversoPreview}
                          alt="Anverso conservado"
                          className="mx-auto max-h-52 w-full rounded-lg object-contain"
                        />
                      </div>
                    ) : null}
                    <p className="border-t border-border px-3 py-3 text-center text-sm text-emerald-800">
                      Anverso conservado. Escanee el reverso o pulse Repetir en la miniatura para cambiarlo.
                    </p>
                  </div>
                ) : (
                  <ImagenDocumentoCaptura
                    label={etiquetaLado('anverso')}
                    modo={modo}
                    varianteCaptura="identidad"
                    uiSimplificada
                    ladoCamara={ladoCamara('anverso')}
                    etiquetaDocumento={tipoEscaneo === 'pasaporte' ? 'pasaporte' : labels.tipoDocumentoCorto}
                    preview={anversoPreview}
                    inputId={`${inputId}-anverso`}
                    inputRef={anversoInputRef}
                    rotation={rotacionAnverso}
                    onRotationChange={setRotacionAnverso}
                    onFileReady={handleAnversoReady}
                    onActivar={abrirCaptura}
                  />
                )}
              </div>
              {requiereReverso && (
                <div className={ladoActivo === 'reverso' ? '' : 'hidden'}>
                  {ladoConservado === 'reverso' ? (
                    <p className="rounded-lg border border-emerald-200 bg-emerald-50/50 px-3 py-2 text-sm text-emerald-800">
                      Reverso conservado. Escanee el anverso.
                    </p>
                  ) : (
                    <ImagenDocumentoCaptura
                      label={etiquetaLado('reverso')}
                      modo={modo}
                      varianteCaptura="identidad"
                      uiSimplificada
                      ladoCamara={ladoCamara('reverso')}
                      etiquetaDocumento={labels.tipoDocumentoCorto}
                      preview={reversoPreview}
                      inputId={`${inputId}-reverso`}
                      inputRef={reversoInputRef}
                      rotation={rotacionReverso}
                      onRotationChange={setRotacionReverso}
                      onFileReady={handleReversoReady}
                      onActivar={abrirCaptura}
                    />
                  )}
                </div>
              )}

              {error && <p className="text-sm text-destructive">{error}</p>}

              {mostrarContinuarCliente && (
                <Button
                  type="button"
                  className="w-full"
                  size="lg"
                  onClick={() => {
                    setRevisandoCapturas(false);
                    autoExtractDoneRef.current = true;
                    void extraerDatos();
                  }}
                >
                  <ScanLine className="mr-2 h-4 w-4" />
                  Continuar con estas fotos
                </Button>
              )}
            </>
          ) : (
            <>
              <header className="space-y-4">
                <div className="flex items-center justify-between gap-3">
                  <button
                    type="button"
                    onClick={volverATipo}
                    className="inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
                  >
                    <ArrowLeft className="h-4 w-4" />
                    Cambiar tipo
                  </button>
                  {requiereReverso && (
                    <div className="flex items-center gap-2" aria-label={`Cara ${indiceLado} de ${totalLados}`}>
                      {[1, 2].map((n) => (
                        <span
                          key={n}
                          className={cn(
                            'h-2 w-6 rounded-full transition-colors',
                            n <= indiceLado ? 'bg-primary' : 'bg-border',
                          )}
                        />
                      ))}
                      <span className="text-xs font-medium text-muted-foreground">
                        {indiceLado} de {totalLados}
                      </span>
                    </div>
                  )}
                </div>

                <div className="space-y-1.5">
                  <h2 className="text-xl font-bold tracking-tight text-foreground">
                    {tipoEscaneo === 'pasaporte' ? 'Pasaporte' : labels.tipoDocumentoCorto}
                  </h2>
                  <p className="text-sm text-muted-foreground">
                    {tipoEscaneo === 'pasaporte'
                      ? 'Página interior con los datos · Imagen JPG/PNG'
                      : ladoActivo === 'anverso'
                        ? 'Anverso · Cara con foto · Seleccione una imagen'
                        : 'Reverso · Banda MRZ · Seleccione una imagen'}
                  </p>
                </div>

                {(anversoListo || reversoListo) && (
                  <div className={cn('grid gap-3', requiereReverso ? 'grid-cols-2' : 'grid-cols-1')}>
                    <MiniaturaLado
                      label="Anverso"
                      url={anversoPreview}
                      done={anversoListo}
                      active={ladoActivo === 'anverso'}
                      onClick={() => setLadoActivo('anverso')}
                    />
                    {requiereReverso && (
                      <MiniaturaLado
                        label="Reverso"
                        url={reversoPreview}
                        done={reversoListo}
                        active={ladoActivo === 'reverso'}
                        onClick={() => setLadoActivo('reverso')}
                      />
                    )}
                  </div>
                )}
              </header>

              <div className={ladoActivo === 'anverso' ? '' : 'hidden'}>
                {ladoConservado === 'anverso' ? (
                  <div className="overflow-hidden rounded-2xl border border-border bg-card shadow-sm">
                    {anversoPreview ? (
                      <div className="bg-muted/20 px-3 py-4">
                        <img
                          src={anversoPreview}
                          alt="Anverso conservado"
                          className="mx-auto max-h-52 w-full rounded-lg object-contain"
                        />
                      </div>
                    ) : null}
                    <p className="border-t border-border px-3 py-3 text-center text-sm text-emerald-800">
                      Anverso conservado. Suba el reverso o pulse la miniatura para cambiarlo.
                    </p>
                  </div>
                ) : (
                  <ImagenDocumentoCaptura
                    label={etiquetaLado('anverso')}
                    modo={modo}
                    uiSimplificada
                    ladoCamara={ladoCamara('anverso')}
                    etiquetaDocumento={tipoEscaneo === 'pasaporte' ? 'pasaporte' : labels.tipoDocumentoCorto}
                    preview={anversoPreview}
                    inputId={`${inputId}-anverso`}
                    inputRef={anversoInputRef}
                    isDragging={isDragging}
                    rotation={rotacionAnverso}
                    onRotationChange={setRotacionAnverso}
                    onFileReady={handleAnversoReady}
                    onActivar={abrirCaptura}
                    onDragEnter={() => setIsDragging(true)}
                    onDragLeave={() => setIsDragging(false)}
                    onDrop={() => setIsDragging(false)}
                  />
                )}
              </div>
              {requiereReverso && (
                <div className={ladoActivo === 'reverso' ? '' : 'hidden'}>
                  {ladoConservado === 'reverso' ? (
                    <p className="rounded-lg border border-emerald-200 bg-emerald-50/50 px-3 py-2 text-sm text-emerald-800">
                      Reverso conservado. Suba el anverso.
                    </p>
                  ) : (
                    <ImagenDocumentoCaptura
                      label={etiquetaLado('reverso')}
                      modo={modo}
                      uiSimplificada
                      ladoCamara={ladoCamara('reverso')}
                      etiquetaDocumento={labels.tipoDocumentoCorto}
                      preview={reversoPreview}
                      inputId={`${inputId}-reverso`}
                      inputRef={reversoInputRef}
                      isDragging={isDragging}
                      rotation={rotacionReverso}
                      onRotationChange={setRotacionReverso}
                      onFileReady={handleReversoReady}
                      onActivar={abrirCaptura}
                      onDragEnter={() => setIsDragging(true)}
                      onDragLeave={() => setIsDragging(false)}
                      onDrop={() => setIsDragging(false)}
                    />
                  )}
                </div>
              )}

              {error && <p className="text-sm text-destructive">{error}</p>}
            </>
          )}
        </section>
      )}

      {paso === 'extraccion' && (
        <div className="flex flex-col items-center gap-3 py-10 text-center">
          <Loader2 className="h-8 w-8 animate-spin text-primary" />
          <p className="text-sm text-muted-foreground">
            {esCliente ? 'Leyendo el documento…' : 'Procesando las imágenes…'}
          </p>
        </div>
      )}
    </>
  );

  // En portal cliente el shell ya aporta el panel: sin cabecera ni texto redundante.
  // En carga del abogado (sin stepper) también va embebido en otro contenedor.
  if (esCliente || ocultarIndicadorPasos) {
    return <div className="space-y-5">{contenido}</div>;
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
            Suba las imágenes del documento desde su equipo (JPG/PNG). Puede girarlas si es necesario.
          </p>
        </div>
      </div>
      <div className="space-y-6 p-6">{contenido}</div>
    </div>
  );
}

function IndicadorPasos({ pasoActual }: { pasoActual: PasoFlujo }) {
  const pasos = [
    { id: 'tipo', labelLargo: 'Tipo de documento' },
    { id: 'captura', labelLargo: 'Escaneo' },
    { id: 'extraccion', labelLargo: 'Revisión de datos' },
  ] as const;

  const indiceActual =
    pasoActual === 'tipo' ? 0 : pasoActual === 'captura' ? 1 : 2;

  return (
    <ol className="grid grid-cols-3 divide-x divide-border overflow-hidden rounded-xl border border-border bg-card">
      {pasos.map((paso, index) => (
        <li
          key={paso.id}
          className={cn(
            'flex flex-col items-center justify-center gap-1.5 px-2 py-3 text-center',
            index <= indiceActual ? 'text-foreground' : 'text-muted-foreground',
          )}
        >
          <span
            className={cn(
              'flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold',
              index < indiceActual
                ? 'bg-primary text-primary-foreground'
                : index === indiceActual
                  ? 'border-2 border-primary text-primary'
                  : 'border border-border',
            )}
          >
            {index < indiceActual ? <CheckCircle2 className="h-4 w-4" /> : index + 1}
          </span>
          <span className="text-[11px] font-medium leading-tight sm:text-xs">{paso.labelLargo}</span>
        </li>
      ))}
    </ol>
  );
}

function TipoCard({
  icon: Icon,
  label,
  onClick,
  grande = false,
}: {
  icon: typeof IdCard;
  label: string;
  onClick: () => void;
  grande?: boolean;
}) {
  return (
    <button
      type="button"
      onClick={onClick}
      className={cn(
        'group flex w-full items-center text-left transition-all cursor-pointer',
        'rounded-xl border-2 border-primary/30 bg-card',
        'hover:border-primary hover:bg-primary/5 hover:shadow-sm',
        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
        'active:scale-[0.99]',
        grande ? 'min-h-[4.5rem] gap-5 px-5 py-6' : 'gap-4 p-5',
      )}
    >
      <span
        className={cn(
          'flex shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary transition-colors',
          'group-hover:bg-primary group-hover:text-primary-foreground',
          grande ? 'h-14 w-14' : 'h-12 w-12',
        )}
      >
        <Icon className={grande ? 'h-7 w-7' : 'h-6 w-6'} />
      </span>
      <span
        className={cn(
          'min-w-0 font-semibold leading-snug text-foreground',
          grande ? 'text-lg' : 'text-sm sm:text-base',
        )}
      >
        {label}
      </span>
    </button>
  );
}

function MiniaturaLado({
  label,
  url,
  done,
  active,
  onClick,
}: {
  label: string;
  url: string | null;
  done: boolean;
  active: boolean;
  onClick: () => void;
}) {
  return (
    <button
      type="button"
      onClick={onClick}
      className={cn(
        'overflow-hidden rounded-xl border text-left transition-colors',
        active ? 'border-primary ring-2 ring-primary/30' : 'border-border',
        done ? 'bg-card' : 'bg-muted/40',
      )}
    >
      <div className="flex aspect-[1.6] items-center justify-center bg-muted/30">
        {url ? (
          <img src={url} alt={label} className="h-full w-full object-cover" />
        ) : (
          <span className="text-xs text-muted-foreground">Pendiente</span>
        )}
      </div>
      <div className="flex items-center justify-between gap-2 px-2.5 py-2">
        <span className="text-xs font-semibold text-foreground">{label}</span>
        {done && <CheckCircle2 className="h-3.5 w-3.5 shrink-0 text-emerald-600" />}
      </div>
    </button>
  );
}
