import { Link } from '@tanstack/react-router';
import { Settings2 } from 'lucide-react';
import type { HojaEncargoVariableCategory } from '@/api/client';
import { VariableChip } from '@/components/hoja-encargo/VariableChip';
import { Button } from '@/components/ui/button';
import { setVariableDragData } from '@/lib/variable-drag';

interface VariablesSidebarProps {
  categories: HojaEncargoVariableCategory[];
  onInsertVariable: (key: string) => void;
  disabled?: boolean;
}

export function VariablesSidebar({ categories, onInsertVariable, disabled }: VariablesSidebarProps) {
  return (
    <aside className="panel flex h-full max-h-full min-h-0 flex-col overflow-hidden">
      <div className="border-b px-4 py-4">
        <h2 className="section-label">Variables legales</h2>
        <p className="mt-1 text-xs text-muted-foreground">
          Arrastre una variable a un bloque de texto o haga clic para insertarla en el bloque seleccionado.
        </p>
      </div>

      <div className="flex-1 space-y-5 overflow-y-auto p-4">
        {categories.map((category) => (
          <div key={category.categoria}>
            <p className="section-label mb-2">{category.categoria.toUpperCase()}</p>
            <div className="flex flex-wrap gap-2">
              {category.variables.map((variable) => (
                <button
                  key={variable.key}
                  type="button"
                  disabled={disabled}
                  draggable={!disabled}
                  onDragStart={(e) => {
                    if (disabled) {
                      e.preventDefault();
                      return;
                    }
                    setVariableDragData(e.dataTransfer, variable.key);
                  }}
                  onClick={() => onInsertVariable(variable.key)}
                  className="cursor-grab transition-opacity hover:opacity-80 active:cursor-grabbing disabled:cursor-not-allowed disabled:opacity-40"
                  title={variable.label}
                >
                  <VariableChip variableKey={variable.key} />
                </button>
              ))}
            </div>
          </div>
        ))}
      </div>

      <div className="border-t p-4">
        <Button variant="outline" className="w-full" asChild>
          <Link to="/config/despacho">
            <Settings2 className="h-4 w-4" />
            Ver configuración de datos
          </Link>
        </Button>
      </div>
    </aside>
  );
}
