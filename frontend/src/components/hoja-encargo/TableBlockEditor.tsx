import type { BloqueTable } from '@/lib/hoja-encargo-variables';
import { cn } from '@/lib/utils';

interface TableBlockEditorProps {
  bloque: BloqueTable;
  selected: boolean;
  onSelect: () => void;
  onChange: (bloque: BloqueTable) => void;
}

function stopEditPropagation(e: React.SyntheticEvent) {
  e.stopPropagation();
}

export function TableBlockEditor({ bloque, selected, onSelect, onChange }: TableBlockEditorProps) {
  const updateRow = (index: number, field: 'label' | 'value', value: string) => {
    const rows = bloque.rows.map((row, rowIndex) =>
      rowIndex === index ? { ...row, [field]: value } : row,
    );
    onChange({ ...bloque, rows });
  };

  return (
    <div
      className={cn(
        'min-w-0 overflow-hidden rounded-lg border p-3 transition-colors',
        selected ? 'border-primary bg-primary/5 ring-1 ring-primary/20' : 'border-transparent',
        'cursor-pointer hover:border-border hover:bg-muted/20',
      )}
      onClick={onSelect}
    >
      <p className="section-label mb-2">Tabla legal</p>
      <input
        value={bloque.clauseTitle}
        onChange={(e) => onChange({ ...bloque, clauseTitle: e.target.value })}
        onClick={stopEditPropagation}
        onMouseDown={stopEditPropagation}
        onFocus={onSelect}
        className="escrito-clause-subtitle mb-2 w-full bg-transparent focus:outline-none"
      />
      <input
        value={bloque.title}
        onChange={(e) => onChange({ ...bloque, title: e.target.value })}
        onClick={stopEditPropagation}
        onMouseDown={stopEditPropagation}
        onFocus={onSelect}
        className="mb-1 w-full bg-transparent text-center text-sm font-bold text-[#2c3e6b] focus:outline-none"
      />
      <input
        value={bloque.subtitle}
        onChange={(e) => onChange({ ...bloque, subtitle: e.target.value })}
        onClick={stopEditPropagation}
        onMouseDown={stopEditPropagation}
        onFocus={onSelect}
        className="mb-3 w-full bg-transparent text-center text-xs italic text-muted-foreground focus:outline-none"
      />
      <table className="escrito-legal-table w-full text-sm">
        <tbody>
          {bloque.rows.map((row, index) => (
            <tr key={`${row.label}-${index}`}>
              <td className="escrito-legal-table-label align-top">
                <input
                  value={row.label}
                  onChange={(e) => updateRow(index, 'label', e.target.value)}
                  onClick={stopEditPropagation}
                  onMouseDown={stopEditPropagation}
                  onFocus={onSelect}
                  className="w-full bg-transparent font-bold focus:outline-none"
                />
              </td>
              <td className="escrito-legal-table-value align-top">
                <textarea
                  value={row.value}
                  onChange={(e) => updateRow(index, 'value', e.target.value)}
                  onClick={stopEditPropagation}
                  onMouseDown={stopEditPropagation}
                  onFocus={onSelect}
                  rows={2}
                  className="w-full resize-y bg-transparent focus:outline-none"
                />
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
