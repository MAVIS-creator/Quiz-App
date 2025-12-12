export type Message = {
  sender: string;
  receiver: string;
  body: string;
  timestamp: string;
  read: boolean;
};

import { API_BASE } from './api';

export const sendMessage = async (from: string, to: string, text: string) => {
  await fetch(`${API_BASE}/messages`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ sender: from, receiver: to, body: text })
  });
};

export const getConversation = async (participant1: string, participant2: string): Promise<Message[]> => {
  const res = await fetch(`${API_BASE}/messages/${participant1}/${participant2}`);
  if (!res.ok) return [];
  return await res.json();
};

export const markAsRead = async (sender: string, receiver: string) => {
  await fetch(`${API_BASE}/messages/mark-read`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ sender, receiver })
  });
};

export const getUnreadCount = async (userId: string): Promise<number> => {
  const res = await fetch(`${API_BASE}/messages/${userId}/admin`);
  if (!res.ok) return 0;
  const msgs: Message[] = await res.json();
  return msgs.filter(m => m.receiver === userId && !m.read).length;
};
