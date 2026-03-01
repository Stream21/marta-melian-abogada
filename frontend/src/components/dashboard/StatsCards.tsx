import { TrendingUp, AlertCircle, ArrowUp, Folders, ReceiptText, Banknote } from 'lucide-react';

interface StatCard {
  label: string;
  value: string;
  badge: string;
  badgeColor: string;
  subtext: string;
  icon: React.ElementType;
  iconColor: string;
  extra?: React.ReactNode;
}

const cards: StatCard[] = [
  {
    label: 'Expedientes Activos',
    value: '142',
    badge: '+5%',
    badgeColor: 'text-emerald-600 bg-emerald-50 border-emerald-100',
    icon: Folders,
    iconColor: 'text-[#1e3a8a]',
    subtext: '70% de capacidad operativa',
    extra: (
      <div className="mt-2">
        <div className="w-full bg-gray-100 rounded-full h-1.5">
          <div className="bg-[#1e3a8a] h-1.5 rounded-full" style={{ width: '70%' }} />
        </div>
      </div>
    ),
  },
  {
    label: 'Requerimientos Pendientes',
    value: '8',
    badge: 'Urgente',
    badgeColor: 'text-red-600 bg-red-50 border-red-100',
    icon: ReceiptText,
    iconColor: 'text-red-500',
    subtext: '',
    extra: (
      <p className="text-[11px] text-red-600 font-bold flex items-center gap-1.5 mt-2">
        <span className="h-1.5 w-1.5 rounded-full bg-red-500 inline-block" />
        2 vencen en menos de 24h
      </p>
    ),
  },
  {
    label: 'Facturación Mensual',
    value: '$12.4k',
    badge: '+15%',
    badgeColor: 'text-emerald-600 bg-emerald-50 border-emerald-100',
    icon: Banknote,
    iconColor: 'text-emerald-500',
    subtext: 'Comparado con el mes anterior',
  },
];

function BadgeIcon({ card }: { card: StatCard }) {
  if (card.label === 'Expedientes Activos') return <TrendingUp className="h-3.5 w-3.5" />;
  if (card.label === 'Requerimientos Pendientes') return <AlertCircle className="h-3.5 w-3.5" />;
  return <ArrowUp className="h-3.5 w-3.5" />;
}

export function StatsCards() {
  return (
    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
      {cards.map((card) => (
        <div
          key={card.label}
          className="bg-white rounded-xl border border-gray-100 p-6 shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between h-36 relative overflow-hidden group"
        >
          <div className="absolute -right-4 -top-4 p-4 opacity-5 group-hover:opacity-10 transition-opacity rotate-12">
            <card.icon className={`h-24 w-24 ${card.iconColor}`} />
          </div>
          <div className="relative z-10">
            <p className="text-gray-500 text-[11px] font-bold mb-2 uppercase tracking-widest">
              {card.label}
            </p>
            <div className="flex items-end gap-3">
              <h3 className="text-gray-900 text-4xl font-bold tracking-tight">{card.value}</h3>
              <span
                className={`mb-1.5 text-[11px] font-bold px-2 py-0.5 rounded flex items-center gap-1 border ${card.badgeColor}`}
              >
                <BadgeIcon card={card} />
                {card.badge}
              </span>
            </div>
          </div>
          <div className="relative z-10 mt-auto">
            {card.extra ?? (
              <p className="text-[11px] text-gray-500 mt-2 font-medium">{card.subtext}</p>
            )}
          </div>
        </div>
      ))}
    </div>
  );
}
