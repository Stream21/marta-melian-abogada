import { ChevronRight } from 'lucide-react';
import { StatsCards } from '@/components/dashboard/StatsCards';
import { VencimientosTable } from '@/components/dashboard/VencimientosTable';
import { ActividadReciente } from '@/components/dashboard/ActividadReciente';

export function DashboardPage() {
  return (
    <div className="flex flex-col min-h-full bg-gray-50">
      <div className="bg-white border-b border-gray-200 px-6 md:px-8 py-2 shrink-0">
        <nav className="flex items-center gap-2 text-[12px]">
          <span className="text-gray-400">Dashboard</span>
          <ChevronRight className="h-3.5 w-3.5 text-gray-300" />
          <span className="text-gray-600 font-medium">Inicio</span>
        </nav>
      </div>

      <main className="flex-1 p-6 md:p-8">
        <div className="max-w-[1400px] mx-auto flex flex-col gap-8">
          <StatsCards />
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 items-stretch">
            <div className="lg:col-span-2 flex flex-col h-full">
              <VencimientosTable />
            </div>
            <div className="lg:col-span-1 flex flex-col h-full">
              <ActividadReciente />
            </div>
          </div>
        </div>
      </main>
    </div>
  );
}
