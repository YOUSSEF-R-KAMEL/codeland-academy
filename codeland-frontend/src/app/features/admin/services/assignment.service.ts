import { inject, Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../../environments/environment';

import { AssignmentListResponse } from '../models/assignment.model';
import {
  CourseLessonsResponse,
  CoursesResponse,
} from '../models/course-lesson.model';

export interface CreateAssignmentPayload {
  course_lesson_id: number;
  title: string;
  instructions: string;
  resource_url: string | null;
  published_at: string | null;
  due_at: string | null;
  is_published: boolean;
}

export type UpdateAssignmentPayload = Partial<CreateAssignmentPayload>;

export interface CreateAssignmentResponse {
  message: string;
  data: unknown;
}

@Injectable({
  providedIn: 'root',
})
export class AssignmentService {
  private readonly http = inject(HttpClient);
  private readonly apiUrl = environment.apiUrl;

  getAssignments(): Observable<AssignmentListResponse> {
    return this.http.get<AssignmentListResponse>(
      `${this.apiUrl}/admin/assignments`,
    );
  }

  getCourses(): Observable<CoursesResponse> {
    return this.http.get<CoursesResponse>(`${this.apiUrl}/admin/courses`);
  }

  getCourseLessons(courseId: number): Observable<CourseLessonsResponse> {
    return this.http.get<CourseLessonsResponse>(
      `${this.apiUrl}/courses/${courseId}/lessons`,
    );
  }

  createAssignment(
    payload: CreateAssignmentPayload,
  ): Observable<CreateAssignmentResponse> {
    return this.http.post<CreateAssignmentResponse>(
      `${this.apiUrl}/admin/assignments`,
      payload,
    );
  }

  updateAssignment(
    assignmentId: number,
    payload: UpdateAssignmentPayload,
  ): Observable<CreateAssignmentResponse> {
    return this.http.patch<CreateAssignmentResponse>(
      `${this.apiUrl}/admin/assignments/${assignmentId}`,
      payload,
    );
  }

  deleteAssignment(assignmentId: number): Observable<void> {
    return this.http.delete<void>(
      `${this.apiUrl}/admin/assignments/${assignmentId}`,
    );
  }
}
