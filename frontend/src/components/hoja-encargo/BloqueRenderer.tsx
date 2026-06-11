import { useRef } from 'react';
import type { DespachoConfigResponse } from '@/api/client';
import { AutoResizeTextarea } from '@/components/hoja-encargo/AutoResizeTextarea';
import { RenderedText } from '@/components/hoja-encargo/RenderedText';
import { ColumnsBlockEditor } from '@/components/hoja-encargo/ColumnsBlockEditor';
import { SignatureZoneBlock } from '@/components/hoja-encargo/SignatureZoneBlock';
import { TableBlockEditor } from '@/components/hoja-encargo/TableBlockEditor';
import { TextFormatToolbar } from '@/components/hoja-encargo/TextFormatToolbar';
import type { BloqueEscrito } from '@/lib/hoja-encargo-variables';
import {
  blockStyleClasses,
  getTextareaSelection,
  isClauseSubtitle,
  restoreTextareaSelection,
  toggleWrap,
  type BloqueTextStyle,
} from '@/lib/escrito-format';
import {
  buildPreviewValues,
  insertVariableAtCursor,
  type VariablePreviewContext,
} from '@/lib/hoja-encargo-variables';
import { handleVariableDragOver, parseVariableDragData } from '@/lib/variable-drag';
import { cn } from '@/lib/utils';

interface BloqueRendererProps {
  bloque: BloqueEscrito;
  selected: boolean;
  preview: boolean;
  nested?: boolean;
  despacho?: DespachoConfigResponse;
  previewContext: VariablePreviewContext;
  logoUrl?: string | null;
  selloUrl?: string | null;
  allBloques?: BloqueEscrito[];
  selectedBlockId?: string | null;
  onSelect: () => void;
  onSelectChild?: (id: string) => void;
  onChange: (bloque: BloqueEscrito) => void;
  onChangeTree?: (bloques: BloqueEscrito[]) => void;
}

function stopEditPropagation(e: React.SyntheticEvent) {
  e.stopPropagation();
}

function titleAlignClass(style?: BloqueTextStyle): string {
  if (style?.align === 'left') return 'text-left';
  if (style?.align === 'right') return 'text-right';
  return 'text-center';
}

function renderDocumentContent(
  content: string,
  previewContext: VariablePreviewContext,
  styleClass?: string,
) {
  const lines = content.split(/\r\n|\r|\n/);
  if (lines.length <= 1) {
    return (
      <p className={cn('whitespace-pre-wrap break-words leading-relaxed text-foreground', styleClass || 'text-sm')}>
        <RenderedText text={content} preview previewContext={previewContext} />
      </p>
    );
  }

  return lines.map((line, index) => {
    if (!line.trim()) {
      return <br key={`blank-${index}`} />;
    }

    return (
      <p
        key={`line-${index}`}
        className={cn(
          'whitespace-pre-wrap break-words leading-relaxed text-foreground',
          isClauseSubtitle(line) ? 'escrito-clause-subtitle' : styleClass || 'text-sm',
        )}
      >
        <RenderedText text={line} preview previewContext={previewContext} />
      </p>
    );
  });
}

export function BloqueRenderer({
  bloque,
  selected,
  preview,
  nested = false,
  despacho,
  previewContext,
  logoUrl,
  selloUrl,
  allBloques = [],
  selectedBlockId,
  onSelect,
  onSelectChild,
  onChange,
  onChangeTree,
}: BloqueRendererProps) {
  const values = buildPreviewValues(previewContext);
  const contentRef = useRef<HTMLTextAreaElement>(null);
  const titleRef = useRef<HTMLTextAreaElement>(null);
  const sectionTitleRef = useRef<HTMLTextAreaElement>(null);

  const wrapperClass = cn(
    'min-w-0 overflow-hidden rounded-lg border transition-colors',
    nested ? 'p-2' : 'p-3',
    selected && !preview ? 'border-primary bg-primary/5 ring-1 ring-primary/20' : 'border-transparent',
    !preview && 'cursor-pointer hover:border-border hover:bg-muted/20',
  );

  const applyInlineFormat = (field: 'content' | 'title', marker: '**' | '*' | '__') => {
    if (bloque.type === 'text' || bloque.type === 'section') {
      const textarea = contentRef.current;
      if (!textarea) return;
      const { start, end } = getTextareaSelection(textarea);
      const { content, selectionStart, selectionEnd } = toggleWrap(bloque.content, start, end, marker);
      onChange({ ...bloque, content });
      restoreTextareaSelection(textarea, selectionStart, selectionEnd);
      return;
    }

    if (bloque.type === 'title' && field === 'title') {
      const textarea = titleRef.current;
      if (!textarea) return;
      const { start, end } = getTextareaSelection(textarea);
      const { content, selectionStart, selectionEnd } = toggleWrap(bloque.title, start, end, marker);
      onChange({ ...bloque, title: content });
      restoreTextareaSelection(textarea, selectionStart, selectionEnd);
    }
  };

  const updateStyle = (style: BloqueTextStyle) => {
    if (bloque.type === 'title' || bloque.type === 'text' || bloque.type === 'section') {
      onChange({ ...bloque, style });
    }
  };

  if (bloque.type === 'title') {
    const styleClass = blockStyleClasses(bloque.style);
    const alignClass = titleAlignClass(bloque.style);

    return (
      <div className={wrapperClass} onClick={onSelect}>
        {selected && !preview && (
          <TextFormatToolbar
            className="mb-2"
            style={bloque.style ?? { align: 'center', fontSize: 18 }}
            onStyleChange={updateStyle}
            onFormat={(marker) => applyInlineFormat('title', marker)}
          />
        )}
        <div className={cn('min-w-0', alignClass)}>
          {!preview ? (
            <AutoResizeTextarea
              ref={titleRef}
              value={bloque.title}
              onChange={(e) => onChange({ ...bloque, title: e.target.value })}
              onClick={stopEditPropagation}
              onMouseDown={stopEditPropagation}
              onFocus={onSelect}
              minRows={1}
              className={cn(
                'bg-transparent font-bold text-primary focus:outline-none focus-visible:ring-0',
                styleClass || 'text-lg',
              )}
            />
          ) : (
            <h2
              className={cn(
                'escrito-document-title whitespace-pre-wrap break-words',
                styleClass,
              )}
            >
              <RenderedText text={bloque.title} preview previewContext={previewContext} />
            </h2>
          )}
          {bloque.showReferencia && (
            <p className="mt-1 text-[11px] text-muted-foreground">
              REFERENCIA: {preview ? values.REFERENCIA_EXPEDIENTE : '[[REFERENCIA_EXPEDIENTE]]'}
            </p>
          )}
        </div>
      </div>
    );
  }

  if (bloque.type === 'text' || bloque.type === 'section') {
    const styleClass = blockStyleClasses(bloque.style);

    return (
      <div className={wrapperClass} onClick={onSelect}>
        {bloque.type === 'section' &&
          (!preview ? (
            <AutoResizeTextarea
              ref={sectionTitleRef}
              value={bloque.title}
              onChange={(e) => onChange({ ...bloque, title: e.target.value })}
              onClick={stopEditPropagation}
              onMouseDown={stopEditPropagation}
              onFocus={onSelect}
              minRows={1}
              className="escrito-section-heading mb-2 bg-transparent text-center focus:outline-none focus-visible:ring-0"
            />
          ) : (
            <p className="escrito-section-heading mb-2 whitespace-pre-wrap break-words">{bloque.title}</p>
          ))}

        {selected && !preview && (
          <TextFormatToolbar
            className="mb-2"
            style={bloque.style}
            onStyleChange={updateStyle}
            onFormat={(marker) => applyInlineFormat('content', marker)}
          />
        )}

        {!preview ? (
          <AutoResizeTextarea
            ref={contentRef}
            value={bloque.content}
            onChange={(e) => onChange({ ...bloque, content: e.target.value })}
            onClick={stopEditPropagation}
            onMouseDown={stopEditPropagation}
            onFocus={onSelect}
            onDragOver={(e) => handleVariableDragOver(e.nativeEvent)}
            onDrop={(e) => {
              e.preventDefault();
              e.stopPropagation();
              const key = parseVariableDragData(e.dataTransfer);
              if (!key) return;
              const textarea = e.currentTarget;
              const { content } = insertVariableAtCursor(
                bloque.content,
                key,
                textarea.selectionStart,
                textarea.selectionEnd,
              );
              onChange({ ...bloque, content });
            }}
            minRows={bloque.type === 'section' ? 5 : 2}
            className={cn(
              'rounded-md border border-input bg-background px-3 py-2 leading-relaxed focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring',
              styleClass || 'text-sm',
            )}
            data-block-id={bloque.id}
          />
        ) : (
          renderDocumentContent(bloque.content, previewContext, styleClass || 'text-sm')
        )}
      </div>
    );
  }

  if (bloque.type === 'table') {
    return (
      <TableBlockEditor
        bloque={bloque}
        selected={selected}
        onSelect={onSelect}
        onChange={onChange}
      />
    );
  }

  if (bloque.type === 'columns' && !nested) {
    return (
      <ColumnsBlockEditor
        bloque={bloque}
        selected={selected}
        selectedBlockId={selectedBlockId ?? null}
        preview={preview}
        previewContext={previewContext}
        despacho={despacho}
        logoUrl={logoUrl}
        selloUrl={selloUrl}
        allBloques={allBloques}
        onSelect={onSelect}
        onSelectChild={onSelectChild ?? onSelect}
        onChange={onChange}
        onChangeTree={onChangeTree ?? (() => undefined)}
      />
    );
  }

  if (bloque.type === 'signature_client' || bloque.type === 'signature_lawyer') {
    return (
      <div
        className={cn(
          nested ? 'rounded-lg p-1' : wrapperClass,
          nested && selected && !preview && 'bg-primary/5 ring-1 ring-primary/20',
        )}
        onClick={onSelect}
      >
        <SignatureZoneBlock
          variant={bloque.type === 'signature_client' ? 'client' : 'lawyer'}
          selloUrl={bloque.type === 'signature_lawyer' ? selloUrl : null}
          logoUrl={logoUrl}
          documentPreview={preview}
          compact={nested}
        />
      </div>
    );
  }

  return null;
}
