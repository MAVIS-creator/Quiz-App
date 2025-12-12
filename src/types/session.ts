export type QuestionTiming = {
  questionId: number;
  timeSpent: number;
  timestamp: string;
};

export type TimeExtension = {
  minutesAdded: number;
  reason: string;
  timestamp: string;
  acknowledged?: boolean;
};

export type SessionData = {
  matric: string | null;
  phone: string;
  name: string;
  startTime: string;
  answers: Record<number, string>;
  questionTimings: QuestionTiming[];
  violations: number;
  submitted?: boolean;
  submittedAt?: string;
  lastSaved?: string;
  questionCount?: number;
  questionIds?: number[];
  examMinutes?: number;
};

export type ParticipantStats = {
  name: string;
  matric: string | null;
  phone: string;
  progress: number;
  accuracy: number;
  avgTimePerQuestion: number;
  violations: number;
  submitted: boolean;
};
