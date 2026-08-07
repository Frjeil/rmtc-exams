export type Role = 'user' | 'admin' | 'supervisor';

export interface User {
  id: number;
  name: string;
  email: string;
  role: Role;
}

export interface Exam {
  id: number;
  title: string;
  date: string;
  vote?: number | null;
}

export interface EnrolledUser {
  id: number;
  name: string;
  email: string;
}

export interface MyVote {
  exam_id: number;
  exam_title: string;
  exam_date: string;
  student_name: string;
  student_email: string;
  vote: number;
  graded_at: string;
}

export interface LoginResponse {
  token: string;
  user: User;
}

export interface ApiResponse<T> {
  data: T;
}
