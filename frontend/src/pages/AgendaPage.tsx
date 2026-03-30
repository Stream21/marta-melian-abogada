import { Calendar } from 'lucide-react';

export function AgendaPage() {
  return (
    <div className="flex flex-col items-center justify-center py-24 text-muted-foreground">
      <Calendar className="mb-3 h-12 w-12" />
      <h2 className="text-lg font-medium text-foreground">Agenda</h2>
      <p className="mt-1 text-sm">Próximamente disponible</p>
    </div>
  );
}
