import {
  Component,
  DestroyRef,
  OnInit,
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
import { DatePipe } from '@angular/common';
import { RouterLink } from '@angular/router';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { TranslatePipe, TranslateService } from '@ngx-translate/core';
import Swal from 'sweetalert2';

import { CoursesService } from '../../services/courses.service';
import { GroupsService } from '../../services/groups.service';
import { ToastService } from '../../../../core/services/toast.service';

import { Course } from '../../models/course.model';
import {
  Group,
  GroupStatus,
  CreateGroupRequest,
  UpdateGroupRequest,
} from '../../models/group.model';

import {
  SelectComponent,
  SelectOption,
} from '../../../../shared/components/select.component';

@Component({
  selector: 'app-groups',
  standalone: true,
  imports: [
    TranslatePipe,
    FormsModule,
    ReactiveFormsModule,
    SelectComponent,
    DatePipe,
    RouterLink,
  ],
  templateUrl: './groups.component.html',
  styleUrl: './groups.component.scss',
})
export class GroupsComponent implements OnInit {
  private readonly fb = inject(FormBuilder);
  private readonly destroyRef = inject(DestroyRef);
  private readonly coursesService = inject(CoursesService);
  private readonly groupsService = inject(GroupsService);
  private readonly toastService = inject(ToastService);
  private readonly translate = inject(TranslateService);

  readonly courses = signal<Course[]>([]);
  readonly groups = signal<Group[]>([]);

  readonly selectedCourseId = signal<number | null>(null);

  readonly loadingCourses = signal(false);
  readonly loadingGroups = signal(false);
  readonly saving = signal(false);

  readonly isModalOpen = signal(false);
  readonly selectedGroupId = signal<number | null>(null);

  readonly courseOptions = computed<SelectOption[]>(() =>
    this.courses().map((course) => ({
      value: course.id,
      label: course.title,
    })),
  );

  readonly groupStatusOptions = [
    { value: 'planned', label: 'admin.groups.status.planned' },
    { value: 'active', label: 'admin.groups.status.active' },
    { value: 'completed', label: 'admin.groups.status.completed' },
    { value: 'cancelled', label: 'admin.groups.status.cancelled' },
  ];

  readonly groupForm = this.fb.group({
    name: ['', [Validators.required, Validators.minLength(2)]],
    code: ['', [Validators.required, Validators.maxLength(255)]],
    capacity: [8, [Validators.required, Validators.min(1), Validators.max(8)]],
    start_date: [''],
    end_date: [''],
    status: ['planned' as GroupStatus, Validators.required],
    notes: [''],
  });

  ngOnInit(): void {
    this.loadCourses();
  }

  loadCourses(): void {
    this.loadingCourses.set(true);

    this.coursesService
      .getAdminCourses()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          const courses = response.data.courses ?? [];

          this.courses.set(courses);
          this.loadingCourses.set(false);

          if (courses.length > 0) {
            this.onCourseChange(courses[0].id);
          } else {
            this.selectedCourseId.set(null);
            this.groups.set([]);
          }
        },
        error: (error: HttpErrorResponse) => {
          this.loadingCourses.set(false);

          this.toastService.error(
            error.error?.message || 'Failed to load courses.',
          );
        },
      });
  }

  onCourseChange(courseId: string | number | null): void {
    const normalizedCourseId =
      courseId === null || courseId === '' ? null : Number(courseId);

    if (!normalizedCourseId || Number.isNaN(normalizedCourseId)) {
      this.selectedCourseId.set(null);
      this.groups.set([]);
      return;
    }

    this.selectedCourseId.set(normalizedCourseId);
    this.loadGroups();
  }

  loadGroups(): void {
    const courseId = this.selectedCourseId();

    if (!courseId) {
      this.groups.set([]);
      return;
    }

    this.loadingGroups.set(true);

    this.groupsService
      .getGroups(courseId)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.groups.set(response.data);
          this.loadingGroups.set(false);
        },
        error: (error: HttpErrorResponse) => {
          this.loadingGroups.set(false);

          this.toastService.error(
            error.error?.message || 'Failed to load groups.',
          );
        },
      });
  }

  openAddModal(): void {
    this.selectedGroupId.set(null);

    this.groupForm.reset({
      name: '',
      code: '',
      capacity: 8,
      start_date: '',
      end_date: '',
      status: 'planned',
      notes: '',
    });

    this.isModalOpen.set(true);
  }

  openEditModal(group: Group): void {
    this.selectedGroupId.set(group.id);

    this.groupForm.patchValue({
      name: group.name,
      code: group.code,
      capacity: group.capacity,
      start_date: group.start_date ?? '',
      end_date: group.end_date ?? '',
      status: group.status,
      notes: group.notes ?? '',
    });

    this.isModalOpen.set(true);
  }

  closeModal(): void {
    if (this.saving()) return;

    this.isModalOpen.set(false);
    this.selectedGroupId.set(null);
    this.groupForm.reset();
  }

  private buildGroupPayload(): CreateGroupRequest {
    const value = this.groupForm.getRawValue();

    return {
      name: value.name!.trim(),
      code: value.code!.trim(),
      capacity: Number(value.capacity),
      start_date: value.start_date || null,
      end_date: value.end_date || null,
      status: value.status!,
      notes: value.notes?.trim() || null,
    };
  }

  saveGroup(): void {
    if (this.groupForm.invalid) {
      this.groupForm.markAllAsTouched();
      return;
    }

    const courseId = this.selectedCourseId();

    if (!courseId || this.saving()) return;

    this.saving.set(true);

    const payload = this.buildGroupPayload();
    const groupId = this.selectedGroupId();

    const request = groupId
      ? this.groupsService.updateGroup(
          courseId,
          groupId,
          payload as UpdateGroupRequest,
        )
      : this.groupsService.createGroup(courseId, payload);

    request.pipe(takeUntilDestroyed(this.destroyRef)).subscribe({
      next: (response) => {
        this.toastService.success(response.message);

        this.isModalOpen.set(false);
        this.selectedGroupId.set(null);
        this.saving.set(false);

        this.loadGroups();
      },
      error: (error: HttpErrorResponse) => {
        this.saving.set(false);

        this.toastService.error(
          error.error?.message || 'Failed to save group.',
        );
      },
    });
  }

  async deleteGroup(group: Group): Promise<void> {
    const courseId = this.selectedCourseId();

    if (!courseId) return;

    const result = await Swal.fire({
      title: this.translate.instant('admin.groups.delete_confirmation_title'),
      text: this.translate.instant('admin.groups.delete_confirmation_text', {
        name: group.name,
      }),
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: this.translate.instant('common.delete'),
      cancelButtonText: this.translate.instant('common.cancel'),
      confirmButtonColor: '#dc2626',
      cancelButtonColor: '#6b7280',
      reverseButtons: true,
    });

    if (!result.isConfirmed) return;

    this.groupsService
      .deleteGroup(courseId, group.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => {
          Swal.fire({
            icon: 'success',
            title: this.translate.instant('common.success'),
            text: this.translate.instant('groups.delete_success'),
            confirmButtonText: this.translate.instant('common.ok'),
            confirmButtonColor: '#16a34a',
          });

          this.loadGroups();
        },
        error: (error: HttpErrorResponse) => {
          const message = error.error?.message;

          if (message?.includes('related academic records')) {
            Swal.fire({
              icon: 'error',
              title: this.translate.instant('common.error'),
              text: this.translate.instant(
                'groups.delete_has_academic_records',
              ),
              confirmButtonText: this.translate.instant('common.ok'),
              confirmButtonColor: '#dc2626',
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: this.translate.instant('common.error'),
              text: message || this.translate.instant('groups.delete_error'),
              confirmButtonText: this.translate.instant('common.ok'),
              confirmButtonColor: '#dc2626',
              customClass: {
                popup: document.documentElement.classList.contains('dark')
                  ? 'swal-dark-popup'
                  : 'swal-light-popup',
                title: 'swal-custom-title',
                htmlContainer: 'swal-custom-text',
              },
            });
          }
        },
      });
  }
}
