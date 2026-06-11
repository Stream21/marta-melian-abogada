/** Marco guía: documento en posición horizontal (landscape). */
export function EncuadreHorizontal() {
  return (
    <div
      className="pointer-events-none absolute inset-6 flex items-center justify-center"
      aria-hidden
    >
      <div className="relative h-[38%] w-[88%] max-w-md rounded-lg border-2 border-dashed border-primary/50">
        <span className="absolute -top-6 left-0 text-[10px] font-medium uppercase tracking-wide text-primary/80">
          Horizontal
        </span>
        <span className="absolute -bottom-6 right-0 text-[10px] text-muted-foreground">
          Ancho &gt; alto
        </span>
      </div>
    </div>
  );
}
