export type TextAlign = 'left' | 'center' | 'right' | 'justify';

export type EscritoFontSize = 10 | 11 | 12 | 14 | 16 | 18;

export interface BloqueTextStyle {
  align?: TextAlign;
  fontSize?: EscritoFontSize;
}

export const FONT_SIZE_OPTIONS: Array<{ value: EscritoFontSize; label: string }> = [
  { value: 10, label: '10 pt' },
  { value: 11, label: '11 pt' },
  { value: 12, label: '12 pt' },
  { value: 14, label: '14 pt' },
  { value: 16, label: '16 pt' },
  { value: 18, label: '18 pt' },
];

export const FONT_SIZE_CLASS: Record<EscritoFontSize, string> = {
  10: 'text-[10pt]',
  11: 'text-[11pt]',
  12: 'text-[12pt]',
  14: 'text-[14pt]',
  16: 'text-[16pt]',
  18: 'text-[18pt]',
};

export const ALIGN_CLASS: Record<TextAlign, string> = {
  left: 'text-left',
  center: 'text-center',
  right: 'text-right',
  justify: 'text-justify',
};

const CLAUSE_SUBTITLE_PATTERN =
  /^(PRIMERA|SEGUNDA|TERCERA|CUARTA|QUINTA|SEXTA|S[EÉ]PTIMA|OCTAVA|NOVENA|D[EÉ]CIMA|UND[EÉ]CIMA|DUOD[EÉ]CIMA)\s*\.?\s*-/iu;

export function isClauseSubtitle(line: string): boolean {
  return CLAUSE_SUBTITLE_PATTERN.test(line.trim());
}

export function blockStyleClasses(style?: BloqueTextStyle): string {
  const parts: string[] = [];
  if (style?.fontSize) parts.push(FONT_SIZE_CLASS[style.fontSize]);
  if (style?.align) parts.push(ALIGN_CLASS[style.align]);
  return parts.join(' ');
}

export function toggleWrap(
  text: string,
  start: number,
  end: number,
  marker: string,
): { content: string; selectionStart: number; selectionEnd: number } {
  const openLen = marker.length;
  const closeLen = marker.length;

  const hasWrapBefore = start >= openLen && text.slice(start - openLen, start) === marker;
  const hasWrapAfter = text.slice(end, end + closeLen) === marker;

  if (hasWrapBefore && hasWrapAfter) {
    const content = text.slice(0, start - openLen) + text.slice(start, end) + text.slice(end + closeLen);
    return {
      content,
      selectionStart: start - openLen,
      selectionEnd: end - openLen,
    };
  }

  const selected = text.slice(start, end) || 'texto';
  const content = text.slice(0, start) + marker + selected + marker + text.slice(end);
  const selectionStart = start + openLen;
  const selectionEnd = selectionStart + selected.length;

  return { content, selectionStart, selectionEnd };
}

export function getTextareaSelection(textarea: HTMLTextAreaElement): { start: number; end: number } {
  return { start: textarea.selectionStart, end: textarea.selectionEnd };
}

export function restoreTextareaSelection(
  textarea: HTMLTextAreaElement,
  start: number,
  end: number,
): void {
  requestAnimationFrame(() => {
    textarea.focus();
    textarea.setSelectionRange(start, end);
  });
}
