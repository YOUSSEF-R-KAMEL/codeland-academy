import { Component, inject, signal, OnDestroy } from '@angular/core';
import { DatePipe } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { TranslatePipe } from '@ngx-translate/core';
import {
  Subject,
  debounceTime,
  distinctUntilChanged,
  switchMap,
  takeUntil,
} from 'rxjs';

import {
  StudentFeedback,
  StudentFeedbackType,
  StudentFeedbackStatus,
  PaginatedStudentFeedback,
} from '../../models/student-feedback.model';

import {
  SelectComponent,
  SelectOption,
} from '../../../../shared/components/select.component';

import { StudentFeedbackService } from '../../services/student-feedback.service';

@Component({
  selector: 'app-admin-student-feedback',
  standalone: true,
  imports: [DatePipe, FormsModule, TranslatePipe, SelectComponent],
  templateUrl: './student-feedback.component.html',
  styleUrl: './student-feedback.component.scss',
})
export class StudentFeedbackComponent implements OnDestroy {
  private readonly service = inject(StudentFeedbackService);
  private readonly destroy$ = new Subject<void>();
  private readonly searchSubject = new Subject<string>();

  readonly feedbackItems = signal<StudentFeedback[]>([]);
  readonly pagination = signal<PaginatedStudentFeedback | null>(null);

  readonly loading = signal(true);
  readonly error = signal<string | null>(null);
  readonly actionError = signal<string | null>(null);

  readonly searchTerm = signal('');
  readonly typeFilter = signal('all');
  readonly statusFilter = signal('all');
  readonly currentPage = signal(1);

  readonly selectedFeedback = signal<StudentFeedback | null>(null);

  readonly replyText = signal('');
  readonly isReplying = signal(false);
  readonly isUpdatingStatus = signal(false);

  readonly typeOptions: SelectOption[] = [
    { value: 'all', label: 'admin.student_feedback.all_types' },
    { value: 'suggestion', label: 'admin.student_feedback.types.suggestion' },
    { value: 'complaint', label: 'admin.student_feedback.types.complaint' },
    { value: 'inquiry', label: 'admin.student_feedback.types.inquiry' },
  ];

  readonly statusOptions: SelectOption[] = [
    { value: 'all', label: 'admin.student_feedback.all_statuses' },
    { value: 'new', label: 'admin.student_feedback.statuses.new' },
    { value: 'in_review', label: 'admin.student_feedback.statuses.in_review' },
    { value: 'resolved', label: 'admin.student_feedback.statuses.resolved' },
    { value: 'rejected', label: 'admin.student_feedback.statuses.rejected' },
  ];

  constructor() {
    this.searchSubject
      .pipe(
        debounceTime(400),
        distinctUntilChanged(),
        switchMap(() => this.fetchFeedback()),
        takeUntil(this.destroy$),
      )
      .subscribe({
        next: (response) => this.handleFeedbackResponse(response),
        error: () => {
          this.error.set('admin.student_feedback.load_error');
          this.loading.set(false);
        },
      });

    this.loadFeedback();
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  private fetchFeedback() {
    return this.service.getAllFeedback({
      page: this.currentPage(),
      per_page: 15,
      search: this.searchTerm().trim() || undefined,
      type:
        this.typeFilter() === 'all'
          ? undefined
          : (this.typeFilter() as StudentFeedbackType),
      status:
        this.statusFilter() === 'all'
          ? undefined
          : (this.statusFilter() as StudentFeedbackStatus),
    });
  }

  private handleFeedbackResponse(response: {
    data: PaginatedStudentFeedback;
  }): void {
    this.feedbackItems.set(response.data.data);
    this.pagination.set(response.data);
    this.loading.set(false);
    this.error.set(null);
  }

  loadFeedback(): void {
    this.loading.set(true);
    this.error.set(null);

    this.fetchFeedback()
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (response) => this.handleFeedbackResponse(response),
        error: () => {
          this.error.set('admin.student_feedback.load_error');
          this.loading.set(false);
        },
      });
  }

  onSearchChange(value: string): void {
    this.searchTerm.set(value);
    this.currentPage.set(1);
    this.loading.set(true);
    this.error.set(null);
    this.searchSubject.next(value);
  }

  onTypeChange(value: string | number): void {
    this.typeFilter.set(String(value));
    this.currentPage.set(1);
    this.loadFeedback();
  }

  onStatusChange(value: string | number): void {
    this.statusFilter.set(String(value));
    this.currentPage.set(1);
    this.loadFeedback();
  }

  resetFilters(): void {
    this.searchTerm.set('');
    this.typeFilter.set('all');
    this.statusFilter.set('all');
    this.currentPage.set(1);
    this.loadFeedback();
  }

  changePage(page: number): void {
    const lastPage = this.pagination()?.last_page ?? 1;

    if (page < 1 || page > lastPage) return;

    this.currentPage.set(page);
    this.loadFeedback();
  }

  openDetails(feedback: StudentFeedback): void {
    this.selectedFeedback.set(feedback);
    this.replyText.set('');
    this.actionError.set(null);
  }

  closeDetails(): void {
    this.selectedFeedback.set(null);
    this.replyText.set('');
    this.actionError.set(null);
  }

  updateStatus(value: string | number): void {
    const feedback = this.selectedFeedback();

    if (!feedback || this.isUpdatingStatus()) return;

    const status = String(value) as StudentFeedbackStatus;

    if (feedback.status === status) return;

    this.isUpdatingStatus.set(true);
    this.actionError.set(null);

    this.service.updateFeedbackStatus(feedback.id, { status }).subscribe({
      next: (response) => {
        this.selectedFeedback.set(response.data);
        this.updateFeedbackInList(response.data);
        this.isUpdatingStatus.set(false);
      },
      error: () => {
        this.actionError.set('admin.student_feedback.status_update_error');
        this.isUpdatingStatus.set(false);
      },
    });
  }

  submitReply(): void {
    const feedback = this.selectedFeedback();
    const adminReply = this.replyText().trim();

    if (!feedback || !adminReply || feedback.admin_reply || this.isReplying()) {
      return;
    }

    this.isReplying.set(true);
    this.actionError.set(null);

    this.service
      .replyToFeedback(feedback.id, { admin_reply: adminReply })
      .subscribe({
        next: (response) => {
          this.selectedFeedback.set(response.data);
          this.updateFeedbackInList(response.data);
          this.replyText.set('');
          this.isReplying.set(false);
        },
        error: (error) => {
          this.actionError.set(
            error.status === 409
              ? 'admin.student_feedback.reply_already_sent'
              : 'admin.student_feedback.reply_error',
          );
          this.isReplying.set(false);
        },
      });
  }

  private updateFeedbackInList(feedback: StudentFeedback): void {
    this.feedbackItems.update((items) =>
      items.map((item) =>
        item.id === feedback.id
          ? {
              ...item,
              ...feedback,
              student: feedback.student ?? item.student,
              admin: feedback.admin ?? item.admin,
            }
          : item,
      ),
    );
  }
}
