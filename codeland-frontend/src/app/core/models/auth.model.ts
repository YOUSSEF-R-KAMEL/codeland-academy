export type UserRole = 'admin' | 'instructor' | 'student';

export interface UserRoleData {
  name: string;
  slug: UserRole;
}

export interface AuthUser {
  id: number;
  name: string;
  email: string | null;
  phone: string | null;
  is_active: boolean;
  roles: UserRoleData[];
}

export interface LoginRequest {
  type: UserRole;
  email: string;
  password: string;
}

export interface LoginResponseData {
  token: string;
  token_type: 'Bearer';
  user: AuthUser;
}

export interface ApiSuccessResponse<T> {
  success: true;
  message: string;
  data: T;
}

export interface ApiErrorResponse {
  success: false;
  message: string;
  errors: Record<string, string[]>;
}
