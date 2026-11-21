import { inject, Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';

import {
  InvoicesReport,
  OutstandingInvoice,
  PaginatedReport,
  PaymentsReport,
  ReportApiResponse,
  ReportFilters,
} from '../models/report.model';
import { environment } from '../../../../environments/environment.development';

@Injectable({
  providedIn: 'root',
})
export class ReportService {
  private readonly http = inject(HttpClient);
  private readonly apiUrl = `${environment.apiUrl}/admin/reports`;

  private buildParams(filters: ReportFilters): HttpParams {
    let params = new HttpParams();

    if (filters.from) {
      params = params.set('from', filters.from);
    }

    if (filters.to) {
      params = params.set('to', filters.to);
    }

    return params;
  }

  getPaymentsReport(
    filters: ReportFilters = {},
  ): Observable<ReportApiResponse<PaymentsReport>> {
    return this.http.get<ReportApiResponse<PaymentsReport>>(
      `${this.apiUrl}/payments`,
      { params: this.buildParams(filters) },
    );
  }

  getInvoicesReport(
    filters: ReportFilters = {},
  ): Observable<ReportApiResponse<InvoicesReport>> {
    return this.http.get<ReportApiResponse<InvoicesReport>>(
      `${this.apiUrl}/invoices`,
      { params: this.buildParams(filters) },
    );
  }

  getOutstandingReport(
    filters: ReportFilters = {},
    page = 1,
  ): Observable<ReportApiResponse<PaginatedReport<OutstandingInvoice>>> {
    let params = this.buildParams(filters);
    params = params.set('page', page).set('per_page', 15);

    return this.http.get<
      ReportApiResponse<PaginatedReport<OutstandingInvoice>>
    >(`${this.apiUrl}/outstanding`, { params });
  }
}
