export interface EvaluationStudent {
  id: number;
  name: string;
}

export interface EvaluationCourse {
  id: number;
  title: string;
}

export interface EvaluationInstructor {
  id: number;
  name: string;
}

export interface CourseEvaluation {
  id: number;
  student_id: number;
  course_id: number;
  instructor_id: number;
  course_rating: number;
  course_comment: string | null;
  instructor_rating: number;
  instructor_comment: string | null;
  created_at: string;
  student: EvaluationStudent;
  course: EvaluationCourse;
  instructor: EvaluationInstructor;
}

export interface EvaluationStatisticsDistribution {
  rating: number;
  total: number;
}

export interface EvaluationStatistics {
  total_evaluations: number;
  average_course_rating: number;
  average_instructor_rating: number;
  course_rating_distribution: EvaluationStatisticsDistribution[];
  instructor_rating_distribution: EvaluationStatisticsDistribution[];
}

export interface PaginatedEvaluations {
  current_page: number;
  data: CourseEvaluation[];
  last_page: number;
  per_page: number;
  total: number;
}

export interface EvaluationsResponse {
  success: boolean;
  message: string;
  data: {
    evaluations: PaginatedEvaluations;
  };
}

export interface EvaluationStatisticsResponse {
  success: boolean;
  message: string;
  data: {
    statistics: EvaluationStatistics;
  };
}

export interface InstructorFeedbackResponse {
  success: boolean;
  message: string;
  data: {
    feedback: PaginatedEvaluations;
  };
}

export interface EvaluationFilters {
  course_id?: number;
  instructor_id?: number;
  rating?: number;
  search?: string;
  per_page?: number;
  page?: number;
}
