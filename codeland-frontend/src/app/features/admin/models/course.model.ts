export interface Course {
  id: number;
  title: string;
  slug: string;
  is_published: boolean;
}

export interface AdminCoursesResponse {
  courses: Course[];
}
