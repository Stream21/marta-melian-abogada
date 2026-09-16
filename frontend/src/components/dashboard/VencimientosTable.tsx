import { useMemo } from 'react';
import { Link } from '@tanstack/react-router';
import { type ColumnDef } from '@tanstack/react-table';
import {
  CalendarClock,
  CalendarX,
  Clock,
  CalendarDays,
  CalendarRange,
  ArrowRight,
} from 'lucide-react';
import type { DashboardUrgencia, DashboardVencimientoItem } from '@/api/client';
import { DataTable, type FilterableColumn } from '@/components/ui/data-table';
import { Badge } from '@/components/ui/badge';
import { textoVencimientoFase } from '@/lib/vencimiento-fase';

interface VencimientosTableProps {
  items: DashboardVencimientoItem[];
}

const fechaConfig: Record<
  DashboardUrgencia,
  { icon: React.ElementType; badge: 'destructive' | 'warning' | 'secondary' }
> = {
  vencido: { icon: CalendarX, badge: 'destructive' },
  hoy: { icon: CalendarX, badge: 'destructive' },
  manana: { icon: Clock, badge: 'warning' },
  proximos: { icon: CalendarDays, badge: 'warning' },
  semana: { icon: CalendarRange, badge: 'secondary' },
};

function urgenciaOrder(u: DashboardUrgencia): number {
  return { vencido: 0, hoy: 1, manana: 2, proximos: 3, semana: 4 }[u];
}

export function VencimientosTable({ items }: VencimientosTableProps) {
  const columns = useMemo<ColumnDef<DashboardVencimientoItem>[]>(
    () => [
      {
        id: 'cliente',
        accessorFn: (row) => `${row.clienteNombre} ${row.expedienteNumero}`,
        header: 'Cliente / Expediente',
        cell: ({ row }) => (
          <div className="flex flex-col">
            <span className="font-semibold text-foreground leading-snug">
              {row.original.clienteNombre}
            </span>
            <span className="text-xs text-muted-foreground font-mono mt-0.5">
              {row.original.expedienteNumero}
            </span>
          </div>
        ),
      },
      {
        id: 'label',
        accessorKey: 'label',
        header: 'Plazo',
        cell: ({ row }) => (
          <div className="flex flex-col gap-1">
            <span className="text-sm text-foreground">{row.original.label}</span>
            <span className="text-[11px] text-muted-foreground">{row.original.tramiteNombre}</span>
          </div>
        ),
      },
      {
        id: 'faseNegocioLabel',
        accessorKey: 'faseNegocioLabel',
        header: 'Fase',
        filterFn: 'equals',
        cell: ({ getValue }) => <Badge variant="info">{getValue<string>()}</Badge>,
      },
      {
        id: 'tipo',
        accessorKey: 'tipo',
        header: 'Tipo',
        filterFn: 'equals',
        cell: ({ getValue }) => (
          <Badge variant="outline">{getValue<string>() === 'cuota' ? 'Cuota' : 'Fase'}</Badge>
        ),
      },
      {
        id: 'urgencia',
        accessorKey: 'urgencia',
        header: 'Fecha límite',
        enableSorting: true,
        filterFn: 'equals',
        sortingFn: (a, b) =>
          urgenciaOrder(a.original.urgencia) - urgenciaOrder(b.original.urgencia) ||
          a.original.diasRestantes - b.original.diasRestantes,
        cell: ({ row }) => {
          const cfg = fechaConfig[row.original.urgencia] ?? fechaConfig.semana;
          const Icon = cfg.icon;
          const label =
            textoVencimientoFase(row.original.fecha) ??
            new Date(`${row.original.fecha}T12:00:00`).toLocaleDateString('es-ES');
          return (
            <Badge variant={cfg.badge} className="gap-1.5 font-bold">
              <Icon className="h-3.5 w-3.5" />
              {label}
            </Badge>
          );
        },
      },
      {
        id: 'acciones',
        header: '',
        enableSorting: false,
        cell: ({ row }) => (
          <div className="text-right">
            <Link
              to="/expedientes/$expedienteId"
              params={{ expedienteId: row.original.expedienteId }}
              className="link-brand hover:underline"
            >
              Ver detalle
            </Link>
          </div>
        ),
      },
    ],
    [],
  );

  const filterableColumns = useMemo<FilterableColumn[]>(
    () => [
      {
        id: 'faseNegocioLabel',
        title: 'Fase',
        options: [...new Set(items.map((d) => d.faseNegocioLabel))].map((t) => ({
          label: t,
          value: t,
        })),
      },
      {
        id: 'tipo',
        title: 'Tipo',
        options: [
          { label: 'Fase', value: 'fase' },
          { label: 'Cuota', value: 'cuota' },
        ],
      },
      {
        id: 'urgencia',
        title: 'Vencimiento',
        options: [
          { label: 'Vencido', value: 'vencido' },
          { label: 'Hoy', value: 'hoy' },
          { label: 'Mañana', value: 'manana' },
          { label: 'En los próximos días', value: 'proximos' },
          { label: 'Esta semana', value: 'semana' },
        ],
      },
    ],
    [items],
  );

  return (
    <div className="panel flex flex-col h-full">
      <div className="panel-header">
        <div className="panel-header-icon">
          <CalendarClock className="h-5 w-5" />
        </div>
        <div>
          <h3 className="panel-title">Vencimientos a revisar</h3>
          <p className="text-xs text-muted-foreground mt-0.5">
            Plazos de fase y cuotas vencidos o en los próximos 7 días
          </p>
        </div>
      </div>

      {items.length === 0 ? (
        <div className="flex-1 flex items-center justify-center p-10 text-sm text-muted-foreground">
          No hay vencimientos urgentes en los próximos 7 días.
        </div>
      ) : (
        <DataTable
          columns={columns}
          data={items}
          filterableColumns={filterableColumns}
          searchPlaceholder="Buscar cliente o expediente…"
          pageSize={8}
        />
      )}

      <div className="panel-footer">
        <Link
          to="/expedientes"
          className="link-brand flex items-center justify-center gap-2"
        >
          Ver todos los expedientes
          <ArrowRight className="h-4 w-4" />
        </Link>
      </div>
    </div>
  );
}
