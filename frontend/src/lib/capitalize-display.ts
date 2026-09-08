/**
 * Capitaliza texto para mostrar en UI (nombres, títulos…).
 * "antonio ARJONES" → "Antonio Arjones"
 */
export function capitalizeDisplay(value: string | null | undefined): string {
  if (!value) return '';
  const trimmed = value.trim().replace(/\s+/g, ' ');
  if (!trimmed) return '';

  return trimmed
    .split(' ')
    .map((word) =>
      word
        .split('-')
        .map((part) => {
          if (!part) return part;
          const lower = part.toLocaleLowerCase('es-ES');
          return lower.charAt(0).toLocaleUpperCase('es-ES') + lower.slice(1);
        })
        .join('-'),
    )
    .join(' ');
}
