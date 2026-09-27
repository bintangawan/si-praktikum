import {
  bigint,
  bigserial,
  boolean,
  date,
  index,
  integer,
  numeric,
  pgEnum,
  pgTable,
  text,
  timestamp,
  unique,
  uniqueIndex,
  varchar,
} from 'drizzle-orm/pg-core';

const createdAt = () => timestamp('created_at', { withTimezone: true }).notNull().defaultNow();
const updatedAt = () => timestamp('updated_at', { withTimezone: true }).notNull().defaultNow();

export const userRoleEnum = pgEnum('user_role', ['Dosen', 'Mahasiswa', 'Laboran', 'Aslab']);
export const attendanceStatusEnum = pgEnum('attendance_status', [
  'Hadir', 'Sakit', 'Izin', 'Tanpa Keterangan', 'H', 'S', 'I', 'TK', 'Alpha',
]);
export const reviewStatusEnum = pgEnum('review_status', ['Pending', 'Revisi', 'Ditolak', 'ACC']);
export const lecturerReviewStatusEnum = pgEnum('lecturer_review_status', ['N/A', 'Pending', 'Revisi', 'Ditolak', 'ACC']);
export const submissionActionEnum = pgEnum('submission_action', ['Upload', 'Revision', 'Rejected', 'ACC']);
export const tutorialTypeEnum = pgEnum('tutorial_type', ['youtube', 'gdrive_pdf']);

export const users = pgTable('users', {
  id: varchar('id', { length: 20 }).primaryKey(),
  name: varchar('name').notNull(),
  email: varchar('email').notNull().unique(),
  password: varchar('password').notNull(),
  role: userRoleEnum('role').notNull().default('Mahasiswa'),
  isFirstLogin: boolean('is_first_login').notNull().default(true),
  avatar: varchar('avatar'),
  rememberToken: varchar('remember_token', { length: 100 }),
  approvedAt: timestamp('approved_at', { withTimezone: true }),
  approvedBy: varchar('approved_by', { length: 20 }),
  createdAt: createdAt(),
  updatedAt: updatedAt(),
}, (table) => [index('users_role_approval_name_index').on(table.role, table.approvedAt, table.name)]).enableRLS();

export const sessions = pgTable('user_sessions', {
  tokenHash: varchar('token_hash', { length: 64 }).primaryKey(),
  userId: varchar('user_id', { length: 20 }).notNull().references(() => users.id, { onDelete: 'cascade' }),
  expiresAt: timestamp('expires_at', { withTimezone: true }).notNull(),
  ipAddress: varchar('ip_address', { length: 45 }),
  userAgent: text('user_agent'),
  createdAt: createdAt(),
}, (table) => [index('sessions_user_expiry_index').on(table.userId, table.expiresAt)]).enableRLS();

export const semesters = pgTable('semesters', {
  id: bigserial('id', { mode: 'number' }).primaryKey(),
  name: varchar('name').notNull(),
  isActive: boolean('is_active').notNull().default(false),
  createdAt: createdAt(),
  updatedAt: updatedAt(),
}, (table) => [index('semesters_active_index').on(table.isActive)]).enableRLS();

export const courses = pgTable('courses', {
  id: bigserial('id', { mode: 'number' }).primaryKey(),
  slug: varchar('slug').notNull().unique(),
  semesterId: bigint('semester_id', { mode: 'number' }).notNull().references(() => semesters.id, { onDelete: 'cascade' }),
  isArchived: boolean('is_archived').notNull().default(false),
  courseName: varchar('course_name').notNull(),
  classGroup: varchar('class_group').notNull(),
  targetSemester: integer('target_semester').notNull(),
  dosenId: varchar('dosen_id', { length: 20 }).notNull().references(() => users.id),
  laboranId: varchar('laboran_id', { length: 20 }).notNull().references(() => users.id),
  aslabId: varchar('aslab_id', { length: 20 }).notNull().references(() => users.id),
  enrollmentCode: varchar('enrollment_code').notNull().unique(),
  createdAt: createdAt(),
  updatedAt: updatedAt(),
}, (table) => [
  unique('courses_semester_name_group_unique').on(table.semesterId, table.courseName, table.classGroup),
  index('courses_semester_created_index').on(table.semesterId, table.createdAt),
  index('courses_semester_laboran_index').on(table.semesterId, table.laboranId),
  index('courses_semester_dosen_index').on(table.semesterId, table.dosenId),
  index('courses_semester_aslab_index').on(table.semesterId, table.aslabId),
]).enableRLS();

export const meetings = pgTable('meetings', {
  id: bigserial('id', { mode: 'number' }).primaryKey(),
  courseId: bigint('course_id', { mode: 'number' }).notNull().references(() => courses.id, { onDelete: 'cascade' }),
  meetingNumber: integer('meeting_number').notNull(),
  title: varchar('title').notNull(),
  description: text('description'),
  moduleDriveLink: varchar('module_drive_link'),
  deadline: timestamp('deadline', { withTimezone: true }),
  publishedAt: timestamp('published_at', { withTimezone: true }),
  createdAt: createdAt(),
  updatedAt: updatedAt(),
}, (table) => [
  unique('meetings_course_meeting_unique').on(table.courseId, table.meetingNumber),
  index('meetings_published_at_index').on(table.publishedAt),
]).enableRLS();

export const attendances = pgTable('attendances', {
  id: bigserial('id', { mode: 'number' }).primaryKey(),
  meetingId: bigint('meeting_id', { mode: 'number' }).notNull().references(() => meetings.id, { onDelete: 'cascade' }),
  studentId: varchar('student_id', { length: 20 }).notNull().references(() => users.id),
  status: attendanceStatusEnum('status').notNull(),
  attendanceDate: date('attendance_date', { mode: 'string' }).notNull(),
  createdAt: createdAt(),
  updatedAt: updatedAt(),
}, (table) => [
  unique('attendances_meeting_student_unique').on(table.meetingId, table.studentId),
  index('attendances_student_index').on(table.studentId),
]).enableRLS();

export const finalTasks = pgTable('final_tasks', {
  id: bigserial('id', { mode: 'number' }).primaryKey(),
  courseId: bigint('course_id', { mode: 'number' }).notNull().references(() => courses.id, { onDelete: 'cascade' }),
  description: text('description').notNull(),
  deadline: timestamp('deadline', { withTimezone: true }),
  createdAt: createdAt(),
  updatedAt: updatedAt(),
}, (table) => [unique('final_tasks_course_unique').on(table.courseId)]).enableRLS();

export const submissions = pgTable('submissions', {
  id: bigserial('id', { mode: 'number' }).primaryKey(),
  documentVersion: integer('document_version').notNull().default(1),
  studentId: varchar('student_id', { length: 20 }).notNull().references(() => users.id),
  meetingId: bigint('meeting_id', { mode: 'number' }).references(() => meetings.id, { onDelete: 'cascade' }),
  finalTaskId: bigint('final_task_id', { mode: 'number' }).references(() => finalTasks.id, { onDelete: 'cascade' }),
  submissionLink: text('submission_link'),
  filePath: varchar('file_path'),
  originalFilename: varchar('original_filename'),
  fileSize: bigint('file_size', { mode: 'number' }),
  notes: text('notes'),
  isFinal: boolean('is_final').notNull().default(false),
  aslabStatus: reviewStatusEnum('aslab_status').notNull().default('Pending'),
  laboranStatus: reviewStatusEnum('laboran_status').notNull().default('Pending'),
  dosenStatus: lecturerReviewStatusEnum('dosen_status').notNull().default('N/A'),
  aslabScore: numeric('aslab_score', { precision: 5, scale: 2 }),
  laboranScore: numeric('laboran_score', { precision: 5, scale: 2 }),
  isCompleted: boolean('is_completed').notNull().default(false),
  firstUploadAt: timestamp('first_upload_at', { withTimezone: true }),
  lastUploadAt: timestamp('last_upload_at', { withTimezone: true }),
  aslabAccAt: timestamp('aslab_acc_at', { withTimezone: true }),
  laboranAccAt: timestamp('laboran_acc_at', { withTimezone: true }),
  dosenAccAt: timestamp('dosen_acc_at', { withTimezone: true }),
  createdAt: createdAt(),
  updatedAt: updatedAt(),
}, (table) => [
  uniqueIndex('submissions_meeting_student_unique').on(table.meetingId, table.studentId),
  uniqueIndex('submissions_final_student_unique').on(table.finalTaskId, table.studentId),
  index('submissions_review_queue_index').on(table.isFinal, table.aslabStatus, table.laboranStatus, table.dosenStatus, table.createdAt),
  index('submissions_student_created_index').on(table.studentId, table.createdAt),
]).enableRLS();

export const submissionHistories = pgTable('submission_histories', {
  id: bigserial('id', { mode: 'number' }).primaryKey(),
  documentVersion: integer('document_version').notNull().default(1),
  submissionId: bigint('submission_id', { mode: 'number' }).notNull().references(() => submissions.id, { onDelete: 'cascade' }),
  driveLink: text('drive_link').notNull(),
  filePath: varchar('file_path'),
  originalFilename: varchar('original_filename'),
  fileSize: bigint('file_size', { mode: 'number' }),
  iteration: integer('iteration').notNull().default(1),
  feedback: text('feedback'),
  actionType: submissionActionEnum('action_type').notNull(),
  reviewedBy: varchar('reviewed_by', { length: 20 }).references(() => users.id),
  createdAt: createdAt(),
  updatedAt: updatedAt(),
}, (table) => [
  unique('submission_histories_submission_iteration_unique').on(table.submissionId, table.iteration),
  index('submission_histories_submission_version_index').on(table.submissionId, table.documentVersion),
]).enableRLS();

export const courseUsers = pgTable('course_user', {
  id: bigserial('id', { mode: 'number' }).primaryKey(),
  courseId: bigint('course_id', { mode: 'number' }).notNull().references(() => courses.id, { onDelete: 'cascade' }),
  userId: varchar('user_id', { length: 20 }).notNull().references(() => users.id, { onDelete: 'cascade' }),
  createdAt: createdAt(),
  updatedAt: updatedAt(),
}, (table) => [
  unique('course_user_course_user_unique').on(table.courseId, table.userId),
  index('course_user_user_course_index').on(table.userId, table.courseId),
]).enableRLS();

export const tutorials = pgTable('tutorials', {
  id: bigserial('id', { mode: 'number' }).primaryKey(),
  title: varchar('title').notNull(),
  description: text('description'),
  type: tutorialTypeEnum('type').notNull(),
  url: text('url').notNull(),
  createdBy: varchar('created_by', { length: 20 }).notNull().references(() => users.id, { onDelete: 'cascade' }),
  createdAt: createdAt(),
  updatedAt: updatedAt(),
}, (table) => [index('tutorials_type_created_index').on(table.type, table.createdAt)]).enableRLS();

export const courseStaffHistories = pgTable('course_staff_histories', {
  id: bigserial('id', { mode: 'number' }).primaryKey(),
  courseId: bigint('course_id', { mode: 'number' }).notNull().references(() => courses.id, { onDelete: 'cascade' }),
  changedBy: varchar('changed_by', { length: 20 }).notNull(),
  previousDosenId: varchar('previous_dosen_id', { length: 20 }).notNull(),
  previousAslabId: varchar('previous_aslab_id', { length: 20 }).notNull(),
  dosenId: varchar('dosen_id', { length: 20 }).notNull(),
  aslabId: varchar('aslab_id', { length: 20 }).notNull(),
  createdAt: createdAt(),
}, (table) => [index('course_staff_histories_course_created_index').on(table.courseId, table.createdAt)]).enableRLS();

export const courseGrades = pgTable('course_grades', {
  id: bigserial('id', { mode: 'number' }).primaryKey(),
  courseId: bigint('course_id', { mode: 'number' }).notNull().references(() => courses.id, { onDelete: 'cascade' }),
  studentId: varchar('student_id', { length: 20 }).notNull().references(() => users.id, { onDelete: 'cascade' }),
  utsScore: numeric('uts_score', { precision: 5, scale: 2 }).notNull(),
  uasScore: numeric('uas_score', { precision: 5, scale: 2 }).notNull(),
  createdAt: createdAt(),
  updatedAt: updatedAt(),
}, (table) => [unique('course_grades_course_student_unique').on(table.courseId, table.studentId)]).enableRLS();
