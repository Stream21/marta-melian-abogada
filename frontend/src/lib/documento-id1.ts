/**
 * ISO/IEC 7810 ID-1 — DNI, NIE y tarjetas equivalentes.
 * 85,60 mm × 53,98 mm en horizontal (ancho × alto).
 */
export const ID1_ANCHO_MM = 85.6;
export const ID1_ALTO_MM = 53.98;
export const ID1_ASPECT_RATIO = ID1_ANCHO_MM / ID1_ALTO_MM;

/**
 * ISO/IEC 7810 ID-3 — página de datos de pasaporte (ICAO TD3).
 * 125 mm × 88 mm; en libreta se fotografía en vertical (ancho × alto).
 */
export const PASAPORTE_ANCHO_MM = 88;
export const PASAPORTE_ALTO_MM = 125;
/** Ancho / alto en vertical (formato libreta, más alto que ancho). */
export const PASAPORTE_ASPECT_RATIO = PASAPORTE_ANCHO_MM / PASAPORTE_ALTO_MM;
