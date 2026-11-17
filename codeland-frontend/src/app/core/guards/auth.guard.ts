import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { catchError, map, of } from 'rxjs';

import { AuthService } from '../services/auth.service';

export const authGuard: CanActivateFn = () => {
  const authService = inject(AuthService);
  const router = inject(Router);

  if (!authService.isAuthenticated()) {
    return router.createUrlTree(['/login']);
  }

  return authService.validateSession().pipe(
    map((isValid) => (isValid ? true : router.createUrlTree(['/login']))),
    catchError(() => {
      authService.clearSession();
      return of(router.createUrlTree(['/login']));
    }),
  );
};
