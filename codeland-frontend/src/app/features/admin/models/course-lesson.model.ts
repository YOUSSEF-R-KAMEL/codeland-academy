export interface CourseOption {
  id: number;
  title: string;
  is_published?: boolean;
}

export interface CourseLessonOption {
  id: number;
  course_id: number;
  title: string;
  lesson_number: number;
  is_published: boolean;
}

export interface CoursesResponse {
  success: boolean;
  message: string;
  data: {
    courses: CourseOption[];
  };
}

export interface CourseLessonsResponse {
  success: boolean;
  message: string;
  data: {
    lessons: CourseLessonOption[];
  };
}
