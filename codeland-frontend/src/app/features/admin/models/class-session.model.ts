export type ClassSessionStatus = 'scheduled' | 'completed' | 'cancelled';

export interface ClassSession {
  id: number;
  group_id: number;
  title: string;
  starts_at: string;
  ends_at: string;
  meeting_url: string | null;
  notes: string | null;
  status: ClassSessionStatus;
  created_at?: string;
  updated_at?: string;
}

export interface ClassSessionPayload {
  title: string;
  starts_at: string;
  ends_at: string;
  meeting_url: string | null;
  notes: string | null;
}

export interface ClassSessionsData {
  sessions?: ClassSession[];
  [key: string]: unknown;
}

export interface ClassSessionsApiResponse {
  success: boolean;
  message: string;
  data: ClassSession[] | ClassSessionsData;
}
