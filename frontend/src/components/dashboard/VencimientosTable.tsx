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
  proximos: { icon: CalendarDays, color: 'bg-muted text-muted-foreground border-border' },
  semana: { icon: CalendarRange, color: 'bg-muted text-muted-foreground border-border' },
};

const columns: ColumnDef<Vencimiento>[] = [
  {
    id: 'cliente',
    accessorKey: 'cliente',
    header: 'Cliente / Expediente',
    cell: ({ row }) => (
      <div className="flex flex-col">
        <span className="font-semibold text-foreground group-hover:text-primary transition-colors leading-snug">
          {row.original.cliente}
        </span>
        <span className="text-xs text-muted-foreground font-mono mt-0.5">{row.original.expediente}</span>
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
          className={`inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold border uppercase tracking-tight ${tipoStyles[tipo] ?? 'bg-muted text-foreground border-border'}`}
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
          className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-bold border uppercase tracking-wide ${isAbogado ? 'bg-primary/10 text-primary border-primary/20' : 'bg-orange-100 text-orange-700 border-orange-200'}`}
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
        <button className="link-brand hover:underline">
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
    <div className="panel flex flex-col h-full">
      <div className="panel-header">
        <div className="panel-header-icon">
          <CalendarClock className="h-5 w-5" />
        </div>
        <h3 className="panel-title">Próximos Vencimientos</h3>
      </div>

      <DataTable
        columns={columns}
        data={data}
        filterableColumns={filterableColumns}
        searchPlaceholder="Buscar cliente o expediente..."
        pageSize={10}
      />

      <div className="panel-footer">
        <a href="#" className="link-brand flex items-center justify-center gap-2">
          Ver calendario completo
          <ArrowRight className="h-4 w-4" />
        </a>
      </div>
    </div>
  );
}
