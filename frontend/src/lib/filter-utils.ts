export function matchesMultiFilter<T>(itemValue: T, filterValue: unknown): boolean {
  if (filterValue == null) return true;
  if (Array.isArray(filterValue)) {
    if (filterValue.length === 0) return true;
    return filterValue.includes(itemValue);
  }
  if (filterValue === '') return true;
  return itemValue === filterValue;
}
