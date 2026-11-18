export type GroupStatus = 'planned' | 'active' | 'completed' | 'cancelled';

export interface GroupInstructor {
  id: number;
  name: string;
  email: string;
  pivot?: {
    is_primary: boolean;
  };
}

export interface Group {
  id: number;
  course_id: number;
  name: string;
  code: string;
  capacity: number;
  start_date: string | null;
  end_date: string | null;
  status: GroupStatus;
  notes: string | null;
  enrollments_count: number;
  instructors: GroupInstructor[];
  created_at: string;
  updated_at: string;
}

export interface GroupsApiResponse {
  success: boolean;
  message?: string;
  data: Group[];
}

export interface GroupApiResponse {
  success: boolean;
  message: string;
  data: Group;
}

export interface CreateGroupRequest {
  name: string;
  code: string;
  capacity: number;
  start_date?: string | null;
  end_date?: string | null;
  status?: GroupStatus;
  notes?: string | null;
}

export type UpdateGroupRequest = Partial<CreateGroupRequest>;
