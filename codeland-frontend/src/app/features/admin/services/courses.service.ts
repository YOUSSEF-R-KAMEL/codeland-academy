import { inject, Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../../environments/environment.development';
import {
  AdminCoursesResponse,
  CoursesApiResponse,
  CreateCourseRequest,
  UpdateCourseRequest,
} from '../models/course.model';
@Injectable({
  providedIn: 'root',
})
export class CoursesService {
  private readonly http = inject(HttpClient);
  private readonly apiUrl = `${environment.apiUrl}/admin/courses`;

  getAdminCourses(): Observable<{
    success: boolean;
    message: string;
    data: AdminCoursesResponse;
  }> {
    return this.http.get<{
      success: boolean;
      message: string;
      data: AdminCoursesResponse;
    }>(this.apiUrl);
  }

  createCourse(payload: CreateCourseRequest): Observable<CoursesApiResponse> {
    return this.http.post<CoursesApiResponse>(this.apiUrl, payload);
  }

  updateCourse(
    id: number,
    payload: UpdateCourseRequest,
  ): Observable<CoursesApiResponse> {
    return this.http.patch<CoursesApiResponse>(`${this.apiUrl}/${id}`, payload);
  }

  deleteCourse(id: number): Observable<CoursesApiResponse> {
    return this.http.delete<CoursesApiResponse>(`${this.apiUrl}/${id}`);
  }
}
