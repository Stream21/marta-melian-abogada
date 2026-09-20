import * as pdfjsLib from 'pdfjs-dist';
import pdfjsWorkerUrl from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

/**
 * pdf.js carga el worker con import(). Si nginx sirve el .mjs como
 * application/octet-stream, el navegador falla con "Failed to fetch dynamically
 * imported module". Re-empaquetamos el worker en un Blob con MIME JS.
 */
let workerReady: Promise<void> | null = null;

function ensurePdfWorker(): Promise<void> {
  if (!workerReady) {
    workerReady = (async () => {
      const response = await fetch(pdfjsWorkerUrl);
      if (!response.ok) {
        throw new Error('No se pudo inicializar el visor PDF.');
      }
      const code = await response.text();
      const blob = new Blob([code], { type: 'text/javascript' });
      pdfjsLib.GlobalWorkerOptions.workerSrc = URL.createObjectURL(blob);
    })().catch((err: unknown) => {
      workerReady = null;
      throw err;
    });
  }
  return workerReady;
}

const RENDER_SCALE = 1.4;

export async function renderPdfPages(container: HTMLElement, blobUrl: string): Promise<number> {
  await ensurePdfWorker();

  const loadingTask = pdfjsLib.getDocument(blobUrl);
  const pdf = await loadingTask.promise;
  container.replaceChildren();

  for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
    const page = await pdf.getPage(pageNum);
    const viewport = page.getViewport({ scale: RENDER_SCALE });
    const canvas = document.createElement('canvas');
    canvas.className = 'mx-auto block max-w-full shadow-sm';
    canvas.width = viewport.width;
    canvas.height = viewport.height;

    const wrapper = document.createElement('div');
    wrapper.className = 'mb-4 bg-white';
    wrapper.appendChild(canvas);
    container.appendChild(wrapper);

    const context = canvas.getContext('2d');
    if (!context) {
      throw new Error('No se pudo inicializar el visor del documento.');
    }

    await page.render({ canvasContext: context, viewport }).promise;
  }

  return pdf.numPages;
}

export async function renderPdfThumbnail(blobUrl: string, maxWidth = 320): Promise<string> {
  await ensurePdfWorker();

  const loadingTask = pdfjsLib.getDocument(blobUrl);
  const pdf = await loadingTask.promise;
  const page = await pdf.getPage(1);
  const unscaled = page.getViewport({ scale: 1 });
  const scale = maxWidth / unscaled.width;
  const viewport = page.getViewport({ scale: Math.max(0.35, Math.min(scale, 1.6)) });
  const canvas = document.createElement('canvas');
  canvas.width = viewport.width;
  canvas.height = viewport.height;
  const context = canvas.getContext('2d');
  if (!context) {
    throw new Error('No se pudo generar la miniatura del documento.');
  }
  await page.render({ canvasContext: context, viewport }).promise;
  return canvas.toDataURL('image/jpeg', 0.78);
}

export function isScrollAtEnd(element: HTMLElement, threshold = 32): boolean {
  return element.scrollHeight - element.scrollTop - element.clientHeight <= threshold;
}
