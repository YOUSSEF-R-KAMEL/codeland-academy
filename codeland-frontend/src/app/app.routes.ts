import { Routes } from '@angular/router';

import { authGuard } from './core/guards/auth.guard';
import { adminGuard } from './core/guards/admin.guard';
import { guestGuard } from './core/guards/guest.guard';

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
    ],
  },
  {
    path: '**',
    redirectTo: 'admin/dashboard',
  },
];
