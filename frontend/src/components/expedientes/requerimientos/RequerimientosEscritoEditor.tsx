import { useMutation, useQueryClient } from '@tanstack/react-query';
import Placeholder from '@tiptap/extension-placeholder';
import TextAlign from '@tiptap/extension-text-align';
import { EditorContent, useEditor } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import {
  AlignCenter,
  AlignLeft,
  Bold,
  FileDown,
  Heading2,
  Heading3,
  Italic,
  List,
  ListOrdered,
} from 'lucide-react';
import { useReducer, useState, type ReactNode } from 'react';
import { api, type RequerimientosEscritoResponse } from '@/api/client';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

interface RequerimientosEscritoEditorProps {
  expedienteId: string;
  escritos: RequerimientosEscritoResponse[];
}

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

export function RequerimientosEscritoEditor({
  expedienteId,
  escritos,
}: RequerimientosEscritoEditorProps) {
  const queryClient = useQueryClient();
  const [titulo, setTitulo] = useState('');
  const [, rerenderToolbar] = useReducer((n: number) => n + 1, 0);

  const editor = useEditor({
    extensions: [
      StarterKit.configure({
        heading: { levels: [2, 3] },
      }),
      TextAlign.configure({
        types: ['heading', 'paragraph'],
      }),
      Placeholder.configure({
        placeholder: 'Escriba aquí el contenido del escrito…',
      }),
    ],
    editorProps: {
      attributes: {
        class: 'escrito-tiptap-editor focus:outline-none',
      },
    },
    onSelectionUpdate: () => rerenderToolbar(),
    onTransaction: () => rerenderToolbar(),
  });

  const guardarMutation = useMutation({
    mutationFn: () => {
      const contenidoHtml = editor?.getHTML() ?? '';
      return api.guardarEscritoRequerimientos(expedienteId, {
        titulo: titulo.trim(),
        contenidoHtml,
      });
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['requerimientos', expedienteId] });
      setTitulo('');
      editor?.commands.clearContent();
    },
  });

  const insertVariable = (token: string) => {
    editor?.chain().focus().insertContent(token).run();
  };

  const disabled = !editor || guardarMutation.isPending;

  return (
    <div className="panel p-6 space-y-4">
      <div>
        <p className="section-label">Escritos</p>
        <h3 className="text-lg font-semibold">Generar escrito con firma</h3>
        <p className="text-sm text-muted-foreground mt-1">
          Redacte el contenido; al guardar se generará un PDF con el sello del abogado.
        </p>
      </div>

      <div className="space-y-2">
        <Label htmlFor="escrito-titulo">Título del escrito</Label>
        <Input
          id="escrito-titulo"
          value={titulo}
          onChange={(e) => setTitulo(e.target.value)}
          placeholder="Ej. Escrito de alegaciones"
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
              onClick={() => insertVariable(v.token)}
            >
              {v.label}
            </Button>
          ))}
        </div>

        <EditorContent editor={editor} className="escrito-tiptap bg-background min-h-[200px] p-4 text-sm" />
      </div>

      <div className="flex items-center gap-3">
        <Button
          onClick={() => guardarMutation.mutate()}
          disabled={guardarMutation.isPending || titulo.trim().length < 2 || !editor}
        >
          {guardarMutation.isPending ? 'Generando PDF…' : 'Guardar y generar PDF'}
        </Button>
        {guardarMutation.error && (
          <p className="text-sm text-destructive">{guardarMutation.error.message}</p>
        )}
      </div>

      {escritos.length > 0 && (
        <div className="space-y-2 pt-2 border-t">
          <p className="text-sm font-medium">Escritos generados</p>
          <ul className="space-y-2">
            {escritos.map((e) => (
              <li
                key={e.id}
                className="flex flex-wrap items-center justify-between gap-2 rounded-lg border p-3 text-sm"
              >
                <span>{e.titulo}</span>
                <Button variant="outline" size="sm" asChild>
                  <a
                    href={api.requerimientosEscritoPdfUrl(expedienteId, e.id)}
                    target="_blank"
                    rel="noreferrer"
                  >
                    <FileDown className="mr-2 h-4 w-4" />
                    Descargar PDF
                  </a>
                </Button>
              </li>
            ))}
          </ul>
        </div>
      )}
    </div>
  );
}
