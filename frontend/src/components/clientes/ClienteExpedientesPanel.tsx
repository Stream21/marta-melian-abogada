import { Link } from '@tanstack/react-router';
import { FolderOpen } from 'lucide-react';
import type { ClienteDetalleResponse } from '@/api/client';
import { Badge } from '@/components/ui/badge';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';

interface ClienteExpedientesPanelProps {
  expedientes: ClienteDetalleResponse['expedientes'];
}

export function ClienteExpedientesPanel({ expedientes }: ClienteExpedientesPanelProps) {
  return (
    <div className="panel">
      <div className="panel-header">
        <div className="panel-header-icon">
          <FolderOpen className="h-5 w-5" />
        </div>
        <div>
          <h2 className="panel-title">Expedientes</h2>
          <p className="text-sm text-muted-foreground">
            Trámites asociados a este cliente. Los trámites derivados programados se mostrarán aquí
            cuando estén configurados.
          </p>
        </div>
      </div>

      {expedientes.length === 0 ? (
        <p className="p-6 text-sm text-muted-foreground">Este cliente aún no tiene expedientes.</p>
      ) : (
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Nº</TableHead>
              <TableHead>Trámite</TableHead>
              <TableHead>Fase</TableHead>
              <TableHead>Estado</TableHead>
              <TableHead>Honorarios</TableHead>
              <TableHead />
            </TableRow>
          </TableHeader>
          <TableBody>
            {expedientes.map((exp) => (
              <TableRow key={exp.id}>
                <TableCell className="font-mono text-sm">{exp.numero}</TableCell>
                <TableCell>{exp.titulo}</TableCell>
                <TableCell>
                  <Badge variant="info">{exp.faseNegocioLabel}</Badge>
                </TableCell>
                <TableCell>
                  <Badge variant="secondary">{exp.estadoFaseLabel}</Badge>
                </TableCell>
                <TableCell>
                  {exp.honorariosAcordados != null
                    ? `${exp.honorariosAcordados.toLocaleString('es-ES', { minimumFractionDigits: 2 })} €`
                    : '—'}
                </TableCell>
                <TableCell>
                  <Link
                    to="/expedientes/$expedienteId"
                    params={{ expedienteId: exp.id }}
                    className="link-brand text-sm"
                  >
                    Abrir
                  </Link>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      )}
    </div>
  );
}
