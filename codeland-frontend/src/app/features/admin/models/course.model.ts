export interface Course {
  id: number;
  title: string;
  slug: string;
  description: string | null;
  thumbnail_url: string | null;
  duration_weeks: number;
  sessions_per_week: number;
  session_duration_minutes: number;
  full_price: number;
  monthly_price: number | null;
  currency: string;
  is_published: boolean;
  sort_order: number;
}

export interface AdminCoursesResponse {
  courses: Course[];
}

export interface CoursesApiResponse {
  success: boolean;
  message: string;
  data: {
    courses?: Course[];
    course?: Course;
  };
}

export interface CreateCourseRequest {
  title: string;
  slug: string;
  description?: string | null;
  thumbnail_url?: string | null;
  duration_weeks: number;
  sessions_per_week: number;
  session_duration_minutes: number;
  full_price: number;
  monthly_price?: number | null;
  currency: 'EGP';
  is_published: boolean;
  sort_order?: number;
}

export type UpdateCourseRequest = Partial<CreateCourseRequest>;
  