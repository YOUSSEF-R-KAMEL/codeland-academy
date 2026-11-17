import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';

import { environment } from '../../../../environments/environment';
import { ApiSuccessResponse } from '../../../core/models/auth.model';

import {
  CreateStudentRequest,
  CreateStudentResponse,
  Student,
  StudentsFilters,
  StudentsResponseData,
  UpdateStudentRequest,
} from '../models/student.model';

@Injectable({
  providedIn: 'root',
})
export class StudentsService {
  private readonly http = inject(HttpClient);

  private readonly apiUrl = `${environment.apiUrl}/admin`;

  getStudents(filters: StudentsFilters) {
    let params = new HttpParams()
      .set('page', filters.page ?? 1)
      .set('per_page', filters.per_page ?? 10)
      .set('role', 'student');

    if (filters.search) {
      params = params.set('search', filters.search);
    }

    if (filters.is_active !== undefined) {
      params = params.set('is_active', filters.is_active ? '1' : '0');
    }

    return this.http.get<ApiSuccessResponse<StudentsResponseData>>(
      `${this.apiUrl}/users`,
      { params },
    );
  }

  createStudent(
    payload: CreateStudentRequest,
  ): Observable<CreateStudentResponse> {
    return this.http.post<CreateStudentResponse>(
      `${this.apiUrl}/users`,
      payload,
    );
  }

  updateStudent(
    studentId: number,
    payload: UpdateStudentRequest,
  ): Observable<ApiSuccessResponse<Student>> {
    return this.http.patch<ApiSuccessResponse<Student>>(
      `${this.apiUrl}/users/${studentId}`,
      payload,
    );
  }

  updateStudentStatus(
    studentId: number,
    isActive: boolean,
  ): Observable<ApiSuccessResponse<Student>> {
    return this.http.patch<ApiSuccessResponse<Student>>(
      `${this.apiUrl}/users/${studentId}/status`,
      { is_active: isActive },
    );
  }
}
