export type StudentFeedbackType = 'suggestion' | 'complaint' | 'inquiry';

export type StudentFeedbackStatus =
  | 'new'
  | 'in_review'
  | 'resolved'
  | 'rejected';

export interface FeedbackUser {
  id: number;
  name: string;
  email?: string;
}

export interface StudentFeedback {
  id: number;
  student_id: number;
  type: StudentFeedbackType;
  subject: string;
  message: string;
  status: StudentFeedbackStatus;
  admin_reply: string | null;
  replied_by: number | null;
  replied_at: string | null;
  created_at: string;
  updated_at: string;
  student?: FeedbackUser;
  admin?: FeedbackUser | null;
}

export interface PaginatedStudentFeedback {
  current_page: number;
  data: StudentFeedback[];
  first_page_url?: string | null;
  from: number | null;
  last_page: number;
  last_page_url?: string | null;
  next_page_url?: string | null;
  prev_page_url?: string | null;
  path?: string;
  per_page: number;
  to: number | null;
  total: number;
}

export interface ApiResponse<T> {
  success: boolean;
  message?: string;
  data: T;
}

export interface StudentFeedbackFilters {
  type?: StudentFeedbackType;
  status?: StudentFeedbackStatus;
  search?: string;
  per_page?: number;
  page?: number;
}

export interface CreateStudentFeedbackRequest {
  type: StudentFeedbackType;
  subject: string;
  message: string;
}

export interface ReplyStudentFeedbackRequest {
  admin_reply: string;
}

export interface UpdateStudentFeedbackStatusRequest {
  status: StudentFeedbackStatus;
}
