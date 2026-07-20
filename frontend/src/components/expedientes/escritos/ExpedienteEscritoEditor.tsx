import { useMutation } from '@tanstack/react-query';
import Placeholder from '@tiptap/extension-placeholder';
import TextAlign from '@tiptap/extension-text-align';
import { EditorContent, useEditor } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import {
  AlignCenter,
  AlignLeft,
  Bold,
  Heading2,
  Heading3,
  Italic,
  List,
  ListOrdered,
} from 'lucide-react';
import { useEffect, useReducer, type ReactNode } from 'react';
import { api } from '@/api/client';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

const VARIABLES = [
  { token: '[[CLIENTE_NOMBRE]]', label: 'Nombre cliente' },
  { token: '[[EXPEDIENTE_NUMERO]]', label: 'Nº expediente' },
  { token: '[[FECHA_HOY]]', label: 'Fecha hoy' },
];

function ToolbarButton({
  active,
  disabled,
  onClick,
  children,
  title,
}: {
  active?: boolean;
  disabled?: boolean;
  onClick: () => void;
  children: ReactNode;
  title: string;
}) {
  return (
    <Button
      type="button"
      variant="ghost"
      size="icon"
      title={title}
      className={cn('h-8 w-8', active && 'bg-primary/10 text-primary')}
      disabled={disabled}
      onClick={onClick}
    >
      {children}
    </Button>
  );
}

interface ExpedienteEscritoEditorProps {
  expedienteId: string;
  escritoId?: string | null;
  initialTitulo?: string;
  initialContenidoHtml?: string;
  onSaved: () => void;
  onCancel?: () => void;
}

export function ExpedienteEscritoEditor({
  expedienteId,
  escritoId,
  initialTitulo = '',
  initialContenidoHtml = '',
  onSaved,
  onCancel,
}: ExpedienteEscritoEditorProps) {
  const [, rerenderToolbar] = useReducer((n: number) => n + 1, 0);
  const isEditing = Boolean(escritoId);

  const editor = useEditor({
    extensions: [
      StarterKit.configure({ heading: { levels: [2, 3] } }),
      TextAlign.configure({ types: ['heading', 'paragraph'] }),
      Placeholder.configure({ placeholder: 'Escriba aquí el contenido del escrito…' }),
    ],
    content: initialContenidoHtml,
    editorProps: {
      attributes: { class: 'escrito-tiptap-editor focus:outline-none' },
    },
    onSelectionUpdate: () => rerenderToolbar(),
    onTransaction: () => rerenderToolbar(),
  });

  useEffect(() => {
    if (!editor) return;
    editor.commands.setContent(initialContenidoHtml || '');
  }, [editor, initialContenidoHtml, escritoId]);

  const guardarMutation = useMutation({
    mutationFn: (payload: { titulo: string; contenidoHtml: string }) => {
      if (isEditing && escritoId) {
        return api.actualizarEscritoExpediente(expedienteId, escritoId, payload);
      }
      return api.crearEscritoExpediente(expedienteId, payload);
    },
    onSuccess: () => onSaved(),
  });

  const tituloId = isEditing ? 'escrito-titulo-edit' : 'escrito-titulo-new';

  const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    const form = e.currentTarget;
    const titulo = (form.elements.namedItem('titulo') as HTMLInputElement).value.trim();
    const contenidoHtml = editor?.getHTML() ?? '';
    guardarMutation.mutate({ titulo, contenidoHtml });
  };

  const disabled = !editor || guardarMutation.isPending;

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <div className="space-y-2">
        <Label htmlFor={tituloId}>Título del escrito</Label>
        <Input
          id={tituloId}
          name="titulo"
          key={escritoId ?? 'new'}
          defaultValue={initialTitulo}
          placeholder="Ej. Escrito de alegaciones"
          required
          minLength={2}
        />
      </div>

      <div className="rounded-lg border overflow-hidden">
        <div className="flex flex-wrap gap-1 border-b bg-muted/40 p-2">
          <ToolbarButton
            title="Negrita"
            active={editor?.isActive('bold')}
            disabled={disabled}
            onClick={() => editor?.chain().focus().toggleBold().run()}
          >
            <Bold className="h-4 w-4" />
          </ToolbarButton>
          <ToolbarButton
            title="Cursiva"
            active={editor?.isActive('italic')}
            disabled={disabled}
            onClick={() => editor?.chain().focus().toggleItalic().run()}
          >
            <Italic className="h-4 w-4" />
          </ToolbarButton>
          <ToolbarButton
            title="Título"
            active={editor?.isActive('heading', { level: 2 })}
            disabled={disabled}
            onClick={() => editor?.chain().focus().toggleHeading({ level: 2 }).run()}
          >
            <Heading2 className="h-4 w-4" />
          </ToolbarButton>
          <ToolbarButton
            title="Subtítulo"
            active={editor?.isActive('heading', { level: 3 })}
            disabled={disabled}
            onClick={() => editor?.chain().focus().toggleHeading({ level: 3 }).run()}
          >
            <Heading3 className="h-4 w-4" />
          </ToolbarButton>
          <ToolbarButton
            title="Lista con viñetas"
            active={editor?.isActive('bulletList')}
            disabled={disabled}
            onClick={() => editor?.chain().focus().toggleBulletList().run()}
          >
            <List className="h-4 w-4" />
          </ToolbarButton>
          <ToolbarButton
            title="Lista numerada"
            active={editor?.isActive('orderedList')}
            disabled={disabled}
            onClick={() => editor?.chain().focus().toggleOrderedList().run()}
          >
            <ListOrdered className="h-4 w-4" />
          </ToolbarButton>
          <ToolbarButton
            title="Alinear a la izquierda"
            active={editor?.isActive({ textAlign: 'left' })}
            disabled={disabled}
            onClick={() => editor?.chain().focus().setTextAlign('left').run()}
          >
            <AlignLeft className="h-4 w-4" />
          </ToolbarButton>
          <ToolbarButton
            title="Centrar"
            active={editor?.isActive({ textAlign: 'center' })}
            disabled={disabled}
            onClick={() => editor?.chain().focus().setTextAlign('center').run()}
          >
            <AlignCenter className="h-4 w-4" />
          </ToolbarButton>
          <span className="mx-2 w-px bg-border self-stretch" />
          {VARIABLES.map((v) => (
            <Button
              key={v.token}
              type="button"
              variant="outline"
              size="sm"
              className="h-8 text-xs"
              disabled={disabled}
              onClick={() => editor?.chain().focus().insertContent(v.token).run()}
            >
              {v.label}
            </Button>
          ))}
        </div>
        <EditorContent editor={editor} className="escrito-tiptap bg-background min-h-[280px] p-4 text-sm" />
      </div>

      <div className="flex flex-wrap items-center gap-3">
        <Button type="submit" disabled={disabled}>
          {guardarMutation.isPending
            ? 'Generando PDF…'
            : isEditing
              ? 'Guardar cambios y regenerar PDF'
              : 'Guardar y generar PDF'}
        </Button>
        {onCancel && (
          <Button type="button" variant="outline" onClick={onCancel} disabled={guardarMutation.isPending}>
            Cancelar
          </Button>
        )}
        {guardarMutation.error && (
          <p className="text-sm text-destructive">{guardarMutation.error.message}</p>
        )}
      </div>
    </form>
  );
}
