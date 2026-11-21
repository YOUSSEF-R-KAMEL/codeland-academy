import { Component, computed, inject, signal } from '@angular/core';
import { DatePipe } from '@angular/common';
import { Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { AttendanceOverviewService } from '../../services/attendance-overview.service';
import {
  AttendanceSession,
  GroupAttendanceOverview,
} from '../../models/attendance-overview.model';
import { TranslatePipe } from '@ngx-translate/core';
import { SelectComponent } from '../../../../shared/components/select.component';

@Component({
  selector: 'app-attendance-overview',
  standalone: true,
  imports: [DatePipe, FormsModule, TranslatePipe, SelectComponent],
  templateUrl: './attendance-overview.component.html',
})
export class AttendanceOverviewComponent {
  private readonly service = inject(AttendanceOverviewService);
  private readonly router = inject(Router);

  sessions = signal<AttendanceSession[]>([]);
  loading = signal(true);
  error = signal<string | null>(null);

  searchTerm = signal('');
  statusFilter = signal('all');

  selectedGroupOverview = signal<GroupAttendanceOverview | null>(null);
  overviewLoading = signal(false);
  overviewError = signal<string | null>(null);
  totalPresentRecords = computed(
    () =>
      this.selectedGroupOverview()?.sessions.reduce(
        (total, session) => total + session.present,
        0,
      ) ?? 0,
  );

  totalAbsentRecords = computed(
    () =>
      this.selectedGroupOverview()?.sessions.reduce(
        (total, session) => total + session.absent,
        0,
      ) ?? 0,
  );
  filteredSessions = computed(() => {
    const search = this.searchTerm().trim().toLowerCase();
    const filter = this.statusFilter();

    return this.sessions().filter((session) => {
      const matchesSearch =
        !search ||
        session.title.toLowerCase().includes(search) ||
        session.group.name.toLowerCase().includes(search) ||
        session.group.code.toLowerCase().includes(search) ||
        session.group.course.title.toLowerCase().includes(search) ||
        session.group.instructors.some((instructor) =>
          instructor.name.toLowerCase().includes(search),
        );

      const matchesStatus =
        filter === 'all' || this.getAttendanceStatus(session) === filter;

      return matchesSearch && matchesStatus;
    });
  });

  totalSessions = computed(() => this.sessions().length);

  completedSessions = computed(
    () =>
      this.sessions().filter(
        (session) => this.getAttendanceStatus(session) === 'completed',
      ).length,
  );

  pendingSessions = computed(
    () =>
      this.sessions().filter(
        (session) => this.getAttendanceStatus(session) === 'pending',
      ).length,
  );

  constructor() {
    this.loadSessions();
  }

  loadSessions(): void {
    this.loading.set(true);
    this.error.set(null);

    this.service.getSessions().subscribe({
      next: (response) => {
        this.sessions.set(response.data ?? []);
        this.loading.set(false);
      },
      error: () => {
        this.error.set('admin.attendance.load_error_overview');
        this.loading.set(false);
      },
    });
  }

  getAttendanceStatus(session: AttendanceSession): string {
    const finalizedCount = session.present_count + session.absent_count;

    if (finalizedCount === 0) return 'pending';

    if (finalizedCount >= session.active_students_count) {
      return 'completed';
    }

    return 'partial';
  }

  getInstructorName(session: AttendanceSession): string {
    return session.group.instructors[0]?.name ?? 'Not assigned';
  }

  openAttendance(session: AttendanceSession): void {
    this.router.navigate([
      '/admin/groups',
      session.group_id,
      'sessions',
      session.id,
      'attendance',
    ]);
  }

  openGroupOverview(session: AttendanceSession): void {
    this.selectedGroupOverview.set(null);
    this.overviewError.set(null);
    this.overviewLoading.set(true);

    this.service.getGroupOverview(session.group_id).subscribe({
      next: (response) => {
        this.selectedGroupOverview.set(response.data);
        this.overviewLoading.set(false);
      },
      error: () => {
        this.overviewError.set('admin.attendance.group_overview_error');
        this.overviewLoading.set(false);
      },
    });
  }

  closeGroupOverview(): void {
    this.selectedGroupOverview.set(null);
    this.overviewError.set(null);
    this.overviewLoading.set(false);
  }
}
