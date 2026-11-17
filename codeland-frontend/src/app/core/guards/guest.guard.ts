import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { catchError, map, of } from 'rxjs';

import { AuthService } from '../services/auth.service';

export const guestGuard: CanActivateFn = () => {
  const authService = inject(AuthService);
  const router = inject(Router);

  if (!authService.getToken()) {
    return true;
  }

  return authService.validateSession().pipe(
    map((isValid) => {
      if (isValid && authService.hasRole('admin')) {
        return router.createUrlTree(['/admin/dashboard']);
      }

      return true;
    }),
    catchError(() => {
      authService.clearSession();
      return of(true);
    }),
  );
};
