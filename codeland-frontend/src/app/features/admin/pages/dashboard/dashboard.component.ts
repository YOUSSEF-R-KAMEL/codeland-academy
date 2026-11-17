import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { TranslatePipe } from '@ngx-translate/core';

import {
  AdminDashboardService,
  DashboardData,
} from '../../../../core/services/admin-dashboard.service';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [TranslatePipe, RouterLink],
  templateUrl: './dashboard.component.html',
  styleUrl: './dashboard.component.scss',
})
export class DashboardComponent implements OnInit {
  private readonly dashboardService = inject(AdminDashboardService);

  readonly isLoading = signal(true);
  readonly dashboardData = signal<DashboardData | null>(null);

  readonly hasPartialErrors = computed(() => {
    const data = this.dashboardData();

    return (
      !!data &&
      (data.studentsCount === null ||
        data.instructorsCount === null ||
        data.coursesCount === null ||
        data.financial === null)
    );
  });

  readonly statistics = computed(() => {
    const data = this.dashboardData();

    return [
      {
        key: 'dashboard.total_students',
        value: this.formatCount(data?.studentsCount),
        icon: 'students',
      },
      {
        key: 'dashboard.instructors',
        value: this.formatCount(data?.instructorsCount),
        icon: 'instructors',
      },
      {
        key: 'nav.courses',
        value: this.formatCount(data?.coursesCount),
        icon: 'courses',
      },
      {
        key: 'nav.payments',
        value: data?.financial
          ? this.formatCurrency(data.financial.paid_amount)
          : '—',
        icon: 'payments',
      },
    ];
  });

  ngOnInit(): void {
    this.loadDashboard();
  }

  loadDashboard(): void {
    this.isLoading.set(true);

    this.dashboardService.getDashboardData().subscribe({
      next: (data) => {
        this.dashboardData.set(data);
        this.isLoading.set(false);
      },
      error: () => {
        this.dashboardData.set(null);
        this.isLoading.set(false);
      },
    });
  }

  formatCount(value: number | null | undefined): string {
    if (value === null || value === undefined) {
      return '—';
    }

    return new Intl.NumberFormat('en-US').format(value);
  }

  formatCurrency(value: string | number): string {
    return new Intl.NumberFormat('en-EG', {
      style: 'currency',
      currency: 'EGP',
      maximumFractionDigits: 0,
    }).format(Number(value));
  }
}
