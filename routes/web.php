<?php

use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinalTaskController;
use App\Http\Controllers\FirstLoginController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SemesterController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\TutorialController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserImportController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::middleware('auth')->group(function () {
    Route::get('force-change-password', [FirstLoginController::class, 'showChangePasswordForm'])->name('first.login.form');
    Route::post('force-change-password', [FirstLoginController::class, 'updatePassword'])->name('first.login.update');
});

Route::middleware(['auth', 'verified', 'password.changed'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('courses/create', [CourseController::class, 'create'])->middleware('role:Laboran')->name('courses.create');
    Route::get('courses/{course}', [MeetingController::class, 'show'])->name('courses.show');
    Route::get('arsip', [ArchiveController::class, 'index'])->name('archives.index');
    Route::get('tutorials', [TutorialController::class, 'index'])->name('tutorials.index');
    Route::post('switch-role', [RoleController::class, 'switchRole'])->name('role.switch');

    Route::middleware('role:Mahasiswa')->group(function () {
        Route::post('courses/enroll', [CourseController::class, 'enroll'])->name('courses.enroll');
        Route::get('courses/{course}/print-card', [CourseController::class, 'printCard'])->name('courses.print-card');
        Route::get('my-submissions', [SubmissionController::class, 'mySubmissions'])->name('submissions.my-index');
        Route::get('mahasiswa/meetings/{meeting}/submission', [SubmissionController::class, 'manage'])->name('mahasiswa.submissions.manage');
        Route::post('mahasiswa/meetings/{meeting}/submit', [SubmissionController::class, 'store'])->name('submissions.store');
        Route::put('mahasiswa/submissions/{submission}', [SubmissionController::class, 'update'])->name('submissions.update');
        Route::get('mahasiswa/final-tasks/{finalTask}', [FinalTaskController::class, 'manage'])->name('mahasiswa.final-tasks.manage');
        Route::post('mahasiswa/final-tasks/{finalTask}/submit', [FinalTaskController::class, 'submit'])->name('final-tasks.submit');
        Route::put('mahasiswa/final-submissions/{submission}', [FinalTaskController::class, 'update'])->name('final-tasks.update');
    });

    Route::middleware('role:Aslab,Laboran,Dosen')->group(function () {
        Route::post('courses/{course}/meetings', [MeetingController::class, 'store'])->name('meetings.store');
        Route::put('meetings/{meeting}', [MeetingController::class, 'update'])->name('meetings.update');
        Route::put('meetings/{meeting}/deadline', [SubmissionController::class, 'updateDeadline'])->name('meetings.update-deadline');
        Route::get('meetings/{meeting}/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('meetings/{meeting}/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
        Route::get('courses/{course}/attendance-report', [AttendanceController::class, 'report'])->name('attendance.report');
        Route::get('courses/{course}/attendance-report/pdf', [AttendanceController::class, 'exportPdf'])->name('attendance.report.pdf');
        Route::get('courses/{course}/attendance-report/excel', [AttendanceController::class, 'exportExcel'])->name('attendance.report.excel');
        Route::get('courses/{course}/students', [CourseController::class, 'students'])->name('courses.students');
        Route::delete('courses/{course}/students/{student}', [CourseController::class, 'removeStudent'])->name('courses.remove-student');
        Route::get('meetings/{meeting}/submissions', [SubmissionController::class, 'index'])->name('submissions.index');
        Route::get('submissions/{submission}/handler', [SubmissionController::class, 'handler'])->name('submissions.handler');
        Route::post('submissions/{submission}/approve', [SubmissionController::class, 'approve'])->name('submissions.approve');
        Route::post('courses/{course}/final-tasks', [FinalTaskController::class, 'store'])->name('final-tasks.store');
        Route::get('final-tasks/{finalTask}', [FinalTaskController::class, 'index'])->name('final-tasks.index');
        Route::put('final-tasks/{finalTask}/deadline', [FinalTaskController::class, 'updateDeadline'])->name('final-tasks.update-deadline');
        Route::put('final-tasks/{finalTask}/description', [FinalTaskController::class, 'updateDescription'])->name('final-tasks.update-description');
        Route::get('final-submissions/{submission}/handler', [FinalTaskController::class, 'handler'])->name('final-tasks.handler');
        Route::patch('final-submissions/{submission}/approve', [FinalTaskController::class, 'approve'])->name('final-tasks.approve');
        Route::get('submissions/pending', [SubmissionController::class, 'pending'])->name('submissions.pending');
    });

    Route::middleware('role:Laboran')->group(function () {
        Route::post('courses', [CourseController::class, 'store'])->name('courses.store');
        Route::get('import-users', [UserImportController::class, 'showImportForm'])->name('user.import.form');
        Route::post('import-users', [UserImportController::class, 'import'])->name('user.import');
        Route::get('users-management', [UserController::class, 'index'])->name('users.index');
        Route::patch('users-management/{user}/reset', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::post('users/{user}/make-aslab', [UserController::class, 'makeAslab'])->name('users.make-aslab');
        Route::post('users/{user}/revoke-aslab', [UserController::class, 'revokeAslab'])->name('users.revoke-aslab');
        Route::resource('semesters', SemesterController::class)->except(['create', 'show', 'edit']);
        Route::patch('semesters/{semester}/set-active', [SemesterController::class, 'setActive'])->name('semesters.set-active');
        Route::post('tutorials', [TutorialController::class, 'store'])->name('tutorials.store');
        Route::delete('tutorials/{tutorial}', [TutorialController::class, 'destroy'])->name('tutorials.destroy');
    });
});

require __DIR__.'/auth.php';
