import { Component, DestroyRef, OnInit, inject, signal } from '@angular/core';
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

  ngOnInit(): void {
    this.groupId = Number(this.route.snapshot.paramMap.get('groupId'));
    this.sessionId = Number(this.route.snapshot.paramMap.get('classSessionId'));

    if (!this.groupId || !this.sessionId) {
      this.errorMessage.set('attendance.invalid_route');
      return;
    }

    this.loadAttendance();
  }

  loadAttendance(): void {
    this.loading.set(true);
    this.errorMessage.set('');

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
          this.errorMessage.set('attendance.load_error');
          this.loading.set(false);
        },
      });
  }

  selectCheck(checkNumber: number): void {
    this.selectedCheck.set(checkNumber);
    this.successMessage.set('');
    this.errorMessage.set('');
  }

  updateStudentAttendance(studentId: number, isPresent: boolean): void {
    this.students.update((students) =>
      students.map((student) => {
        if (student.student_id !== studentId) {
          return student;
        }

        return {
          ...student,
          checks: student.checks.map((check) =>
            check.check_number === this.selectedCheck()
              ? {
                  ...check,
                  is_present: isPresent,
                  is_recorded: true,
                }
              : check,
          ),
        };
      }),
    );
  }

  saveAttendance(): void {
    const students = this.students();

    const hasUnmarkedStudents = students.some((student) => {
      const check = student.checks.find(
        (item) => item.check_number === this.selectedCheck(),
      );

      return !check?.is_recorded;
    });

    if (hasUnmarkedStudents) {
      this.errorMessage.set('attendance.mark_all_students');
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
          this.successMessage.set('attendance.save_success');
          this.saving.set(false);
          this.loadAttendance();
        },
        error: () => {
          this.errorMessage.set('attendance.save_error');
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
