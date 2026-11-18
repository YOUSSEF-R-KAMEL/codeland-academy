import { Component, DestroyRef, OnInit, inject, signal } from '@angular/core';
import { DatePipe } from '@angular/common';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { TranslatePipe } from '@ngx-translate/core';

import { AttendanceService } from '../../../admin/services/attendance.service';
import { StudentAttendanceSession } from '../../../admin/models/attendance.model';

@Component({
  selector: 'app-student-attendance',
  standalone: true,
  imports: [DatePipe, TranslatePipe],
  templateUrl: './student-attendance.component.html',
})
export class StudentAttendanceComponent implements OnInit {
  private readonly attendanceService = inject(AttendanceService);
  private readonly destroyRef = inject(DestroyRef);

  readonly sessions = signal<StudentAttendanceSession[]>([]);
  readonly loading = signal(false);
  readonly errorMessage = signal('');

  ngOnInit(): void {
    this.loadAttendance();
  }

  loadAttendance(): void {
    this.loading.set(true);
    this.errorMessage.set('');

    this.attendanceService
      .getMyAttendance()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.sessions.set(response.data.sessions);
          this.loading.set(false);
        },
        error: () => {
          this.errorMessage.set('attendance.load_error');
          this.loading.set(false);
        },
      });
  }
}
