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
import { FormControl } from '@angular/forms';
import { ToastService } from '../../../../core/services/toast.service';
import { InstructorsService } from '../../services/instructors.service';

import {
  CreateInstructorRequest,
  Instructor,
  InstructorsFilters,
  InstructorPagination,
  UpdateInstructorRequest,
} from '../../models/instructor.model';
import { SelectComponent } from '../../../../shared/components/select.component';

@Component({
  selector: 'app-instructors',
  standalone: true,
  imports: [TranslatePipe, ReactiveFormsModule, SelectComponent],
  templateUrl: './instructors.component.html',
  styleUrl: './instructors.component.scss',
})
export class InstructorsComponent implements OnInit {
  private readonly fb = inject(FormBuilder);
  private readonly instructorsService = inject(InstructorsService);
  private readonly destroyRef = inject(DestroyRef);
  private readonly toastService = inject(ToastService);
  private readonly searchSubject = new Subject<string>();

  readonly instructors = signal<Instructor[]>([]);
  readonly loading = signal(false);
  readonly saving = signal(false);

  readonly isModalOpen = signal(false);
  readonly selectedInstructorId = signal<number | null>(null);
  readonly processingInstructorId = signal<number | null>(null);

  readonly searchTerm = signal('');
  readonly selectedStatus = signal('');

  readonly currentPage = signal(1);
  readonly pagination = signal<InstructorPagination>({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
  });
  readonly statusOptions = [
    { value: '', label: 'instructors.all_statuses' },
    { value: 'active', label: 'instructors.active' },
    { value: 'inactive', label: 'instructors.inactive' },
  ];

  readonly statusControl = new FormControl('', {
    nonNullable: true,
  });

  readonly totalInstructors = computed(() => this.pagination().total);

  readonly activeInstructors = computed(
    () =>
      this.instructors().filter((instructor) => instructor.is_active).length,
  );

  readonly inactiveInstructors = computed(
    () =>
      this.instructors().filter((instructor) => !instructor.is_active).length,
  );

  readonly instructorForm = this.fb.group({
    name: ['', [Validators.required, Validators.minLength(3)]],
    email: ['', [Validators.required, Validators.email]],
    password: ['', [Validators.minLength(8)]],
    password_confirmation: [''],
  });

  ngOnInit(): void {
    this.statusControl.valueChanges
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe((status) => {
        this.onStatusChange(status);
      });
    this.searchSubject
      .pipe(
        debounceTime(350),
        distinctUntilChanged(),
        takeUntilDestroyed(this.destroyRef),
      )
      .subscribe((search) => {
        this.searchTerm.set(search);
        this.loadInstructors(1);
      });

    this.loadInstructors(1);
  }

  loadInstructors(page = this.currentPage()): void {
    this.loading.set(true);

    const filters: InstructorsFilters = {
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

    this.instructorsService
      .getInstructors(filters)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.instructors.set(response.data.users);
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

  onStatusChange(value: string): void {
    this.selectedStatus.set(value);
    this.loadInstructors(1);
  }

  goToPage(page: number): void {
    if (
      page < 1 ||
      page > this.pagination().last_page ||
      page === this.currentPage()
    ) {
      return;
    }

    this.loadInstructors(page);
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
          this.loadInstructors(1);
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
          this.loadInstructors(this.currentPage());
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

          this.loadInstructors(this.currentPage());
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
