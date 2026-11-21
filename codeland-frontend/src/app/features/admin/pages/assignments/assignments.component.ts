import { Component, computed, inject, signal } from '@angular/core';
import { DatePipe } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { TranslatePipe } from '@ngx-translate/core';
import Swal from 'sweetalert2';

import {
  CourseOption,
  CourseLessonOption,
} from '../../models/course-lesson.model';

import {
  CreateAssignmentPayload,
  UpdateAssignmentPayload,
  AssignmentService,
} from '../../services/assignment.service';

import { Assignment } from '../../models/assignment.model';
import { SelectComponent } from '../../../../shared/components/select.component';

@Component({
  selector: 'app-assignments',
  standalone: true,
  imports: [DatePipe, FormsModule, TranslatePipe, SelectComponent],
  templateUrl: './assignments.component.html',
})
export class AssignmentsComponent {
  private readonly assignmentService = inject(AssignmentService);

  courses = signal<CourseOption[]>([]);
  lessons = signal<CourseLessonOption[]>([]);
  assignments = signal<Assignment[]>([]);

  loading = signal(true);
  loadingCourses = signal(false);
  loadingLessons = signal(false);
  savingAssignment = signal(false);
  deletingAssignmentId = signal<number | null>(null);

  error = signal<string | null>(null);
  createError = signal<string | null>(null);
  createSuccess = signal<string | null>(null);

  showCreateForm = signal(false);
  editingAssignmentId = signal<number | null>(null);

  searchTerm = signal('');
  statusFilter = signal('all');

  currentPage = signal(1);
  pageSize = signal(15);

  selectedCourseId = signal<number>(0);

  courseOptions = computed(() =>
    this.courses().map((course) => ({
      value: course.id,
      label: course.title,
    })),
  );

  lessonOptions = computed(() =>
    this.lessons().map((lesson) => ({
      value: lesson.id,
      label: `${lesson.lesson_number} - ${lesson.title}`,
    })),
  );

  statusOptions = [
    { value: 'all', label: 'admin.assignments.all_statuses' },
    { value: 'published', label: 'admin.assignments.published' },
    { value: 'draft', label: 'admin.assignments.drafts' },
  ];

  total = computed(() => this.assignments().length);

  publishedCount = computed(
    () =>
      this.assignments().filter((assignment) => assignment.is_published).length,
  );

  draftCount = computed(
    () =>
      this.assignments().filter((assignment) => !assignment.is_published)
        .length,
  );

  filteredAssignments = computed(() => {
    const search = this.searchTerm().trim().toLowerCase();
    const status = this.statusFilter();

    return this.assignments().filter((assignment) => {
      const matchesSearch =
        assignment.title.toLowerCase().includes(search) ||
        assignment.course_lesson?.title?.toLowerCase().includes(search) ||
        assignment.course_lesson?.course?.title?.toLowerCase().includes(search);

      const matchesStatus =
        status === 'all' ||
        (status === 'published' && assignment.is_published) ||
        (status === 'draft' && !assignment.is_published);

      return matchesSearch && matchesStatus;
    });
  });

  lastPage = computed(() =>
    Math.max(1, Math.ceil(this.filteredAssignments().length / this.pageSize())),
  );

  paginatedAssignments = computed(() => {
    const start = (this.currentPage() - 1) * this.pageSize();
    const end = start + this.pageSize();

    return this.filteredAssignments().slice(start, end);
  });

  newAssignment = signal({
    course_lesson_id: 0,
    title: '',
    instructions: '',
    resource_url: '',
    published_at: '',
    due_at: '',
    is_published: false,
  });

  constructor() {
    this.loadAssignments();
  }

  // ==========================================
  // Assignments List
  // ==========================================

  loadAssignments(): void {
    this.loading.set(true);
    this.error.set(null);

    this.assignmentService.getAssignments().subscribe({
      next: (response) => {
        this.assignments.set(response.data ?? []);
        this.currentPage.set(1);
        this.loading.set(false);
      },
      error: () => {
        this.error.set('admin.assignments.load_error');
        this.loading.set(false);
      },
    });
  }

  changePage(page: number): void {
    if (page < 1 || page > this.lastPage()) return;

    this.currentPage.set(page);
  }

  onSearchChange(value: string): void {
    this.searchTerm.set(value);
    this.currentPage.set(1);
  }

  onStatusFilterChange(value: string): void {
    this.statusFilter.set(value);
    this.currentPage.set(1);
  }

  // ==========================================
  // Form
  // ==========================================

  openCreateForm(): void {
    this.resetForm();
    this.showCreateForm.set(true);
    this.loadCourses();
  }

  closeCreateForm(): void {
    this.showCreateForm.set(false);
    this.createError.set(null);
    this.editingAssignmentId.set(null);
  }

  resetForm(): void {
    this.newAssignment.set({
      course_lesson_id: 0,
      title: '',
      instructions: '',
      resource_url: '',
      published_at: '',
      due_at: '',
      is_published: false,
    });

    this.selectedCourseId.set(0);
    this.lessons.set([]);
    this.editingAssignmentId.set(null);
    this.createError.set(null);
    this.createSuccess.set(null);
  }

  updateNewAssignment(
    field: keyof ReturnType<typeof this.newAssignment>,
    value: string | number | boolean,
  ): void {
    this.newAssignment.update((current) => ({
      ...current,
      [field]: value,
    }));
  }

  // ==========================================
  // Edit Assignment
  // ==========================================

  editAssignment(assignment: Assignment): void {
    const courseId = assignment.course_lesson?.course?.id;

    if (!courseId) {
      this.createError.set('admin.assignments.course_not_found');
      this.showCreateForm.set(true);
      return;
    }

    this.resetForm();
    this.editingAssignmentId.set(assignment.id);
    this.selectedCourseId.set(courseId);

    this.newAssignment.set({
      course_lesson_id: assignment.course_lesson?.id ?? 0,
      title: assignment.title ?? '',
      instructions: assignment.instructions ?? '',
      resource_url: assignment.resource_url ?? '',
      published_at: this.toDateTimeLocal(assignment.published_at),
      due_at: this.toDateTimeLocal(assignment.due_at),
      is_published: assignment.is_published,
    });

    this.showCreateForm.set(true);
    this.loadCourses();
    this.loadLessonsForEdit(courseId);
  }

  private loadLessonsForEdit(courseId: number): void {
    this.loadingLessons.set(true);

    this.assignmentService.getCourseLessons(courseId).subscribe({
      next: (response) => {
        this.lessons.set(response.data.lessons ?? []);
        this.loadingLessons.set(false);
      },
      error: () => {
        this.createError.set('admin.assignments.lessons_load_error');
        this.loadingLessons.set(false);
      },
    });
  }

  private toDateTimeLocal(value: string | null | undefined): string {
    if (!value) return '';

    const date = new Date(value);
    const offset = date.getTimezoneOffset() * 60000;

    return new Date(date.getTime() - offset).toISOString().slice(0, 16);
  }

  // ==========================================
  // Courses & Lessons
  // ==========================================

  loadCourses(): void {
    this.loadingCourses.set(true);

    this.assignmentService.getCourses().subscribe({
      next: (response) => {
        this.courses.set(response.data.courses ?? []);
        this.loadingCourses.set(false);
      },
      error: () => {
        this.createError.set('admin.assignments.courses_load_error');
        this.loadingCourses.set(false);
      },
    });
  }

  onCourseChange(courseId: number): void {
    this.selectedCourseId.set(courseId);

    this.newAssignment.update((value) => ({
      ...value,
      course_lesson_id: 0,
    }));

    this.lessons.set([]);
    this.createError.set(null);

    if (!courseId) return;

    this.loadingLessons.set(true);

    this.assignmentService.getCourseLessons(courseId).subscribe({
      next: (response) => {
        this.lessons.set(response.data.lessons ?? []);
        this.loadingLessons.set(false);
      },
      error: () => {
        this.createError.set('admin.assignments.lessons_load_error');
        this.loadingLessons.set(false);
      },
    });
  }

  // ==========================================
  // Create / Update Assignment
  // ==========================================

  saveAssignment(): void {
    const value = this.newAssignment();

    if (
      !value.course_lesson_id ||
      !value.title.trim() ||
      !value.instructions.trim()
    ) {
      this.createError.set('admin.assignments.required_fields');
      return;
    }

    if (this.savingAssignment()) return;

    const publishedAt = value.published_at
      ? new Date(value.published_at).toISOString()
      : value.is_published
        ? new Date().toISOString()
        : null;

    const payload: CreateAssignmentPayload = {
      course_lesson_id: value.course_lesson_id,
      title: value.title.trim(),
      instructions: value.instructions.trim(),
      resource_url: value.resource_url.trim() || null,
      published_at: publishedAt,
      due_at: value.due_at ? new Date(value.due_at).toISOString() : null,
      is_published: value.is_published,
    };

    this.savingAssignment.set(true);
    this.createError.set(null);
    this.createSuccess.set(null);

    const assignmentId = this.editingAssignmentId();

    const request = assignmentId
      ? this.assignmentService.updateAssignment(
          assignmentId,
          payload as UpdateAssignmentPayload,
        )
      : this.assignmentService.createAssignment(payload);

    request.subscribe({
      next: () => {
        this.savingAssignment.set(false);
        this.closeCreateForm();

        if (!assignmentId) {
          this.currentPage.set(1);
        }

        this.loadAssignments();
      },
      error: (error) => {
        this.savingAssignment.set(false);
        this.createError.set(
          error.error?.message || 'admin.assignments.save_error',
        );
      },
    });
  }

  // ==========================================
  // Delete Assignment
  // ==========================================

  deleteAssignment(assignment: Assignment): void {
    if (this.deletingAssignmentId()) return;

    Swal.fire({
      title: 'هل أنت متأكد؟',
      text: `هل تريد حذف الواجب "${assignment.title}"؟`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc2626',
      cancelButtonColor: '#6b7280',
      confirmButtonText: 'نعم، احذف الواجب',
      cancelButtonText: 'إلغاء',
      reverseButtons: true,
    }).then((result) => {
      if (!result.isConfirmed) return;

      this.deletingAssignmentId.set(assignment.id);
      this.error.set(null);

      this.assignmentService.deleteAssignment(assignment.id).subscribe({
        next: () => {
          this.deletingAssignmentId.set(null);

          this.assignments.update((assignments) =>
            assignments.filter((item) => item.id !== assignment.id),
          );

          if (this.currentPage() > this.lastPage()) {
            this.currentPage.set(this.lastPage());
          }

          Swal.fire({
            title: 'تم الحذف!',
            text: 'تم حذف الواجب بنجاح.',
            icon: 'success',
            confirmButtonColor: '#2563eb',
            confirmButtonText: 'حسنًا',
          });
        },
        error: () => {
          this.deletingAssignmentId.set(null);
          this.error.set('admin.assignments.delete_error');

          Swal.fire({
            title: 'حدث خطأ!',
            text: 'تعذر حذف الواجب، حاول مرة أخرى.',
            icon: 'error',
            confirmButtonText: 'حسنًا',
          });
        },
      });
    });
  }
}
