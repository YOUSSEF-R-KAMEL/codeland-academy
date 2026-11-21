export interface AssignmentLesson {
  id: number;
  title: string;
  course: {
    id: number;
    title: string;
  };
}

export interface AssignmentCreator {
  id: number;
  name: string;
}

export interface Assignment {
  id: number;
  course_lesson_id: number;
  title: string;
  instructions: string;
  resource_url: string | null;
  published_at: string | null;
  due_at: string | null;
  is_published: boolean;
  created_by: number;
  created_at: string;
  updated_at: string;
  course_lesson: AssignmentLesson;
  creator: AssignmentCreator;
  submissions_count: number;
}

export interface AssignmentListResponse {
  data: Assignment[];
}
