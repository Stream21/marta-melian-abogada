import type { ReactNode } from 'react';
import {
  buildPreviewValues,
  splitTextWithVariables,
  substituteVariables,
  type VariablePreviewContext,
} from '@/lib/hoja-encargo-variables';
import { VariableChip } from '@/components/hoja-encargo/VariableChip';

interface RenderedTextProps {
  text: string;
  preview: boolean;
  previewContext: VariablePreviewContext;
}

type FormatRule = {
  marker: string;
  wrap: (children: ReactNode, key: string) => ReactNode;
};

const FORMAT_RULES: FormatRule[] = [
  {
    marker: '**',
    wrap: (children, key) => (
      <strong key={key} className="font-semibold">
        {children}
      </strong>
    ),
  },
  {
    marker: '__',
    wrap: (children, key) => (
      <span key={key} className="underline">
        {children}
      </span>
    ),
  },
  {
    marker: '*',
    wrap: (children, key) => (
      <em key={key} className="italic">
        {children}
      </em>
    ),
  },
];

function parseInlineFormatting(text: string, keyPrefix = 'fmt'): ReactNode[] {
  if (!text) return [];

  for (const rule of FORMAT_RULES) {
    const { marker } = rule;
    const start = text.indexOf(marker);
    if (start === -1) continue;

    const end = text.indexOf(marker, start + marker.length);
    if (end === -1) continue;

    const before = text.slice(0, start);
    const inner = text.slice(start + marker.length, end);
    const after = text.slice(end + marker.length);

    return [
      ...parseInlineFormatting(before, `${keyPrefix}-b`),
      rule.wrap(<>{parseInlineFormatting(inner, `${keyPrefix}-i`)}</>, `${keyPrefix}-w`),
      ...parseInlineFormatting(after, `${keyPrefix}-a`),
    ];
  }

  return [text];
}

function renderParts(text: string, preview: boolean, previewContext: VariablePreviewContext): ReactNode {
  if (preview) {
    const values = buildPreviewValues(previewContext);
    const parts = splitTextWithVariables(text);
    if (parts.length === 0) {
      return parseInlineFormatting(substituteVariables(text, values));
    }

    return parts.map((part, index) => {
      if (part.type === 'variable') {
        const value = values[part.value] ?? part.value;
        return (
          <strong key={`var-${part.value}-${index}`} className="font-bold">
            {value}
          </strong>
        );
      }
      return <span key={`txt-${index}`}>{parseInlineFormatting(part.value, `p-${index}`)}</span>;
    });
  }

  const parts = splitTextWithVariables(text);
  if (parts.length === 0) {
    return <span className="text-muted-foreground italic">{text || 'Texto vacío'}</span>;
  }

  return parts.map((part, index) => {
    if (part.type === 'variable') {
      return <VariableChip key={`var-${part.value}-${index}`} variableKey={part.value} />;
    }
    return (
      <span key={`txt-${index}`}>{parseInlineFormatting(part.value, `p-${index}`)}</span>
    );
  });
}

export function RenderedText({ text, preview, previewContext }: RenderedTextProps) {
  return <span className="whitespace-pre-wrap">{renderParts(text, preview, previewContext)}</span>;
}
