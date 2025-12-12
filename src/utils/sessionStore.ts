import type { SessionData, ParticipantStats, TimeExtension } from '../types/session';
import { questions } from '../data/questions';

const SESSIONS_KEY = 'quiz_sessions';
const EXTENSIONS_KEY = 'time_extensions';

export const saveSession = (session: SessionData) => {
  const identifier = session.matric || session.phone;
  const data = localStorage.getItem(SESSIONS_KEY);
  const sessions: Record<string, SessionData> = data ? JSON.parse(data) : {};
  sessions[identifier] = session;
  localStorage.setItem(SESSIONS_KEY, JSON.stringify(sessions));
};

export const getSession = (identifier: string): SessionData | null => {
  const data = localStorage.getItem(SESSIONS_KEY);
  const sessions: Record<string, SessionData> = data ? JSON.parse(data) : {};
  return sessions[identifier] || null;
};

export const getAllSessions = (): SessionData[] => {
  const data = localStorage.getItem(SESSIONS_KEY);
  const sessionsObj: Record<string, SessionData> = data ? JSON.parse(data) : {};
  return Object.values(sessionsObj);
};

export const clearSession = (identifier: string) => {
  const data = localStorage.getItem(SESSIONS_KEY);
  const sessions: Record<string, SessionData> = data ? JSON.parse(data) : {};
  delete sessions[identifier];
  localStorage.setItem(SESSIONS_KEY, JSON.stringify(sessions));
};

export const calculateStats = (session: SessionData): ParticipantStats => {
  const answered = Object.keys(session.answers).length;
  let correctAnswers = 0;

  // Calculate accuracy based on answers
  Object.entries(session.answers).forEach(([, answer]) => {
    const question = questions.find(q => q.id === parseInt('1'));
    if (question && question.answer === answer) {
      correctAnswers++;
    }
  });

  const totalTimeSpent = session.questionTimings.reduce(
    (sum, timing) => sum + timing.timeSpent,
    0
  );

  const avgTimePerQuestion =
    session.questionTimings.length > 0
      ? Math.round(totalTimeSpent / session.questionTimings.length)
      : 0;

  const progress = answered > 0 ? Math.round((answered / 40) * 100) : 0;

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

export const addTimeExtension = (identifier: string, minutes: number, reason: string) => {
  const extensions = getTimeExtensions();
  if (!extensions[identifier]) {
    extensions[identifier] = [];
  }
  
  const extension: TimeExtension = {
    minutesAdded: minutes,
    reason,
    timestamp: new Date().toISOString(),
    acknowledged: false
  };
  
  extensions[identifier].push(extension);
  localStorage.setItem(EXTENSIONS_KEY, JSON.stringify(extensions));
};

export const getTimeExtensions = (): Record<string, TimeExtension[]> => {
  const data = localStorage.getItem(EXTENSIONS_KEY);
  return data ? JSON.parse(data) : {};
};

export const getPendingExtension = (identifier: string): TimeExtension | null => {
  const extensions = getTimeExtensions();
  const userExtensions = extensions[identifier] || [];
  
  const pending = userExtensions.find(ext => !ext.acknowledged);
  return pending || null;
};

export const acknowledgeExtension = (identifier: string) => {
  const extensions = getTimeExtensions();
  const userExtensions = extensions[identifier] || [];
  
  if (userExtensions.length > 0) {
    userExtensions.forEach(ext => {
      ext.acknowledged = true;
    });
    extensions[identifier] = userExtensions;
    localStorage.setItem(EXTENSIONS_KEY, JSON.stringify(extensions));
  }
};
