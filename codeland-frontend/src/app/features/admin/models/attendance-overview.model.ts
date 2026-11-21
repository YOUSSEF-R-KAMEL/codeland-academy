export interface AttendanceInstructor {
  id: number;
  name: string;
  pivot: {
    is_primary: number;
  };
}

export interface AttendanceCourse {
  id: number;
  title: string;
}

export interface AttendanceGroup {
  id: number;
  name: string;
  code: string;
  course: AttendanceCourse;
  instructors: AttendanceInstructor[];
}

export interface AttendanceSession {
  id: number;
  group_id: number;
  course_lesson_id: number;
  title: string;
  starts_at: string;
  ends_at: string;
  meeting_url: string | null;
  status: string;
  present_count: number;
  absent_count: number;
  active_students_count: number;
  group: AttendanceGroup;
}

export interface AttendanceOverviewResponse {
  success: boolean;
  data: AttendanceSession[];
}

export interface GroupAttendanceSession {
  session_id: number;
  session_title: string;
  starts_at: string;
  ends_at: string;
  session_status: string;
  total_students: number;
  present: number;
  absent: number;
  pending: number;
  attendance_percentage: number;
}

export interface GroupAttendanceOverview {
  group_id: number;
  group_name: string;
  sessions: GroupAttendanceSession[];
}

export interface GroupAttendanceOverviewResponse {
  success: boolean;
  message: string;
  data: GroupAttendanceOverview;
}
