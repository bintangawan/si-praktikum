import { TRPCError } from '@trpc/server';
import * as XLSX from '@e965/xlsx';
import { appRouter } from '@/server/routers';
import { createTRPCContext } from '@/server/trpc/context';

export const runtime = 'nodejs';

const text = (value: unknown) => String(value ?? '').trim();
const normalizeHeader = (value: string) => value.trim().replace(/^\uFEFF/, '').toLowerCase().replace(/[\s-]+/g, '_');

export async function POST(request: Request) {
  try {
    const form = await request.formData();
    const file = form.get('file');
    const slug = text(form.get('slug'));
    if (!slug) return Response.json({ error: 'Kelas tidak valid.' }, { status: 400 });
    if (!(file instanceof File)) return Response.json({ error: 'Pilih file Excel atau CSV untuk diimpor.' }, { status: 400 });
    if (file.size < 1 || file.size > 4 * 1024 * 1024) return Response.json({ error: 'Ukuran file maksimum 4 MB.' }, { status: 413 });
    if (!/\.(xlsx|xls|csv)$/i.test(file.name)) return Response.json({ error: 'Format file harus XLS, XLSX, atau CSV.' }, { status: 400 });

    const workbook = XLSX.read(Buffer.from(await file.arrayBuffer()), { type: 'buffer', cellDates: false, raw: false });
    const firstSheet = workbook.Sheets[workbook.SheetNames[0] ?? ''];
    if (!firstSheet) return Response.json({ error: 'File tidak berisi lembar kerja.' }, { status: 400 });
    const source = XLSX.utils.sheet_to_json<unknown[]>(firstSheet, { header: 1, defval: '', raw: false, blankrows: true });
    if (source.length < 2) return Response.json({ error: 'Tambahkan minimal satu NIM setelah baris judul.' }, { status: 400 });

    const headers = source[0].map((value) => normalizeHeader(text(value)));
    const studentIdIndex = headers.findIndex((header) => ['nim', 'id', 'student_id', 'studentid'].includes(header));
    if (studentIdIndex < 0) {
      return Response.json({ error: 'Baris pertama harus memiliki kolom NIM.' }, { status: 400 });
    }

    const rows = source.slice(1).flatMap((row, index) => row.some((value) => text(value)) ? [{
      rowNumber: index + 2,
      studentId: text(row[studentIdIndex]),
    }] : []);
    if (!rows.length || rows.length > 1000) return Response.json({ error: 'File harus berisi 1 sampai 1000 baris data mahasiswa.' }, { status: 400 });

    const caller = appRouter.createCaller(await createTRPCContext());
    return Response.json(await caller.courses.importStudents({ slug, rows }));
  } catch (error) {
    const status = error instanceof TRPCError ? error.code === 'UNAUTHORIZED' ? 401 : error.code === 'FORBIDDEN' ? 403 : 400 : 500;
    const message = error instanceof Error ? error.message : 'Import gagal diproses. Periksa format dan isi file.';
    if (status >= 500) console.error('Course student import failed.', message);
    return Response.json({ error: status >= 500 ? 'Import gagal diproses. Periksa format dan isi file.' : message }, { status });
  }
}
