import { inject, Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';

import { environment } from '../../../../environments/environment';

import {
  EvaluationFilters,
  EvaluationsResponse,
  EvaluationStatisticsResponse,
  InstructorFeedbackResponse,
} from '../models/evaluation.model';

@Injectable({
  providedIn: 'root',
})
export class EvaluationService {
  private readonly http = inject(HttpClient);
  private readonly apiUrl = environment.apiUrl;

  getEvaluations(
    filters: EvaluationFilters = {},
  ): Observable<EvaluationsResponse> {
    return this.http.get<EvaluationsResponse>(
      `${this.apiUrl}/admin/evaluations`,
      {
        params: this.buildParams(filters),
      },
    );
  }

  getStatistics(): Observable<EvaluationStatisticsResponse> {
    return this.http.get<EvaluationStatisticsResponse>(
      `${this.apiUrl}/admin/evaluations/statistics`,
    );
  }

  getInstructorFeedback(
    filters: EvaluationFilters = {},
  ): Observable<InstructorFeedbackResponse> {
    return this.http.get<InstructorFeedbackResponse>(
      `${this.apiUrl}/admin/evaluations/instructor-feedback`,
      {
        params: this.buildParams(filters),
      },
    );
  }

  private buildParams(filters: EvaluationFilters): HttpParams {
    let params = new HttpParams();

    Object.entries(filters).forEach(([key, value]) => {
      if (value !== undefined && value !== null && value !== '') {
        params = params.set(key, String(value));
      }
    });

    return params;
  }
}
