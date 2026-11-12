<?php

use App\Http\Controllers\Api\V1\AdminFinancialSummaryController;
use App\Http\Controllers\Api\V1\AdminInvoiceController;
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
use App\Http\Controllers\Api\V1\ClassSessionController;
use App\Http\Controllers\Api\V1\AssignmentController;
use App\Http\Controllers\Api\V1\AssignmentSubmissionReviewController;
use App\Http\Controllers\Api\V1\FeedbackTemplateController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\StudentAssignmentController;
use App\Http\Controllers\Api\V1\StudentAssignmentSubmissionController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\StudentInvoiceController;
use App\Http\Controllers\Api\V1\StudentProjectController;
use App\Http\Controllers\Api\V1\StudentProjectReviewController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\PaymentInstallmentController;


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

        /*
        |--------------------------------------------------------------------------
        | Student Projects
        |--------------------------------------------------------------------------
        */

        Route::prefix('student')
            ->middleware(['role:student'])
            ->group(function () {
                Route::get('projects', [StudentProjectController::class, 'index'])
                    ->middleware('permission:submit_projects')
                    ->name('student.projects.index');

                Route::post(
                    'projects/{project}/submit',
                    [StudentProjectController::class, 'submit']
                )
                    ->middleware('permission:submit_projects')
                    ->name('student.projects.submit');

                Route::get(
                    'project-submissions',
                    [StudentProjectController::class, 'mySubmissions']
                )
                    ->middleware('permission:submit_projects')
                    ->name('student.projects.submissions');
            });

        /*
        |--------------------------------------------------------------------------
        | Project Submissions Review
        |--------------------------------------------------------------------------
        */

        Route::prefix('admin')
            ->middleware([
                'role:admin,instructor',
                'permission:review_submissions',
            ])
            ->group(function () {
                Route::get(
                    'project-submissions',
                    [StudentProjectReviewController::class, 'index']
                )->name('admin.project-submissions.index');

                Route::patch(
                    'project-submissions/{studentProject}/start-review',
                    [StudentProjectReviewController::class, 'startReview']
                )->name('admin.project-submissions.start-review');

                Route::patch(
                    'project-submissions/{studentProject}/review',
                    [StudentProjectReviewController::class, 'review']
                )->name('admin.project-submissions.review');
            });

        /*
        |--------------------------------------------------------------------------
        | Feedback Templates
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Student Invoices
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Parent Invoices
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Admin Assignments
        |--------------------------------------------------------------------------
        */

        Route::prefix('admin/assignments')
            ->middleware([
                'role:admin,instructor',
                'permission:manage_assignments',
            ])
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

        /*
        |--------------------------------------------------------------------------
        | Student Assignments
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Assignment Submission Reviews
        |--------------------------------------------------------------------------
        */

        Route::prefix('admin/assignments/{assignment}/submissions')
            ->middleware([
                'role:admin,instructor',
                'permission:review_submissions',
            ])
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

        /*
        |--------------------------------------------------------------------------
        | Admin Feedback Templates (Instructor Access)
        |--------------------------------------------------------------------------
        */

        Route::prefix('admin/feedback-templates')
            ->middleware([
                'role:admin,instructor',
                'permission:manage_assignments',
            ])
            ->group(function () {
                Route::get('/', [FeedbackTemplateController::class, 'index'])
                    ->name('api.v1.admin.feedback-templates.instructor-index');
            });

        /*
        |--------------------------------------------------------------------------
        | Authentication
        |--------------------------------------------------------------------------
        */

        Route::post('/auth/logout', [AuthController::class, 'logout'])
            ->name('api.v1.auth.logout');

        Route::get('/auth/me', [AuthController::class, 'me'])
            ->name('api.v1.auth.me');

        /*
        |--------------------------------------------------------------------------
        | Lesson Resources & Course Lessons Reading
        |--------------------------------------------------------------------------
        */

        Route::middleware('role:admin,instructor,student')->group(function () {
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
        });
        /*
        |--------------------------------------------------------------------------
        | Public Course Reading (Authenticated)
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Attendance
        |--------------------------------------------------------------------------
        */

        Route::middleware([
            'role:admin,instructor',
            'permission:manage_attendance',
        ])->group(function () {
            Route::get(
                '/class-sessions/{classSession}/attendance',
                [AttendanceController::class, 'index']
            );

            Route::post(
                '/class-sessions/{classSession}/attendance',
                [AttendanceController::class, 'store']
            );
        });
        /*
        |--------------------------------------------------------------------------
        | User Management
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Course Management
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Course Level Management
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Course Lesson Management
        |--------------------------------------------------------------------------
        */

        Route::middleware([
            'role:admin,instructor',
            'permission:manage_lessons',
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

        /*
        |--------------------------------------------------------------------------
        | Lesson Resource Management
        |--------------------------------------------------------------------------
        */

        Route::middleware([
            'role:admin,instructor',
            'permission:manage_lessons',
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

        /*
        |--------------------------------------------------------------------------
        | Group Management
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Group Instructor Management
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Enrollment Management
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Class Session Management
        |--------------------------------------------------------------------------
        */

        Route::middleware([
            'role:admin,instructor',
            'permission:manage_sessions'
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

        /*
        |--------------------------------------------------------------------------
        | Invoice & Payment Management
        |--------------------------------------------------------------------------
        */

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
            });
    });

    /*
|--------------------------------------------------------------------------
| Admin Invoice Management
|--------------------------------------------------------------------------create_payment_installments_table
*/

    Route::middleware([
        'role:admin',
        'permission:manage_payments',
    ])->prefix('admin/invoices')
        ->name('api.v1.admin.invoices.')
        ->group(function () {
            Route::get('/', [AdminInvoiceController::class, 'index'])
                ->name('index');

            Route::post('/', [AdminInvoiceController::class, 'store'])
                ->name('store');

            Route::get('/{invoice}', [AdminInvoiceController::class, 'show'])
                ->name('show');

            Route::patch('/{invoice}', [AdminInvoiceController::class, 'update'])
                ->name('update');

            Route::delete('/{invoice}', [AdminInvoiceController::class, 'destroy'])
                ->name('destroy');
        });

    // Admin Financial Summary
    Route::middleware([
        'role:admin',
        'permission:manage_payments',
    ])->get('/admin/financial-summary', AdminFinancialSummaryController::class)
        ->name('api.v1.admin.financial-summary');

    /*
    |--------------------------------------------------------------------------
    | Admin Payment Installments
    |--------------------------------------------------------------------------
    */

    Route::middleware([
        'role:admin',
        'permission:manage_payments',
    ])->prefix('admin/invoices/{invoice}/installments')
        ->name('api.v1.admin.invoices.installments.')
        ->group(function () {
            Route::get('/', [PaymentInstallmentController::class, 'index'])
                ->name('index');

            Route::post('/', [PaymentInstallmentController::class, 'store'])
                ->name('store');

            Route::patch('/{installment}', [PaymentInstallmentController::class, 'update'])
                ->name('update');

            Route::delete('/{installment}', [PaymentInstallmentController::class, 'destroy'])
                ->name('destroy');
        });


    /*
    |--------------------------------------------------------------------------
    | Admin Payment Management
    |--------------------------------------------------------------------------
    */

    Route::middleware([
        'auth:sanctum',
        \App\Http\Middleware\EnsureUserIsActive::class,
        'role:admin',
        'permission:manage_payments',
    ])->prefix('admin/payments')
        ->name('api.v1.admin.payments.')
        ->group(function () {
            Route::get('/', [PaymentController::class, 'index'])->name('index');
            Route::post('/', [PaymentController::class, 'store'])->name('store');
            Route::get('/{payment}', [PaymentController::class, 'show'])->name('show');
        });

    /*
    |--------------------------------------------------------------------------
    | Admin Financial Summary
    |--------------------------------------------------------------------------
    */

    Route::middleware([
        'auth:sanctum',
        \App\Http\Middleware\EnsureUserIsActive::class,
        'role:admin',
        'permission:manage_payments',
    ])->get('/admin/financial-summary', AdminFinancialSummaryController::class)
        ->name('api.v1.admin.financial-summary');

});
