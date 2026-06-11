import { forwardRef, useEffect, useImperativeHandle, useRef, type TextareaHTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

interface AutoResizeTextareaProps extends Omit<TextareaHTMLAttributes<HTMLTextAreaElement>, 'rows'> {
  minRows?: number;
}

export const AutoResizeTextarea = forwardRef<HTMLTextAreaElement, AutoResizeTextareaProps>(
  function AutoResizeTextarea({ value, className, minRows = 1, onChange, ...props }, forwardedRef) {
    const innerRef = useRef<HTMLTextAreaElement>(null);

    useImperativeHandle(forwardedRef, () => innerRef.current as HTMLTextAreaElement);

    useEffect(() => {
      const el = innerRef.current;
      if (!el) return;
      el.style.height = 'auto';
      el.style.height = `${Math.max(el.scrollHeight, minRows * 24)}px`;
    }, [value, minRows]);

    return (
      <textarea
        ref={innerRef}
        value={value}
        rows={minRows}
        onChange={onChange}
        className={cn(
          'block w-full min-w-0 resize-none overflow-hidden whitespace-pre-wrap break-words',
          className,
        )}
        {...props}
      />
    );
  },
);
