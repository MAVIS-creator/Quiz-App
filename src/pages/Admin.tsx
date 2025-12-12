import { useState, useEffect } from 'react';
import Swal from 'sweetalert2';
import { getAllSessions, calculateStats, addTimeExtension } from '../utils/sessionStore';
import { getConversation, sendMessage } from '../utils/messaging';
import type { SessionData, ParticipantStats } from '../types/session';

export default function Admin() {
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const [sessions, setSessions] = useState<SessionData[]>([]);
  const [stats, setStats] = useState<ParticipantStats[]>([]);
  const [selectedParticipant, setSelectedParticipant] = useState<ParticipantStats | null>(null);
  const [messages, setMessages] = useState<any[]>([]);
  const [messageText, setMessageText] = useState('');
  const [defaultQuestionCount, setDefaultQuestionCount] = useState(40);
  const [examMinutes, setExamMinutes] = useState(60);
  const [snapshot, setSnapshot] = useState<string | null>(null);
  const [snapshotUpdated, setSnapshotUpdated] = useState<string | null>(null);

  const isSessionOnline = (session?: SessionData | null) => {
    if (!session || session.submitted) return false;
    if (!session.lastSaved) return false;
    const last = new Date(session.lastSaved).getTime();
    if (Number.isNaN(last)) return false;
    return Date.now() - last <= 15000; // consider offline after 15s without save
  };

  useEffect(() => {
    if (isAuthenticated) {
      loadData();
      fetchConfig();
      const interval = setInterval(loadData, 3000);
      return () => clearInterval(interval);
    }
  }, [isAuthenticated]);

  useEffect(() => {
    if (selectedParticipant) {
      (async () => {
        const conv = await getConversation('admin', selectedParticipant.matric || selectedParticipant.phone);
        setMessages(conv);
      })();
    }
  }, [selectedParticipant]);

  // Poll camera snapshot for the selected participant
  useEffect(() => {
    if (!selectedParticipant) {
      setSnapshot(null);
      setSnapshotUpdated(null);
      return;
    }

    const identifier = selectedParticipant.matric || selectedParticipant.phone;
    const loadSnapshot = async () => {
      try {
        const res = await fetch(`http://localhost:3001/api/snapshot/${identifier}`);
        const data = await res.json();
        setSnapshot(data.image || null);
        setSnapshotUpdated(data.timestamp || null);
      } catch (err) {
        console.error('Failed to load snapshot', err);
      }
    };

    loadSnapshot();
    const interval = setInterval(loadSnapshot, 1000);
    return () => clearInterval(interval);
  }, [selectedParticipant]);

  const fetchConfig = async () => {
    try {
      const response = await fetch('http://localhost:3001/api/config');
      const data = await response.json();
      setDefaultQuestionCount(data.questionCount || 40);
      setExamMinutes(data.examMinutes || 60);
    } catch (error) {
      console.error('Failed to fetch config:', error);
    }
  };

  const saveConfig = async () => {
    try {
      const res = await fetch('http://localhost:3001/api/config', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ questionCount: defaultQuestionCount, examMinutes })
      });
      if (!res.ok) {
        const errorData = await res.json().catch(() => ({ error: 'Unknown error' }));
        throw new Error(errorData.error || 'Failed to save config');
      }
      await res.json();
      Swal.fire({ title: 'Config saved!', icon: 'success', timer: 1500, showConfirmButton: false });
    } catch (err: any) {
      console.error('Save config error:', err);
      Swal.fire({ 
        title: 'Save failed', 
        text: err.message || 'Could not connect to server. Is it running?',
        icon: 'error' 
      });
    }
  };

  const loadData = async () => {
    try {
      const allSessions = await getAllSessions();
      setSessions(allSessions);
      setStats(allSessions.map(calculateStats));
    } catch (error) {
      console.error('Failed to load sessions:', error);
      setSessions([]);
      setStats([]);
    }
  };

  const handleLogin = () => {
    Swal.fire({
      title: 'Admin Login',
      html: `
        <div class="text-left">
          <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
          <input type="password" id="admin-password" class="swal2-input" placeholder="Enter admin password">
        </div>
      `,
      showCancelButton: true,
      confirmButtonText: 'Login',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#3b82f6',
      preConfirm: () => {
        const password = (document.getElementById('admin-password') as HTMLInputElement).value;
        if (password !== 'admin123') {
          Swal.showValidationMessage('Invalid password');
          return false;
        }
        return true;
      }
    }).then(result => {
      if (result.isConfirmed) {
        setIsAuthenticated(true);
        Swal.fire({
          title: 'Welcome!',
          text: 'Admin access granted',
          icon: 'success',
          timer: 1500,
          showConfirmButton: false
        });
      }
    });
  };

  const handleAddTime = (participant: ParticipantStats) => {
    Swal.fire({
      title: 'Add Time Extension',
      html: `
        <div class="text-left space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Participant</label>
            <p class="text-gray-900 font-semibold">${participant.name}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Additional Minutes</label>
            <input type="number" id="time-minutes" class="swal2-input" min="1" max="60" value="10" placeholder="Minutes">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Reason</label>
            <textarea id="time-reason" class="swal2-textarea" placeholder="Reason for extension"></textarea>
          </div>
        </div>
      `,
      showCancelButton: true,
      confirmButtonText: 'Add Time',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#10b981',
      preConfirm: () => {
        const minutes = parseInt((document.getElementById('time-minutes') as HTMLInputElement).value);
        const reason = (document.getElementById('time-reason') as HTMLTextAreaElement).value.trim();
        
        if (!minutes || minutes < 1) {
          Swal.showValidationMessage('Please enter a valid number of minutes');
          return false;
        }
        if (!reason) {
          Swal.showValidationMessage('Please provide a reason');
          return false;
        }
        
        return { minutes, reason };
      }
    }).then(result => {
      if (result.isConfirmed && result.value) {
        const identifier = participant.matric || participant.phone;
        addTimeExtension(identifier, result.value.minutes, result.value.reason).then(loadData);
      }
    });
  };

  const handleSendMessage = () => {
    if (!messageText.trim() || !selectedParticipant) return;
    
    const identifier = selectedParticipant.matric || selectedParticipant.phone;
    (async () => {
      await sendMessage('admin', identifier, messageText);
      setMessageText('');
      const conv = await getConversation('admin', identifier);
      setMessages(conv);
    })();
  };

  const handleImportQuestions = () => {
    Swal.fire({
      title: 'Import Questions',
      html: `<input type="file" id="md-file" accept=".md" class="block w-full text-sm text-gray-600">`,
      showCancelButton: true,
      confirmButtonText: 'Parse',
      confirmButtonColor: '#3b82f6',
      preConfirm: () => {
        const fileInput = document.getElementById('md-file') as HTMLInputElement;
        if (!fileInput.files || fileInput.files.length === 0) {
          Swal.showValidationMessage('Please select a file');
          return false;
        }
        return fileInput.files[0];
      }
    }).then(result => {
      if (result.isConfirmed && result.value) {
        const file = result.value as File;
        const reader = new FileReader();
        
        reader.onload = (e) => {
          const content = e.target?.result as string;
          const parsed = parseMarkdownQuestions(content);
          
          Swal.fire({
            title: `Found ${parsed.length} Questions`,
            html: `<textarea readonly class="w-full h-64 p-3 border rounded font-mono text-xs">${JSON.stringify(parsed, null, 2)}</textarea>`,
            confirmButtonText: 'Close',
            confirmButtonColor: '#3b82f6',
            width: '800px'
          });
        };
        
        reader.readAsText(file);
      }
    });
  };

  const parseMarkdownQuestions = (markdown: string) => {
    const questions = [];
    const lines = markdown.split('\n');
    let currentCategory = '';
    let currentQuestion = null;
    let currentOptions: string[] = [];
    let currentAnswer = '';
    let questionId = 1;

    for (let i = 0; i < lines.length; i++) {
      const line = lines[i].trim();
      
      if (line.startsWith('## ')) {
        currentCategory = line.substring(3).trim();
      } else if (/^\d+\.\s/.test(line)) {
        if (currentQuestion && currentOptions.length > 0) {
          questions.push({
            id: questionId++,
            category: currentCategory,
            prompt: currentQuestion,
            options: currentOptions,
            answer: currentAnswer || currentOptions[0]
          });
        }
        currentQuestion = line.replace(/^\d+\.\s/, '');
        currentOptions = [];
        currentAnswer = '';
      } else if (line.startsWith('- ')) {
        currentOptions.push(line.substring(2).trim());
      } else if (line.startsWith('Answer: ')) {
        currentAnswer = line.substring(8).trim();
      }
    }

    if (currentQuestion && currentOptions.length > 0) {
      questions.push({
        id: questionId,
        category: currentCategory,
        prompt: currentQuestion,
        options: currentOptions,
        answer: currentAnswer || currentOptions[0]
      });
    }

    return questions;
  };

  if (!isAuthenticated) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-purple-50 via-white to-blue-50 flex items-center justify-center p-4">
        <div className="bg-white rounded-lg shadow-xl p-8 max-w-md w-full text-center">
          <i className="bx bx-lock-alt text-6xl text-purple-500 mb-4"></i>
          <h1 className="text-3xl font-bold text-gray-800 mb-2">Admin Panel</h1>
          <p className="text-gray-600 mb-6">Login required</p>
          <button
            onClick={handleLogin}
            className="w-full flex items-center justify-center gap-2 px-6 py-3 bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition-colors font-semibold"
          >
            <i className="bx bx-log-in-circle text-xl"></i>
            Admin Login
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-purple-50 via-white to-blue-50 py-8 px-4 text-gray-800">
      <div className="max-w-7xl mx-auto">
        <div className="bg-white rounded-lg shadow-md p-6 mb-6">
          <div className="flex flex-col gap-4">
            <div className="flex justify-between items-center gap-4 flex-wrap">
              <div>
                <h1 className="text-3xl font-bold text-gray-800">Quiz App - Admin</h1>
                <p className="text-gray-600 mt-1">Live monitoring dashboard</p>
                <a
                  href="/proctor"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1 mt-1"
                >
                  <i className="bx bx-video"></i>
                  Open Full Proctor Dashboard
                  <i className="bx bx-link-external text-xs"></i>
                </a>
              </div>
              <button
                onClick={handleImportQuestions}
                className="flex items-center gap-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600"
              >
                <i className="bx bx-import"></i>
                Import Questions
              </button>
            </div>
            <div className="flex flex-wrap items-center gap-3">
              <div className="flex items-center gap-2">
                <label htmlFor="question-count" className="text-sm font-medium text-gray-700 flex items-center gap-1">
                  <i className="bx bx-list-ol text-blue-500"></i>
                  Questions
                </label>
                <input
                  id="question-count"
                  type="number"
                  min={1}
                  max={100}
                  value={defaultQuestionCount}
                  onChange={(e) => {
                    const val = e.target.value;
                    if (val === '' || val === '0') {
                      setDefaultQuestionCount(1);
                    } else {
                      const num = Number(val);
                      if (num >= 1 && num <= 100) {
                        setDefaultQuestionCount(num);
                      }
                    }
                  }}
                  onKeyDown={(e) => {
                    if (e.key === 'Backspace' && defaultQuestionCount < 10) {
                      e.preventDefault();
                      setDefaultQuestionCount(1);
                    }
                  }}
                  className="w-24 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
              <div className="flex items-center gap-2">
                <label htmlFor="exam-time" className="text-sm font-medium text-gray-700 flex items-center gap-1">
                  <i className="bx bx-time text-blue-500"></i>
                  Time (min)
                </label>
                <input
                  id="exam-time"
                  type="number"
                  min={5}
                  max={300}
                  value={examMinutes}
                  onChange={(e) => {
                    const val = e.target.value;
                    if (val === '' || Number(val) < 5) {
                      setExamMinutes(5);
                    } else {
                      const num = Number(val);
                      if (num >= 5 && num <= 300) {
                        setExamMinutes(num);
                      }
                    }
                  }}
                  onKeyDown={(e) => {
                    if (e.key === 'Backspace' && examMinutes < 10) {
                      e.preventDefault();
                      setExamMinutes(5);
                    }
                  }}
                  className="w-24 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
              <button
                onClick={saveConfig}
                className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm"
              >
                Save Config
              </button>
            </div>
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
          <div className="bg-white rounded-lg shadow-md p-6">
            <div className="flex items-center gap-3">
              <i className="bx bx-user-check text-3xl text-blue-500"></i>
              <div>
                <p className="text-sm text-gray-600">Participants</p>
                <p className="text-2xl font-bold">{sessions.length}</p>
              </div>
            </div>
          </div>
          <div className="bg-white rounded-lg shadow-md p-6">
            <div className="flex items-center gap-3">
              <i className="bx bx-check-circle text-3xl text-green-500"></i>
              <div>
                <p className="text-sm text-gray-600">Completed</p>
                <p className="text-2xl font-bold">{sessions.filter(s => s.submitted).length}</p>
              </div>
            </div>
          </div>
          <div className="bg-white rounded-lg shadow-md p-6">
            <div className="flex items-center gap-3">
              <i className="bx bx-time-five text-3xl text-yellow-500"></i>
              <div>
                <p className="text-sm text-gray-600">Active</p>
                <p className="text-2xl font-bold">{sessions.filter(isSessionOnline).length}</p>
              </div>
            </div>
          </div>
          <div className="bg-white rounded-lg shadow-md p-6">
            <div className="flex items-center gap-3">
              <i className="bx bx-error-circle text-3xl text-red-500"></i>
              <div>
                <p className="text-sm text-gray-600">Violations</p>
                <p className="text-2xl font-bold">
                  {(sessions.reduce((sum, s) => sum + s.violations, 0) / Math.max(sessions.length, 1)).toFixed(1)}
                </p>
              </div>
            </div>
          </div>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div className="lg:col-span-2 bg-white rounded-lg shadow-md overflow-hidden">
            <div className="p-6 border-b">
              <h2 className="text-lg font-semibold">Live Participant Monitor</h2>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead className="bg-gray-50 border-b">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Progress</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Questions</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Accuracy</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                  </tr>
                </thead>
                <tbody className="divide-y">
                  {stats.map((p, idx) => (
                    <tr key={idx} className="hover:bg-gray-50">
                      {(() => {
                        const session = sessions.find(s => (s.matric || s.phone) === (p.matric || p.phone));
                        const isOnline = isSessionOnline(session);
                        const statusLabel = session?.submitted ? 'Done' : isOnline ? 'Active' : 'Offline';
                        const statusClass = session?.submitted
                          ? 'bg-green-100 text-green-800'
                          : isOnline
                          ? 'bg-blue-100 text-blue-800'
                          : 'bg-gray-200 text-gray-700';
                        const lastSeen = session?.lastSaved ? new Date(session.lastSaved).toLocaleTimeString() : '—';
                        return (
                          <>
                            <td className="px-6 py-4">
                              <div className="flex items-center gap-3">
                                <div
                                  className="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold cursor-pointer hover:ring-2 hover:ring-blue-500"
                                  style={{ backgroundColor: `hsl(${(idx * 45) % 360}, 70%, 60%)` }}
                                  onClick={() => setSelectedParticipant(p)}
                                  title="Click to message"
                                >
                                  {p.name.charAt(0).toUpperCase()}
                                </div>
                                <div>
                                  <p className="font-medium">{p.name}</p>
                                  <p className="text-xs text-gray-600">{p.matric || p.phone}</p>
                                </div>
                              </div>
                            </td>
                            <td className="px-6 py-4">
                              <div className="flex items-center gap-2">
                                <div className="w-20 bg-gray-200 rounded-full h-2">
                                  <div className="bg-blue-600 h-2 rounded-full" style={{ width: `${p.progress}%` }}></div>
                                </div>
                                <span className="text-sm">{p.progress}%</span>
                              </div>
                            </td>
                            <td className="px-6 py-4">
                              <span className="text-sm font-medium">
                                {sessions.find(s => (s.matric || s.phone) === (p.matric || p.phone))?.questionCount || 40}
                              </span>
                            </td>
                            <td className="px-6 py-4 font-semibold">{p.accuracy}%</td>
                            <td className="px-6 py-4">
                              <div className="flex flex-col">
                                <span className={`px-2 py-1 text-xs rounded-full font-semibold ${statusClass}`}>
                                  {statusLabel}
                                </span>
                                <span className="text-[10px] text-gray-500 mt-1">Last seen: {lastSeen}</span>
                              </div>
                            </td>
                            <td className="px-6 py-4 space-y-1">
                              {!p.submitted && (
                                <button
                                  onClick={() => handleAddTime(p)}
                                  className="block w-full px-2 py-1 text-sm bg-green-500 text-white rounded hover:bg-green-600"
                                >
                                  Add Time
                                </button>
                              )}
                              <button
                                onClick={() => setSelectedParticipant(p)}
                                className="w-full px-2 py-1 text-sm bg-blue-500 text-white rounded hover:bg-blue-600"
                              >
                                Message
                              </button>
                            </td>
                          </>
                        );
                      })()}
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>

          {selectedParticipant && (
            <div className="bg-white rounded-lg shadow-md overflow-hidden flex flex-col max-h-96">
              <div className="p-4 border-b bg-blue-50">
                <div className="flex items-center gap-2">
                  <div className="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold" style={{ backgroundColor: `hsl(${Math.random() * 360}, 70%, 60%)` }}>
                    {selectedParticipant.name.charAt(0).toUpperCase()}
                  </div>
                  <p className="font-semibold">{selectedParticipant.name}</p>
                </div>
              </div>

              <div className="p-4 border-b flex items-center gap-4 bg-gray-50">
                <div className="flex-1">
                  <p className="text-sm font-semibold text-gray-700">Live Camera Preview</p>
                  <p className="text-xs text-gray-500">Refreshes ~1s for near-real-time view</p>
                  <p className="text-[11px] text-gray-500 mt-1">{snapshotUpdated ? `Last snapshot: ${new Date(snapshotUpdated).toLocaleTimeString()}` : 'No snapshot yet'}</p>
                </div>
                <div className="w-40 h-28 bg-gray-200 rounded overflow-hidden flex items-center justify-center border">
                  {snapshot ? (
                    <img src={snapshot} alt="Participant camera" className="w-full h-full object-cover" />
                  ) : (
                    <span className="text-xs text-gray-600">No feed</span>
                  )}
                </div>
              </div>

              <div className="flex-1 overflow-y-auto p-4 space-y-2 bg-gray-50">
                {messages.map((m) => {
                  const from = m.from ?? m.sender;
                  const isAdmin = from === 'admin';
                  return (
                    <div key={m.id || m.timestamp} className={`flex ${isAdmin ? 'justify-end' : 'justify-start'}`}>
                      <div className={`max-w-xs px-3 py-2 rounded-lg text-sm ${
                        isAdmin ? 'bg-blue-500 text-white' : 'bg-gray-300 text-gray-900'
                      }`}>
                        {m.text ?? m.body}
                      </div>
                    </div>
                  );
                })}
              </div>

              <div className="p-3 border-t flex gap-2">
                <input
                  type="text"
                  value={messageText}
                  onChange={(e) => setMessageText(e.target.value)}
                  onKeyPress={(e) => e.key === 'Enter' && handleSendMessage()}
                  placeholder="Message..."
                  className="flex-1 px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
                <button onClick={handleSendMessage} title="Send message" className="px-3 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 text-sm">
                  <i className="bx bx-send"></i>
                </button>
              </div>
            </div>
          )}
        </div>
      </div>

      <div className="mt-12 text-center pb-4">
        <p className="text-sm text-gray-600">
          © 2025 <span className="bg-gradient-to-r from-yellow-400 to-blue-400 bg-clip-text text-transparent font-semibold">MAVIS</span>. All rights reserved.
        </p>
      </div>
    </div>
  );
}
