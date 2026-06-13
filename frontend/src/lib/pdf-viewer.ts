import * as pdfjsLib from 'pdfjs-dist';
import pdfjsWorker from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = pdfjsWorker;

const RENDER_SCALE = 1.4;

export async function renderPdfPages(container: HTMLElement, blobUrl: string): Promise<number> {
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

export function isScrollAtEnd(element: HTMLElement, threshold = 32): boolean {
  return element.scrollHeight - element.scrollTop - element.clientHeight <= threshold;
}
