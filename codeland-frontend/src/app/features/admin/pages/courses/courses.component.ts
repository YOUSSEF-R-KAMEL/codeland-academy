import {
  Component,
  OnInit,
  DestroyRef,
  computed,
  inject,
  signal,
} from '@angular/core';
import { HttpErrorResponse } from '@angular/common/http';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { TranslatePipe } from '@ngx-translate/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { CoursesService } from '../../services/courses.service';
import { ToastService } from '../../../../core/services/toast.service';
import { Course } from '../../models/course.model';

@Component({
  selector: 'app-courses',
  standalone: true,
  imports: [TranslatePipe, ReactiveFormsModule],
  templateUrl: './courses.component.html',
  styleUrl: './courses.component.scss',
})
export class CoursesComponent implements OnInit {
  private readonly fb = inject(FormBuilder);
  private readonly coursesService = inject(CoursesService);
  private readonly toastService = inject(ToastService);
  private readonly destroyRef = inject(DestroyRef);

  readonly courses = signal<Course[]>([]);
  readonly loading = signal(false);
  readonly saving = signal(false);
  readonly isModalOpen = signal(false);
  readonly selectedCourseId = signal<number | null>(null);
  readonly searchTerm = signal('');

  readonly totalCourses = computed(() => this.courses().length);
  readonly publishedCourses = computed(
    () => this.courses().filter((course) => course.is_published).length,
  );
  readonly draftCourses = computed(
    () => this.courses().filter((course) => !course.is_published).length,
  );

  readonly filteredCourses = computed(() => {
    const search = this.searchTerm().trim().toLowerCase();

    if (!search) return this.courses();

    return this.courses().filter((course) =>
      course.title.toLowerCase().includes(search),
    );
  });

  readonly courseForm = this.fb.group({
    title: ['', [Validators.required, Validators.minLength(3)]],
    slug: ['', [Validators.required, Validators.maxLength(255)]],
    description: [''],
    thumbnail_url: ['', Validators.pattern(/^https?:\/\/.+/)],
    duration_weeks: [12, [Validators.required, Validators.min(1)]],
    sessions_per_week: [
      1,
      [Validators.required, Validators.min(1), Validators.max(7)],
    ],
    session_duration_minutes: [120, [Validators.required, Validators.min(15)]],
    full_price: [0, [Validators.required, Validators.min(0)]],
    monthly_price: [null as number | null, [Validators.min(0)]],
    currency: ['EGP' as const],
    is_published: [false],
    sort_order: [0],
  });

  ngOnInit(): void {
    this.loadCourses();
  }

  loadCourses(): void {
    this.loading.set(true);

    this.coursesService
      .getAdminCourses()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.courses.set(response.data.courses);
          this.loading.set(false);
        },
        error: (error: HttpErrorResponse) => {
          this.loading.set(false);
          this.toastService.error(
            error.error?.message || 'Something went wrong.',
          );
        },
      });
  }

  onSearchChange(value: string): void {
    this.searchTerm.set(value);
  }

  private buildCoursePayload() {
    const value = this.courseForm.getRawValue();

    return {
      title: value.title!.trim(),
      slug: value.slug!.trim(),
      description: value.description?.trim() || null,
      thumbnail_url: value.thumbnail_url?.trim() || null,
      duration_weeks: Number(value.duration_weeks),
      sessions_per_week: Number(value.sessions_per_week),
      session_duration_minutes: Number(value.session_duration_minutes),
      full_price: Number(value.full_price),
      monthly_price:
        value.monthly_price === null ? null : Number(value.monthly_price),
      currency: 'EGP' as const,
      is_published: Boolean(value.is_published),
      sort_order: Number(value.sort_order),
    };
  }

  saveCourse(): void {
    if (this.saving()) return;
    if (this.courseForm.invalid) {
      this.courseForm.markAllAsTouched();
      return;
    }

    this.saving.set(true);

    const payload = this.buildCoursePayload();
    const courseId = this.selectedCourseId();

    const request = courseId
      ? this.coursesService.updateCourse(courseId, payload)
      : this.coursesService.createCourse(payload);

    request.pipe(takeUntilDestroyed(this.destroyRef)).subscribe({
      next: (response) => {
        this.toastService.success(response.message);
        this.isModalOpen.set(false);
        this.courseForm.reset({
          title: '',
          slug: '',
          description: '',
          thumbnail_url: '',
          duration_weeks: 12,
          sessions_per_week: 1,
          session_duration_minutes: 120,
          full_price: 0,
          monthly_price: null,
          currency: 'EGP',
          is_published: false,
          sort_order: 0,
        });

        this.selectedCourseId.set(null);
        this.loadCourses();
        this.saving.set(false);
      },
      error: (error) => {
        this.saving.set(false);
        this.toastService.error(
          error.error?.message || 'حدث خطأ أثناء حفظ الكورس',
        );
      },
    });
  }

  openAddModal(): void {
    this.selectedCourseId.set(null);

    this.courseForm.reset({
      title: '',
      slug: '',
      description: '',
      thumbnail_url: '',
      duration_weeks: 12,
      sessions_per_week: 1,
      session_duration_minutes: 120,
      full_price: 0,
      monthly_price: null,
      currency: 'EGP',
      is_published: false,
      sort_order: 0,
    });

    this.isModalOpen.set(true);
  }

  closeModal(): void {
    this.isModalOpen.set(false);
    this.selectedCourseId.set(null);
    this.courseForm.reset({
      title: '',
      slug: '',
      description: '',
      thumbnail_url: '',
      duration_weeks: 12,
      sessions_per_week: 1,
      session_duration_minutes: 120,
      full_price: 0,
      monthly_price: null,
      currency: 'EGP',
      is_published: false,
      sort_order: 0,
    });
  }

  openEditModal(course: Course): void {
    this.selectedCourseId.set(course.id);

    this.courseForm.patchValue({
      title: course.title,
      slug: course.slug,
      description: course.description ?? '',
      thumbnail_url: course.thumbnail_url ?? '',
      duration_weeks: course.duration_weeks,
      sessions_per_week: course.sessions_per_week,
      session_duration_minutes: course.session_duration_minutes,
      full_price: course.full_price,
      monthly_price: course.monthly_price,
      currency: 'EGP',
      is_published: course.is_published,
      sort_order: course.sort_order,
    });

    this.isModalOpen.set(true);
  }

  deleteCourse(course: Course): void {
    if (!confirm(`هل أنت متأكد من حذف الكورس "${course.title}"؟`)) {
      return;
    }

    this.coursesService
      .deleteCourse(course.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.toastService.success(response.message);
          this.loadCourses();
        },
        error: (error) => {
          this.toastService.error(
            error.error?.message || 'حدث خطأ أثناء حذف الكورس',
          );
        },
      });
  }

  toggleCourseStatus(course: Course): void {
    this.coursesService
      .updateCourse(course.id, {
        is_published: !course.is_published,
      })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.toastService.success(response.message);
          this.loadCourses();
        },
        error: (error) => {
          this.toastService.error(
            error.error?.message || 'حدث خطأ أثناء تحديث حالة الكورس',
          );
        },
      });
  }
}
