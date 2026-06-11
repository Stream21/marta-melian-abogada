import {
  AlignCenter,
  AlignJustify,
  AlignLeft,
  AlignRight,
  Bold,
  Italic,
  Underline,
} from 'lucide-react';
import {
  FONT_SIZE_OPTIONS,
  type BloqueTextStyle,
  type EscritoFontSize,
  type TextAlign,
} from '@/lib/escrito-format';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

interface TextFormatToolbarProps {
  style?: BloqueTextStyle;
  onStyleChange: (style: BloqueTextStyle) => void;
  onFormat: (marker: '**' | '*' | '__') => void;
  disabled?: boolean;
  className?: string;
}

const ALIGN_OPTIONS: Array<{ value: TextAlign; icon: typeof AlignLeft; label: string }> = [
  { value: 'left', icon: AlignLeft, label: 'Alinear izquierda' },
  { value: 'center', icon: AlignCenter, label: 'Centrar' },
  { value: 'right', icon: AlignRight, label: 'Alinear derecha' },
  { value: 'justify', icon: AlignJustify, label: 'Justificar' },
];

export function TextFormatToolbar({
  style,
  onStyleChange,
  onFormat,
  disabled,
  className,
}: TextFormatToolbarProps) {
  const currentAlign = style?.align ?? 'left';
  const currentSize = style?.fontSize ?? 12;

  const setAlign = (align: TextAlign) => {
    onStyleChange({ ...style, align });
  };

  const setFontSize = (fontSize: EscritoFontSize) => {
    onStyleChange({ ...style, fontSize });
  };

  return (
    <div
      className={cn(
        'flex flex-wrap items-center gap-1 rounded-md border border-border bg-muted/40 p-1',
        className,
      )}
      onClick={(e) => e.stopPropagation()}
    >
      <div className="flex items-center gap-0.5 border-r border-border pr-1">
        <Button
          type="button"
          variant="ghost"
          size="sm"
          className="h-8 w-8 p-0"
          title="Negrita"
          disabled={disabled}
          onClick={() => onFormat('**')}
        >
          <Bold className="h-4 w-4" />
        </Button>
        <Button
          type="button"
          variant="ghost"
          size="sm"
          className="h-8 w-8 p-0"
          title="Cursiva"
          disabled={disabled}
          onClick={() => onFormat('*')}
        >
          <Italic className="h-4 w-4" />
        </Button>
        <Button
          type="button"
          variant="ghost"
          size="sm"
          className="h-8 w-8 p-0"
          title="Subrayado"
          disabled={disabled}
          onClick={() => onFormat('__')}
        >
          <Underline className="h-4 w-4" />
        </Button>
      </div>

      <div className="flex items-center gap-0.5 border-r border-border px-1">
        {ALIGN_OPTIONS.map(({ value, icon: Icon, label }) => (
          <Button
            key={value}
            type="button"
            variant={currentAlign === value ? 'secondary' : 'ghost'}
            size="sm"
            className="h-8 w-8 p-0"
            title={label}
            disabled={disabled}
            onClick={() => setAlign(value)}
          >
            <Icon className="h-4 w-4" />
          </Button>
        ))}
      </div>

      <select
        value={currentSize}
        disabled={disabled}
        title="Tamaño de letra"
        className="h-8 rounded-md border border-input bg-background px-2 text-xs text-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
        onChange={(e) => setFontSize(Number(e.target.value) as EscritoFontSize)}
      >
        {FONT_SIZE_OPTIONS.map(({ value, label }) => (
          <option key={value} value={value}>
            {label}
          </option>
        ))}
      </select>
    </div>
  );
}
