import { Link } from '@tanstack/react-router';
import { AlertTriangle, CreditCard, CloudOff, FileCheck } from 'lucide-react';
import type { DashboardKpisResponse } from '@/api/client';
import { cn } from '@/lib/utils';

interface AlertasOperativasProps {
  operativo: DashboardKpisResponse['operativo'];
}

export function AlertasOperativas({ operativo }: AlertasOperativasProps) {
  const alerts = [
    {
      show: operativo.holdedSyncPendientes > 0,
      icon: CloudOff,
      tone: 'border-amber-200 bg-amber-50 text-amber-900',
      iconTone: 'text-amber-700',
      text: `${operativo.holdedSyncPendientes} cobro${operativo.holdedSyncPendientes === 1 ? '' : 's'} pendiente${operativo.holdedSyncPendientes === 1 ? '' : 's'} de sync con Holded`,
      to: '/facturacion' as const,
    },
    {
      show: operativo.stripePendientes > 0,
      icon: CreditCard,
      tone: 'border-violet-200 bg-violet-50 text-violet-900',
      iconTone: 'text-violet-700',
      text: `${operativo.stripePendientes} enlace${operativo.stripePendientes === 1 ? '' : 's'} Stripe pendiente${operativo.stripePendientes === 1 ? '' : 's'}`,
      to: '/facturacion' as const,
    },
    {
      show: operativo.contratacionPendienteRevision > 0,
      icon: FileCheck,
      tone: 'border-blue-200 bg-blue-50 text-blue-900',
      iconTone: 'text-blue-700',
      text: `${operativo.contratacionPendienteRevision} paso${operativo.contratacionPendienteRevision === 1 ? '' : 's'} de contratación por revisar`,
      to: '/expedientes' as const,
    },
  ].filter((a) => a.show);

  if (alerts.length === 0) {
    return null;
  }

  return (
    <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
      {alerts.map((a) => (
        <Link
          key={a.text}
          to={a.to}
          className={cn(
            'flex items-start gap-3 rounded-lg border px-4 py-3 text-sm transition-opacity hover:opacity-90',
            a.tone,
          )}
        >
          <a.icon className={cn('h-4 w-4 shrink-0 mt-0.5', a.iconTone)} />
          <span className="flex-1">{a.text}</span>
          <AlertTriangle className={cn('h-3.5 w-3.5 shrink-0 opacity-50', a.iconTone)} />
        </Link>
      ))}
    </div>
  );
}
