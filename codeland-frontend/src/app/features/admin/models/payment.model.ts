export type PaymentMethod = 'cash' | 'instapay' | 'bank_transfer';

export interface PaymentStudent {
  id: number;
  name: string;
  email: string;
}

export interface PaymentGroup {
  id: number;
  name: string;
  code: string;
}

export interface PaymentInstallment {
  id: number;
  invoice_id: number;
  installment_number: number;
  amount: string | number;
  paid_amount: string | number;
  remaining_amount: string | number;
  paid_at: string | null;
  due_at: string;
  status: string;
  is_overdue?: boolean;
  overdue_days?: number;
  notes?: string | null;
}

export interface PaymentInvoice {
  id: number;
  invoice_number: string;
  total_amount: string | number;
  paid_amount: string | number;
  remaining_amount?: number;
  currency: string;
  status: string;
  issued_at: string;
  due_at: string | null;
  enrollment?: {
    student: PaymentStudent;
    group?: PaymentGroup | null;
  };
  installments?: PaymentInstallment[];
}

export interface Payment {
  id: number;
  invoice_id: number;
  payment_installment_id: number | null;
  recorded_by: number;
  transaction_reference: string | null;
  amount: string | number;
  currency: string;
  payment_method: PaymentMethod;
  status: string;
  paid_at: string;
  notes: string | null;
  invoice?: PaymentInvoice;
  installment?: PaymentInstallment | null;
  recorded_by_user?: {
    id: number;
    name: string;
  };
  recordedBy?: {
    id: number;
    name: string;
  };
}

export interface PaymentFilters {
  search?: string;
  page?: number;
  per_page?: number;
  from?: string;
  to?: string;
}

export interface PaginatedData<T> {
  current_page: number;
  data: T[];
  last_page: number;
  per_page: number;
  total: number;
}

export interface ApiResponse<T> {
  success: boolean;
  message?: string;
  data: T;
}

export interface CreatePaymentPayload {
  invoice_id: number;
  payment_installment_id?: number | null;
  amount: number;
  payment_method: PaymentMethod;
  paid_at: string;
  transaction_reference?: string | null;
  notes?: string | null;
}


export interface UpdatePaymentPayload {
  payment_installment_id: number | null;
  amount: number;
  payment_method: PaymentMethod;
  paid_at: string;
  transaction_reference?: string | null;
  notes?: string | null;
}
