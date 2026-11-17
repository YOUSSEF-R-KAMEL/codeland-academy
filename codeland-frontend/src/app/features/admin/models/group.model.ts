export interface Group {
  id: number;
  course_id: number;
  name: string;
  code: string;
  capacity: number;
  start_date: string | null;
  end_date: string | null;
  status: string;
}

export interface GroupsResponse {
  success: boolean;
  data: Group[];
}
