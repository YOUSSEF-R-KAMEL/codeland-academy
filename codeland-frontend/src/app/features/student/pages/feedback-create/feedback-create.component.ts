import { Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { TranslatePipe } from '@ngx-translate/core';
import { StudentFeedbackService } from '../../../admin/services/student-feedback.service';
import { StudentFeedbackType } from '../../../admin/models/student-feedback.model';

@Component({
  selector: 'app-feedback-create',
  standalone: true,
  imports: [FormsModule, RouterLink, TranslatePipe],
  templateUrl: './feedback-create.component.html',
  styleUrl: './feedback-create.component.scss',
})
export class FeedbackCreateComponent {
  private readonly feedbackService = inject(StudentFeedbackService);
  private readonly router = inject(Router);

  readonly type = signal<StudentFeedbackType>('suggestion');
  readonly subject = signal('');
  readonly message = signal('');
  readonly isSubmitting = signal(false);
  readonly errorMessage = signal('');

  submit(): void {
    if (this.isSubmitting()) return;

    const subject = this.subject().trim();
    const message = this.message().trim();

    if (!subject || message.length < 10) {
      this.errorMessage.set('student.feedback.validation_error');
      return;
    }

    this.isSubmitting.set(true);
    this.errorMessage.set('');

    this.feedbackService
      .submitFeedback({
        type: this.type(),
        subject,
        message,
      })
      .subscribe({
        next: () => {
          this.router.navigate(['/student/feedback']);
        },
        error: (error) => {
          this.errorMessage.set(
            error?.error?.message || 'student.feedback.submit_error',
          );
          this.isSubmitting.set(false);
        },
      });
  }
}
