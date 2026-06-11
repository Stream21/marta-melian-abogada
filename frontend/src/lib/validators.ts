const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

export function isValidEmail(email: string): boolean {
  const trimmed = email.trim();
  if ('' === trimmed) return true;
  return EMAIL_REGEX.test(trimmed);
}

export function isValidTelefono(telefono: string): boolean {
  const trimmed = telefono.trim().replace(/\s+/g, '');
  if ('' === trimmed) return false;
  return /^\+?[0-9]{6,20}$/.test(trimmed);
}
