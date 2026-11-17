import { inject, Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { Course, AdminCoursesResponse } from '../models/course.model';
import { environment } from '../../../../environments/environment.development';

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
}
