import { useEffect, useState, type FormEvent } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Building2, ChevronDown, FileText, Landmark, Save, Upload } from 'lucide-react';
import { SignaturePad } from '@/components/signature/SignaturePad';
import { api, fetchAuthenticatedAsset, getDespachoAssetPath } from '@/api/client';
import { DespachoAssetPreview } from '@/components/config/despacho/DespachoAssetPreview';
import { DespachoConfigSection } from '@/components/config/despacho/DespachoConfigSection';
import { DespachoMembretePreview } from '@/components/config/despacho/DespachoMembretePreview';
import { TwilioIntegracionPanel } from '@/components/config/TwilioIntegracionPanel';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { cn } from '@/lib/utils';

type DespachoConfigTab = 'identidad' | 'documentos' | 'cobros';

const TAB_ITEMS: Array<{ value: DespachoConfigTab; label: string; icon: typeof Building2 }> = [
  { value: 'identidad', label: 'Identidad profesional', icon: Building2 },
  { value: 'documentos', label: 'Documentos', icon: FileText },
  { value: 'cobros', label: 'Datos bancarios', icon: Landmark },
];

export function DespachoConfigForm() {
  const queryClient = useQueryClient();
  const [activeTab, setActiveTab] = useState<DespachoConfigTab>('identidad');
  const [nombreFirma, setNombreFirma] = useState('');
  const [nombreLetrada, setNombreLetrada] = useState('');
  const [numColegiado, setNumColegiado] = useState('');
  const [direccion, setDireccion] = useState('');
  const [ciudad, setCiudad] = useState('');
  const [subtituloProfesional, setSubtituloProfesional] = useState('');
  const [telefono, setTelefono] = useState('');
  const [email, setEmail] = useState('');
  const [web, setWeb] = useState('');
  const [nif, setNif] = useState('');
  const [colegioAbogados, setColegioAbogados] = useState('');
  const [iban, setIban] = useState('');
  const [entidadBancaria, setEntidadBancaria] = useState('');
  const [titularCuenta, setTitularCuenta] = useState('');
  const [cabeceraHtml, setCabeceraHtml] = useState('');
  const [pieHtml, setPieHtml] = useState('');
  const [logoPreview, setLogoPreview] = useState<string | null>(null);
  const [selloPreview, setSelloPreview] = useState<string | null>(null);

  const { data, isLoading } = useQuery({
    queryKey: ['despacho-config'],
    queryFn: () => api.getDespachoConfig(),
  });

  useEffect(() => {
    if (!data) return;
    setNombreFirma(data.nombreFirma);
    setNombreLetrada(data.nombreLetrada);
    setNumColegiado(data.numColegiado);
    setDireccion(data.direccion);
    setCiudad(data.ciudad);
    setSubtituloProfesional(data.subtituloProfesional ?? '');
    setTelefono(data.telefono ?? '');
    setEmail(data.email ?? '');
    setWeb(data.web ?? '');
    setNif(data.nif ?? '');
    setColegioAbogados(data.colegioAbogados ?? '');
    setIban(data.iban ?? '');
    setEntidadBancaria(data.entidadBancaria ?? '');
    setTitularCuenta(data.titularCuenta ?? '');
    setCabeceraHtml(data.cabeceraHtml ?? '');
    setPieHtml(data.pieHtml ?? '');
  }, [data]);

  useEffect(() => {
    let active = true;
    let logoObjectUrl: string | null = null;
    let selloObjectUrl: string | null = null;

    async function loadAssets() {
      if (data?.logoUrl) {
        logoObjectUrl = await fetchAuthenticatedAsset(
          getDespachoAssetPath('logo'),
          data.updatedAt ?? undefined,
        );
        if (active) {
          setLogoPreview((prev) => {
            if (prev?.startsWith('blob:')) URL.revokeObjectURL(prev);
            return logoObjectUrl;
          });
        }
      } else if (active) {
        setLogoPreview((prev) => {
          if (prev?.startsWith('blob:')) URL.revokeObjectURL(prev);
          return null;
        });
      }

      if (data?.selloUrl) {
        selloObjectUrl = await fetchAuthenticatedAsset(
          getDespachoAssetPath('sello'),
          data.updatedAt ?? undefined,
        );
        if (active) {
          setSelloPreview((prev) => {
            if (prev?.startsWith('blob:')) URL.revokeObjectURL(prev);
            return selloObjectUrl;
          });
        }
      } else if (active) {
        setSelloPreview((prev) => {
          if (prev?.startsWith('blob:')) URL.revokeObjectURL(prev);
          return null;
        });
      }
    }

    void loadAssets();

    return () => {
      active = false;
    };
  }, [data?.logoUrl, data?.selloUrl, data?.updatedAt]);

  const saveMutation = useMutation({
    mutationFn: () =>
      api.putDespachoConfig({
        nombreFirma: nombreFirma.trim(),
        nombreLetrada: nombreLetrada.trim(),
        numColegiado: numColegiado.trim(),
        direccion: direccion.trim(),
        ciudad: ciudad.trim(),
        subtituloProfesional: subtituloProfesional.trim(),
        telefono: telefono.trim(),
        email: email.trim(),
        web: web.trim(),
        nif: nif.trim(),
        colegioAbogados: colegioAbogados.trim(),
        iban: iban.trim(),
        entidadBancaria: entidadBancaria.trim(),
        titularCuenta: titularCuenta.trim(),
        cabeceraHtml: cabeceraHtml.trim() || null,
        pieHtml: pieHtml.trim() || null,
      }),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['despacho-config'] });
    },
  });

  const logoMutation = useMutation({
    mutationFn: (file: File) => api.uploadDespachoLogo(file),
    onMutate: (file) => {
      setLogoPreview((prev) => {
        if (prev?.startsWith('blob:')) URL.revokeObjectURL(prev);
        return URL.createObjectURL(file);
      });
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['despacho-config'] });
    },
    onError: () => {
      void queryClient.invalidateQueries({ queryKey: ['despacho-config'] });
    },
  });

  const selloMutation = useMutation({
    mutationFn: (file: File) => api.uploadDespachoSello(file),
    onMutate: (file) => {
      setSelloPreview((prev) => {
        if (prev?.startsWith('blob:')) URL.revokeObjectURL(prev);
        return URL.createObjectURL(file);
      });
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['despacho-config'] });
    },
    onError: () => {
      void queryClient.invalidateQueries({ queryKey: ['despacho-config'] });
    },
  });

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    saveMutation.mutate();
  };

  const isPending =
    isLoading || saveMutation.isPending || logoMutation.isPending || selloMutation.isPending;

  const saveError =
    saveMutation.error instanceof Error
      ? saveMutation.error.message
      : logoMutation.error instanceof Error
        ? logoMutation.error.message
        : selloMutation.error instanceof Error
          ? selloMutation.error.message
          : null;

  const fieldGrid = 'grid gap-4 md:grid-cols-2';

  return (
    <form onSubmit={handleSubmit} className="panel">
      <div className="panel-header">
        <div className="panel-header-icon">
          <Building2 className="h-5 w-5" />
        </div>
        <div>
          <h2 className="panel-title">Configuración del despacho</h2>
          <p className="text-sm text-muted-foreground">
            Datos del bufete para hojas de encargo, designaciones, RGPD y el resto de documentos generados.
          </p>
        </div>
      </div>

      <div className="space-y-6 p-6">
        {saveError && (
          <p
            className="rounded-lg border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive"
            role="alert"
          >
            {saveError}
          </p>
        )}

        <Tabs value={activeTab} onValueChange={(v) => setActiveTab(v as DespachoConfigTab)}>
          <TabsList className="h-auto w-full flex-wrap justify-start gap-1 bg-muted/50 p-1">
            {TAB_ITEMS.map(({ value, label, icon: Icon }) => (
              <TabsTrigger key={value} value={value} className="gap-2 data-[state=active]:text-primary">
                <Icon className="h-4 w-4 shrink-0" />
                {label}
              </TabsTrigger>
            ))}
          </TabsList>

          <TabsContent value="identidad" className="mt-6 space-y-6">
            <DespachoConfigSection
              title="Datos de la firma"
              description="Identidad que aparece en la cabecera de los documentos y en las variables de las plantillas."
            >
              <div className={fieldGrid}>
                <div className="space-y-2 md:col-span-2">
                  <Label htmlFor="despacho-nombre-firma">Nombre de la firma</Label>
                  <Input
                    id="despacho-nombre-firma"
                    value={nombreFirma}
                    onChange={(e) => setNombreFirma(e.target.value)}
                    disabled={isPending}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="despacho-nombre-letrada">Nombre de la letrada</Label>
                  <Input
                    id="despacho-nombre-letrada"
                    value={nombreLetrada}
                    onChange={(e) => setNombreLetrada(e.target.value)}
                    disabled={isPending}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="despacho-subtitulo">Subtítulo profesional</Label>
                  <Input
                    id="despacho-subtitulo"
                    value={subtituloProfesional}
                    onChange={(e) => setSubtituloProfesional(e.target.value)}
                    placeholder="Abogada y Mediadora"
                    disabled={isPending}
                  />
                </div>
              </div>
            </DespachoConfigSection>

            <DespachoConfigSection
              title="Colegiación y fiscal"
              description="Datos del colegio profesional y NIF que se insertan en pies de página y cláusulas."
            >
              <div className={fieldGrid}>
                <div className="space-y-2">
                  <Label htmlFor="despacho-num-colegiado">Número de colegiado</Label>
                  <Input
                    id="despacho-num-colegiado"
                    value={numColegiado}
                    onChange={(e) => setNumColegiado(e.target.value)}
                    disabled={isPending}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="despacho-nif">NIF</Label>
                  <Input
                    id="despacho-nif"
                    value={nif}
                    onChange={(e) => setNif(e.target.value)}
                    disabled={isPending}
                  />
                </div>
                <div className="space-y-2 md:col-span-2">
                  <Label htmlFor="despacho-colegio">Colegio de abogados</Label>
                  <Input
                    id="despacho-colegio"
                    value={colegioAbogados}
                    onChange={(e) => setColegioAbogados(e.target.value)}
                    disabled={isPending}
                  />
                </div>
              </div>
            </DespachoConfigSection>

            <DespachoConfigSection
              title="Domicilio profesional"
              description="Dirección y ciudad usadas en cabecera y pie de los documentos ([[DOMICILIO_DESPACHO]], [[CIUDAD_DESPACHO]])."
            >
              <div className={fieldGrid}>
                <div className="space-y-2">
                  <Label htmlFor="despacho-ciudad">Ciudad</Label>
                  <Input
                    id="despacho-ciudad"
                    value={ciudad}
                    onChange={(e) => setCiudad(e.target.value)}
                    disabled={isPending}
                  />
                </div>
                <div className="space-y-2 md:col-span-2">
                  <Label htmlFor="despacho-direccion">Domicilio profesional</Label>
                  <Input
                    id="despacho-direccion"
                    value={direccion}
                    onChange={(e) => setDireccion(e.target.value)}
                    disabled={isPending}
                  />
                </div>
              </div>
            </DespachoConfigSection>
          </TabsContent>

          <TabsContent value="documentos" className="mt-6 space-y-6">
            <DespachoConfigSection
              title="Logotipo y firma"
              description="Logotipo corporativo para el membrete y firma manuscrita de la letrada, con fondo transparente para los documentos."
            >
              <div className="grid gap-6 md:grid-cols-2">
                <div className="space-y-3">
                  <DespachoAssetPreview
                    label="Logotipo corporativo"
                    imageUrl={logoPreview}
                    emptyLabel="Sin logotipo subido"
                  />
                  <label className="inline-flex cursor-pointer">
                    <input
                      type="file"
                      accept="image/png,image/jpeg,image/webp,image/gif"
                      className="sr-only"
                      disabled={logoMutation.isPending}
                      onChange={(e) => {
                        const file = e.target.files?.[0];
                        if (file) logoMutation.mutate(file);
                        e.target.value = '';
                      }}
                    />
                    <span className="inline-flex h-9 items-center justify-center gap-2 rounded-md border border-input bg-background px-3 text-sm font-medium transition-colors hover:bg-muted">
                      <Upload className="h-4 w-4" />
                      {logoPreview ? 'Cambiar logotipo' : 'Subir logotipo'}
                    </span>
                  </label>
                </div>

                <SignaturePad
                  title="Firma de la letrada"
                  savedImageUrl={selloPreview}
                  isSaving={selloMutation.isPending}
                  disabled={isPending}
                  filename="firma-letrada.png"
                  onSave={(file) => selloMutation.mutate(file)}
                />
              </div>
            </DespachoConfigSection>

            <DespachoConfigSection
              title="Contacto en el pie de página"
              description="Teléfono, email y web del pie de página en hojas de encargo, designaciones y demás documentos."
            >
              <div className={fieldGrid}>
                <div className="space-y-2">
                  <Label htmlFor="despacho-telefono">Teléfono</Label>
                  <Input
                    id="despacho-telefono"
                    value={telefono}
                    onChange={(e) => setTelefono(e.target.value)}
                    disabled={isPending}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="despacho-email">Email</Label>
                  <Input
                    id="despacho-email"
                    type="email"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    disabled={isPending}
                  />
                </div>
                <div className="space-y-2 md:col-span-2">
                  <Label htmlFor="despacho-web">Sitio web</Label>
                  <Input
                    id="despacho-web"
                    value={web}
                    onChange={(e) => setWeb(e.target.value)}
                    disabled={isPending}
                  />
                </div>
              </div>
            </DespachoConfigSection>

            <DespachoConfigSection
              title="Cabecera y pie de página"
              description="Aspecto común de todos los documentos generados (hoja de encargo, designación, RGPD, etc.)."
            >
              <DespachoMembretePreview
                nombreFirma={nombreFirma}
                subtituloProfesional={subtituloProfesional}
                direccion={direccion}
                web={web}
                email={email}
                telefono={telefono}
                colegioAbogados={colegioAbogados}
                nif={nif}
                cabeceraHtml={cabeceraHtml}
                pieHtml={pieHtml}
                logoUrl={logoPreview}
              />
            </DespachoConfigSection>

            <details className="group rounded-lg border border-border bg-muted/20">
              <summary className="flex cursor-pointer list-none items-center justify-between gap-2 p-5 font-medium text-foreground [&::-webkit-details-marker]:hidden">
                <span>HTML avanzado de cabecera y pie</span>
                <ChevronDown className="h-4 w-4 shrink-0 text-muted-foreground transition-transform group-open:rotate-180" />
              </summary>
              <div className="space-y-4 border-t border-border px-5 pb-5 pt-4">
                <p className="text-sm text-muted-foreground">
                  Sustituye la cabecera y el pie automáticos. Use variables como{' '}
                  <code className="text-xs">[[NOMBRE_FIRMA]]</code>,{' '}
                  <code className="text-xs">[[LOGO_DESPACHO]]</code> o{' '}
                  <code className="text-xs">[[WEB_DESPACHO]]</code>.
                </p>
                <div className="grid gap-6 lg:grid-cols-2">
                  <div className="space-y-2">
                    <Label htmlFor="despacho-cabecera-html">Cabecera HTML</Label>
                    <textarea
                      id="despacho-cabecera-html"
                      value={cabeceraHtml}
                      onChange={(e) => setCabeceraHtml(e.target.value)}
                      rows={6}
                      placeholder="HTML personalizado para la cabecera de cada página…"
                      disabled={isPending}
                      className={cn(
                        'flex min-h-[120px] w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-xs',
                        'ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                        'disabled:cursor-not-allowed disabled:opacity-50',
                      )}
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="despacho-pie-html">Pie HTML</Label>
                    <textarea
                      id="despacho-pie-html"
                      value={pieHtml}
                      onChange={(e) => setPieHtml(e.target.value)}
                      rows={6}
                      placeholder="HTML personalizado para el pie de cada página…"
                      disabled={isPending}
                      className={cn(
                        'flex min-h-[120px] w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-xs',
                        'ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                        'disabled:cursor-not-allowed disabled:opacity-50',
                      )}
                    />
                  </div>
                </div>
              </div>
            </details>
          </TabsContent>

          <TabsContent value="cobros" className="mt-6 space-y-6">
            <TwilioIntegracionPanel />
            <DespachoConfigSection
              title="Cuenta para honorarios"
              description="Datos bancarios insertados en hojas de encargo mediante [[IBAN]], [[ENTIDAD_BANCARIA]] y [[TITULAR_CUENTA]]."
            >
              <div className={fieldGrid}>
                <div className="space-y-2 md:col-span-2">
                  <Label htmlFor="despacho-titular">Titular de la cuenta</Label>
                  <Input
                    id="despacho-titular"
                    value={titularCuenta}
                    onChange={(e) => setTitularCuenta(e.target.value)}
                    disabled={isPending}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="despacho-entidad">Entidad bancaria</Label>
                  <Input
                    id="despacho-entidad"
                    value={entidadBancaria}
                    onChange={(e) => setEntidadBancaria(e.target.value)}
                    disabled={isPending}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="despacho-iban">IBAN</Label>
                  <Input
                    id="despacho-iban"
                    value={iban}
                    onChange={(e) => setIban(e.target.value)}
                    disabled={isPending}
                    className="font-mono text-sm"
                  />
                </div>
              </div>
            </DespachoConfigSection>
          </TabsContent>
        </Tabs>
      </div>

      <Separator />

      <div className="flex flex-col gap-3 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
        <p className="text-xs text-muted-foreground">
          Los cambios de texto requieren guardar. Logotipo y sello se suben al instante.
        </p>
        <Button type="submit" disabled={isPending} className="shrink-0">
          <Save className="h-4 w-4" />
          Guardar configuración
        </Button>
      </div>
    </form>
  );
}
