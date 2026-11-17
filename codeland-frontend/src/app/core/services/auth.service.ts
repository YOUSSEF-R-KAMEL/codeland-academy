import { Injectable, inject, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, tap, finalize, map } from 'rxjs';

import {
  ApiSuccessResponse,
  AuthUser,
  LoginRequest,
  LoginResponseData,
} from '../models/auth.model';
import { environment } from '../../../environments/environment.development';

@Injectable({
  providedIn: 'root',
})
export class AuthService {
  private readonly http = inject(HttpClient);

  private readonly apiUrl = environment.apiUrl;
  private readonly tokenKey = 'codeland_auth_token';
  private readonly userKey = 'codeland_auth_user';

  readonly currentUser = signal<AuthUser | null>(this.getStoredUser());
  readonly isAuthenticated = signal<boolean>(!!this.getStoredToken());
  readonly sessionValidated = signal(false);

  login(
    credentials: LoginRequest,
  ): Observable<ApiSuccessResponse<LoginResponseData>> {
    return this.http
      .post<
        ApiSuccessResponse<LoginResponseData>
      >(`${this.apiUrl}/auth/login`, credentials)
      .pipe(
        tap((response) => {
          this.setSession(response.data.token, response.data.user);
        }),
      );
  }

  validateSession(): Observable<boolean> {
    if (!this.getStoredToken()) {
      this.clearSession();
      return new Observable((subscriber) => {
        subscriber.next(false);
        subscriber.complete();
      });
    }

    if (this.sessionValidated()) {
      return new Observable((subscriber) => {
        subscriber.next(true);
        subscriber.complete();
      });
    }

    return this.me().pipe(
      map((response) => {
        const user = response.data.user;

        if (!user.is_active) {
          this.clearSession();
          return false;
        }

        this.sessionValidated.set(true);
        return true;
      }),
    );
  }

  logout(): Observable<ApiSuccessResponse<unknown>> {
    return this.http
      .post<ApiSuccessResponse<unknown>>(`${this.apiUrl}/auth/logout`, {})
      .pipe(finalize(() => this.clearSession()));
  }

  me(): Observable<ApiSuccessResponse<{ user: AuthUser }>> {
    return this.http
      .get<ApiSuccessResponse<{ user: AuthUser }>>(`${this.apiUrl}/auth/me`)
      .pipe(
        tap((response) => {
          this.currentUser.set(response.data.user);
          localStorage.setItem(
            this.userKey,
            JSON.stringify(response.data.user),
          );
        }),
      );
  }

  getToken(): string | null {
    return this.getStoredToken();
  }

  hasRole(role: string): boolean {
    return (
      this.currentUser()?.roles.some((userRole) => userRole.slug === role) ??
      false
    );
  }

  clearSession(): void {
    localStorage.removeItem(this.tokenKey);
    localStorage.removeItem(this.userKey);

    this.currentUser.set(null);
    this.isAuthenticated.set(false);
    this.sessionValidated.set(false);
  }

  private setSession(token: string, user: AuthUser): void {
    localStorage.setItem(this.tokenKey, token);
    localStorage.setItem(this.userKey, JSON.stringify(user));

    this.currentUser.set(user);
    this.isAuthenticated.set(true);
    this.sessionValidated.set(true);
  }

  private getStoredToken(): string | null {
    return localStorage.getItem(this.tokenKey);
  }

  private getStoredUser(): AuthUser | null {
    const storedUser = localStorage.getItem(this.userKey);

    if (!storedUser) {
      return null;
    }

    try {
      return JSON.parse(storedUser) as AuthUser;
    } catch {
      localStorage.removeItem(this.userKey);
      return null;
    }
  }
}
