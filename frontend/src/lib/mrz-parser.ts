/** Parser MRZ TD1 (DNI/NIE español) — espejo simplificado del backend. */

export interface MrzParseResult {
  nombre: string;
  numDocumento: string;
  tipoDocumento: 'DNI' | 'NIE' | 'PASAPORTE';
  fechaNacimiento: string | null;
}

export function parseMrzFromText(text: string): MrzParseResult | null {
  const norm = normalizarTextoOcrMrz(text);
  const lines = extractTd1Lines(norm);
  if (!lines) return null;

  const [line1, line2, line3] = lines;
  const numDocumento = extractNumDocumentoLinea1(line1);
  let nombres = parseNombres(line3);
  if (!nombres.nombre) {
    const line3Alt = buscarLinea3EnTexto(norm);
    if (line3Alt) nombres = parseNombres(line3Alt);
  }
  const fechaNacimiento = parseMrzDate(line2.slice(0, 6));

  if (!numDocumento && !nombres.nombre) return null;

  const tipoDocumento = inferTipoDocumento(numDocumento, line1.slice(0, 2));

  return {
    nombre: nombres.nombre,
    numDocumento: normalizarNumDocumento(numDocumento, tipoDocumento),
    tipoDocumento,
    fechaNacimiento,
  };
}

export function parseNombreFromMrzText(text: string): string {
  const norm = normalizarTextoOcrMrz(text);
  const lines = extractTd1Lines(norm);
  if (lines) {
    const nombre = parseNombres(lines[2]).nombre;
    if (nombre) return nombre;
  }
  const line3 = buscarLinea3EnTexto(norm);
  return line3 ? parseNombres(line3).nombre : '';
}

export function tieneMrzLegible(text: string): boolean {
  const norm = normalizarTextoOcrMrz(text);
  return parseMrzFromText(norm) !== null || cuentaLineasMrz(norm) >= 2;
}

/** Normaliza errores típicos de OCR en banda MRZ (O→0, espacios, ruido). */
export function normalizarTextoOcrMrz(text: string): string {
  return text
    .toUpperCase()
    .replace(/[«»]/g, '<')
    .replace(/[|]/g, 'I')
    .replace(/[°]/g, '0')
    .replace(/[^A-Z0-9<\n\r]/g, ' ')
    .replace(/[ \t]+/g, ' ')
    .replace(/(?<=[0-9])O(?=[0-9])/g, '0')
    .replace(/(?<=[0-9])I(?=[0-9])/g, '1');
}

/** Puntuación 0–100 de señales MRZ parciales (sin exigir parse completo). */
export function puntuacionMrzParcial(text: string): number {
  const norm = normalizarTextoOcrMrz(text).replace(/[\s\n\r]/g, '');
  if (norm.length < 6) return 0;

  let score = 0;

  if (/ID[A-Z<]{0,3}ESP/.test(norm) || norm.includes('IDESP')) score += 28;
  if (tieneDniNieEnTexto(norm)) score += 22;

  const chevrons = (norm.match(/</g) ?? []).length;
  if (chevrons >= 4) score += 12;
  if (chevrons >= 10) score += 12;
  if (chevrons >= 18) score += 8;

  const lineas = cuentaLineasMrz(norm);
  if (lineas >= 1) score += 14;
  if (lineas >= 2) score += 18;
  if (lineas >= 3) score += 12;

  if (/[A-Z0-9<]{20,}/.test(norm)) score += 8;

  return Math.min(100, score);
}

export function tieneDniNieEnTexto(text: string): boolean {
  const upper = text.toUpperCase().replace(/[\s\n\r]/g, '');
  return /[XYZ]\d{7}[A-Z]/.test(upper) || /\d{8}[A-Z]/.test(upper);
}

export function cuentaLineasMrz(text: string): number {
  const normalized = normalizarTextoOcrMrz(text).replace(/[\s\n\r]/g, '');
  const matches = normalized.match(/[A-Z0-9<]{18,}/g);
  if (!matches) return 0;
  return matches.filter((l) => l.includes('<') || l.startsWith('ID') || /\d{5,}/.test(l)).length;
}

function extractTd1Lines(text: string): [string, string, string] | null {
  const normalized = text.toUpperCase().replace(/[\s\t\n\r]/g, '');

  const blocks = normalized.match(/[A-Z0-9<]{27,32}/g);
  if (blocks && blocks.length >= 3) {
    for (let i = 0; i + 2 < blocks.length; i++) {
      const l1 = padMrzLine(blocks[i]!);
      const l2 = padMrzLine(blocks[i + 1]!);
      const l3 = padMrzLine(blocks[i + 2]!);
      if (looksLikeSpanishIdMrz(l1, l2)) return [l1, l2, l3];
    }
  }

  const rawLines = text
    .toUpperCase()
    .split(/\r?\n/)
    .map((l) => l.replace(/[^A-Z0-9<]/g, ''))
    .filter((l) => l.length >= 22);

  for (let i = 0; i + 2 < rawLines.length; i++) {
    const l1 = padMrzLine(rawLines[i]!);
    const l2 = padMrzLine(rawLines[i + 1]!);
    const l3 = padMrzLine(rawLines[i + 2]!);
    if (looksLikeSpanishIdMrz(l1, l2)) return [l1, l2, l3];
  }

  return null;
}

function padMrzLine(line: string): string {
  if (line.length >= 30) return line.slice(0, 30);
  return line.padEnd(30, '<');
}

function looksLikeSpanishIdMrz(line1: string, line2: string): boolean {
  const prefix = line1.slice(0, 6);
  const fuzzyIdEsp = /^I[D0][A-Z<]{0,2}E[S5][P9]/.test(prefix) || prefix.startsWith('IDESP');
  return (
    fuzzyIdEsp ||
    prefix.startsWith('IDES') ||
    (line1.startsWith('ID') && line1.includes('ESP')) ||
    /^[A-Z]{2}[A-Z<]{3}/.test(line1) ||
    (/\d{6}/.test(line2) && line1.includes('<'))
  );
}

function parseNombres(line3: string): { nombre: string } {
  const cleaned = line3.trim().replace(/^<+|<>+$/g, '');

  let apellidos: string;
  let nombre: string;

  if (cleaned.includes('<<')) {
    const parts = cleaned.split('<<', 2);
    apellidos = (parts[0] ?? '').replace(/</g, ' ').replace(/\s+/g, ' ').trim();
    nombre = (parts[1] ?? '').replace(/</g, ' ').replace(/\s+/g, ' ').trim();
  } else {
    const segmentos = cleaned
      .split('<')
      .map((s) => s.trim())
      .filter(Boolean);
    if (segmentos.length >= 2) {
      nombre = segmentos.pop() ?? '';
      apellidos = segmentos.join(' ');
    } else {
      apellidos = cleaned.replace(/</g, ' ').replace(/\s+/g, ' ').trim();
      nombre = '';
    }
  }

  return { nombre: [nombre, apellidos].filter(Boolean).join(' ').trim() };
}

function buscarLinea3EnTexto(text: string): string {
  const rawLines = text
    .toUpperCase()
    .split(/\r?\n/)
    .map((l) => l.replace(/[^A-Z0-9<]/g, ''))
    .filter((l) => l.length >= 15);

  for (let i = rawLines.length - 1; i >= 0; i--) {
    const clean = rawLines[i]!;
    if (/[A-Z]{4,}/.test(clean) && (clean.match(/</g) ?? []).length >= 2) {
      return padMrzLine(clean);
    }
  }

  const normalized = text.toUpperCase().replace(/[\s\n\r]/g, '');
  const matches = normalized.match(/[A-Z0-9<]{20,}/g);
  if (matches && matches.length > 0) {
    const ultima = matches[matches.length - 1]!;
    if (/[A-Z]{4,}/.test(ultima) && ultima.includes('<')) {
      return padMrzLine(ultima);
    }
  }

  return '';
}

function parseMrzDate(yymmdd: string): string | null {
  if (!/^\d{6}$/.test(yymmdd)) return null;
  const yy = parseInt(yymmdd.slice(0, 2), 10);
  const mm = yymmdd.slice(2, 4);
  const dd = yymmdd.slice(4, 6);
  const year = yy > 30 ? 1900 + yy : 2000 + yy;
  return `${year}-${mm}-${dd}`;
}

function extractNumDocumentoLinea1(line1: string): string {
  if (isSpanishIdLine1(line1)) {
    const trasCabecera = line1.slice(5);
    const dni = trasCabecera.match(/(\d{8}[A-Z])/);
    if (dni) return dni[1]!;
    const nie = trasCabecera.match(/([XYZ]\d{7}[A-Z])/);
    if (nie) return nie[1]!;
    const campo = line1.slice(14, 23).replace(/</g, '').replace(/<+$/, '');
    if (looksLikeDniNie(campo)) return campo;
    return '';
  }
  return line1.slice(5, 14).replace(/<+$/, '');
}

function isSpanishIdLine1(line1: string): boolean {
  return (
    line1.startsWith('IDESP') ||
    line1.startsWith('IDESM') ||
    (line1.startsWith('ID') && line1.includes('ESP'))
  );
}

function looksLikeDniNie(num: string): boolean {
  const n = num.toUpperCase().replace(/</g, '');
  return /^\d{8}[A-Z]$/.test(n) || /^[XYZ]\d{7}[A-Z]$/.test(n);
}

function inferTipoDocumento(num: string, tipoCodigo: string): 'DNI' | 'NIE' | 'PASAPORTE' {
  const n = num.toUpperCase();
  if (/^[XYZ]\d{7}[A-Z]$/.test(n)) return 'NIE';
  if (/^\d{8}[A-Z]$/.test(n)) return 'DNI';
  return tipoCodigo.startsWith('P') ? 'PASAPORTE' : 'DNI';
}

function normalizarNumDocumento(num: string, tipo: 'DNI' | 'NIE' | 'PASAPORTE'): string {
  const n = num.toUpperCase().replace(/<+$/, '');
  if (tipo === 'DNI') {
    const m = n.match(/^(\d{1,8})([A-Z])$/);
    if (m) return m[1]!.padStart(8, '0') + m[2];
  }
  return n;
}
