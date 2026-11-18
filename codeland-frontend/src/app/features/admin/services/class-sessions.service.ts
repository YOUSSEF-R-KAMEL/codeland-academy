import { inject, Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

import { environment } from '../../../../environments/environment';
import {
  ClassSessionPayload,
  ClassSessionsApiResponse,
} from '../models/class-session.model';

@Injectable({
  providedIn: 'root',
})
export class ClassSessionsService {
  private readonly http = inject(HttpClient);
  private readonly apiUrl = environment.apiUrl;

  getSessions(groupId: number): Observable<ClassSessionsApiResponse> {
    return this.http.get<ClassSessionsApiResponse>(
      `${this.apiUrl}/admin/groups/${groupId}/sessions`,
    );
  }

  createSession(
    groupId: number,
    payload: ClassSessionPayload,
  ): Observable<ClassSessionsApiResponse> {
    return this.http.post<ClassSessionsApiResponse>(
      `${this.apiUrl}/admin/groups/${groupId}/sessions`,
      payload,
    );
  }

  updateSession(
    groupId: number,
    sessionId: number,
    payload: ClassSessionPayload,
  ): Observable<ClassSessionsApiResponse> {
    return this.http.patch<ClassSessionsApiResponse>(
      `${this.apiUrl}/admin/groups/${groupId}/sessions/${sessionId}`,
      payload,
    );
  }

  deleteSession(
    groupId: number,
    sessionId: number,
  ): Observable<ClassSessionsApiResponse> {
    return this.http.delete<ClassSessionsApiResponse>(
      `${this.apiUrl}/admin/groups/${groupId}/sessions/${sessionId}`,
    );
  }
}
