import { Component, DestroyRef, OnInit, inject, signal } from '@angular/core';
import { DatePipe } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { TranslatePipe } from '@ngx-translate/core';

import { ClassSessionsService } from '../../services/class-sessions.service';
import {
  ClassSession,
  ClassSessionPayload,
  ClassSessionsApiResponse,
} from '../../models/class-session.model';

@Component({
  selector: 'app-class-sessions',
  standalone: true,
  imports: [FormsModule, DatePipe, TranslatePipe],
  templateUrl: './class-sessions.component.html',
})
export class ClassSessionsComponent implements OnInit {
  private readonly service = inject(ClassSessionsService);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly destroyRef = inject(DestroyRef);

  readonly sessions = signal<ClassSession[]>([]);
  readonly loading = signal(false);
  readonly saving = signal(false);
  readonly errorMessage = signal('');
  readonly successMessage = signal('');
  readonly showForm = signal(false);
  readonly editingSessionId = signal<number | null>(null);
  readonly deletingSessionId = signal<number | null>(null);

  groupId = 0;

  form: ClassSessionPayload = this.emptyForm();

  ngOnInit(): void {
    this.groupId = Number(this.route.snapshot.paramMap.get('groupId'));

    if (!this.groupId) {
      this.errorMessage.set('class_sessions.invalid_group');
      return;
    }

    this.loadSessions();
  }

  private emptyForm(): ClassSessionPayload {
    return {
      title: '',
      starts_at: '',
      ends_at: '',
      meeting_url: null,
      notes: null,
    };
  }

  loadSessions(): void {
    this.loading.set(true);
    this.errorMessage.set('');

    this.service
      .getSessions(this.groupId)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          const data = response.data;

          const sessions = Array.isArray(data) ? data : (data.sessions ?? []);

          this.sessions.set(sessions);
          this.loading.set(false);
        },
        error: () => {
          this.errorMessage.set('class_sessions.load_error');
          this.loading.set(false);
        },
      });
  }

  openCreateForm(): void {
    this.form = this.emptyForm();
    this.editingSessionId.set(null);
    this.showForm.set(true);
    this.errorMessage.set('');
    this.successMessage.set('');
  }

  openEditForm(session: ClassSession): void {
    this.form = {
      title: session.title,
      starts_at: this.toDateTimeLocal(session.starts_at),
      ends_at: this.toDateTimeLocal(session.ends_at),
      meeting_url: session.meeting_url,
      notes: session.notes,
    };

    this.editingSessionId.set(session.id);
    this.showForm.set(true);
    this.errorMessage.set('');
    this.successMessage.set('');
  }

  closeForm(): void {
    this.showForm.set(false);
    this.editingSessionId.set(null);
    this.form = this.emptyForm();
  }

  saveSession(): void {
    if (!this.form.title.trim() || !this.form.starts_at || !this.form.ends_at) {
      this.errorMessage.set('class_sessions.required_fields');
      return;
    }

    if (
      new Date(this.form.ends_at).getTime() <=
      new Date(this.form.starts_at).getTime()
    ) {
      this.errorMessage.set('class_sessions.invalid_dates');
      return;
    }

    const payload: ClassSessionPayload = {
      ...this.form,
      title: this.form.title.trim(),
      meeting_url: this.form.meeting_url?.trim() || null,
      notes: this.form.notes?.trim() || null,
    };

    const sessionId = this.editingSessionId();

    this.saving.set(true);
    this.errorMessage.set('');

    const request = sessionId
      ? this.service.updateSession(this.groupId, sessionId, payload)
      : this.service.createSession(this.groupId, payload);

    request.pipe(takeUntilDestroyed(this.destroyRef)).subscribe({
      next: () => {
        this.saving.set(false);
        this.successMessage.set(
          sessionId
            ? 'class_sessions.update_success'
            : 'class_sessions.create_success',
        );
        this.closeForm();
        this.loadSessions();
      },
      error: () => {
        this.saving.set(false);
        this.errorMessage.set('class_sessions.save_error');
      },
    });
  }

  deleteSession(session: ClassSession): void {
    if (!confirm('Are you sure you want to delete this session?')) {
      return;
    }

    this.deletingSessionId.set(session.id);
    this.errorMessage.set('');

    this.service
      .deleteSession(this.groupId, session.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => {
          this.deletingSessionId.set(null);
          this.successMessage.set('class_sessions.delete_success');
          this.loadSessions();
        },
        error: () => {
          this.deletingSessionId.set(null);
          this.errorMessage.set('class_sessions.delete_error');
        },
      });
  }

  openAttendance(session: ClassSession): void {
    this.router.navigate([
      '/admin/groups',
      this.groupId,
      'sessions',
      session.id,
      'attendance',
    ]);
  }

  private toDateTimeLocal(value: string): string {
    const date = new Date(value);

    const localDate = new Date(
      date.getTime() - date.getTimezoneOffset() * 60000,
    );

    return localDate.toISOString().slice(0, 16);
  }
}
