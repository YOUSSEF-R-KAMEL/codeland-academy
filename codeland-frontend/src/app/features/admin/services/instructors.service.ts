import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';

import { environment } from '../../../../environments/environment';
import { ApiSuccessResponse } from '../../../core/models/auth.model';

import {
  CreateInstructorRequest,
  Instructor,
  InstructorsFilters,
  InstructorsResponseData,
  UpdateInstructorRequest,
} from '../models/instructor.model';

@Injectable({
  providedIn: 'root',
})
export class InstructorsService {
  private readonly http = inject(HttpClient);
  private readonly apiUrl = `${environment.apiUrl}/admin`;

  getInstructors(
    filters: InstructorsFilters = {},
  ): Observable<ApiSuccessResponse<InstructorsResponseData>> {
    let params = new HttpParams().set('role', 'instructor');

    if (filters.search) {
      params = params.set('search', filters.search);
    }

    if (filters.is_active !== undefined) {
      params = params.set('is_active', String(filters.is_active));
    }

    if (filters.per_page !== undefined) {
      params = params.set('per_page', String(filters.per_page));
    }

    if (filters.page !== undefined) {
      params = params.set('page', String(filters.page));
    }

    return this.http.get<ApiSuccessResponse<InstructorsResponseData>>(
      `${this.apiUrl}/users`,
      { params },
    );
  }

  createInstructor(
    payload: CreateInstructorRequest,
  ): Observable<ApiSuccessResponse<{ user: Instructor }>> {
    return this.http.post<ApiSuccessResponse<{ user: Instructor }>>(
      `${this.apiUrl}/users`,
      payload,
    );
  }

  updateInstructor(
    instructorId: number,
    payload: UpdateInstructorRequest,
  ): Observable<ApiSuccessResponse<{ user: Instructor }>> {
    return this.http.patch<ApiSuccessResponse<{ user: Instructor }>>(
      `${this.apiUrl}/users/${instructorId}`,
      payload,
    );
  }

  updateInstructorStatus(
    instructorId: number,
    isActive: boolean,
  ): Observable<ApiSuccessResponse<{ user: Instructor }>> {
    return this.http.patch<ApiSuccessResponse<{ user: Instructor }>>(
      `${this.apiUrl}/users/${instructorId}/status`,
      { is_active: isActive },
    );
  }
}
