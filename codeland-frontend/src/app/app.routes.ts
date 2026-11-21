import { Routes } from '@angular/router';

import { authGuard } from './core/guards/auth.guard';
import { adminGuard } from './core/guards/admin.guard';
import { guestGuard } from './core/guards/guest.guard';
import { studentGuard } from './core/guards/student.guard';
export const routes: Routes = [
  {
    path: '',
    redirectTo: 'admin/dashboard',
    pathMatch: 'full',
  },
  {
    path: 'login',
    canActivate: [guestGuard],
    loadComponent: () =>
      import('./features/auth/login/login.component').then(
        (m) => m.LoginComponent,
      ),
  },
  {
    path: 'admin',
    canActivate: [authGuard, adminGuard],
    loadComponent: () =>
      import('./layouts/admin-layout/admin-layout.component').then(
        (m) => m.AdminLayoutComponent,
      ),
    children: [
      {
        path: '',
        redirectTo: 'dashboard',
        pathMatch: 'full',
      },
      {
        path: 'dashboard',
        loadComponent: () =>
          import('./features/admin/pages/dashboard/dashboard.component').then(
            (m) => m.DashboardComponent,
          ),
      },
      {
        path: 'students',
        loadComponent: () =>
          import('./features/admin/pages/students/students.component').then(
            (m) => m.StudentsComponent,
          ),
      },
      {
        path: 'instructors',
        loadComponent: () =>
          import('./features/admin/pages/instructors/instructors.component').then(
            (m) => m.InstructorsComponent,
          ),
      },
      {
        path: 'courses',
        loadComponent: () =>
          import('./features/admin/pages/courses/courses.component').then(
            (m) => m.CoursesComponent,
          ),
      },
      {
        path: 'groups',
        loadComponent: () =>
          import('./features/admin/pages/groups/groups.component').then(
            (m) => m.GroupsComponent,
          ),
      },
      {
        path: 'groups/:groupId/sessions',
        loadComponent: () =>
          import('./features/admin/pages/class-sessions/class-sessions.component').then(
            (m) => m.ClassSessionsComponent,
          ),
      },
      {
        path: 'groups/:groupId/sessions/:classSessionId/attendance',
        loadComponent: () =>
          import('./features/admin/pages/attendance-management/attendance-management.component').then(
            (m) => m.AttendanceManagementComponent,
          ),
      },
      {
        path: 'groups/:groupId/sessions/:classSessionId/attendance',
        loadComponent: () =>
          import('./features/admin/pages/attendance-management/attendance-management.component').then(
            (m) => m.AttendanceManagementComponent,
          ),
      },
      {
        path: 'attendance',
        loadComponent: () =>
          import('./features/admin/pages/attendance-overview/attendance-overview.component').then(
            (m) => m.AttendanceOverviewComponent,
          ),
      },
      {
        path: 'assignments',
        loadComponent: () =>
          import('./features/admin/pages/assignments/assignments.component').then(
            (m) => m.AssignmentsComponent,
          ),
      },
      {
        path: 'evaluations',
        loadComponent: () =>
          import('./features/admin/pages/evaluations/evaluations.component').then(
            (m) => m.EvaluationsComponent,
          ),
      },
      {
        path: 'student-feedback',
        loadComponent: () =>
          import('./features/admin/pages/student-feedback/student-feedback.component').then(
            (m) => m.StudentFeedbackComponent,
          ),
      },
      // {
      //   path: 'student-feedback/:id',
      //   loadComponent: () =>
      //     import('./features/admin/pages/student-feedback-details/student-feedback-details.component').then(
      //       (m) => m.StudentFeedbackDetailsComponent,
      //     ),
      // },
      {
        path: 'payments',
        loadComponent: () =>
          import('./features/admin/pages/admin-payments/admin-payments.component').then(
            (m) => m.AdminPaymentsComponent,
          ),
      },
      {
        path: 'payments',
        loadComponent: () =>
          import('./features/admin/pages/admin-reports/admin-reports.component').then(
            (m) => m.AdminReportsComponent,
          ),
      },
    ],
  },
  {
    path: 'student',
    canActivate: [authGuard, studentGuard],
    loadComponent: () =>
      import('./layouts/student-layout/student-layout.component').then(
        (m) => m.StudentLayoutComponent,
      ),
    children: [
      {
        path: '',
        redirectTo: 'feedback',
        pathMatch: 'full',
      },
      {
        path: 'feedback',
        loadComponent: () =>
          import('./features/student/pages/student-feedback/student-feedback.component').then(
            (m) => m.StudentFeedbackComponent,
          ),
      },
      {
        path: 'feedback/new',
        loadComponent: () =>
          import('./features/student/pages/feedback-create/feedback-create.component').then(
            (m) => m.FeedbackCreateComponent,
          ),
      },
      {
        path: 'feedback/:id',
        loadComponent: () =>
          import('./features/student/pages/feedback-details/feedback-details.component').then(
            (m) => m.FeedbackDetailsComponent,
          ),
      },
    ],
  },

  {
    path: '**',
    redirectTo: 'admin/dashboard',
  },
];
