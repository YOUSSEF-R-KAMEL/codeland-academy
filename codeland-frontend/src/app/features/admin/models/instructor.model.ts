import { UserRoleData } from '../../../core/models/auth.model';

export interface Instructor {
  id: number;
  name: string;
  username: string | null;
  email: string | null;
  phone: string | null;
  is_active: boolean;
  roles: UserRoleData[];
}

export interface InstructorPagination {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface InstructorsResponseData {
  users: Instructor[];
  pagination: InstructorPagination;
}

export interface InstructorsFilters {
  search?: string;
  is_active?: boolean;
  per_page?: number;
  page?: number;
}

export interface CreateInstructorRequest {
  name: string;
  role: 'instructor';
  email: string;
  password: string;
  password_confirmation: string;
}

export interface UpdateInstructorRequest {
  name: string;
  email: string;
  password?: string;
  password_confirmation?: string;
}
