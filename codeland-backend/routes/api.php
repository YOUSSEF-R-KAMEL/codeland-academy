<?php

use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CourseController;
use App\Http\Controllers\Api\V1\CourseLessonController;
use App\Http\Controllers\Api\V1\CourseLevelController;
use App\Http\Controllers\Api\V1\EnrollmentController;
use App\Http\Controllers\Api\V1\GroupController;
use App\Http\Controllers\Api\V1\GroupInstructorController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\LessonResourceController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\ClassSessionController;
use App\Http\Controllers\Api\V1\AssignmentController;
use App\Http\Controllers\Api\V1\AssignmentSubmissionReviewController;
use App\Http\Controllers\Api\V1\FeedbackTemplateController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\StudentAssignmentController;
use App\Http\Controllers\Api\V1\StudentAssignmentSubmissionController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\StudentInvoiceController;

Route::prefix('v1')->group(function () {
    Route::get('/health', HealthController::class)
        ->name('api.v1.health');

    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('api.v1.auth.login');

    Route::middleware([
        'auth:sanctum',
        'active',
    ])->group(function () {
        Route::prefix('admin/feedback-templates')
            ->middleware([
                'role:admin',
                'permission:manage_assignments',
            ])
            ->controller(FeedbackTemplateController::class)
            ->group(function () {
                Route::get('/', 'index')
                    ->name('api.v1.admin.feedback-templates.index');

                Route::post('/', 'store')
                    ->name('api.v1.admin.feedback-templates.store');

                Route::get('/{feedbackTemplate}', 'show')
                    ->name('api.v1.admin.feedback-templates.show');

                Route::patch('/{feedbackTemplate}', 'update')
                    ->name('api.v1.admin.feedback-templates.update');

                Route::delete('/{feedbackTemplate}', 'destroy')
                    ->name('api.v1.admin.feedback-templates.destroy');
            });
        Route::middleware([
            'role:student',
            'permission:view_payments',
        ])->prefix('student/invoices')
            ->name('api.v1.student.invoices.')
            ->group(function () {
                Route::get('/', [
                    StudentInvoiceController::class,
                    'index',
                ])->name('index');

                Route::get('/{invoice}', [
                    StudentInvoiceController::class,
                    'show',
                ])->name('show');
            });
        Route::middleware([
            'role:parent',
            'permission:view_child_payments',
        ])->prefix('parent/invoices')
            ->name('api.v1.parent.invoices.')
            ->group(function () {
                Route::get('/', [
                    StudentInvoiceController::class,
                    'index',
                ])->name('index');

                Route::get('/{invoice}', [
                    StudentInvoiceController::class,
                    'show',
                ])->name('show');
            });
        Route::prefix('admin/assignments')
            ->middleware(['role:admin,instructor', 'permission:manage_assignments'])
            ->group(function () {
                Route::get('/', [AssignmentController::class, 'index'])
                    ->name('api.v1.admin.assignments.index');

                Route::post('/', [AssignmentController::class, 'store'])
                    ->name('api.v1.admin.assignments.store');

                Route::get('/{assignment}', [AssignmentController::class, 'show'])
                    ->name('api.v1.admin.assignments.show');

                Route::patch('/{assignment}', [AssignmentController::class, 'update'])
                    ->name('api.v1.admin.assignments.update');

                Route::delete('/{assignment}', [AssignmentController::class, 'destroy'])
                    ->name('api.v1.admin.assignments.destroy');
            });
        Route::prefix('student/assignments')
            ->middleware('role:student')
            ->group(function () {
                Route::get('/', [StudentAssignmentController::class, 'index'])
                    ->name('api.v1.student.assignments.index');

                Route::get('/{assignment}', [StudentAssignmentController::class, 'show'])
                    ->name('api.v1.student.assignments.show');

                Route::post(
                    '/{assignment}/submissions',
                    [StudentAssignmentSubmissionController::class, 'store']
                )->name('api.v1.student.assignments.submissions.store');
            });
        Route::prefix('admin/assignments/{assignment}/submissions')
            ->middleware(['role:admin,instructor', 'permission:review_submissions'])
            ->group(function () {
                Route::get(
                    '/',
                    [AssignmentSubmissionReviewController::class, 'index']
                )->name('api.v1.admin.assignments.submissions.index');
            });

        Route::patch(
            '/admin/assignment-submissions/{submission}/review',
            [AssignmentSubmissionReviewController::class, 'review']
        )->middleware([
                    'role:admin,instructor',
                    'permission:review_submissions',
                ])->name('api.v1.admin.assignment-submissions.review');
        Route::prefix('admin/feedback-templates')
            ->middleware(['role:admin,instructor', 'permission:manage_assignments'])
            ->group(function () {
                Route::get('/', [FeedbackTemplateController::class, 'index'])
                    ->name('api.v1.admin.feedback-templates.index');
            });
        Route::post('/auth/logout', [AuthController::class, 'logout'])
            ->name('api.v1.auth.logout');

        Route::get('/auth/me', [AuthController::class, 'me'])
            ->name('api.v1.auth.me');

        Route::get(
            '/lessons/{lesson}/resources',
            [LessonResourceController::class, 'index']
        )->name('api.v1.lessons.resources.index');

        Route::get(
            '/lessons/{lesson}/resources/{resource}',
            [LessonResourceController::class, 'show']
        )->name('api.v1.lessons.resources.show');

        Route::get(
            '/course-levels/{level}/lessons',
            [CourseLessonController::class, 'index']
        )->name('api.v1.course-levels.lessons.index');

        Route::get(
            '/course-levels/{level}/lessons/{lesson}',
            [CourseLessonController::class, 'show']
        )->name('api.v1.course-levels.lessons.show');

        Route::get('/courses', [CourseController::class, 'index'])
            ->name('api.v1.courses.index');

        Route::get('/courses/{slug}', [CourseController::class, 'show'])
            ->name('api.v1.courses.show');

        Route::get(
            '/courses/{course}/levels',
            [CourseLevelController::class, 'index']
        )->name('api.v1.courses.levels.index');

        Route::get(
            '/courses/{course}/levels/{level}',
            [CourseLevelController::class, 'show']
        )->name('api.v1.courses.levels.show');

        Route::get(
            '/class-sessions/{classSession}/attendance',
            [AttendanceController::class, 'index']
        )->middleware('permission:manage_attendance');

        Route::post(
            '/class-sessions/{classSession}/attendance',
            [AttendanceController::class, 'store']
        )->middleware('permission:manage_attendance');

        Route::middleware([
            'role:admin',
            'permission:manage_users',
        ])->prefix('admin')->group(function () {
            Route::post('/users', [UserController::class, 'store'])
                ->name('api.v1.admin.users.store');

            Route::post(
                '/students-with-parent',
                [UserController::class, 'storeStudentWithParent']
            )->name('api.v1.admin.students.store-with-parent');

            Route::get('/users', [UserController::class, 'index'])
                ->name('api.v1.admin.users.index');

            Route::get('/users/{id}', [UserController::class, 'show'])
                ->whereNumber('id')
                ->name('api.v1.admin.users.show');

            Route::patch('/users/{id}', [UserController::class, 'update'])
                ->whereNumber('id')
                ->name('api.v1.admin.users.update');

            Route::patch('/users/{id}/status', [UserController::class, 'updateStatus'])
                ->whereNumber('id')
                ->name('api.v1.admin.users.update-status');

            Route::post(
                '/students/{studentId}/parents',
                [UserController::class, 'attachParent']
            )
                ->whereNumber('studentId')
                ->name('api.v1.admin.students.attach-parent');

            Route::delete(
                '/students/{studentId}/parents/{parentId}',
                [UserController::class, 'detachParent']
            )
                ->whereNumber('studentId')
                ->whereNumber('parentId')
                ->name('api.v1.admin.students.detach-parent');
        });

        Route::middleware([
            'role:admin',
            'permission:manage_courses',
        ])->prefix('admin/courses')
            ->name('api.v1.admin.courses.')
            ->group(function () {
                Route::post('/', [CourseController::class, 'store'])
                    ->name('store');

                Route::patch('/{course}', [CourseController::class, 'update'])
                    ->name('update');

                Route::delete('/{course}', [CourseController::class, 'destroy'])
                    ->name('destroy');
            });

        Route::middleware([
            'role:admin',
            'permission:manage_courses',
        ])->prefix('admin/courses/{course}/levels')
            ->name('api.v1.admin.courses.levels.')
            ->group(function () {
                Route::post('/', [CourseLevelController::class, 'store'])
                    ->name('store');

                Route::patch('/{level}', [CourseLevelController::class, 'update'])
                    ->name('update');

                Route::delete('/{level}', [CourseLevelController::class, 'destroy'])
                    ->name('destroy');
            });

        Route::middleware([
            'role:admin',
            'permission:manage_courses',
        ])->prefix('admin/course-levels/{level}/lessons')
            ->name('api.v1.admin.course-levels.lessons.')
            ->group(function () {
                Route::post('/', [CourseLessonController::class, 'store'])
                    ->name('store');

                Route::patch('/{lesson}', [CourseLessonController::class, 'update'])
                    ->name('update');

                Route::delete('/{lesson}', [CourseLessonController::class, 'destroy'])
                    ->name('destroy');
            });

        Route::middleware([
            'role:admin',
            'permission:manage_courses',
        ])->prefix('admin/lessons/{lesson}/resources')
            ->name('api.v1.admin.lessons.resources.')
            ->group(function () {
                Route::post('/', [LessonResourceController::class, 'store'])
                    ->name('store');

                Route::patch('/{resource}', [LessonResourceController::class, 'update'])
                    ->name('update');

                Route::delete('/{resource}', [LessonResourceController::class, 'destroy'])
                    ->name('destroy');
            });

        Route::middleware([
            'role:admin',
            'permission:manage_groups',
        ])->prefix('admin/course-levels/{level}')
            ->name('api.v1.admin.course-levels.groups.')
            ->group(function () {
                Route::get('/groups', [GroupController::class, 'index'])
                    ->name('index');

                Route::post('/groups', [GroupController::class, 'store'])
                    ->name('store');

                Route::get('/groups/{group}', [GroupController::class, 'show'])
                    ->name('show');

                Route::patch('/groups/{group}', [GroupController::class, 'update'])
                    ->name('update');

                Route::delete('/groups/{group}', [GroupController::class, 'destroy'])
                    ->name('destroy');
            });
        Route::middleware([
            'role:admin',
            'permission:manage_groups',
        ])->prefix('admin/groups/{group}/instructors')
            ->name('api.v1.admin.groups.instructors.')
            ->group(function () {
                Route::get('/', [GroupInstructorController::class, 'index'])
                    ->name('index');

                Route::post('/', [GroupInstructorController::class, 'store'])
                    ->name('store');

                Route::delete('/{instructor}', [GroupInstructorController::class, 'destroy'])
                    ->name('destroy');

                Route::patch('/{instructor}/primary', [
                    GroupInstructorController::class,
                    'updatePrimary',
                ])->name('update-primary');
            });

        Route::middleware([
            'role:admin',
            'permission:manage_groups',
        ])->prefix('admin/groups/{group}/enrollments')
            ->name('api.v1.admin.groups.enrollments.')
            ->group(function () {
                Route::get('/', [EnrollmentController::class, 'index'])
                    ->name('index');

                Route::post('/', [EnrollmentController::class, 'store'])
                    ->name('store');

                Route::get('/{enrollment}', [EnrollmentController::class, 'show'])
                    ->name('show');

                Route::patch('/{enrollment}', [EnrollmentController::class, 'update'])
                    ->name('update');

                Route::delete('/{enrollment}', [EnrollmentController::class, 'destroy'])
                    ->name('destroy');

                Route::post('/{enrollment}/transfer', [
                    EnrollmentController::class,
                    'transfer',
                ])->name('transfer');
            });

        Route::middleware([
            'role:admin',
            'permission:manage_groups',
        ])->prefix('admin/groups/{group}/sessions')
            ->name('api.v1.admin.groups.sessions.')
            ->group(function () {
                Route::get('/', [ClassSessionController::class, 'index'])
                    ->name('index');

                Route::post('/', [ClassSessionController::class, 'store'])
                    ->name('store');

                Route::get('/{classSession}', [ClassSessionController::class, 'show'])
                    ->name('show');

                Route::patch('/{classSession}', [ClassSessionController::class, 'update'])
                    ->name('update');

                Route::delete('/{classSession}', [ClassSessionController::class, 'destroy'])
                    ->name('destroy');
            });
        Route::middleware([
            'role:admin',
            'permission:manage_payments',
        ])->prefix('admin/invoices')
            ->name('api.v1.admin.invoices.')
            ->group(function () {
                Route::get('/', [
                    InvoiceController::class,
                    'index',
                ])->name('index');

                Route::get('/{invoice}', [
                    InvoiceController::class,
                    'show',
                ])->name('show');
                Route::get('/{invoice}/payments', [
                    PaymentController::class,
                    'index',
                ])->name('payments.index');

                Route::post('/{invoice}/payments', [
                    PaymentController::class,
                    'store',
                ])->name('payments.store');
            });

    });
});
