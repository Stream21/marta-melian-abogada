import { History, PenLine, AlertTriangle, Banknote, CheckCircle, CalendarCheck } from 'lucide-react';
import { cn } from '@/lib/utils';

interface Actividad {
  icon: React.ElementType;
  iconBg: string;
  iconColor: string;
  text: React.ReactNode;
  sub?: string;
  time: string;
}

const actividades: Actividad[] = [
  {
    icon: PenLine,
    iconBg: 'bg-blue-100',
    iconColor: 'text-blue-700',
    text: (
      <>
        <span className="font-bold">Carlos Ruiz</span> firmó Hoja de Encargo
      </>
    ),
    time: 'Hace 15 min',
  },
  {
    icon: AlertTriangle,
    iconBg: 'bg-orange-100',
    iconColor: 'text-orange-700',
    text: <span className="font-bold">Notificación de resolución recibida</span>,
    sub: 'Expediente #3302 - Extranjería',
    time: 'Hace 1h',
  },
  {
    icon: Banknote,
    iconBg: 'bg-emerald-100',
    iconColor: 'text-emerald-700',
    text: (
      <>
        <span className="font-bold">Ana Belén</span> realizó el pago de cuota 2/12
      </>
    ),
    time: 'Hace 2h',
  },
  {
    icon: CheckCircle,
    iconBg: 'bg-indigo-100',
    iconColor: 'text-indigo-700',
    text: (
      <>
        Documentos validados por <span className="font-bold">Administrativo</span>
      </>
    ),
    time: 'Hace 3h',
  },
  {
    icon: CalendarCheck,
    iconBg: 'bg-purple-100',
    iconColor: 'text-purple-700',
    text: <span className="font-bold">Nueva cita agendada por cliente</span>,
    sub: 'Mañana a las 10:30 - Presencial',
    time: 'Hace 5h',
  },
];

export function ActividadReciente() {
  return (
    <div className="bg-white rounded-xl border border-gray-100 shadow-sm flex flex-col h-full overflow-hidden">
      <div className="flex items-center justify-between p-6 border-b border-gray-100">
        <div className="flex items-center gap-3">
          <div className="p-2 bg-blue-50 rounded-lg text-[#1e3a8a]">
            <History className="h-5 w-5" />
          </div>
          <h3 className="text-gray-900 text-lg font-bold leading-tight">Actividad Reciente</h3>
        </div>
      </div>

      <div className="p-6 flex-1 overflow-y-auto">
        <div className="relative">
          <div className="absolute left-4 top-2 bottom-2 w-px bg-gray-100" />
          <div className="flex flex-col gap-6">
            {actividades.map((a, i) => (
              <div key={i} className="flex gap-4 relative">
                <div
                  className={cn(
                    'size-8 rounded-full flex items-center justify-center shrink-0 z-10 border-2 border-white ring-1 ring-gray-100 shadow-sm',
                    a.iconBg,
                    a.iconColor,
                  )}
                >
                  <a.icon className="h-4 w-4" />
                </div>
                <div className="flex flex-col pb-2">
                  <p className="text-sm text-gray-900">{a.text}</p>
                  {a.sub && <p className="text-[11px] text-gray-500 mt-1">{a.sub}</p>}
                  <span className="text-[10px] text-gray-400 mt-1.5 font-bold uppercase tracking-wide">
                    {a.time}
                  </span>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>

      <div className="p-4 border-t border-gray-100 bg-gray-50/50 text-center">
        <button className="text-sm text-[#1e3a8a] font-bold hover:text-blue-900">
          Ver todas las notificaciones
        </button>
      </div>
    </div>
  );
}
