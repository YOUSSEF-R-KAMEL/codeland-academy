import {
  Component,
  DestroyRef,
  OnInit,
  computed,
  inject,
  signal,
} from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { TranslatePipe } from '@ngx-translate/core';

import { AttendanceService } from '../../services/attendance.service';
import {
  AttendanceStudent,
  RecordAttendancePayload,
} from '../../models/attendance.model';

@Component({
  selector: 'app-attendance-management',
  standalone: true,
  imports: [TranslatePipe],
  templateUrl: './attendance-management.component.html',
})
export class AttendanceManagementComponent implements OnInit {
  private readonly attendanceService = inject(AttendanceService);
  private readonly route = inject(ActivatedRoute);
  private readonly destroyRef = inject(DestroyRef);

  readonly students = signal<AttendanceStudent[]>([]);
  readonly sessionTitle = signal('');
  readonly selectedCheck = signal(1);

  readonly loading = signal(false);
  readonly saving = signal(false);
  readonly errorMessage = signal('');
  readonly successMessage = signal('');

  private groupId = 0;
  private sessionId = 0;

  readonly presentCount = computed(
    () =>
      this.students().filter((student) => {
        const check = student.checks.find(
          (item) => item.check_number === this.selectedCheck(),
        );

        return check?.is_recorded && check.is_present === true;
      }).length,
  );

  readonly absentCount = computed(
    () =>
      this.students().filter((student) => {
        const check = student.checks.find(
          (item) => item.check_number === this.selectedCheck(),
        );

        return check?.is_recorded && check.is_present === false;
      }).length,
  );

  readonly unmarkedCount = computed(
    () =>
      this.students().filter((student) => {
        const check = student.checks.find(
          (item) => item.check_number === this.selectedCheck(),
        );

        return !check?.is_recorded || check.is_present === null;
      }).length,
  );

  private calculateFinalStatus(
    checks: AttendanceStudent['checks'],
  ): AttendanceStudent['final_status'] {
    if (checks.some((check) => check.is_present === true)) {
      return 'present';
    }

    const allChecksRecorded = [1, 2, 3].every((number) =>
      checks.some(
        (check) => check.check_number === number && check.is_recorded,
      ),
    );

    return allChecksRecorded ? 'absent' : 'pending';
  }

  ngOnInit(): void {
    this.groupId = Number(this.route.snapshot.paramMap.get('groupId'));
    this.sessionId = Number(this.route.snapshot.paramMap.get('classSessionId'));

    if (!this.groupId || !this.sessionId) {
      this.errorMessage.set('admin.attendance.invalid_route');
      return;
    }

    this.loadAttendance();
  }

  loadAttendance(preserveSuccess = false): void {
    if (this.loading() || this.saving()) {
      return;
    }

    this.loading.set(true);
    this.errorMessage.set('');

    if (!preserveSuccess) {
      this.successMessage.set('');
    }

    this.attendanceService
      .getSessionAttendance(this.groupId, this.sessionId)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.students.set(response.data.students);
          this.sessionTitle.set(response.data.session_title);
          this.loading.set(false);
        },
        error: () => {
          this.errorMessage.set('admin.attendance.load_error');
          this.successMessage.set('');
          this.loading.set(false);
        },
      });
  }

  retryLoading(): void {
    this.loadAttendance();
  }

  selectCheck(checkNumber: number): void {
    if (this.saving() || this.loading()) {
      return;
    }

    if (![1, 2, 3].includes(checkNumber)) {
      return;
    }

    this.selectedCheck.set(checkNumber);
    this.successMessage.set('');
    this.errorMessage.set('');
  }

  updateStudentAttendance(studentId: number, isPresent: boolean): void {
    if (this.saving() || this.loading()) {
      return;
    }

    this.students.update((students) =>
      students.map((student) => {
        if (student.student_id !== studentId) {
          return student;
        }

        const updatedChecks = student.checks.map((check) =>
          check.check_number === this.selectedCheck()
            ? {
                ...check,
                is_present: isPresent,
                is_recorded: true,
              }
            : check,
        );

        return {
          ...student,
          checks: updatedChecks,
          final_status: this.calculateFinalStatus(updatedChecks),
        };
      }),
    );

    this.errorMessage.set('');
    this.successMessage.set('');
  }

  saveAttendance(): void {
    if (this.saving() || this.loading()) {
      return;
    }

    const students = this.students();

    if (students.length === 0) {
      return;
    }

    const hasUnmarkedStudents = students.some((student) => {
      const check = student.checks.find(
        (item) => item.check_number === this.selectedCheck(),
      );

      return !check?.is_recorded || check.is_present === null;
    });

    if (hasUnmarkedStudents) {
      this.errorMessage.set('admin.attendance.mark_all_students');
      this.successMessage.set('');
      return;
    }

    const payload: RecordAttendancePayload = {
      check_number: this.selectedCheck(),
      students: students.map((student) => {
        const check = student.checks.find(
          (item) => item.check_number === this.selectedCheck(),
        );

        return {
          student_id: student.student_id,
          is_present: check!.is_present!,
        };
      }),
    };

    this.saving.set(true);
    this.errorMessage.set('');
    this.successMessage.set('');

    this.attendanceService
      .recordAttendance(this.groupId, this.sessionId, payload)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => {
          this.saving.set(false);
          this.successMessage.set('admin.attendance.save_success');

          this.loadAttendance(true);
        },
        error: () => {
          this.errorMessage.set('admin.attendance.save_error');
          this.saving.set(false);
        },
      });
  }

  getCheckStatus(student: AttendanceStudent): boolean | null {
    return (
      student.checks.find(
        (check) => check.check_number === this.selectedCheck(),
      )?.is_present ?? null
    );
  }
}
