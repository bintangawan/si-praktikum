CREATE TYPE "public"."attendance_status" AS ENUM('Hadir', 'Sakit', 'Izin', 'Tanpa Keterangan', 'H', 'S', 'I', 'TK', 'Alpha');--> statement-breakpoint
CREATE TYPE "public"."lecturer_review_status" AS ENUM('N/A', 'Pending', 'Revisi', 'Ditolak', 'ACC');--> statement-breakpoint
CREATE TYPE "public"."review_status" AS ENUM('Pending', 'Revisi', 'Ditolak', 'ACC');--> statement-breakpoint
CREATE TYPE "public"."submission_action" AS ENUM('Upload', 'Revision', 'Rejected', 'ACC');--> statement-breakpoint
CREATE TYPE "public"."tutorial_type" AS ENUM('youtube', 'gdrive_pdf');--> statement-breakpoint
CREATE TYPE "public"."user_role" AS ENUM('Dosen', 'Mahasiswa', 'Laboran', 'Aslab');--> statement-breakpoint
CREATE TABLE "attendances" (
	"id" bigserial PRIMARY KEY NOT NULL,
	"meeting_id" bigint NOT NULL,
	"student_id" varchar(20) NOT NULL,
	"status" "attendance_status" NOT NULL,
	"attendance_date" date NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"updated_at" timestamp with time zone DEFAULT now() NOT NULL,
	CONSTRAINT "attendances_meeting_student_unique" UNIQUE("meeting_id","student_id")
);
--> statement-breakpoint
ALTER TABLE "attendances" ENABLE ROW LEVEL SECURITY;--> statement-breakpoint
CREATE TABLE "course_grades" (
	"id" bigserial PRIMARY KEY NOT NULL,
	"course_id" bigint NOT NULL,
	"student_id" varchar(20) NOT NULL,
	"uts_score" numeric(5, 2) NOT NULL,
	"uas_score" numeric(5, 2) NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"updated_at" timestamp with time zone DEFAULT now() NOT NULL,
	CONSTRAINT "course_grades_course_student_unique" UNIQUE("course_id","student_id")
);
--> statement-breakpoint
ALTER TABLE "course_grades" ENABLE ROW LEVEL SECURITY;--> statement-breakpoint
CREATE TABLE "course_staff_histories" (
	"id" bigserial PRIMARY KEY NOT NULL,
	"course_id" bigint NOT NULL,
	"changed_by" varchar(20) NOT NULL,
	"previous_dosen_id" varchar(20) NOT NULL,
	"previous_aslab_id" varchar(20) NOT NULL,
	"dosen_id" varchar(20) NOT NULL,
	"aslab_id" varchar(20) NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL
);
--> statement-breakpoint
ALTER TABLE "course_staff_histories" ENABLE ROW LEVEL SECURITY;--> statement-breakpoint
CREATE TABLE "course_user" (
	"id" bigserial PRIMARY KEY NOT NULL,
	"course_id" bigint NOT NULL,
	"user_id" varchar(20) NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"updated_at" timestamp with time zone DEFAULT now() NOT NULL,
	CONSTRAINT "course_user_course_user_unique" UNIQUE("course_id","user_id")
);
--> statement-breakpoint
ALTER TABLE "course_user" ENABLE ROW LEVEL SECURITY;--> statement-breakpoint
CREATE TABLE "courses" (
	"id" bigserial PRIMARY KEY NOT NULL,
	"slug" varchar NOT NULL,
	"semester_id" bigint NOT NULL,
	"is_archived" boolean DEFAULT false NOT NULL,
	"course_name" varchar NOT NULL,
	"class_group" varchar NOT NULL,
	"target_semester" integer NOT NULL,
	"dosen_id" varchar(20) NOT NULL,
	"laboran_id" varchar(20) NOT NULL,
	"aslab_id" varchar(20) NOT NULL,
	"enrollment_code" varchar NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"updated_at" timestamp with time zone DEFAULT now() NOT NULL,
	CONSTRAINT "courses_slug_unique" UNIQUE("slug"),
	CONSTRAINT "courses_enrollment_code_unique" UNIQUE("enrollment_code"),
	CONSTRAINT "courses_semester_name_group_unique" UNIQUE("semester_id","course_name","class_group")
);
--> statement-breakpoint
ALTER TABLE "courses" ENABLE ROW LEVEL SECURITY;--> statement-breakpoint
CREATE TABLE "email_verification_tokens" (
	"id" bigserial PRIMARY KEY NOT NULL,
	"user_id" varchar(20) NOT NULL,
	"token_hash" varchar(64) NOT NULL,
	"expires_at" timestamp with time zone NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	CONSTRAINT "email_verification_tokens_token_hash_unique" UNIQUE("token_hash")
);
--> statement-breakpoint
ALTER TABLE "email_verification_tokens" ENABLE ROW LEVEL SECURITY;--> statement-breakpoint
CREATE TABLE "final_tasks" (
	"id" bigserial PRIMARY KEY NOT NULL,
	"course_id" bigint NOT NULL,
	"description" text NOT NULL,
	"deadline" timestamp with time zone,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"updated_at" timestamp with time zone DEFAULT now() NOT NULL,
	CONSTRAINT "final_tasks_course_unique" UNIQUE("course_id")
);
--> statement-breakpoint
ALTER TABLE "final_tasks" ENABLE ROW LEVEL SECURITY;--> statement-breakpoint
CREATE TABLE "meetings" (
	"id" bigserial PRIMARY KEY NOT NULL,
	"course_id" bigint NOT NULL,
	"meeting_number" integer NOT NULL,
	"title" varchar NOT NULL,
	"description" text,
	"module_drive_link" varchar,
	"deadline" timestamp with time zone,
	"published_at" timestamp with time zone,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"updated_at" timestamp with time zone DEFAULT now() NOT NULL,
	CONSTRAINT "meetings_course_meeting_unique" UNIQUE("course_id","meeting_number")
);
--> statement-breakpoint
ALTER TABLE "meetings" ENABLE ROW LEVEL SECURITY;--> statement-breakpoint
CREATE TABLE "password_reset_tokens" (
	"email" varchar PRIMARY KEY NOT NULL,
	"token" varchar NOT NULL,
	"created_at" timestamp with time zone
);
--> statement-breakpoint
ALTER TABLE "password_reset_tokens" ENABLE ROW LEVEL SECURITY;--> statement-breakpoint
CREATE TABLE "semesters" (
	"id" bigserial PRIMARY KEY NOT NULL,
	"name" varchar NOT NULL,
	"is_active" boolean DEFAULT false NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"updated_at" timestamp with time zone DEFAULT now() NOT NULL
);
--> statement-breakpoint
ALTER TABLE "semesters" ENABLE ROW LEVEL SECURITY;--> statement-breakpoint
CREATE TABLE "user_sessions" (
	"token_hash" varchar(64) PRIMARY KEY NOT NULL,
	"user_id" varchar(20) NOT NULL,
	"expires_at" timestamp with time zone NOT NULL,
	"ip_address" varchar(45),
	"user_agent" text,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL
);
--> statement-breakpoint
ALTER TABLE "user_sessions" ENABLE ROW LEVEL SECURITY;--> statement-breakpoint
CREATE TABLE "submission_histories" (
	"id" bigserial PRIMARY KEY NOT NULL,
	"document_version" integer DEFAULT 1 NOT NULL,
	"submission_id" bigint NOT NULL,
	"drive_link" text NOT NULL,
	"file_path" varchar,
	"original_filename" varchar,
	"file_size" bigint,
	"iteration" integer DEFAULT 1 NOT NULL,
	"feedback" text,
	"action_type" "submission_action" NOT NULL,
	"reviewed_by" varchar(20),
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"updated_at" timestamp with time zone DEFAULT now() NOT NULL,
	CONSTRAINT "submission_histories_submission_iteration_unique" UNIQUE("submission_id","iteration")
);
--> statement-breakpoint
ALTER TABLE "submission_histories" ENABLE ROW LEVEL SECURITY;--> statement-breakpoint
CREATE TABLE "submissions" (
	"id" bigserial PRIMARY KEY NOT NULL,
	"document_version" integer DEFAULT 1 NOT NULL,
	"student_id" varchar(20) NOT NULL,
	"meeting_id" bigint,
	"final_task_id" bigint,
	"submission_link" text,
	"file_path" varchar,
	"original_filename" varchar,
	"file_size" bigint,
	"notes" text,
	"is_final" boolean DEFAULT false NOT NULL,
	"aslab_status" "review_status" DEFAULT 'Pending' NOT NULL,
	"laboran_status" "review_status" DEFAULT 'Pending' NOT NULL,
	"dosen_status" "lecturer_review_status" DEFAULT 'N/A' NOT NULL,
	"aslab_score" numeric(5, 2),
	"laboran_score" numeric(5, 2),
	"is_completed" boolean DEFAULT false NOT NULL,
	"first_upload_at" timestamp with time zone,
	"last_upload_at" timestamp with time zone,
	"aslab_acc_at" timestamp with time zone,
	"laboran_acc_at" timestamp with time zone,
	"dosen_acc_at" timestamp with time zone,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"updated_at" timestamp with time zone DEFAULT now() NOT NULL
);
--> statement-breakpoint
ALTER TABLE "submissions" ENABLE ROW LEVEL SECURITY;--> statement-breakpoint
CREATE TABLE "tutorials" (
	"id" bigserial PRIMARY KEY NOT NULL,
	"title" varchar NOT NULL,
	"description" text,
	"type" "tutorial_type" NOT NULL,
	"url" text NOT NULL,
	"created_by" varchar(20) NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"updated_at" timestamp with time zone DEFAULT now() NOT NULL
);
--> statement-breakpoint
ALTER TABLE "tutorials" ENABLE ROW LEVEL SECURITY;--> statement-breakpoint
CREATE TABLE "users" (
	"id" varchar(20) PRIMARY KEY NOT NULL,
	"name" varchar NOT NULL,
	"email" varchar NOT NULL,
	"email_verified_at" timestamp with time zone,
	"password" varchar NOT NULL,
	"role" "user_role" DEFAULT 'Mahasiswa' NOT NULL,
	"is_first_login" boolean DEFAULT true NOT NULL,
	"avatar" varchar,
	"remember_token" varchar(100),
	"approved_at" timestamp with time zone,
	"approved_by" varchar(20),
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"updated_at" timestamp with time zone DEFAULT now() NOT NULL,
	CONSTRAINT "users_email_unique" UNIQUE("email")
);
--> statement-breakpoint
ALTER TABLE "users" ENABLE ROW LEVEL SECURITY;--> statement-breakpoint
ALTER TABLE "attendances" ADD CONSTRAINT "attendances_meeting_id_meetings_id_fk" FOREIGN KEY ("meeting_id") REFERENCES "public"."meetings"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "attendances" ADD CONSTRAINT "attendances_student_id_users_id_fk" FOREIGN KEY ("student_id") REFERENCES "public"."users"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "course_grades" ADD CONSTRAINT "course_grades_course_id_courses_id_fk" FOREIGN KEY ("course_id") REFERENCES "public"."courses"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "course_grades" ADD CONSTRAINT "course_grades_student_id_users_id_fk" FOREIGN KEY ("student_id") REFERENCES "public"."users"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "course_staff_histories" ADD CONSTRAINT "course_staff_histories_course_id_courses_id_fk" FOREIGN KEY ("course_id") REFERENCES "public"."courses"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "course_user" ADD CONSTRAINT "course_user_course_id_courses_id_fk" FOREIGN KEY ("course_id") REFERENCES "public"."courses"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "course_user" ADD CONSTRAINT "course_user_user_id_users_id_fk" FOREIGN KEY ("user_id") REFERENCES "public"."users"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "courses" ADD CONSTRAINT "courses_semester_id_semesters_id_fk" FOREIGN KEY ("semester_id") REFERENCES "public"."semesters"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "courses" ADD CONSTRAINT "courses_dosen_id_users_id_fk" FOREIGN KEY ("dosen_id") REFERENCES "public"."users"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "courses" ADD CONSTRAINT "courses_laboran_id_users_id_fk" FOREIGN KEY ("laboran_id") REFERENCES "public"."users"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "courses" ADD CONSTRAINT "courses_aslab_id_users_id_fk" FOREIGN KEY ("aslab_id") REFERENCES "public"."users"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "email_verification_tokens" ADD CONSTRAINT "email_verification_tokens_user_id_users_id_fk" FOREIGN KEY ("user_id") REFERENCES "public"."users"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "final_tasks" ADD CONSTRAINT "final_tasks_course_id_courses_id_fk" FOREIGN KEY ("course_id") REFERENCES "public"."courses"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "meetings" ADD CONSTRAINT "meetings_course_id_courses_id_fk" FOREIGN KEY ("course_id") REFERENCES "public"."courses"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "user_sessions" ADD CONSTRAINT "user_sessions_user_id_users_id_fk" FOREIGN KEY ("user_id") REFERENCES "public"."users"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "submission_histories" ADD CONSTRAINT "submission_histories_submission_id_submissions_id_fk" FOREIGN KEY ("submission_id") REFERENCES "public"."submissions"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "submission_histories" ADD CONSTRAINT "submission_histories_reviewed_by_users_id_fk" FOREIGN KEY ("reviewed_by") REFERENCES "public"."users"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "submissions" ADD CONSTRAINT "submissions_student_id_users_id_fk" FOREIGN KEY ("student_id") REFERENCES "public"."users"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "submissions" ADD CONSTRAINT "submissions_meeting_id_meetings_id_fk" FOREIGN KEY ("meeting_id") REFERENCES "public"."meetings"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "submissions" ADD CONSTRAINT "submissions_final_task_id_final_tasks_id_fk" FOREIGN KEY ("final_task_id") REFERENCES "public"."final_tasks"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "tutorials" ADD CONSTRAINT "tutorials_created_by_users_id_fk" FOREIGN KEY ("created_by") REFERENCES "public"."users"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
CREATE INDEX "attendances_student_index" ON "attendances" USING btree ("student_id");--> statement-breakpoint
CREATE INDEX "course_staff_histories_course_created_index" ON "course_staff_histories" USING btree ("course_id","created_at");--> statement-breakpoint
CREATE INDEX "course_user_user_course_index" ON "course_user" USING btree ("user_id","course_id");--> statement-breakpoint
CREATE INDEX "courses_semester_created_index" ON "courses" USING btree ("semester_id","created_at");--> statement-breakpoint
CREATE INDEX "courses_semester_laboran_index" ON "courses" USING btree ("semester_id","laboran_id");--> statement-breakpoint
CREATE INDEX "courses_semester_dosen_index" ON "courses" USING btree ("semester_id","dosen_id");--> statement-breakpoint
CREATE INDEX "courses_semester_aslab_index" ON "courses" USING btree ("semester_id","aslab_id");--> statement-breakpoint
CREATE INDEX "email_verification_tokens_user_index" ON "email_verification_tokens" USING btree ("user_id");--> statement-breakpoint
CREATE INDEX "meetings_published_at_index" ON "meetings" USING btree ("published_at");--> statement-breakpoint
CREATE INDEX "semesters_active_index" ON "semesters" USING btree ("is_active");--> statement-breakpoint
CREATE INDEX "sessions_user_expiry_index" ON "user_sessions" USING btree ("user_id","expires_at");--> statement-breakpoint
CREATE INDEX "submission_histories_submission_version_index" ON "submission_histories" USING btree ("submission_id","document_version");--> statement-breakpoint
CREATE UNIQUE INDEX "submissions_meeting_student_unique" ON "submissions" USING btree ("meeting_id","student_id");--> statement-breakpoint
CREATE UNIQUE INDEX "submissions_final_student_unique" ON "submissions" USING btree ("final_task_id","student_id");--> statement-breakpoint
CREATE INDEX "submissions_review_queue_index" ON "submissions" USING btree ("is_final","aslab_status","laboran_status","dosen_status","created_at");--> statement-breakpoint
CREATE INDEX "submissions_student_created_index" ON "submissions" USING btree ("student_id","created_at");--> statement-breakpoint
CREATE INDEX "tutorials_type_created_index" ON "tutorials" USING btree ("type","created_at");--> statement-breakpoint
CREATE INDEX "users_role_approval_name_index" ON "users" USING btree ("role","approved_at","name");