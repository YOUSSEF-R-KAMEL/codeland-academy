import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { Observable } from 'rxjs';

import { GroupsResponse } from '../models/group.model';
import { environment } from '../../../../environments/environment.development';

@Injectable({
  providedIn: 'root',
})
export class GroupsService {
  private readonly http = inject(HttpClient);
  private readonly apiUrl = `${environment.apiUrl}/admin/courses`;

  getGroups(courseId: number): Observable<GroupsResponse> {
    return this.http.get<GroupsResponse>(`${this.apiUrl}/${courseId}/groups`);
  }
}
