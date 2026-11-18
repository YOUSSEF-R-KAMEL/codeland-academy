export interface AttendanceCheck {
  check_number: number;
  is_present: boolean | null;
  is_recorded: boolean;
  marked_at?: string | null;
  notes?: string | null;
}

export type AttendanceFinalStatus = 'present' | 'absent' | 'pending';

export interface AttendanceStudent {
  student_id: number;
  name: string;
  username: string;
  checks: AttendanceCheck[];
  final_status: AttendanceFinalStatus;
}

export interface SessionAttendanceRoster {
  session_id: number;
  session_title: string;
  students: AttendanceStudent[];
}

export interface RecordAttendanceStudent {
  student_id: number;
  is_present: boolean;
}

export interface RecordAttendancePayload {
  check_number: number;
  students: RecordAttendanceStudent[];
}

export interface RecordAttendanceResponse {
  session_id: number;
  check_number: number;
}

export interface StudentAttendanceSession {
  session_id: number;
  session_title: string;
  course_lesson: string | null;
  group: {
    id: number;
    name: string;
  };
  starts_at: string;
  ends_at: string;
  session_status: string;
  checks: AttendanceCheck[];
  final_status: AttendanceFinalStatus;
}

export interface StudentAttendanceResponse {
  student_id: number;
  sessions: StudentAttendanceSession[];
}
