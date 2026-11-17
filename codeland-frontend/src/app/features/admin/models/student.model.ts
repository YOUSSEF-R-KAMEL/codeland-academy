import { UserRoleData } from '../../../core/models/auth.model';

export interface StudentGroup {
  id: number;
  course_id: number;
  name: string;
  code: string;
}

export interface StudentEnrollment {
  id: number;
  status: 'active' | 'pending' | 'completed' | 'cancelled';
  enrolled_at: string | null;
  group: StudentGroup | null;
}

export interface Student {
  id: number;
  name: string;
  email: string | null;
  phone: string | null;
  is_active: boolean;
  roles: UserRoleData[];
  enrollment: StudentEnrollment | null;
}

export interface StudentPagination {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface StudentsResponseData {
  users: Student[];
  pagination: StudentPagination;
}

export interface StudentsFilters {
  page?: number;
  per_page?: number;
  search?: string;
  is_active?: boolean;
}

export interface CreateStudentRequest {
  name: string;
  role: 'student';
  email: string;
  password: string;
}

export interface UpdateStudentRequest {
  name: string;
  email: string;
  password?: string;
  password_confirmation?: string;
}

export interface CreateStudentResponse {
  success: boolean;
  message: string;
  data: {
    user: Student;
  };
}
