import {
  Component,
  OnInit,
  DestroyRef,
  computed,
  inject,
  signal,
} from '@angular/core';
import { HttpErrorResponse } from '@angular/common/http';
import {
  FormBuilder,
  FormsModule,
  ReactiveFormsModule,
  Validators,
} from '@angular/forms';
import { TranslatePipe } from '@ngx-translate/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { ToastService } from '../../../../core/services/toast.service';
import { InstructorsService } from '../../services/instructors.service';

import {
  CreateInstructorRequest,
  Instructor,
  InstructorPagination,
  UpdateInstructorRequest,
} from '../../models/instructor.model';
import { SelectComponent } from '../../../../shared/components/select.component';

type InstructorSort = 'newest' | 'oldest' | 'name_asc' | 'name_desc';

@Component({
  selector: 'app-instructors',
  standalone: true,
  imports: [TranslatePipe, ReactiveFormsModule, SelectComponent, FormsModule],
  templateUrl: './instructors.component.html',
  styleUrl: './instructors.component.scss',
})
export class InstructorsComponent implements OnInit {
  private readonly fb = inject(FormBuilder);
  private readonly instructorsService = inject(InstructorsService);
  private readonly destroyRef = inject(DestroyRef);
  private readonly toastService = inject(ToastService);

  private readonly pageSize = 10;

  readonly instructors = signal<Instructor[]>([]);
  readonly loading = signal(false);
  readonly saving = signal(false);

  readonly isModalOpen = signal(false);
  readonly selectedInstructorId = signal<number | null>(null);
  readonly processingInstructorId = signal<number | null>(null);

  readonly searchTerm = signal('');
  readonly selectedStatus = signal('');
  readonly sortBy = signal<InstructorSort>('newest');
  readonly currentPage = signal(1);

  readonly pagination = signal<InstructorPagination>({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
  });

  readonly statusOptions = [
    { value: '', label: 'admin.instructors.all_statuses' },
    { value: 'active', label: 'admin.instructors.active' },
    { value: 'inactive', label: 'admin.instructors.inactive' },
  ];

  readonly sortOptions = [
    { value: 'newest', label: 'admin.instructors.sort.newest' },
    { value: 'oldest', label: 'admin.instructors.sort.oldest' },
    { value: 'name_asc', label: 'admin.instructors.sort.name_asc' },
    { value: 'name_desc', label: 'admin.instructors.sort.name_desc' },
  ];

  readonly statusControl = this.fb.control('', { nonNullable: true });
  readonly sortControl = this.fb.control<InstructorSort>('newest', {
    nonNullable: true,
  });

  readonly filteredInstructors = computed(() => {
    const search = this.searchTerm().trim().toLowerCase();
    const status = this.selectedStatus();

    let result = this.instructors().filter((instructor) => {
      const matchesSearch =
        !search ||
        instructor.name.toLowerCase().includes(search) ||
        (instructor.email ?? '').toLowerCase().includes(search) ||
        String(instructor.id).includes(search);

      const matchesStatus =
        status === '' ||
        (status === 'active' && instructor.is_active) ||
        (status === 'inactive' && !instructor.is_active);

      return matchesSearch && matchesStatus;
    });

    result = [...result];

    switch (this.sortBy()) {
      case 'newest':
        result.sort((a, b) => b.id - a.id);
        break;

      case 'oldest':
        result.sort((a, b) => a.id - b.id);
        break;

      case 'name_asc':
        result.sort((a, b) =>
          a.name.localeCompare(b.name, undefined, { sensitivity: 'base' }),
        );
        break;

      case 'name_desc':
        result.sort((a, b) =>
          b.name.localeCompare(a.name, undefined, { sensitivity: 'base' }),
        );
        break;
    }

    return result;
  });

  readonly totalInstructors = computed(() => this.filteredInstructors().length);

  readonly activeInstructors = computed(
    () =>
      this.filteredInstructors().filter((instructor) => instructor.is_active)
        .length,
  );

  readonly inactiveInstructors = computed(
    () =>
      this.filteredInstructors().filter((instructor) => !instructor.is_active)
        .length,
  );

  readonly lastPage = computed(() =>
    Math.max(1, Math.ceil(this.totalInstructors() / this.pageSize)),
  );

  readonly paginatedInstructors = computed(() => {
    const start = (this.currentPage() - 1) * this.pageSize;
    return this.filteredInstructors().slice(start, start + this.pageSize);
  });

  readonly instructorForm = this.fb.group({
    name: ['', [Validators.required, Validators.minLength(3)]],
    email: ['', [Validators.required, Validators.email]],
    password: ['', [Validators.minLength(8)]],
    password_confirmation: [''],
  });

  ngOnInit(): void {
    this.statusControl.valueChanges
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe((status) => this.onStatusChange(status));

    this.sortControl.valueChanges
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe((value) => this.onSortChange(value));

    this.loadInstructors();
  }

  loadInstructors(): void {
    this.loading.set(true);

    this.instructorsService
      .getInstructors({ page: 1, per_page: 100 })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.instructors.set(response.data.users);
          this.currentPage.set(1);
          this.updatePagination();
          this.loading.set(false);
        },
        error: (error: HttpErrorResponse) => {
          this.loading.set(false);
          this.toastService.error(this.getErrorMessage(error));
        },
      });
  }

  onSearchChange(value: string): void {
    this.searchTerm.set(value);
    this.currentPage.set(1);
    this.updatePagination();
  }

  onStatusChange(value: string): void {
    this.selectedStatus.set(value);
    this.currentPage.set(1);
    this.updatePagination();
  }

  onSortChange(value: InstructorSort): void {
    this.sortBy.set(value);
    this.currentPage.set(1);
    this.updatePagination();
  }

  resetFilters(): void {
    this.searchTerm.set('');
    this.selectedStatus.set('');
    this.sortBy.set('newest');

    this.statusControl.setValue('', { emitEvent: false });
    this.sortControl.setValue('newest', { emitEvent: false });

    this.currentPage.set(1);
    this.updatePagination();
  }

  goToPage(page: number): void {
    if (page < 1 || page > this.lastPage()) {
      return;
    }

    this.currentPage.set(page);
    this.updatePagination();
  }

  private updatePagination(): void {
    this.pagination.set({
      current_page: this.currentPage(),
      last_page: this.lastPage(),
      per_page: this.pageSize,
      total: this.totalInstructors(),
    });
  }

  openAddModal(): void {
    this.selectedInstructorId.set(null);

    this.instructorForm.reset({
      name: '',
      email: '',
      password: '',
      password_confirmation: '',
    });

    this.instructorForm.controls.password.setValidators([
      Validators.required,
      Validators.minLength(8),
    ]);

    this.instructorForm.controls.password_confirmation.setValidators([
      Validators.required,
    ]);

    this.updatePasswordValidity();
    this.isModalOpen.set(true);
  }

  openEditModal(instructor: Instructor): void {
    this.selectedInstructorId.set(instructor.id);

    this.instructorForm.reset({
      name: instructor.name,
      email: instructor.email ?? '',
      password: '',
      password_confirmation: '',
    });

    this.instructorForm.controls.password.setValidators([
      Validators.minLength(8),
    ]);

    this.instructorForm.controls.password_confirmation.clearValidators();

    this.updatePasswordValidity();
    this.isModalOpen.set(true);
  }

  closeModal(): void {
    if (this.saving()) {
      return;
    }

    this.isModalOpen.set(false);
    this.selectedInstructorId.set(null);
    this.instructorForm.reset();
  }

  saveInstructor(): void {
    if (this.instructorForm.invalid || this.saving()) {
      this.instructorForm.markAllAsTouched();
      return;
    }

    const value = this.instructorForm.getRawValue();

    if (value.password && value.password !== value.password_confirmation) {
      this.toastService.error('instructors.password_mismatch');
      return;
    }

    const instructorId = this.selectedInstructorId();

    if (instructorId !== null) {
      this.updateInstructor(instructorId);
    } else {
      this.createInstructor();
    }
  }

  private createInstructor(): void {
    const value = this.instructorForm.getRawValue();

    if (
      !value.name?.trim() ||
      !value.email?.trim() ||
      !value.password ||
      !value.password_confirmation
    ) {
      return;
    }

    const payload: CreateInstructorRequest = {
      name: value.name.trim(),
      role: 'instructor',
      email: value.email.trim(),
      password: value.password,
      password_confirmation: value.password_confirmation,
    };

    this.saving.set(true);

    this.instructorsService
      .createInstructor(payload)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => {
          this.saving.set(false);
          this.isModalOpen.set(false);
          this.selectedInstructorId.set(null);
          this.toastService.success('instructors.created_successfully');
          this.loadInstructors();
        },
        error: (error: HttpErrorResponse) => {
          this.saving.set(false);
          this.toastService.error(this.getErrorMessage(error));
        },
      });
  }

  private updateInstructor(instructorId: number): void {
    const value = this.instructorForm.getRawValue();

    if (!value.name?.trim() || !value.email?.trim()) {
      return;
    }

    const payload: UpdateInstructorRequest = {
      name: value.name.trim(),
      email: value.email.trim(),
    };

    if (value.password) {
      payload.password = value.password;
      payload.password_confirmation = value.password;
    }

    this.saving.set(true);

    this.instructorsService
      .updateInstructor(instructorId, payload)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => {
          this.saving.set(false);
          this.isModalOpen.set(false);
          this.selectedInstructorId.set(null);
          this.toastService.success('instructors.updated_successfully');
          this.loadInstructors();
        },
        error: (error: HttpErrorResponse) => {
          this.saving.set(false);
          this.toastService.error(this.getErrorMessage(error));
        },
      });
  }

  toggleInstructorStatus(instructor: Instructor): void {
    if (this.processingInstructorId() !== null) {
      return;
    }

    const newStatus = !instructor.is_active;
    this.processingInstructorId.set(instructor.id);

    this.instructorsService
      .updateInstructorStatus(instructor.id, newStatus)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => {
          this.processingInstructorId.set(null);
          this.toastService.success(
            newStatus
              ? 'instructors.activated_successfully'
              : 'instructors.deactivated_successfully',
          );
          this.loadInstructors();
        },
        error: (error: HttpErrorResponse) => {
          this.processingInstructorId.set(null);
          this.toastService.error(this.getErrorMessage(error));
        },
      });
  }

  private updatePasswordValidity(): void {
    this.instructorForm.controls.password.updateValueAndValidity();
    this.instructorForm.controls.password_confirmation.updateValueAndValidity();
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
}
