import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import {
  CreateEnrollmentRequest,
  Enrollment,
} from '../models/enrollment.model';
import { environment } from '../../../../environments/environment.development';

interface EnrollmentResponse {
  success: boolean;
  message: string;
  data: Enrollment;
}

@Injectable({
  providedIn: 'root',
})
export class EnrollmentsService {
  private readonly http = inject(HttpClient);

  private readonly apiUrl = `${environment.apiUrl}/admin/groups`;

  createEnrollment(
    groupId: number,
    payload: CreateEnrollmentRequest,
  ): Observable<EnrollmentResponse> {
    return this.http.post<EnrollmentResponse>(
      `${this.apiUrl}/${groupId}/enrollments`,
      payload,
    );
  }
}
