import { serveSubmissionFile } from '@/server/file-response';

export async function GET(_request: Request, { params }: { params: Promise<{ historyId: string }> }) {
  const { historyId } = await params;
  const id = Number(historyId);
  if (!Number.isSafeInteger(id) || id < 1) return new Response('Not found', { status: 404 });
  return serveSubmissionFile('history', id);
}
