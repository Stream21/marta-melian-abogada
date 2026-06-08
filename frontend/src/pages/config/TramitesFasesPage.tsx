import { Link } from '@tanstack/react-router';
import { ArrowLeft, Construction, Layers } from 'lucide-react';
import { ConfigBreadcrumb } from '@/components/config/ConfigBreadcrumb';
import { Button } from '@/components/ui/button';

interface TramitesFasesPageProps {
  tramiteId: string;
}

export function TramitesFasesPage({ tramiteId: _tramiteId }: TramitesFasesPageProps) {
  return (
    <div className="flex min-h-full flex-col bg-muted/30">
      <ConfigBreadcrumb section="tramites" variant="fases" />

      <main className="flex flex-1 items-center justify-center p-6 md:p-8">
        <div className="panel mx-auto w-full max-w-md p-8 text-center">
          <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-xl bg-primary/10 text-primary">
            <Layers className="h-7 w-7" />
          </div>

          <div className="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-amber-50 text-amber-700">
            <Construction className="h-5 w-5" aria-hidden />
          </div>

          <h1 className="page-title text-xl">Configuración de fases en construcción</h1>
          <p className="mt-2 text-sm text-muted-foreground leading-relaxed">
            Esta pantalla estará disponible próximamente para definir el comportamiento de las cuatro fases
            del trámite.
          </p>

          <Button variant="outline" className="mt-6" asChild>
            <Link to="/config/tramites">
              <ArrowLeft className="h-4 w-4" />
              Volver a trámites
            </Link>
          </Button>
        </div>
      </main>
    </div>
  );
}
