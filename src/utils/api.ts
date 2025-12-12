const env: any = (import.meta as any).env || {};
export const API_BASE: string = env.VITE_API_BASE || 'http://localhost:3001/api';
export const WS_BASE: string = env.VITE_WS_BASE || 'http://localhost:3001';
