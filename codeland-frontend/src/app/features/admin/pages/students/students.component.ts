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
import { Subject, debounceTime, distinctUntilChanged } from 'rxjs';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';

import { Course } from '../../models/course.model';
import { Group } from '../../models/group.model';
import {
  Student,
  StudentsFilters,
  StudentPagination,
  CreateStudentRequest,
  UpdateStudentRequest,
} from '../../models/student.model';

import { CoursesService } from '../../services/courses.service';
import { GroupsService } from '../../services/groups.service';
import { StudentsService } from '../../services/students.service';
import { EnrollmentsService } from '../../services/enrollments.service';
import { ToastService } from '../../../../core/services/toast.service';

@Component({
  selector: 'app-students',
  standalone: true,
  imports: [TranslatePipe, ReactiveFormsModule],
  templateUrl: './students.component.html',
  styleUrl: './students.component.scss',
})
export class StudentsComponent implements OnInit {
  private readonly fb = inject(FormBuilder);
  private readonly studentsService = inject(StudentsService);
  private readonly coursesService = inject(CoursesService);
  private readonly groupsService = inject(GroupsService);
  private readonly enrollmentsService = inject(EnrollmentsService);
  private readonly toastService = inject(ToastService);
  private readonly destroyRef = inject(DestroyRef);

  private readonly searchSubject = new Subject<string>();

  readonly students = signal<Student[]>([]);
  readonly loading = signal(false);
  readonly saving = signal(false);

  readonly isAddModalOpen = signal(false);
  readonly selectedStudentId = signal<number | null>(null);
  readonly processingStudentId = signal<number | null>(null);

  readonly searchTerm = signal('');
  readonly selectedStatus = signal('');
  readonly selectedGroup = signal('');
  readonly currentPage = signal(1);
  readonly showPassword = signal(false);

  readonly courses = signal<Course[]>([]);
  readonly availableGroups = signal<Group[]>([]);
  readonly loadingGroups = signal(false);

  readonly pagination = signal<StudentPagination>({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
  });

  readonly totalStudents = computed(() => this.pagination().total);

  readonly activeStudents = computed(
    () => this.students().filter((student) => student.is_active).length,
  );

  readonly inactiveStudents = computed(
    () => this.students().filter((student) => !student.is_active).length,
  );

  readonly groups = computed(() => {
    const groupNames = this.students()
      .map((student) => student.enrollment?.group?.name)
      .filter((name): name is string => !!name);

    return [...new Set(groupNames)];
  });

  readonly filteredStudents = computed(() => {
    const group = this.selectedGroup();

    if (!group) {
      return this.students();
    }

    return this.students().filter(
      (student) => student.enrollment?.group?.name === group,
    );
  });

  readonly studentForm = this.fb.group({
    name: ['', [Validators.required, Validators.minLength(3)]],
    email: ['', [Validators.required, Validators.email]],
    password: ['', [Validators.minLength(8)]],
    course_id: [null as number | null],
    group_id: [null as number | null],
    payment_plan: ['monthly' as 'full' | 'monthly'],
  });

  ngOnInit(): void {
    this.searchSubject
      .pipe(
        debounceTime(350),
        distinctUntilChanged(),
        takeUntilDestroyed(this.destroyRef),
      )
      .subscribe((search) => {
        this.searchTerm.set(search);
        this.loadStudents(1);
      });

    this.loadCourses();
    this.loadStudents(1);
  }

  private loadCourses(): void {
    this.coursesService
      .getAdminCourses()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.courses.set(response.data.courses);
        },
        error: (error: HttpErrorResponse) => {
          this.toastService.error(this.getErrorMessage(error));
        },
      });
  }

  onCourseChange(
    courseId: number | null,
    selectedGroupId: number | null = null,
  ): void {
    this.studentForm.patchValue({ group_id: null });
    this.availableGroups.set([]);

    if (!courseId) {
      this.loadingGroups.set(false);
      return;
    }

    this.loadingGroups.set(true);

    this.groupsService
      .getGroups(courseId)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.availableGroups.set(response.data);

          if (
            selectedGroupId &&
            response.data.some((group) => group.id === selectedGroupId)
          ) {
            this.studentForm.patchValue({
              group_id: selectedGroupId,
            });
          }

          this.loadingGroups.set(false);
        },
        error: (error: HttpErrorResponse) => {
          this.loadingGroups.set(false);
          this.toastService.error(this.getErrorMessage(error));
        },
      });
  }

  loadStudents(page = this.currentPage()): void {
    this.loading.set(true);

    const filters: StudentsFilters = {
      page,
      per_page: 10,
    };

    const search = this.searchTerm().trim();

    if (search) {
      filters.search = search;
    }

    if (this.selectedStatus() === 'active') {
      filters.is_active = true;
    } else if (this.selectedStatus() === 'inactive') {
      filters.is_active = false;
    }

    this.studentsService
      .getStudents(filters)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.students.set(response.data.users);
          this.pagination.set(response.data.pagination);
          this.currentPage.set(response.data.pagination.current_page);
          this.loading.set(false);
        },
        error: (error: HttpErrorResponse) => {
          this.loading.set(false);
          this.toastService.error(this.getErrorMessage(error));
        },
      });
  }

  onSearchChange(value: string): void {
    this.searchSubject.next(value);
  }

  onStatusChange(status: string): void {
    this.selectedStatus.set(status);
    this.currentPage.set(1);
    this.loadStudents(1);
  }

  onGroupChange(value: string): void {
    this.selectedGroup.set(value);
  }

  goToPage(page: number): void {
    if (
      page < 1 ||
      page > this.pagination().last_page ||
      page === this.currentPage()
    ) {
      return;
    }

    this.loadStudents(page);
  }

  openAddModal(): void {
    this.selectedStudentId.set(null);
    this.availableGroups.set([]);
    this.loadingGroups.set(false);
    this.showPassword.set(false);

    this.studentForm.reset({
      name: '',
      email: '',
      password: '',
      course_id: null,
      group_id: null,
      payment_plan: 'monthly',
    });

    this.studentForm.controls.name.setValidators([
      Validators.required,
      Validators.minLength(3),
    ]);

    this.studentForm.controls.email.setValidators([
      Validators.required,
      Validators.email,
    ]);

    this.studentForm.controls.password.setValidators([
      Validators.required,
      Validators.minLength(8),
    ]);

    this.studentForm.controls.name.updateValueAndValidity();
    this.studentForm.controls.email.updateValueAndValidity();
    this.studentForm.controls.password.updateValueAndValidity();

    this.isAddModalOpen.set(true);
  }

  openEditModal(student: Student): void {
    this.selectedStudentId.set(student.id);
    this.availableGroups.set([]);
    this.loadingGroups.set(false);
    this.showPassword.set(false);

    const currentGroup = student.enrollment?.group;
    const courseId = currentGroup?.course_id ?? null;
    const groupId = currentGroup?.id ?? null;

    this.studentForm.reset({
      name: student.name,
      email: student.email ?? '',
      password: '',
      course_id: courseId,
      group_id: null,
      payment_plan: 'monthly',
    });

    this.studentForm.controls.name.setValidators([
      Validators.required,
      Validators.minLength(3),
    ]);

    this.studentForm.controls.email.setValidators([
      Validators.required,
      Validators.email,
    ]);

    this.studentForm.controls.password.setValidators([Validators.minLength(8)]);

    this.studentForm.controls.name.updateValueAndValidity();
    this.studentForm.controls.email.updateValueAndValidity();
    this.studentForm.controls.password.updateValueAndValidity();

    this.isAddModalOpen.set(true);

    if (courseId) {
      this.onCourseChange(courseId, groupId);
    }
  }

  closeAddModal(): void {
    if (this.saving()) {
      return;
    }

    this.isAddModalOpen.set(false);
    this.selectedStudentId.set(null);
    this.availableGroups.set([]);
    this.loadingGroups.set(false);
    this.showPassword.set(false);
    this.studentForm.reset();
  }

  saveStudent(): void {
    if (this.studentForm.invalid || this.saving()) {
      this.studentForm.markAllAsTouched();

      Object.entries(this.studentForm.controls).forEach(([key, control]) => {
        if (control.invalid) {
          console.log(key, control.errors);
        }
      });

      return;
    }

    const studentId = this.selectedStudentId();

    if (studentId !== null) {
      this.updateStudent(studentId);
    } else {
      this.createStudent();
    }
  }

  private createStudent(): void {
    const value = this.studentForm.getRawValue();

    if (!value.name?.trim() || !value.email?.trim() || !value.password) {
      return;
    }

    const payload: CreateStudentRequest = {
      name: value.name.trim(),
      role: 'student',
      email: value.email.trim(),
      password: value.password,
    };

    this.saving.set(true);

    this.studentsService
      .createStudent(payload)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          const studentId = response.data.user.id;
          const groupId = value.group_id;

          if (!groupId) {
            this.finishStudentCreation();
            return;
          }

          this.enrollmentsService
            .createEnrollment(groupId, {
              student_id: studentId,
              payment_plan: value.payment_plan as 'full' | 'monthly',
            })
            .pipe(takeUntilDestroyed(this.destroyRef))
            .subscribe({
              next: () => {
                this.finishStudentCreation();
              },
              error: (error: HttpErrorResponse) => {
                this.saving.set(false);
                this.isAddModalOpen.set(false);
                this.selectedStudentId.set(null);

                this.toastService.error(
                  `Student account created, but enrollment failed: ${this.getErrorMessage(error)}`,
                );

                this.loadStudents(1);
              },
            });
        },
        error: (error: HttpErrorResponse) => {
          this.saving.set(false);
          this.toastService.error(this.getErrorMessage(error));
        },
      });
  }

  private finishStudentCreation(): void {
    this.saving.set(false);
    this.isAddModalOpen.set(false);
    this.selectedStudentId.set(null);

    this.toastService.success('students.created_successfully');

    this.loadStudents(1);
  }

  private updateStudent(studentId: number): void {
    const value = this.studentForm.getRawValue();

    if (!value.name?.trim() || !value.email?.trim()) {
      return;
    }

    const payload: UpdateStudentRequest = {
      name: value.name.trim(),
      email: value.email.trim(),
    };

    if (value.password) {
      payload.password = value.password;
    }

    this.saving.set(true);

    this.studentsService
      .updateStudent(studentId, payload)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => {
          this.saving.set(false);
          this.isAddModalOpen.set(false);
          this.selectedStudentId.set(null);

          this.toastService.success('students.updated_successfully');

          this.loadStudents(this.currentPage());
        },
        error: (error: HttpErrorResponse) => {
          this.saving.set(false);
          this.toastService.error(this.getErrorMessage(error));
        },
      });
  }

  toggleStudentStatus(student: Student): void {
    if (this.processingStudentId() !== null) {
      return;
    }

    const newStatus = !student.is_active;

    this.processingStudentId.set(student.id);

    this.studentsService
      .updateStudentStatus(student.id, newStatus)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => {
          this.processingStudentId.set(null);

          this.toastService.success(
            newStatus
              ? 'students.activated_successfully'
              : 'students.deactivated_successfully',
          );

          this.loadStudents(this.currentPage());
        },
        error: (error: HttpErrorResponse) => {
          this.processingStudentId.set(null);
          this.toastService.error(this.getErrorMessage(error));
        },
      });
  }

  private getErrorMessage(error: HttpErrorResponse): string {
    const response = error.error;

    if (response?.errors) {
      const firstError = Object.values(
        response.errors as Record<string, string[]>,
      )[0];

      if (firstError?.length) {
        return firstError[0];
      }
    }

    return response?.message || 'Something went wrong. Please try again.';
  }

  togglePasswordVisibility(): void {
    this.showPassword.update((value) => !value);
  }
}
