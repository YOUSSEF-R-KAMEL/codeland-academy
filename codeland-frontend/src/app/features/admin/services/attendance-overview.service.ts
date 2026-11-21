import { inject, Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../../environments/environment';
import {
  AttendanceOverviewResponse,
  GroupAttendanceOverviewResponse,
} from '../models/attendance-overview.model';

@Injectable({
  providedIn: 'root',
})
export class AttendanceOverviewService {
  private readonly http = inject(HttpClient);
  private readonly apiUrl = environment.apiUrl;

  getSessions(): Observable<AttendanceOverviewResponse> {
    return this.http.get<AttendanceOverviewResponse>(
      `${this.apiUrl}/admin/attendance/sessions`,
    );
  }

  getGroupOverview(
    groupId: number,
  ): Observable<GroupAttendanceOverviewResponse> {
    return this.http.get<GroupAttendanceOverviewResponse>(
      `${this.apiUrl}/admin/groups/${groupId}/attendance-overview`,
    );
  }
}
