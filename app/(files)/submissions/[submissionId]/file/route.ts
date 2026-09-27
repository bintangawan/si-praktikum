import { serveSubmissionFile } from '@/server/file-response';

export async function GET(_request: Request, { params }: { params: Promise<{ submissionId: string }> }) {
  const { submissionId } = await params;
  const id = Number(submissionId);
  if (!Number.isSafeInteger(id) || id < 1) return new Response('Not found', { status: 404 });
  return serveSubmissionFile('submission', id);
}
