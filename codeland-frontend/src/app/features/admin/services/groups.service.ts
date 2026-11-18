import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { environment } from '../../../../environments/environment';
import {
  CreateGroupRequest,
  GroupApiResponse,
  GroupsApiResponse,
  UpdateGroupRequest,
} from '../models/group.model';

@Injectable({
  providedIn: 'root',
})
export class GroupsService {
  private readonly http = inject(HttpClient);

  private readonly apiUrl = `${environment.apiUrl}/admin/courses`;

  getGroups(courseId: number) {
    return this.http.get<GroupsApiResponse>(
      `${this.apiUrl}/${courseId}/groups`,
    );
  }

  getGroup(courseId: number, groupId: number) {
    return this.http.get<GroupApiResponse>(
      `${this.apiUrl}/${courseId}/groups/${groupId}`,
    );
  }

  createGroup(courseId: number, payload: CreateGroupRequest) {
    return this.http.post<GroupApiResponse>(
      `${this.apiUrl}/${courseId}/groups`,
      payload,
    );
  }

  updateGroup(courseId: number, groupId: number, payload: UpdateGroupRequest) {
    return this.http.patch<GroupApiResponse>(
      `${this.apiUrl}/${courseId}/groups/${groupId}`,
      payload,
    );
  }

  deleteGroup(courseId: number, groupId: number) {
    return this.http.delete<GroupApiResponse>(
      `${this.apiUrl}/${courseId}/groups/${groupId}`,
    );
  }
}
