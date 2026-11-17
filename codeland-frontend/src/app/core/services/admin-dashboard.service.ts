import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, forkJoin, of } from 'rxjs';
import { catchError, map } from 'rxjs/operators';

import { environment } from '../../../environments/environment';

interface ApiResponse<T> {
  success: boolean;
  message?: string;
  data: T;
}

interface UsersResponse {
  users: unknown[];
  pagination: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

interface CoursesResponse {
  courses: unknown[];
}

export interface FinancialSummary {
  total_invoices: number;
  total_amount: string;
  paid_amount: string;
  remaining_amount: string;
  overdue_invoices_count: number;
  paid_invoices_count: number;
  unpaid_invoices_count: number;
  partially_paid_invoices_count: number;
  payments_by_month: {
    month: string;
    payments_count: number;
    total_amount: string;
  }[];
}

export interface DashboardData {
  studentsCount: number | null;
  instructorsCount: number | null;
  coursesCount: number | null;
  financial: FinancialSummary | null;
}

@Injectable({
  providedIn: 'root',
})
export class AdminDashboardService {
  private readonly http = inject(HttpClient);
  private readonly apiUrl = environment.apiUrl;

  getDashboardData(): Observable<DashboardData> {
    return forkJoin({
      studentsCount: this.getUsersCount('student'),
      instructorsCount: this.getUsersCount('instructor'),
      coursesCount: this.getCoursesCount(),
      financial: this.getFinancialSummary(),
    });
  }

  private getUsersCount(role: string): Observable<number | null> {
    return this.http
      .get<ApiResponse<UsersResponse>>(`${this.apiUrl}/admin/users`, {
        params: {
          role,
          per_page: 1,
        },
      })
      .pipe(
        map((response) => response.data.pagination.total),
        catchError(() => of(null)),
      );
  }

  private getCoursesCount(): Observable<number | null> {
    return this.http
      .get<ApiResponse<CoursesResponse>>(`${this.apiUrl}/courses`)
      .pipe(
        map((response) => response.data.courses.length),
        catchError(() => of(null)),
      );
  }

  private getFinancialSummary(): Observable<FinancialSummary | null> {
    return this.http
      .get<
        ApiResponse<FinancialSummary>
      >(`${this.apiUrl}/admin/financial-summary`)
      .pipe(
        map((response) => response.data),
        catchError(() => of(null)),
      );
  }
}
