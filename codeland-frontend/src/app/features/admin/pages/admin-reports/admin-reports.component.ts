import { CommonModule } from '@angular/common';
import { Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { forkJoin } from 'rxjs';

import {
  InvoicesReport,
  OutstandingInvoice,
  PaginatedReport,
  PaymentMethodReport,
  PaymentMonthReport,
  PaymentsReport,
  ReportFilters,
} from '../../models/report.model';
import { ReportService } from '../../services/report.service';

@Component({
  selector: 'app-admin-reports',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './admin-reports.component.html',
})
export class AdminReportsComponent implements OnInit {
  private readonly reportService = inject(ReportService);

  loading = signal(false);
  error = signal('');

  from = signal('');
  to = signal('');

  payments = signal<PaymentsReport | null>(null);
  invoices = signal<InvoicesReport | null>(null);
  outstanding = signal<PaginatedReport<OutstandingInvoice> | null>(null);

  ngOnInit(): void {
    this.loadReports();
  }

  loadReports(): void {
    if (this.from() && this.to() && this.from() > this.to()) {
      this.error.set('The start date cannot be after the end date.');
      return;
    }

    this.loading.set(true);
    this.error.set('');

    const filters: ReportFilters = {
      from: this.from() || undefined,
      to: this.to() || undefined,
    };

    forkJoin({
      payments: this.reportService.getPaymentsReport(filters),
      invoices: this.reportService.getInvoicesReport(filters),
      outstanding: this.reportService.getOutstandingReport(filters),
    }).subscribe({
      next: (response) => {
        this.payments.set(response.payments.data);
        this.invoices.set(response.invoices.data);
        this.outstanding.set(response.outstanding.data);
        this.loading.set(false);
      },
      error: (err) => {
        this.error.set(err.error?.message || 'Unable to load reports.');
        this.loading.set(false);
      },
    });
  }

  resetFilters(): void {
    this.from.set('');
    this.to.set('');
    this.loadReports();
  }

  changePage(page: number): void {
    const current = this.outstanding();

    if (!current || page < 1 || page > current.last_page) {
      return;
    }

    this.loading.set(true);

    const filters: ReportFilters = {
      from: this.from() || undefined,
      to: this.to() || undefined,
    };

    this.reportService.getOutstandingReport(filters, page).subscribe({
      next: (response) => {
        this.outstanding.set(response.data);
        this.loading.set(false);
      },
      error: (err) => {
        this.error.set(
          err.error?.message || 'Unable to load outstanding invoices.',
        );
        this.loading.set(false);
      },
    });
  }

  formatAmount(value: number | string | null | undefined): string {
    return Number(value ?? 0).toLocaleString('en-US', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
  }

  getRemaining(invoice: OutstandingInvoice): number {
    return Math.max(
      0,
      Number(invoice.total_amount) - Number(invoice.paid_amount),
    );
  }

  paymentMethodLabel(method: string): string {
    const labels: Record<string, string> = {
      cash: 'Cash',
      instapay: 'Instapay',
      bank_transfer: 'Bank Transfer',
    };

    return labels[method] ?? method;
  }

  statusLabel(status: string): string {
    return status.replaceAll('_', ' ');
  }

  trackByMethod(_index: number, item: PaymentMethodReport): string {
    return item.payment_method;
  }

  trackByMonth(_index: number, item: PaymentMonthReport): string {
    return item.month;
  }

  trackByInvoice(_index: number, item: OutstandingInvoice): number {
    return item.id;
  }
}
