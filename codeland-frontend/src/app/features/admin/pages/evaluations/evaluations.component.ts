import { Component, computed, inject, signal } from '@angular/core';
import { DatePipe } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { TranslatePipe } from '@ngx-translate/core';
import { EMPTY, expand, reduce } from 'rxjs';

import { EvaluationService } from '../../services/evaluation.service';

import {
  CourseEvaluation,
  EvaluationStatistics,
  EvaluationFilters,
} from '../../models/evaluation.model';

import {
  SelectComponent,
  SelectOption,
} from '../../../../shared/components/select.component';

@Component({
  selector: 'app-evaluations',
  standalone: true,
  imports: [DatePipe, FormsModule, TranslatePipe, SelectComponent],
  templateUrl: './evaluations.component.html',
  styleUrl: './evaluations.component.scss',
})
export class EvaluationsComponent {
  private readonly service = inject(EvaluationService);

  private readonly pageSize = 15;
  private readonly apiPageSize = 100;

  readonly evaluations = signal<CourseEvaluation[]>([]);
  readonly statistics = signal<EvaluationStatistics | null>(null);

  readonly loading = signal(true);
  readonly error = signal<string | null>(null);

  readonly searchTerm = signal('');
  readonly ratingFilter = signal('all');

  readonly sortBy = signal<
    'newest' | 'oldest' | 'student' | 'course_rating' | 'instructor_rating'
  >('newest');

  readonly currentPage = signal(1);

  readonly selectedTab = signal<'courses' | 'instructors'>('courses');

  readonly ratingOptions: SelectOption[] = [
    { value: 'all', label: 'admin.evaluations.all_ratings' },
    ...[5, 4, 3, 2, 1].map((r) => ({
      value: String(r),
      label: `${r} ★`,
    })),
  ];

  readonly sortOptions: SelectOption[] = [
    { value: 'newest', label: 'admin.evaluations.sort.newest' },
    { value: 'oldest', label: 'admin.evaluations.sort.oldest' },
    { value: 'student', label: 'admin.evaluations.sort.student' },
    { value: 'course_rating', label: 'admin.evaluations.sort.course_rating' },
    {
      value: 'instructor_rating',
      label: 'admin.evaluations.sort.instructor_rating',
    },
  ];

  readonly totalEvaluations = computed(
    () => this.filteredEvaluations().length,
  );

  readonly lastPage = computed(() =>
    Math.max(1, Math.ceil(this.totalEvaluations() / this.pageSize)),
  );

  readonly filteredEvaluations = computed(() => {
    const search = this.searchTerm().trim().toLowerCase();
    const rating = this.ratingFilter();
    const sort = this.sortBy();
    const tab = this.selectedTab();

    const result = this.evaluations().filter((evaluation) => {
      const matchesSearch =
        !search ||
        evaluation.student.name.toLowerCase().includes(search) ||
        evaluation.course.title.toLowerCase().includes(search) ||
        evaluation.instructor.name.toLowerCase().includes(search) ||
        (evaluation.course_comment ?? '').toLowerCase().includes(search) ||
        (evaluation.instructor_comment ?? '').toLowerCase().includes(search);

      const currentRating =
        tab === 'courses'
          ? evaluation.course_rating
          : evaluation.instructor_rating;

      const matchesRating =
        rating === 'all' || currentRating === Number(rating);

      return matchesSearch && matchesRating;
    });

    return [...result].sort((a, b) => {
      switch (sort) {
        case 'oldest':
          return (
            new Date(a.created_at).getTime() -
            new Date(b.created_at).getTime()
          );

        case 'student':
          return a.student.name.localeCompare(b.student.name);

        case 'course_rating':
          return b.course_rating - a.course_rating;

        case 'instructor_rating':
          return b.instructor_rating - a.instructor_rating;

        case 'newest':
        default:
          return (
            new Date(b.created_at).getTime() -
            new Date(a.created_at).getTime()
          );
      }
    });
  });

  readonly paginatedEvaluations = computed(() => {
    const start = (this.currentPage() - 1) * this.pageSize;

    return this.filteredEvaluations().slice(
      start,
      start + this.pageSize,
    );
  });

  constructor() {
    this.loadStatistics();
    this.loadEvaluations();
  }

  loadStatistics(): void {
    this.service.getStatistics().subscribe({
      next: (response) => {
        this.statistics.set(response.data.statistics);
      },
      error: () => {
        this.statistics.set(null);
      },
    });
  }

  loadEvaluations(): void {
    this.loading.set(true);
    this.error.set(null);

    this.service
      .getEvaluations({
        page: 1,
        per_page: this.apiPageSize,
      })
      .pipe(
        expand((response) => {
          const pagination = response.data.evaluations;

          return pagination.current_page < pagination.last_page
            ? this.service.getEvaluations({
                page: pagination.current_page + 1,
                per_page: this.apiPageSize,
              })
            : EMPTY;
        }),
        reduce(
          (all, response) => [
            ...all,
            ...response.data.evaluations.data,
          ],
          [] as CourseEvaluation[],
        ),
      )
      .subscribe({
        next: (evaluations) => {
          this.evaluations.set(evaluations);
          this.currentPage.set(1);
          this.loading.set(false);
        },
        error: () => {
          this.error.set('admin.evaluations.load_error');
          this.loading.set(false);
        },
      });
  }

  loadInstructorFeedback(): void {
    this.loading.set(true);
    this.error.set(null);

    this.service
      .getInstructorFeedback({
        page: 1,
        per_page: this.apiPageSize,
      })
      .pipe(
        expand((response) => {
          const pagination = response.data.feedback;

          return pagination.current_page < pagination.last_page
            ? this.service.getInstructorFeedback({
                page: pagination.current_page + 1,
                per_page: this.apiPageSize,
              })
            : EMPTY;
        }),
        reduce(
          (all, response) => [
            ...all,
            ...response.data.feedback.data,
          ],
          [] as CourseEvaluation[],
        ),
      )
      .subscribe({
        next: (feedback) => {
          this.evaluations.set(feedback);
          this.currentPage.set(1);
          this.loading.set(false);
        },
        error: () => {
          this.error.set('admin.evaluations.load_error');
          this.loading.set(false);
        },
      });
  }

  setTab(tab: 'courses' | 'instructors'): void {
    if (this.selectedTab() === tab) return;

    this.selectedTab.set(tab);
    this.searchTerm.set('');
    this.ratingFilter.set('all');
    this.sortBy.set('newest');
    this.currentPage.set(1);

    if (tab === 'instructors') {
      this.loadInstructorFeedback();
    } else {
      this.loadEvaluations();
    }
  }

  onSearchChange(value: string): void {
    this.searchTerm.set(value);
    this.currentPage.set(1);
  }

  onRatingChange(value: string | number): void {
    this.ratingFilter.set(String(value));
    this.currentPage.set(1);
  }

  onSortChange(value: string | number): void {
    this.sortBy.set(
      value as
        | 'newest'
        | 'oldest'
        | 'student'
        | 'course_rating'
        | 'instructor_rating',
    );

    this.currentPage.set(1);
  }

  resetFilters(): void {
    this.searchTerm.set('');
    this.ratingFilter.set('all');
    this.sortBy.set('newest');
    this.currentPage.set(1);
  }

  changePage(page: number): void {
    if (page < 1 || page > this.lastPage()) return;

    this.currentPage.set(page);
  }

  getRatingStars(rating: number): string {
    return '★'.repeat(rating) + '☆'.repeat(5 - rating);
  }

  getRatingPercentage(
    distribution: { rating: number; total: number }[],
    rating: number,
  ): number {
    const item = distribution.find((entry) => entry.rating === rating);
    const total = this.statistics()?.total_evaluations ?? 0;

    return total > 0 ? ((item?.total ?? 0) / total) * 100 : 0;
  }

  getRatingCount(
    distribution: { rating: number; total: number }[],
    rating: number,
  ): number {
    return distribution.find((item) => item.rating === rating)?.total ?? 0;
  }
}
