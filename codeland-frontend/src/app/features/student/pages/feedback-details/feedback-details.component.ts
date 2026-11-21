import { Component, OnInit, inject, signal } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { DatePipe } from '@angular/common';
import { TranslatePipe } from '@ngx-translate/core';
import { StudentFeedbackService } from '../../../admin/services/student-feedback.service';
import { StudentFeedback } from '../../../admin/models/student-feedback.model';

@Component({
  selector: 'app-feedback-details',
  standalone: true,
  imports: [RouterLink, DatePipe, TranslatePipe],
  templateUrl: './feedback-details.component.html',
  styleUrl: './feedback-details.component.scss',
})
export class FeedbackDetailsComponent implements OnInit {
  private readonly route = inject(ActivatedRoute);
  private readonly feedbackService = inject(StudentFeedbackService);

  readonly feedback = signal<StudentFeedback | null>(null);
  readonly isLoading = signal(true);
  readonly errorMessage = signal('');

  ngOnInit(): void {
    const id = Number(this.route.snapshot.paramMap.get('id'));

    if (!Number.isInteger(id) || id <= 0) {
      this.errorMessage.set('student.feedback.not_found');
      this.isLoading.set(false);
      return;
    }

    this.feedbackService.getMyFeedbackDetails(id).subscribe({
      next: (response) => {
        this.feedback.set(response.data);
        this.isLoading.set(false);
      },
      error: () => {
        this.errorMessage.set('student.feedback.details_error');
        this.isLoading.set(false);
      },
    });
  }
}
