import { Component, OnInit, inject, signal } from '@angular/core';
import { DatePipe } from '@angular/common';
import { RouterLink } from '@angular/router';
import { TranslatePipe } from '@ngx-translate/core';
import { StudentFeedbackService } from '../../../admin/services/student-feedback.service';
import { StudentFeedback } from '../../../admin/models/student-feedback.model'

@Component({
  selector: 'app-student-feedback',
  standalone: true,
  imports: [DatePipe, RouterLink, TranslatePipe],
  templateUrl: './student-feedback.component.html',
  styleUrl: './student-feedback.component.scss',
})
export class StudentFeedbackComponent implements OnInit {
  private readonly feedbackService = inject(StudentFeedbackService);

  readonly feedbackItems = signal<StudentFeedback[]>([]);
  readonly isLoading = signal(false);
  readonly errorMessage = signal('');

  ngOnInit(): void {
    this.loadFeedback();
  }

  loadFeedback(): void {
    this.isLoading.set(true);
    this.errorMessage.set('');

    this.feedbackService.getMyFeedback({ per_page: 10, page: 1 }).subscribe({
      next: (response) => {
        this.feedbackItems.set(response.data.data);
        this.isLoading.set(false);
      },
      error: () => {
        this.errorMessage.set('student.feedback.load_error');
        this.isLoading.set(false);
      },
    });
  }
}
