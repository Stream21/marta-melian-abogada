import type { KeyboardEvent } from 'react';

/**
 * En móvil, Enter / «Hecho» del teclado virtual no quita el foco de un input de una línea.
 * Evita enviar el formulario y cierra el teclado.
 */
export function cerrarTecladoAlEnter(e: KeyboardEvent): void {
  if (e.key !== 'Enter') return;

  const el = e.target;
  if (!(el instanceof HTMLElement)) return;
  if (el instanceof HTMLTextAreaElement) return;
  if (el instanceof HTMLButtonElement) return;
  if (el instanceof HTMLInputElement && (el.type === 'submit' || el.type === 'button')) return;

  e.preventDefault();
  el.blur();
}
