const ETIQUETAS_EDITABLES = ['INPUT', 'SELECT', 'TEXTAREA'];

export function esCampoEditable(el: Element | null): el is HTMLElement {
  if (!(el instanceof HTMLElement)) return false;

  return ETIQUETAS_EDITABLES.includes(el.tagName) || el.isContentEditable;
}
