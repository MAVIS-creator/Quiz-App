export type Message = {
  id: string;
  from: string; // admin or participant identifier
  to: string; // recipient identifier
  text: string;
  timestamp: string;
  read: boolean;
};

const MESSAGES_KEY = 'quiz_messages';

export const sendMessage = (from: string, to: string, text: string) => {
  const messages = getMessages();
  const newMessage: Message = {
    id: `msg_${Date.now()}`,
    from,
    to,
    text,
    timestamp: new Date().toISOString(),
    read: false
  };
  messages.push(newMessage);
  localStorage.setItem(MESSAGES_KEY, JSON.stringify(messages));
  return newMessage;
};

export const getMessages = (): Message[] => {
  const data = localStorage.getItem(MESSAGES_KEY);
  return data ? JSON.parse(data) : [];
};

export const getConversation = (participant1: string, participant2: string): Message[] => {
  const messages = getMessages();
  return messages.filter(
    m =>
      (m.from === participant1 && m.to === participant2) ||
      (m.from === participant2 && m.to === participant1)
  );
};

export const markAsRead = (messageId: string) => {
  const messages = getMessages();
  const updated = messages.map(m =>
    m.id === messageId ? { ...m, read: true } : m
  );
  localStorage.setItem(MESSAGES_KEY, JSON.stringify(updated));
};

export const getUnreadCount = (userId: string): number => {
  const messages = getMessages();
  return messages.filter(m => m.to === userId && !m.read).length;
};
