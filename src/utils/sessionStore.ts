import { questions } from '../data/questions';
import type { SessionData, ParticipantStats, TimeExtension } from '../types/session';

const API_BASE = 'http://localhost:3001/api';

export const saveSession = async (session: SessionData) => {
  await fetch(`${API_BASE}/sessions`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(session)
  });
};

export const getAllSessions = async (): Promise<SessionData[]> => {
  const res = await fetch(`${API_BASE}/sessions`);
  if (!res.ok) throw new Error('Failed to load sessions');
  return await res.json();
};

export const calculateStats = (session: SessionData): ParticipantStats => {
  const answered = Object.keys(session.answers || {}).length;
  let correctAnswers = 0;

  if (session.questionIds && Array.isArray(session.questionIds)) {
    session.questionIds.forEach((id: number, idx: number) => {
      const q = questions.find((q: any) => q.id === id);
      const ans = (session.answers || {})[idx];
      if (q && ans && q.answer === ans) correctAnswers++;
    });
  }

  const totalTimeSpent = (session.questionTimings || []).reduce(
    (sum, timing) => sum + timing.timeSpent,
    0
  );

  const avgTimePerQuestion =
    (session.questionTimings || []).length > 0
      ? Math.round(totalTimeSpent / (session.questionTimings || []).length)
      : 0;

  const denominator = session.questionIds?.length || session.questionCount || 40;
  const progress = answered > 0 && denominator > 0 ? Math.round((answered / denominator) * 100) : 0;

  return {
    name: session.name,
    matric: session.matric,
    phone: session.phone,
    accuracy: answered > 0 ? Math.round((correctAnswers / answered) * 100) : 0,
    avgTimePerQuestion,
    violations: session.violations,
    progress,
    submitted: session.submitted || false,
  };
};

export const addTimeExtension = async (identifier: string, minutes: number, reason: string) => {
  await fetch(`${API_BASE}/time-extension`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ identifier, minutesAdded: minutes, reason })
  });
};

export const getPendingExtension = async (identifier: string): Promise<TimeExtension | null> => {
  const res = await fetch(`${API_BASE}/time-extension/${identifier}`);
  if (!res.ok) return null;
  return await res.json();
};

export const acknowledgeExtension = async (identifier: string) => {
  await fetch(`${API_BASE}/time-extension/acknowledge`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ identifier })
  });
};
