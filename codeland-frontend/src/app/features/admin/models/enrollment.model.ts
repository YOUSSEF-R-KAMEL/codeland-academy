export interface CreateEnrollmentRequest {
  student_id: number;
  payment_plan: 'full' | 'monthly';
}

export interface Enrollment {
  id: number;
  student_id: number;
  group_id: number;
  status: 'pending' | 'active' | 'completed' | 'cancelled';
  enrolled_at: string;
}
