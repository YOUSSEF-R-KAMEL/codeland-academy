export interface ReportApiResponse<T> {
  success: boolean;
  message?: string;
  data: T;
}

export interface ReportFilters {
  from?: string;
  to?: string;
}

export interface PaymentMethodReport {
  payment_method: string;
  count: number;
  total: number | string;
}

export interface PaymentMonthReport {
  month: string;
  count: number;
  total: number | string;
}

export interface PaymentsReport {
  payments_count: number;
  total_amount: number;
  by_method: PaymentMethodReport[];
  by_month: PaymentMonthReport[];
}

export interface InvoiceStatusReport {
  status: string;
  count: number;
}

export interface InvoicePlanReport {
  payment_plan: string;
  count: number;
  total: number | string;
}

export interface InvoicesReport {
  count: number;
  total_amount: number;
  paid_amount: number;
  remaining_amount: number;
  by_status: InvoiceStatusReport[];
  by_plan: InvoicePlanReport[];
}

export interface OutstandingInvoice {
  id: number;
  invoice_number: string;
  total_amount: number | string;
  paid_amount: number | string;
  currency: string;
  status: string;
  issued_at: string;
  due_at: string | null;
  enrollment?: {
    student?: {
      id: number;
      name: string;
      email: string;
    };
    group?: {
      id: number;
      name: string;
      code: string;
    } | null;
  };
}

export interface PaginatedReport<T> {
  current_page: number;
  data: T[];
  last_page: number;
  per_page: number;
  total: number;
}
