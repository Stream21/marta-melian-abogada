import type { BriefingPlanStep } from './ContratacionBriefingDialog';
import { SUBFASES_CONTRATACION } from '@/lib/portal-fases';

/** Plan de bienvenida: las 3 subfases de contratación. */
export function buildContratacionJourneyPlan(): BriefingPlanStep[] {
  return SUBFASES_CONTRATACION.map((s) => ({
    title: `${s.orden}. ${s.label}`,
    description: s.descripcion,
  }));
}

export function markActivePlanStep(
  plan: BriefingPlanStep[],
  activeIndex: number,
): BriefingPlanStep[] {
  return plan.map((step, index) => ({ ...step, active: index === activeIndex }));
}
