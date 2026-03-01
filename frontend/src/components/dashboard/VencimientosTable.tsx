import { type ColumnDef } from '@tanstack/react-table';
import { CalendarClock, CalendarX, Clock, CalendarDays, CalendarRange, ArrowRight } from 'lucide-react';
import { DataTable, type FilterableColumn } from '@/components/ui/data-table';

export interface Vencimiento {
  id: string;
  cliente: string;
  expediente: string;
  tipo: string;
  responsable: 'Acción Abogado' | 'Pendiente Cliente';
  fechaLabel: string;
  fechaUrgencia: 'hoy' | 'manana' | 'proximos' | 'semana';
}

const data: Vencimiento[] = [
  {
    id: '1',
    cliente: 'Maria Gonzalez',
    expediente: 'Exp-2023-889',
    tipo: 'Renovación NIE',
    responsable: 'Acción Abogado',
    fechaLabel: 'Hoy',
    fechaUrgencia: 'hoy',
  },
  {
    id: '2',
    cliente: 'Ahmed Al-Farsi',
    expediente: 'Exp-2024-112',
    tipo: 'Arraigo Familiar',
    responsable: 'Pendiente Cliente',
    fechaLabel: 'Mañana',
    fechaUrgencia: 'manana',
  },
  {
    id: '3',
    cliente: 'Lucía Mendez',
    expediente: 'Exp-2023-445',
    tipo: 'Nacionalidad',
    responsable: 'Acción Abogado',
    fechaLabel: 'En 3 días',
    fechaUrgencia: 'proximos',
  },
  {
    id: '4',
    cliente: 'Oliver Smith',
    expediente: 'Exp-2024-002',
    tipo: 'Visado Nómada',
    responsable: 'Pendiente Cliente',
    fechaLabel: 'Próx. semana',
    fechaUrgencia: 'semana',
  },
  {
    id: '5',
    cliente: 'TechSolutions S.L.',
    expediente: 'Rec-2024-912',
    tipo: 'Recurso Reposición',
    responsable: 'Acción Abogado',
    fechaLabel: 'Próx. semana',
    fechaUrgencia: 'semana',
  },
];

const tipoStyles: Record<string, string> = {
  'Renovación NIE': 'bg-blue-50 text-blue-700 border-blue-100',
  'Arraigo Familiar': 'bg-orange-50 text-orange-700 border-orange-100',
  'Nacionalidad': 'bg-purple-50 text-purple-700 border-purple-100',
  'Visado Nómada': 'bg-teal-50 text-teal-700 border-teal-100',
  'Recurso Reposición': 'bg-indigo-50 text-indigo-700 border-indigo-100',
};

const fechaConfig: Record<string, { icon: React.ElementType; color: string }> = {
  hoy: { icon: CalendarX, color: 'bg-red-50 text-red-700 border-red-100' },
  manana: { icon: Clock, color: 'bg-orange-50 text-orange-700 border-orange-100' },
  proximos: { icon: CalendarDays, color: 'bg-gray-50 text-gray-600 border-gray-200' },
  semana: { icon: CalendarRange, color: 'bg-gray-50 text-gray-600 border-gray-200' },
};

const columns: ColumnDef<Vencimiento>[] = [
  {
    id: 'cliente',
    accessorKey: 'cliente',
    header: 'Cliente / Expediente',
    cell: ({ row }) => (
      <div className="flex flex-col">
        <span className="font-semibold text-gray-900 group-hover:text-[#1e3a8a] transition-colors leading-snug">
          {row.original.cliente}
        </span>
        <span className="text-xs text-gray-400 font-mono mt-0.5">{row.original.expediente}</span>
      </div>
    ),
  },
  {
    id: 'tipo',
    accessorKey: 'tipo',
    header: 'Tipo de Caso',
    filterFn: 'equals',
    cell: ({ getValue }) => {
      const tipo = getValue<string>();
      return (
        <span
          className={`inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold border uppercase tracking-tight ${tipoStyles[tipo] ?? 'bg-gray-50 text-gray-700 border-gray-100'}`}
        >
          {tipo}
        </span>
      );
    },
  },
  {
    id: 'responsable',
    accessorKey: 'responsable',
    header: 'Responsable',
    filterFn: 'equals',
    cell: ({ getValue }) => {
      const val = getValue<string>();
      const isAbogado = val === 'Acción Abogado';
      return (
        <span
          className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-bold border uppercase tracking-wide ${isAbogado ? 'bg-blue-100 text-[#1e3a8a] border-blue-200' : 'bg-orange-100 text-orange-700 border-orange-200'}`}
        >
          {val}
        </span>
      );
    },
  },
  {
    id: 'fechaUrgencia',
    accessorKey: 'fechaUrgencia',
    header: 'Fecha Límite',
    enableSorting: true,
    filterFn: 'equals',
    sortingFn: (a, b) => {
      const order = { hoy: 0, manana: 1, proximos: 2, semana: 3 };
      return order[a.original.fechaUrgencia] - order[b.original.fechaUrgencia];
    },
    cell: ({ row }) => {
      const cfg = fechaConfig[row.original.fechaUrgencia];
      const Icon = cfg.icon;
      return (
        <div className={`flex items-center gap-1.5 px-3 py-1.5 rounded-md w-fit border text-xs font-bold ${cfg.color}`}>
          <Icon className="h-3.5 w-3.5" />
          {row.original.fechaLabel}
        </div>
      );
    },
  },
  {
    id: 'acciones',
    header: '',
    enableSorting: false,
    cell: () => (
      <div className="text-right">
        <button className="text-sm font-bold text-[#1e3a8a] hover:text-blue-900 hover:underline">
          Ver detalle
        </button>
      </div>
    ),
  },
];

const filterableColumns: FilterableColumn[] = [
  {
    id: 'tipo',
    title: 'Tipo de caso',
    options: [...new Set(data.map((d) => d.tipo))].map((t) => ({ label: t, value: t })),
  },
  {
    id: 'responsable',
    title: 'Responsable',
    options: [
      { label: 'Acción Abogado', value: 'Acción Abogado' },
      { label: 'Pendiente Cliente', value: 'Pendiente Cliente' },
    ],
  },
  {
    id: 'fechaUrgencia',
    title: 'Vencimiento',
    options: [
      { label: 'Hoy', value: 'hoy' },
      { label: 'Mañana', value: 'manana' },
      { label: 'En los próximos días', value: 'proximos' },
      { label: 'Próxima semana', value: 'semana' },
    ],
  },
];

export function VencimientosTable() {
  return (
    <div className="bg-white rounded-xl border border-gray-100 shadow-sm flex flex-col h-full overflow-hidden">
      <div className="flex items-center gap-3 p-6 border-b border-gray-100">
        <div className="p-2 bg-blue-50 rounded-lg text-[#1e3a8a]">
          <CalendarClock className="h-5 w-5" />
        </div>
        <h3 className="text-gray-900 text-lg font-bold leading-tight">Próximos Vencimientos</h3>
      </div>

      <DataTable
        columns={columns}
        data={data}
        filterableColumns={filterableColumns}
        searchPlaceholder="Buscar cliente o expediente..."
        pageSize={10}
      />

      <div className="p-4 border-t border-gray-100 bg-gray-50/50 text-center">
        <a
          href="#"
          className="text-sm text-[#1e3a8a] font-bold hover:text-blue-900 flex items-center justify-center gap-2"
        >
          Ver calendario completo
          <ArrowRight className="h-4 w-4" />
        </a>
      </div>
    </div>
  );
}
