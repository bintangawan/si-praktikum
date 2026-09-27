import { TRPCError } from '@trpc/server';
import * as XLSX from '@e965/xlsx';
import { USER_ROLES } from '@/lib/roles';
import { appRouter } from '@/server/routers';
import { createTRPCContext } from '@/server/trpc/context';

export const runtime = 'nodejs';

const text = (value: unknown) => String(value ?? '').trim();
const normalizeHeader = (value: string) => value.trim().toLowerCase().replace(/[\s-]+/g, '_');

export async function POST(request: Request) {
  try {
    const form = await request.formData();
    const file = form.get('file');
    if (!(file instanceof File)) return Response.json({ error: 'Pilih file Excel untuk diimpor.' }, { status: 400 });
    if (file.size < 1 || file.size > 4 * 1024 * 1024) return Response.json({ error: 'Ukuran file maksimum 4 MB.' }, { status: 413 });
    if (!/\.(xlsx|xls|csv)$/i.test(file.name)) return Response.json({ error: 'Format file harus XLS, XLSX, atau CSV.' }, { status: 400 });

    const workbook = XLSX.read(Buffer.from(await file.arrayBuffer()), { type: 'buffer', cellDates: false, raw: true });
    const firstSheet = workbook.Sheets[workbook.SheetNames[0] ?? ''];
    if (!firstSheet) return Response.json({ error: 'File tidak berisi lembar kerja.' }, { status: 400 });
    const source = XLSX.utils.sheet_to_json<Record<string, unknown>>(firstSheet, { defval: '', raw: true, blankrows: false });
    if (!source.length || source.length > 1000) return Response.json({ error: 'File harus berisi 1 sampai 1000 baris data.' }, { status: 400 });
    const rows = source.map((row) => {
      const normalized = Object.fromEntries(Object.entries(row).map(([key, value]) => [normalizeHeader(key), value]));
      const role = USER_ROLES.find((item) => item.toLowerCase() === text(normalized.role).toLowerCase());
      return { id: text(normalized.id), name: text(normalized.name), email: text(normalized.email), role };
    });
    const invalidRow = rows.find((row) => !row.id || !row.name || !row.email || !row.role);
    if (invalidRow) return Response.json({ error: `Kolom id, name, email, dan role wajib diisi pada baris ${invalidRow.id || 'tanpa ID'}.` }, { status: 400 });

    const context = await createTRPCContext();
    const caller = appRouter.createCaller(context);
    const result = await caller.users.importUsers({ rows: rows as Array<{ id: string; name: string; email: string; role: (typeof USER_ROLES)[number] }> });
    return Response.json(result);
  } catch (error) {
    const status = error instanceof TRPCError ? error.code === 'UNAUTHORIZED' ? 401 : error.code === 'FORBIDDEN' ? 403 : 400 : 500;
    const message = error instanceof Error ? error.message : 'Import gagal diproses. Periksa format dan isi file.';
    if (status >= 500) console.error('User import failed.', message);
    return Response.json({ error: status >= 500 ? 'Import gagal diproses. Periksa format dan isi file.' : message }, { status });
  }
}
