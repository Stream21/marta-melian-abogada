import { Briefcase, Gavel, Globe2, HeartHandshake, Scale, type LucideIcon } from 'lucide-react';

/**
 * Catálogo estático de áreas jurídicas.
 * Los `id` coinciden con la tabla `area_juridica` en BD; el `value` (codigo) es lo que se envía a la API.
 * No se consulta al backend porque estos datos no cambian.
 */
export type TipoServicioValue =
  | 'extranjeria_nacionalidad'
  | 'familia_sucesiones'
  | 'civil_contratacion'
  | 'penal'
  | 'laboral_seguridad_social';

export interface TipoServicioOption {
  id: string;
  value: TipoServicioValue;
  label: string;
  shortLabel: string;
  icon: LucideIcon;
  iconClass: string;
}

export const TIPOS_SERVICIO: TipoServicioOption[] = [
  {
    id: 'a1000001-0001-4000-8000-000000000001',
    value: 'extranjeria_nacionalidad',
    label: 'Derecho de extranjería y nacionalidad',
    shortLabel: 'Extranjería',
    icon: Globe2,
    iconClass: 'bg-sky-100 text-sky-700',
  },
  {
    id: 'a1000001-0001-4000-8000-000000000002',
    value: 'familia_sucesiones',
    label: 'Derecho de Familia y Sucesiones',
    shortLabel: 'Familia',
    icon: HeartHandshake,
    iconClass: 'bg-rose-100 text-rose-700',
  },
  {
    id: 'a1000001-0001-4000-8000-000000000003',
    value: 'civil_contratacion',
    label: 'Derecho Civil y Contratación',
    shortLabel: 'Civil',
    icon: Scale,
    iconClass: 'bg-amber-100 text-amber-800',
  },
  {
    id: 'a1000001-0001-4000-8000-000000000004',
    value: 'penal',
    label: 'Derecho Penal',
    shortLabel: 'Penal',
    icon: Gavel,
    iconClass: 'bg-violet-100 text-violet-800',
  },
  {
    id: 'a1000001-0001-4000-8000-000000000005',
    value: 'laboral_seguridad_social',
    label: 'Derecho laboral y Seguridad Social',
    shortLabel: 'Laboral',
    icon: Briefcase,
    iconClass: 'bg-emerald-100 text-emerald-700',
  },
];

export function getTipoServicioOption(value: string | null | undefined): TipoServicioOption | undefined {
  return TIPOS_SERVICIO.find((t) => t.value === value);
}

export function getTipoServicioById(id: string | null | undefined): TipoServicioOption | undefined {
  return TIPOS_SERVICIO.find((t) => t.id === id);
}
