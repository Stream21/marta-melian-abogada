import { useCallback, useEffect, useState, type KeyboardEvent } from 'react';
import { useDebouncedValue } from '@/hooks/useDebouncedValue';

const DEFAULT_DELAY_MS = 600;

/**
 * Separa el texto del input del valor aplicado al filtro/API.
 * Aplica el filtro al pulsar Enter o tras el debounce configurado.
 */
export function useDeferredSearch(initial = '', delayMs = DEFAULT_DELAY_MS) {
  const [input, setInput] = useState(initial);
  const [query, setQuery] = useState(initial);
  const debouncedInput = useDebouncedValue(input, delayMs);

  useEffect(() => {
    setQuery(debouncedInput);
  }, [debouncedInput]);

  const submit = useCallback(() => {
    setQuery(input);
  }, [input]);

  const onSearchKeyDown = useCallback(
    (e: KeyboardEvent<HTMLInputElement>) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        submit();
      }
    },
    [submit],
  );

  return { input, setInput, query, onSearchKeyDown, submit };
}
