import { inject, Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

import { environment } from '../../../../environments/environment';

import {
  SessionAttendanceRoster,
  RecordAttendancePayload,
  RecordAttendanceResponse,
  StudentAttendanceResponse,
} from '../models/attendance.model';

interface ApiResponse<T> {
  success: boolean;
  message: string;
  data: T;
}

@Injectable({
  providedIn: 'root',
})
export class AttendanceService {
  private readonly http = inject(HttpClient);
  private readonly apiUrl = environment.apiUrl;

  getSessionAttendance(
    groupId: number,
    sessionId: number,
  ): Observable<ApiResponse<SessionAttendanceRoster>> {
    return this.http.get<ApiResponse<SessionAttendanceRoster>>(
      `${this.apiUrl}/admin/groups/${groupId}/sessions/${sessionId}/attendance`,
    );
  }

  recordAttendance(
    groupId: number,
    sessionId: number,
    payload: RecordAttendancePayload,
  ): Observable<ApiResponse<RecordAttendanceResponse>> {
    return this.http.post<ApiResponse<RecordAttendanceResponse>>(
      `${this.apiUrl}/admin/groups/${groupId}/sessions/${sessionId}/attendance`,
      payload,
    );
  }

  getMyAttendance(): Observable<ApiResponse<StudentAttendanceResponse>> {
    return this.http.get<ApiResponse<StudentAttendanceResponse>>(
      `${this.apiUrl}/student/attendance`,
    );
  }
}
