import { inject, Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';

import {
  ApiResponse,
  CreatePaymentPayload,
  PaginatedData,
  Payment,
  PaymentFilters,
  PaymentInvoice,
  UpdatePaymentPayload,
} from '../models/payment.model';
import { environment } from '../../../../environments/environment.development';

@Injectable({
  providedIn: 'root',
})
export class PaymentService {
  private readonly http = inject(HttpClient);
  private readonly apiUrl = `${environment.apiUrl}/admin/payments`;
  getPayments(
    filters: PaymentFilters = {},
  ): Observable<ApiResponse<PaginatedData<Payment>>> {
    let params = new HttpParams();

    Object.entries(filters).forEach(([key, value]) => {
      if (value !== undefined && value !== null && value !== '') {
        params = params.set(key, value.toString());
      }
    });

    return this.http.get<ApiResponse<PaginatedData<Payment>>>(this.apiUrl, {
      params,
    });
  }

  getOutstandingInvoices(
    search = '',
    perPage = 100,
  ): Observable<ApiResponse<PaginatedData<PaymentInvoice>>> {
    const params = new HttpParams()
      .set('search', search)
      .set('per_page', perPage);

    return this.http.get<ApiResponse<PaginatedData<PaymentInvoice>>>(
      `${this.apiUrl}/invoices`,
      { params },
    );
  }

  createPayment(
    payload: CreatePaymentPayload,
  ): Observable<ApiResponse<Payment>> {
    return this.http.post<ApiResponse<Payment>>(this.apiUrl, payload);
  }

  updatePayment(
    id: number,
    payload: UpdatePaymentPayload,
  ): Observable<ApiResponse<Payment>> {
    return this.http.put<ApiResponse<Payment>>(`${this.apiUrl}/${id}`, payload);
  }

  cancelPayment(id: number): Observable<ApiResponse<null>> {
    return this.http.delete<ApiResponse<null>>(`${this.apiUrl}/${id}`);
  }
}
