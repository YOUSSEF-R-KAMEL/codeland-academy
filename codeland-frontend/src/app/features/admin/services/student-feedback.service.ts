import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';

import {
  ApiResponse,
  PaginatedStudentFeedback,
  StudentFeedback,
  StudentFeedbackFilters,
  CreateStudentFeedbackRequest,
  ReplyStudentFeedbackRequest,
  UpdateStudentFeedbackStatusRequest,
} from '../models/student-feedback.model';
import { environment } from '../../../../environments/environment.development';

@Injectable({
  providedIn: 'root',
})
export class StudentFeedbackService {
  private readonly http = inject(HttpClient);
  private readonly apiUrl = environment.apiUrl;

  // Student endpoints

  getMyFeedback(
    filters: StudentFeedbackFilters = {},
  ): Observable<ApiResponse<PaginatedStudentFeedback>> {
    return this.http.get<ApiResponse<PaginatedStudentFeedback>>(
      `${this.apiUrl}/student/feedback`,
      {
        params: this.buildParams(filters),
      },
    );
  }

  getMyFeedbackDetails(id: number): Observable<ApiResponse<StudentFeedback>> {
    return this.http.get<ApiResponse<StudentFeedback>>(
      `${this.apiUrl}/student/feedback/${id}`,
    );
  }

  submitFeedback(
    request: CreateStudentFeedbackRequest,
  ): Observable<ApiResponse<StudentFeedback>> {
    return this.http.post<ApiResponse<StudentFeedback>>(
      `${this.apiUrl}/student/feedback`,
      request,
    );
  }

  // Admin endpoints

  getAllFeedback(
    filters: StudentFeedbackFilters = {},
  ): Observable<ApiResponse<PaginatedStudentFeedback>> {
    return this.http.get<ApiResponse<PaginatedStudentFeedback>>(
      `${this.apiUrl}/admin/student-feedback`,
      {
        params: this.buildParams(filters),
      },
    );
  }

  getFeedbackDetails(id: number): Observable<ApiResponse<StudentFeedback>> {
    return this.http.get<ApiResponse<StudentFeedback>>(
      `${this.apiUrl}/admin/student-feedback/${id}`,
    );
  }

  replyToFeedback(
    id: number,
    request: ReplyStudentFeedbackRequest,
  ): Observable<ApiResponse<StudentFeedback>> {
    return this.http.put<ApiResponse<StudentFeedback>>(
      `${this.apiUrl}/admin/student-feedback/${id}/reply`,
      request,
    );
  }

  updateFeedbackStatus(
    id: number,
    request: UpdateStudentFeedbackStatusRequest,
  ): Observable<ApiResponse<StudentFeedback>> {
    return this.http.patch<ApiResponse<StudentFeedback>>(
      `${this.apiUrl}/admin/student-feedback/${id}/status`,
      request,
    );
  }

  private buildParams(filters: StudentFeedbackFilters): HttpParams {
    let params = new HttpParams();

    Object.entries(filters).forEach(([key, value]) => {
      if (value !== undefined && value !== null && value !== '') {
        params = params.set(key, String(value));
      }
    });

    return params;
  }
}
