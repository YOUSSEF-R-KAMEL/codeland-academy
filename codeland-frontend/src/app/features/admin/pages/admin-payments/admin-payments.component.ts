import { CommonModule } from '@angular/common';
import { Component, computed, inject, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import {
  CreatePaymentPayload,
  Payment,
  PaymentInvoice,
  PaymentMethod,
  UpdatePaymentPayload,
} from '../../models/payment.model';
import { PaymentService } from '../../services/payment.service';
import { TranslatePipe, TranslateService } from '@ngx-translate/core';
import Swal from 'sweetalert2';
@Component({
  selector: 'app-admin-payments',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, TranslatePipe],
  templateUrl: './admin-payments.component.html',
})
export class AdminPaymentsComponent implements OnInit {
  private readonly paymentService = inject(PaymentService);
  private readonly fb = inject(FormBuilder);
  private readonly translate = inject(TranslateService);

  payments = signal<Payment[]>([]);
  invoices = signal<PaymentInvoice[]>([]);

  loading = signal(false);
  saving = signal(false);
  error = signal('');
  success = signal('');

  search = signal('');
  selectedInvoiceId = signal<number | null>(null);

  currentPage = signal(1);
  lastPage = signal(1);
  totalPayments = signal(0);

  showForm = signal(false);
  editingPayment = signal<Payment | null>(null);

  readonly isEditMode = computed(() => this.editingPayment() !== null);

  readonly paymentMethods: {
    value: PaymentMethod;
    label: string;
  }[] = [
    { value: 'cash', label: 'admin.admin_payments.methods.cash' },
    { value: 'instapay', label: 'admin.admin_payments.methods.instapay' },
    {
      value: 'bank_transfer',
      label: 'admin.admin_payments.methods.bank_transfer',
    },
  ];

  readonly form = this.fb.nonNullable.group({
    invoice_id: [0, [Validators.required, Validators.min(1)]],
    payment_installment_id: [undefined as number | undefined],
    amount: [0, [Validators.required, Validators.min(0.01)]],
    payment_method: ['cash' as PaymentMethod, Validators.required],
    paid_at: [this.today(), Validators.required],
    transaction_reference: [''],
    notes: [''],
  });

  selectedInvoice = computed(() => {
    const id = this.selectedInvoiceId();

    return this.invoices().find((invoice) => invoice.id === id) ?? null;
  });

  ngOnInit(): void {
    this.loadPayments();
    this.loadInvoices();
  }

  loadPayments(page = 1): void {
    this.loading.set(true);
    this.error.set('');

    this.paymentService
      .getPayments({
        search: this.search(),
        per_page: 15,
        page,
        from: undefined,
        to: undefined,
      })
      .subscribe({
        next: (response) => {
          this.payments.set(response.data.data);
          this.currentPage.set(response.data.current_page);
          this.lastPage.set(response.data.last_page);
          this.totalPayments.set(response.data.total);
          this.loading.set(false);
        },
        error: (err) => {
          this.error.set(
            this.apiError(
              err,
              'admin.admin_payments.messages.load_payments_error',
            ),
          );
          this.loading.set(false);
        },
      });
  }

  loadInvoices(search = ''): void {
    this.paymentService.getOutstandingInvoices(search).subscribe({
      next: (response) => {
        this.invoices.set(response.data.data);
      },
      error: (err) => {
        this.error.set(
          this.apiError(
            err,
            'admin.admin_payments.messages.load_invoices_error',
          ),
        );
      },
    });
  }

  onSearch(value: string): void {
    this.search.set(value.trim());
    this.loadPayments(1);
  }

  openForm(): void {
    this.editingPayment.set(null);
    this.success.set('');
    this.error.set('');

    this.form.reset({
      invoice_id: 0,
      payment_installment_id: undefined,
      amount: 0,
      payment_method: 'cash',
      paid_at: this.today(),
      transaction_reference: '',
      notes: '',
    });

    this.selectedInvoiceId.set(null);
    this.showForm.set(true);
  }

  openEditForm(payment: Payment): void {
    this.success.set('');
    this.error.set('');

    const invoice = payment.invoice;

    if (!invoice) {
      this.error.set(
        this.translate.instant(
          'admin.admin_payments.messages.payment_invoice_missing',
        ),
      );
      return;
    }

    // Make sure the invoice is available to the modal,
    // even if it was not returned by the outstanding invoices endpoint.
    this.invoices.update((currentInvoices) => {
      const exists = currentInvoices.some((item) => item.id === invoice.id);

      return exists ? currentInvoices : [...currentInvoices, invoice];
    });

    this.editingPayment.set(payment);
    this.selectedInvoiceId.set(invoice.id);

    this.form.reset({
      invoice_id: invoice.id,
      payment_installment_id: payment.payment_installment_id ?? undefined,
      amount: Number(payment.amount),
      payment_method: payment.payment_method,
      paid_at: this.toLocalDateInput(payment.paid_at),
      transaction_reference: payment.transaction_reference ?? '',
      notes: payment.notes ?? '',
    });

    this.showForm.set(true);
  }

  closeForm(): void {
    this.showForm.set(false);
    this.editingPayment.set(null);
    this.selectedInvoiceId.set(null);
  }

  onInvoiceChange(invoiceId: number): void {
    const invoice = this.invoices().find(
      (item) => item.id === Number(invoiceId),
    );

    this.selectedInvoiceId.set(invoice?.id ?? null);

    this.form.patchValue({
      invoice_id: invoice?.id ?? 0,
      payment_installment_id: undefined,
      amount: invoice ? this.remainingAmount(invoice) : 0,
    });
  }

  onInstallmentChange(installmentId: number | null): void {
    const invoice = this.selectedInvoice();

    if (!invoice || installmentId === null) {
      this.form.patchValue({
        payment_installment_id: undefined,
        amount: invoice ? this.remainingAmount(invoice) : 0,
      });
      return;
    }

    const installment = invoice.installments?.find(
      (item) => item.id === Number(installmentId),
    );

    if (!installment) {
      this.form.patchValue({
        payment_installment_id: undefined,
        amount: invoice ? this.remainingAmount(invoice) : 0,
      });
      return;
    }

    this.form.patchValue({
      payment_installment_id: installment.id,
      amount: Number(installment.remaining_amount),
    });
  }

  remainingAmount(invoice: PaymentInvoice): number {
    return Math.max(
      0,
      Number(invoice.total_amount) - Number(invoice.paid_amount),
    );
  }

  submitPayment(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    this.saving.set(true);
    this.error.set('');
    this.success.set('');

    const raw = this.form.getRawValue();
    const editingPayment = this.editingPayment();

    if (editingPayment) {
      const payload: UpdatePaymentPayload = {
        payment_installment_id: raw.payment_installment_id ?? null,
        amount: Number(raw.amount),
        payment_method: raw.payment_method,
        paid_at: raw.paid_at,
        transaction_reference: raw.transaction_reference || null,
        notes: raw.notes || null,
      };

      this.paymentService.updatePayment(editingPayment.id, payload).subscribe({
        next: (response) => {
          this.saving.set(false);
          this.closeForm();

          this.success.set(
            response.message ||
              this.translate.instant(
                'admin.admin_payments.messages.update_success',
              ),
          );

          this.loadPayments(this.currentPage());
          this.loadInvoices();
        },
        error: (err) => {
          this.saving.set(false);
          this.error.set(
            this.apiError(err, 'admin.admin_payments.messages.update_error'),
          );
        },
      });

      return;
    }

    const payload: CreatePaymentPayload = {
      invoice_id: Number(raw.invoice_id),
      payment_installment_id: raw.payment_installment_id,
      amount: Number(raw.amount),
      payment_method: raw.payment_method,
      paid_at: raw.paid_at,
      transaction_reference: raw.transaction_reference || null,
      notes: raw.notes || null,
    };

    this.paymentService.createPayment(payload).subscribe({
      next: (response) => {
        this.saving.set(false);
        this.closeForm();

        this.success.set(
          response.message ||
            this.translate.instant(
              'admin.admin_payments.messages.payment_success',
            ),
        );

        this.loadPayments();
        this.loadInvoices();
      },
      error: (err) => {
        this.saving.set(false);
        this.error.set(
          this.apiError(err, 'admin.admin_payments.messages.payment_error'),
        );
      },
    });
  }

  cancelPayment(payment: Payment): void {
    Swal.fire({
      title: this.translate.instant('admin.admin_payments.cancel_payment'),
      text: this.translate.instant(
        'admin.admin_payments.messages.confirm_cancel',
      ),
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: this.translate.instant(
        'admin.admin_payments.cancel_payment',
      ),
      cancelButtonText: this.translate.instant('admin.admin_payments.close'),
      confirmButtonColor: '#dc2626',
      cancelButtonColor: '#6b7280',
      reverseButtons: true,
    }).then((result) => {
      if (!result.isConfirmed) {
        return;
      }

      this.error.set('');
      this.success.set('');

      this.paymentService.cancelPayment(payment.id).subscribe({
        next: (response) => {
          this.success.set(
            response.message ||
              this.translate.instant(
                'admin.admin_payments.messages.cancel_success',
              ),
          );

          Swal.fire({
            title: this.translate.instant('common.success'),
            text:
              response.message ||
              this.translate.instant(
                'admin.admin_payments.messages.cancel_success',
              ),
            icon: 'success',
            confirmButtonText: this.translate.instant('common.ok'),
          });

          this.loadPayments(this.currentPage());
          this.loadInvoices();
        },
        error: (err) => {
          const message = this.apiError(
            err,
            'admin.admin_payments.messages.cancel_error',
          );

          this.error.set(message);

          Swal.fire({
            title: this.translate.instant('common.error'),
            text: message,
            icon: 'error',
            confirmButtonText: this.translate.instant('common.ok'),
          });
        },
      });
    });
  }

  formatAmount(value: string | number): string {
    return Number(value || 0).toFixed(2);
  }

  private toLocalDateInput(value: string | null): string {
    if (!value) {
      return this.today();
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
      return this.today();
    }

    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
  }

  private apiError(err: any, fallbackKey: string): string {
    const validationErrors = err?.error?.errors;

    if (validationErrors && typeof validationErrors === 'object') {
      const firstError = Object.values(validationErrors)
        .flat()
        .find((message) => typeof message === 'string');

      if (firstError) {
        return firstError as string;
      }
    }

    const message = err?.error?.message;

    if (message && !message.includes('Server Error')) {
      return message;
    }

    return this.translate.instant(fallbackKey);
  }

  private today(): string {
    const date = new Date();
    const offset = date.getTimezoneOffset();

    return new Date(date.getTime() - offset * 60000).toISOString().slice(0, 10);
  }
}
