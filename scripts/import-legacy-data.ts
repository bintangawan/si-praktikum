import { loadEnvConfig } from '@next/env';
import postgres from 'postgres';
import { drizzle } from 'drizzle-orm/postgres-js';
import { sql } from 'drizzle-orm';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { z } from 'zod';
import * as schema from '../src/server/db/schema';

loadEnvConfig(process.cwd());

type LegacyValue = string | null;
type LegacyRow = LegacyValue[];
type LegacyTable = 'users' | 'semesters' | 'courses' | 'meetings' | 'attendances' | 'course_user' | 'course_grades' | 'submissions' | 'submission_histories';

const sourceColumns: Record<LegacyTable, number> = {
  users: 13,
  semesters: 5,
  courses: 13,
  meetings: 10,
  attendances: 7,
  course_user: 5,
  course_grades: 7,
  submissions: 24,
  submission_histories: 13,
};

const applicationTables = [
  'users', 'user_sessions', 'semesters', 'courses', 'meetings', 'attendances',
  'final_tasks', 'submissions', 'submission_histories', 'course_user', 'tutorials',
  'course_staff_histories', 'course_grades',
] as const;

function readDatabaseUrl(): string {
  return z.string().trim().min(1).refine((value) => {
    try {
      return ['postgres:', 'postgresql:'].includes(new URL(value).protocol);
    } catch {
      return false;
    }
  }, 'DATABASE_URL harus berupa URL PostgreSQL').parse(process.env.DATABASE_URL);
}

function parseSqlString(source: string, start: number): { value: string; next: number } {
  let index = start + 1;
  let value = '';

  while (index < source.length) {
    const char = source[index];
    if (char === "'") {
      if (source[index + 1] === "'") {
        value += "'";
        index += 2;
        continue;
      }
      return { value, next: index + 1 };
    }
    if (char === '\\') {
      const escaped = source[index + 1];
      if (escaped === undefined) throw new Error('String SQL terpotong.');
      const escapes: Record<string, string> = { '0': '\0', b: '\b', n: '\n', r: '\r', t: '\t', Z: '\x1a' };
      value += escapes[escaped] ?? escaped;
      index += 2;
      continue;
    }
    value += char;
    index += 1;
  }
  throw new Error('String SQL tidak ditutup.');
}

function parseInsertRows(source: string, start: number): { rows: LegacyRow[]; next: number } {
  const rows: LegacyRow[] = [];
  let index = start;

  while (index < source.length) {
    while (/\s/.test(source[index] ?? '')) index += 1;
    if (source[index] !== '(') throw new Error('Format INSERT MariaDB tidak dikenali.');
    index += 1;
    const row: LegacyRow = [];

    while (index < source.length) {
      while (/\s/.test(source[index] ?? '')) index += 1;
      if (source[index] === "'") {
        const parsed = parseSqlString(source, index);
        row.push(parsed.value);
        index = parsed.next;
      } else {
        const valueStart = index;
        while (index < source.length && source[index] !== ',' && source[index] !== ')') index += 1;
        const raw = source.slice(valueStart, index).trim();
        row.push(raw.toUpperCase() === 'NULL' ? null : raw);
      }

      while (/\s/.test(source[index] ?? '')) index += 1;
      if (source[index] === ',') {
        index += 1;
        continue;
      }
      if (source[index] === ')') {
        index += 1;
        rows.push(row);
        break;
      }
      throw new Error('Pemisah nilai INSERT MariaDB tidak dikenali.');
    }

    while (/\s/.test(source[index] ?? '')) index += 1;
    if (source[index] === ',') {
      index += 1;
      continue;
    }
    if (source[index] === ';') return { rows, next: index + 1 };
    throw new Error('Akhir pernyataan INSERT MariaDB tidak dikenali.');
  }
  throw new Error('Pernyataan INSERT MariaDB tidak lengkap.');
}

function parseDump(source: string): Partial<Record<LegacyTable, LegacyRow[]>> {
  const tick = String.fromCharCode(96);
  const insertPattern = new RegExp('INSERT\\s+INTO\\s+' + tick + '([^' + tick + ']+)' + tick + '\\s+VALUES\\s*', 'gi');
  const tables: Partial<Record<LegacyTable, LegacyRow[]>> = {};
  let match: RegExpExecArray | null;

  while ((match = insertPattern.exec(source)) !== null) {
    const tableName = match[1] as LegacyTable;
    const parsed = parseInsertRows(source, insertPattern.lastIndex);
    insertPattern.lastIndex = parsed.next;
    if (!(tableName in sourceColumns)) continue;
    const table = tableName as LegacyTable;
    for (const row of parsed.rows) {
      if (row.length !== sourceColumns[table]) {
        throw new Error('Jumlah kolom tidak cocok pada tabel ' + table + '.');
      }
    }
    tables[table] = [...(tables[table] ?? []), ...parsed.rows];
  }
  return tables;
}

function requiredString(value: LegacyValue, table: string, row: number, field: string): string {
  if (value === null || value.length === 0) throw new Error('Nilai wajib kosong pada ' + table + ' baris ' + row + ' (' + field + ').');
  return value;
}

function optionalString(value: LegacyValue): string | null {
  return value;
}

function requiredInteger(value: LegacyValue, table: string, row: number, field: string): number {
  const parsed = value === null ? NaN : Number(value);
  if (!Number.isSafeInteger(parsed)) throw new Error('Integer tidak valid pada ' + table + ' baris ' + row + ' (' + field + ').');
  return parsed;
}

function optionalInteger(value: LegacyValue, table: string, row: number, field: string): number | null {
  return value === null ? null : requiredInteger(value, table, row, field);
}

function decimal(value: LegacyValue, table: string, row: number, field: string): string | null {
  if (value === null) return null;
  if (!/^-?\d+(\.\d+)?$/.test(value)) throw new Error('Angka desimal tidak valid pada ' + table + ' baris ' + row + ' (' + field + ').');
  return value;
}

function requiredBoolean(value: LegacyValue, table: string, row: number, field: string): boolean {
  if (value === '0') return false;
  if (value === '1') return true;
  throw new Error('Boolean MySQL tidak valid pada ' + table + ' baris ' + row + ' (' + field + ').');
}

function timestamp(value: LegacyValue, table: string, row: number, field: string): Date | null {
  if (value === null) return null;
  const parsed = new Date(value.replace(' ', 'T') + 'Z');
  if (Number.isNaN(parsed.getTime())) throw new Error('Timestamp tidak valid pada ' + table + ' baris ' + row + ' (' + field + ').');
  return parsed;
}

function requiredTimestamp(value: LegacyValue, table: string, row: number, field: string): Date {
  const parsed = timestamp(value, table, row, field);
  if (!parsed) throw new Error('Timestamp wajib kosong pada ' + table + ' baris ' + row + ' (' + field + ').');
  return parsed;
}

function requiredDecimal(value: LegacyValue, table: string, row: number, field: string): string {
  const parsed = decimal(value, table, row, field);
  if (parsed === null) throw new Error('Angka desimal wajib kosong pada ' + table + ' baris ' + row + ' (' + field + ').');
  return parsed;
}

function requiredDate(value: LegacyValue, table: string, row: number, field: string): string {
  if (value === null || !/^\d{4}-\d{2}-\d{2}$/.test(value)) {
    throw new Error('Tanggal tidak valid pada ' + table + ' baris ' + row + ' (' + field + ').');
  }
  const parsed = new Date(value + 'T00:00:00Z');
  if (Number.isNaN(parsed.getTime()) || parsed.toISOString().slice(0, 10) !== value) {
    throw new Error('Tanggal tidak valid pada ' + table + ' baris ' + row + ' (' + field + ').');
  }
  return value;
}

function defaultedTimestamp(value: LegacyValue, table: string, row: number, field: string): Date | undefined {
  return timestamp(value, table, row, field) ?? undefined;
}

function assertEnum(value: string, accepted: readonly string[], table: string, row: number, field: string): string {
  if (!accepted.includes(value)) throw new Error('Nilai enum tidak dikenal pada ' + table + ' baris ' + row + ' (' + field + ').');
  return value;
}

function assertUnique(values: string[], table: string, label: string) {
  if (new Set(values).size !== values.length) throw new Error('Nilai ' + label + ' duplikat pada tabel ' + table + '.');
}

function assertReferences(rows: LegacyRow[], sourceColumn: number, target: Set<string>, table: string, field: string, nullable = false) {
  rows.forEach((row, index) => {
    const value = row[sourceColumn];
    if (value === null && nullable) return;
    if (value === null || !target.has(value)) throw new Error('Relasi tidak valid pada ' + table + ' baris ' + (index + 1) + ' (' + field + ').');
  });
}

function validateSource(tables: Partial<Record<LegacyTable, LegacyRow[]>>) {
  for (const name of Object.keys(sourceColumns) as LegacyTable[]) {
    tables[name] ??= [];
  }
  const rows = tables as Record<LegacyTable, LegacyRow[]>;
  const ids = (table: LegacyTable) => rows[table].map((row, index) => requiredString(row[0], table, index + 1, 'id'));
  const userIds = ids('users');
  const semesterIds = ids('semesters');
  const courseIds = ids('courses');
  const meetingIds = ids('meetings');
  const submissionIds = ids('submissions');
  const finalTaskIds = new Set<string>();

  for (const [table, values] of [['users', userIds], ['semesters', semesterIds], ['courses', courseIds], ['meetings', meetingIds], ['submissions', submissionIds]] as const) assertUnique(values, table, 'id');
  assertUnique(rows.users.map((row, index) => requiredString(row[2], 'users', index + 1, 'email')), 'users', 'email');

  rows.users.forEach((row, index) => {
    assertEnum(requiredString(row[5], 'users', index + 1, 'role'), ['Dosen', 'Mahasiswa', 'Laboran', 'Aslab'], 'users', index + 1, 'role');
    requiredBoolean(row[6], 'users', index + 1, 'is_first_login');
    requiredTimestamp(row[9], 'users', index + 1, 'created_at');
    requiredTimestamp(row[10], 'users', index + 1, 'updated_at');
  });
  rows.semesters.forEach((row, index) => {
    requiredString(row[1], 'semesters', index + 1, 'name');
    requiredBoolean(row[2], 'semesters', index + 1, 'is_active');
    requiredTimestamp(row[3], 'semesters', index + 1, 'created_at');
    requiredTimestamp(row[4], 'semesters', index + 1, 'updated_at');
  });

  const userSet = new Set(userIds);
  const semesterSet = new Set(semesterIds);
  const courseSet = new Set(courseIds);
  const meetingSet = new Set(meetingIds);
  const submissionSet = new Set(submissionIds);
  assertReferences(rows.users.filter((row) => row[12] !== null), 12, userSet, 'users', 'approved_by');
  assertReferences(rows.courses, 2, semesterSet, 'courses', 'semester_id');
  for (const field of [7, 8, 9]) assertReferences(rows.courses, field, userSet, 'courses', 'user reference');
  assertReferences(rows.meetings, 1, courseSet, 'meetings', 'course_id');
  assertReferences(rows.attendances, 1, meetingSet, 'attendances', 'meeting_id');
  assertReferences(rows.attendances, 2, userSet, 'attendances', 'student_id');
  assertReferences(rows.course_user, 1, courseSet, 'course_user', 'course_id');
  assertReferences(rows.course_user, 2, userSet, 'course_user', 'user_id');
  assertReferences(rows.course_grades, 1, courseSet, 'course_grades', 'course_id');
  assertReferences(rows.course_grades, 2, userSet, 'course_grades', 'student_id');
  assertReferences(rows.submissions, 1, userSet, 'submissions', 'student_id');
  assertReferences(rows.submissions.filter((row) => row[2] !== null), 2, meetingSet, 'submissions', 'meeting_id');
  assertReferences(rows.submissions.filter((row) => row[3] !== null), 3, finalTaskIds, 'submissions', 'final_task_id');
  assertReferences(rows.submission_histories, 1, submissionSet, 'submission_histories', 'submission_id');
  assertReferences(rows.submission_histories.filter((row) => row[9] !== null), 9, userSet, 'submission_histories', 'reviewed_by');
  return rows;
}

function mapSource(tables: Record<LegacyTable, LegacyRow[]>) {
  const rows = tables;
  return {
    users: rows.users.map((r, i) => ({
      id: requiredString(r[0], 'users', i + 1, 'id'),
      name: requiredString(r[1], 'users', i + 1, 'name'),
      email: requiredString(r[2], 'users', i + 1, 'email'),
      password: requiredString(r[4], 'users', i + 1, 'password'),
      role: assertEnum(requiredString(r[5], 'users', i + 1, 'role'), ['Dosen', 'Mahasiswa', 'Laboran', 'Aslab'], 'users', i + 1, 'role') as 'Dosen' | 'Mahasiswa' | 'Laboran' | 'Aslab',
      isFirstLogin: requiredBoolean(r[6], 'users', i + 1, 'is_first_login'),
      avatar: optionalString(r[7]),
      rememberToken: optionalString(r[8]),
      createdAt: requiredTimestamp(r[9], 'users', i + 1, 'created_at'),
      updatedAt: requiredTimestamp(r[10], 'users', i + 1, 'updated_at'),
      approvedAt: timestamp(r[11], 'users', i + 1, 'approved_at'),
      approvedBy: optionalString(r[12]),
    })),
    semesters: rows.semesters.map((r, i) => ({
      id: requiredInteger(r[0], 'semesters', i + 1, 'id'),
      name: requiredString(r[1], 'semesters', i + 1, 'name'),
      isActive: requiredBoolean(r[2], 'semesters', i + 1, 'is_active'),
      createdAt: requiredTimestamp(r[3], 'semesters', i + 1, 'created_at'),
      updatedAt: requiredTimestamp(r[4], 'semesters', i + 1, 'updated_at'),
    })),
    courses: rows.courses.map((r, i) => ({
      id: requiredInteger(r[0], 'courses', i + 1, 'id'),
      slug: requiredString(r[1], 'courses', i + 1, 'slug'),
      semesterId: requiredInteger(r[2], 'courses', i + 1, 'semester_id'),
      isArchived: requiredBoolean(r[3], 'courses', i + 1, 'is_archived'),
      courseName: requiredString(r[4], 'courses', i + 1, 'course_name'),
      classGroup: requiredString(r[5], 'courses', i + 1, 'class_group'),
      targetSemester: requiredInteger(r[6], 'courses', i + 1, 'target_semester'),
      dosenId: requiredString(r[7], 'courses', i + 1, 'dosen_id'),
      laboranId: requiredString(r[8], 'courses', i + 1, 'laboran_id'),
      aslabId: requiredString(r[9], 'courses', i + 1, 'aslab_id'),
      enrollmentCode: requiredString(r[10], 'courses', i + 1, 'enrollment_code'),
      createdAt: defaultedTimestamp(r[11], 'courses', i + 1, 'created_at'),
      updatedAt: defaultedTimestamp(r[12], 'courses', i + 1, 'updated_at'),
    })),
    meetings: rows.meetings.map((r, i) => ({
      id: requiredInteger(r[0], 'meetings', i + 1, 'id'),
      courseId: requiredInteger(r[1], 'meetings', i + 1, 'course_id'),
      meetingNumber: requiredInteger(r[2], 'meetings', i + 1, 'meeting_number'),
      title: requiredString(r[3], 'meetings', i + 1, 'title'),
      description: optionalString(r[4]),
      moduleDriveLink: optionalString(r[5]),
      deadline: timestamp(r[6], 'meetings', i + 1, 'deadline'),
      publishedAt: timestamp(r[7], 'meetings', i + 1, 'published_at'),
      createdAt: defaultedTimestamp(r[8], 'meetings', i + 1, 'created_at'),
      updatedAt: defaultedTimestamp(r[9], 'meetings', i + 1, 'updated_at'),
    })),
    attendances: rows.attendances.map((r, i) => ({
      id: requiredInteger(r[0], 'attendances', i + 1, 'id'),
      meetingId: requiredInteger(r[1], 'attendances', i + 1, 'meeting_id'),
      studentId: requiredString(r[2], 'attendances', i + 1, 'student_id'),
      status: assertEnum(requiredString(r[3], 'attendances', i + 1, 'status'), ['Hadir', 'Sakit', 'Izin', 'Tanpa Keterangan', 'H', 'S', 'I', 'TK', 'Alpha'], 'attendances', i + 1, 'status') as typeof schema.attendances.$inferInsert.status,
      attendanceDate: requiredDate(r[4], 'attendances', i + 1, 'attendance_date'),
      createdAt: defaultedTimestamp(r[5], 'attendances', i + 1, 'created_at'),
      updatedAt: defaultedTimestamp(r[6], 'attendances', i + 1, 'updated_at'),
    })),
    courseUsers: rows.course_user.map((r, i) => ({
      id: requiredInteger(r[0], 'course_user', i + 1, 'id'),
      courseId: requiredInteger(r[1], 'course_user', i + 1, 'course_id'),
      userId: requiredString(r[2], 'course_user', i + 1, 'user_id'),
      createdAt: defaultedTimestamp(r[3], 'course_user', i + 1, 'created_at'),
      updatedAt: defaultedTimestamp(r[4], 'course_user', i + 1, 'updated_at'),
    })),
    courseGrades: rows.course_grades.map((r, i) => ({
      id: requiredInteger(r[0], 'course_grades', i + 1, 'id'),
      courseId: requiredInteger(r[1], 'course_grades', i + 1, 'course_id'),
      studentId: requiredString(r[2], 'course_grades', i + 1, 'student_id'),
      utsScore: requiredDecimal(r[3], 'course_grades', i + 1, 'uts_score'),
      uasScore: requiredDecimal(r[4], 'course_grades', i + 1, 'uas_score'),
      createdAt: defaultedTimestamp(r[5], 'course_grades', i + 1, 'created_at'),
      updatedAt: defaultedTimestamp(r[6], 'course_grades', i + 1, 'updated_at'),
    })),
    submissions: rows.submissions.map((r, i) => ({
      id: requiredInteger(r[0], 'submissions', i + 1, 'id'),
      studentId: requiredString(r[1], 'submissions', i + 1, 'student_id'),
      meetingId: optionalInteger(r[2], 'submissions', i + 1, 'meeting_id'),
      finalTaskId: optionalInteger(r[3], 'submissions', i + 1, 'final_task_id'),
      submissionLink: optionalString(r[4]),
      filePath: optionalString(r[5]),
      originalFilename: optionalString(r[6]),
      fileSize: optionalInteger(r[7], 'submissions', i + 1, 'file_size'),
      notes: optionalString(r[8]),
      isFinal: requiredBoolean(r[9], 'submissions', i + 1, 'is_final'),
      aslabStatus: assertEnum(requiredString(r[10], 'submissions', i + 1, 'aslab_status'), ['Pending', 'Revisi', 'Ditolak', 'ACC'], 'submissions', i + 1, 'aslab_status') as typeof schema.submissions.$inferInsert.aslabStatus,
      laboranStatus: assertEnum(requiredString(r[11], 'submissions', i + 1, 'laboran_status'), ['Pending', 'Revisi', 'Ditolak', 'ACC'], 'submissions', i + 1, 'laboran_status') as typeof schema.submissions.$inferInsert.laboranStatus,
      dosenStatus: assertEnum(requiredString(r[12], 'submissions', i + 1, 'dosen_status'), ['N/A', 'Pending', 'Revisi', 'Ditolak', 'ACC'], 'submissions', i + 1, 'dosen_status') as typeof schema.submissions.$inferInsert.dosenStatus,
      aslabScore: decimal(r[13], 'submissions', i + 1, 'aslab_score'),
      laboranScore: decimal(r[14], 'submissions', i + 1, 'laboran_score'),
      isCompleted: requiredBoolean(r[15], 'submissions', i + 1, 'is_completed'),
      firstUploadAt: timestamp(r[16], 'submissions', i + 1, 'first_upload_at'),
      lastUploadAt: timestamp(r[17], 'submissions', i + 1, 'last_upload_at'),
      aslabAccAt: timestamp(r[18], 'submissions', i + 1, 'aslab_acc_at'),
      laboranAccAt: timestamp(r[19], 'submissions', i + 1, 'laboran_acc_at'),
      dosenAccAt: timestamp(r[20], 'submissions', i + 1, 'dosen_acc_at'),
      createdAt: requiredTimestamp(r[21], 'submissions', i + 1, 'created_at'),
      updatedAt: requiredTimestamp(r[22], 'submissions', i + 1, 'updated_at'),
      documentVersion: requiredInteger(r[23], 'submissions', i + 1, 'document_version'),
    })),
    submissionHistories: rows.submission_histories.map((r, i) => ({
      id: requiredInteger(r[0], 'submission_histories', i + 1, 'id'),
      submissionId: requiredInteger(r[1], 'submission_histories', i + 1, 'submission_id'),
      driveLink: requiredString(r[2], 'submission_histories', i + 1, 'drive_link'),
      filePath: optionalString(r[3]),
      originalFilename: optionalString(r[4]),
      fileSize: optionalInteger(r[5], 'submission_histories', i + 1, 'file_size'),
      iteration: requiredInteger(r[6], 'submission_histories', i + 1, 'iteration'),
      feedback: optionalString(r[7]),
      actionType: assertEnum(requiredString(r[8], 'submission_histories', i + 1, 'action_type'), ['Upload', 'Revision', 'Rejected', 'ACC'], 'submission_histories', i + 1, 'action_type') as typeof schema.submissionHistories.$inferInsert.actionType,
      reviewedBy: optionalString(r[9]),
      createdAt: requiredTimestamp(r[10], 'submission_histories', i + 1, 'created_at'),
      updatedAt: requiredTimestamp(r[11], 'submission_histories', i + 1, 'updated_at'),
      documentVersion: requiredInteger(r[12], 'submission_histories', i + 1, 'document_version'),
    })),
  };
}

function reportCounts(tables: Record<LegacyTable, LegacyRow[]>) {
  console.log('Validasi dump legacy berhasil. Jumlah row yang siap diimpor:');
  for (const table of Object.keys(sourceColumns) as LegacyTable[]) console.log('  ' + table + ': ' + tables[table].length);
  console.log('Data dosen: email sudah menggunakan domain gmail.com. Tidak ada nilai data atau kredensial yang ditampilkan.');
}

async function main() {
  const dumpPath = resolve(process.cwd(), 'si-praktikum.sql');
  const parsed = validateSource(parseDump(readFileSync(dumpPath, 'utf8')));
  const mapped = mapSource(parsed);
  reportCounts(parsed);

  if (process.argv.includes('--check')) return;
  if (process.argv.includes('--verify') && process.argv.length === 3) {
    const client = postgres(readDatabaseUrl(), {
      max: 1,
      connect_timeout: Number(process.env.DB_CONNECT_TIMEOUT ?? 10),
      idle_timeout: Number(process.env.DB_IDLE_TIMEOUT ?? 20),
      prepare: false,
      ssl: 'require',
    });
    try {
      const db = drizzle(client, { schema });
      const countRows = await db.execute<{ table_name: string; row_count: number | string }>(sql.raw(
        'SELECT ' + applicationTables.map((table) => '\'' + table + '\'::text AS table_name, COUNT(*)::int AS row_count FROM public."' + table + '"').join(' UNION ALL SELECT ')
      ));
      const expected: Record<string, number> = {
        users: mapped.users.length,
        user_sessions: 0,
        semesters: mapped.semesters.length,
        courses: mapped.courses.length,
        meetings: mapped.meetings.length,
        attendances: mapped.attendances.length,
        final_tasks: 0,
        submissions: mapped.submissions.length,
        submission_histories: mapped.submissionHistories.length,
        course_user: mapped.courseUsers.length,
        tutorials: 0,
        course_staff_histories: 0,
        course_grades: mapped.courseGrades.length,
      };
      const mismatches = countRows
        .filter((row) => Number(row.row_count) !== expected[row.table_name])
        .map((row) => row.table_name + ': expected ' + expected[row.table_name] + ', found ' + row.row_count);
      const expectedDosen = parsed.users.filter((row) => row[0]?.startsWith('DOSEN-')).length;
      const dosenResult = await db.execute<{ lecturer_count: number | string; invalid_email_count: number | string }>(sql`
        SELECT COUNT(*) FILTER (WHERE id LIKE 'DOSEN-%')::int AS lecturer_count,
               COUNT(*) FILTER (WHERE id LIKE 'DOSEN-%' AND email NOT LIKE '%@gmail.com')::int AS invalid_email_count
        FROM public.users
      `);
      const dosenCounts = dosenResult[0];
      if (Number(dosenCounts?.lecturer_count) !== expectedDosen || Number(dosenCounts?.invalid_email_count) !== 0) {
        mismatches.push('email dosen tidak sesuai jumlah sumber atau domain yang diharapkan');
      }
      if (mismatches.length) throw new Error('Verifikasi database gagal: ' + mismatches.join('; '));
      console.log('Verifikasi Supabase berhasil; seluruh jumlah row cocok dan email akun DOSEN memakai gmail.com.');
    } finally {
      await client.end({ timeout: 5 });
    }
    return;
  }
  if (!process.argv.includes('--apply') || process.argv.includes('--check') || process.argv.length !== 3) {
    throw new Error('Pilih tepat satu mode: --check, --apply, atau --verify. Import hanya dijalankan dengan --apply.');
  }

  const databaseUrl = readDatabaseUrl();
  const poolMax = Number(process.env.DB_POOL_MAX ?? 1);
  const connectTimeout = Number(process.env.DB_CONNECT_TIMEOUT ?? 10);
  const idleTimeout = Number(process.env.DB_IDLE_TIMEOUT ?? 20);
  const client = postgres(databaseUrl, {
    max: poolMax,
    connect_timeout: connectTimeout,
    idle_timeout: idleTimeout,
    prepare: false,
    ssl: 'require',
  });
  const db = drizzle(client, { schema });

  try {
    await db.transaction(async (tx) => {
      await tx.execute(sql`select pg_advisory_xact_lock(7812456309123)`);
      const existing = await tx.execute<{ table_name: string }>(sql.raw(
        'SELECT table_name FROM information_schema.tables WHERE table_schema = \'public\' AND table_name IN (' + applicationTables.map((table) => '\'' + table + '\'').join(', ') + ') AND table_type = \'BASE TABLE\''
      ));
      const existingNames = new Set(existing.map((row) => row.table_name));
      const missing = applicationTables.filter((table) => !existingNames.has(table));
      if (missing.length) throw new Error('Skema belum lengkap; jalankan migrasi Drizzle terlebih dahulu. Tabel hilang: ' + missing.join(', ') + '.');

      await tx.execute(sql.raw('LOCK TABLE ' + applicationTables.map((table) => 'public."' + table + '"').join(', ') + ' IN SHARE ROW EXCLUSIVE MODE'));

      const populated = await tx.execute<{ table_name: string }>(sql.raw(
        'SELECT table_name FROM (' + applicationTables.map((table) => 'SELECT \' ' + table + '\'::text AS table_name WHERE EXISTS (SELECT 1 FROM public."' + table + '" LIMIT 1)').join(' UNION ALL ') + ') AS nonempty'
      ));
      if (populated.length) throw new Error('Impor dibatalkan: tabel aplikasi sudah berisi data (' + populated.map((row) => row.table_name.trim()).join(', ') + '). Tidak ada perubahan yang disimpan.');

      if (mapped.users.length) await tx.insert(schema.users).values(mapped.users);
      if (mapped.semesters.length) await tx.insert(schema.semesters).values(mapped.semesters);
      if (mapped.courses.length) await tx.insert(schema.courses).values(mapped.courses);
      if (mapped.meetings.length) await tx.insert(schema.meetings).values(mapped.meetings);
      if (mapped.attendances.length) await tx.insert(schema.attendances).values(mapped.attendances);
      if (mapped.courseUsers.length) await tx.insert(schema.courseUsers).values(mapped.courseUsers);
      if (mapped.courseGrades.length) await tx.insert(schema.courseGrades).values(mapped.courseGrades);
      if (mapped.submissions.length) await tx.insert(schema.submissions).values(mapped.submissions);
      if (mapped.submissionHistories.length) await tx.insert(schema.submissionHistories).values(mapped.submissionHistories);

      const serialTables = ['semesters', 'courses', 'meetings', 'attendances', 'final_tasks', 'submissions', 'submission_histories', 'course_user', 'tutorials', 'course_staff_histories', 'course_grades'] as const;
      for (const table of serialTables) {
        await tx.execute(sql.raw('SELECT setval(pg_get_serial_sequence(\'public."' + table + '"\', \'id\'), COALESCE((SELECT MAX(id) FROM public."' + table + '"), 1), EXISTS (SELECT 1 FROM public."' + table + '"))'));
      }
    });
  } finally {
    await client.end({ timeout: 5 });
  }

  console.log('Impor legacy selesai dan transaksi sudah commit. Jalankan aplikasi untuk pemeriksaan tampilan/alur.');
}

main().catch((error: unknown) => {
  const message = error instanceof Error ? error.message : 'Kesalahan tidak diketahui.';
  console.error('Import legacy gagal: ' + message);
  process.exitCode = 1;
});
