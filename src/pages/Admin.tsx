import { useState, useEffect } from 'react';
import Swal from 'sweetalert2';
import { getAllSessions, calculateStats, addTimeExtension } from '../utils/sessionStore';
import type { SessionData, ParticipantStats } from '../types/session';

export default function Admin() {
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const [sessions, setSessions] = useState<SessionData[]>([]);
  const [stats, setStats] = useState<ParticipantStats[]>([]);

  useEffect(() => {
    if (isAuthenticated) {
      loadData();
      const interval = setInterval(loadData, 5000);
      return () => clearInterval(interval);
    }
  }, [isAuthenticated]);

  const loadData = () => {
    const allSessions = getAllSessions();
    setSessions(allSessions);
    setStats(allSessions.map(calculateStats));
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
            <textarea id="time-reason" class="swal2-textarea" placeholder="Reason for extension (required)"></textarea>
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
          Swal.showValidationMessage('Please provide a reason for the extension');
          return false;
        }
        
        return { minutes, reason };
      }
    }).then(result => {
      if (result.isConfirmed && result.value) {
        const identifier = participant.matric || participant.phone;
        addTimeExtension(identifier, result.value.minutes, result.value.reason);
        
        Swal.fire({
          title: 'Time Extended!',
          html: `<p>Added <strong>${result.value.minutes} minutes</strong> to ${participant.name}'s quiz.</p>`,
          icon: 'success',
          confirmButtonColor: '#10b981'
        });
        
        loadData();
      }
    });
  };

  const handleImportQuestions = () => {
    Swal.fire({
      title: 'Import Questions from Markdown',
      html: `
        <div class="text-left space-y-3">
          <p class="text-sm text-gray-600">Upload a .md file with questions in this format:</p>
          <pre class="bg-gray-100 p-3 rounded text-xs overflow-x-auto">## Category Name
1. Question text here?
   - Option A
   - Option B
   - Option C
   - Option D
   Answer: Option A</pre>
          <input type="file" id="md-file" accept=".md" class="block w-full text-sm text-gray-600">
        </div>
      `,
      showCancelButton: true,
      confirmButtonText: 'Parse File',
      cancelButtonText: 'Cancel',
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
            title: 'Parsed Questions',
            html: `
              <div class="text-left">
                <p class="mb-3">Found <strong>${parsed.length}</strong> questions. Copy the JSON below:</p>
                <textarea readonly class="w-full h-64 p-3 border rounded font-mono text-xs">${JSON.stringify(parsed, null, 2)}</textarea>
              </div>
            `,
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
        if (currentQuestion && currentOptions.length > 0 && currentAnswer) {
          questions.push({
            id: questionId++,
            category: currentCategory,
            question: currentQuestion,
            options: currentOptions,
            correctAnswer: currentAnswer
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

    if (currentQuestion && currentOptions.length > 0 && currentAnswer) {
      questions.push({
        id: questionId,
        category: currentCategory,
        question: currentQuestion,
        options: currentOptions,
        correctAnswer: currentAnswer
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
          <p className="text-gray-600 mb-6">Login required to access quiz analytics</p>
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
    <div className="min-h-screen bg-gradient-to-br from-purple-50 via-white to-blue-50 py-8 px-4">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="bg-white rounded-lg shadow-md p-6 mb-6">
          <div className="flex justify-between items-center">
            <div>
              <h1 className="text-3xl font-bold text-gray-800 flex items-center gap-3">
                <i className="bx bx-bar-chart-alt-2 text-purple-500"></i>
                Quiz Analytics Dashboard
              </h1>
              <p className="text-gray-600 mt-1">Monitor participant progress and performance</p>
            </div>
            <button
              onClick={handleImportQuestions}
              className="flex items-center gap-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
            >
              <i className="bx bx-import"></i>
              Import Questions
            </button>
          </div>
        </div>

        {/* Summary Cards */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
          <div className="bg-white rounded-lg shadow-md p-6">
            <div className="flex items-center gap-3">
              <i className="bx bx-user-check text-3xl text-blue-500"></i>
              <div>
                <p className="text-sm text-gray-600">Total Participants</p>
                <p className="text-2xl font-bold text-gray-800">{sessions.length}</p>
              </div>
            </div>
          </div>
          <div className="bg-white rounded-lg shadow-md p-6">
            <div className="flex items-center gap-3">
              <i className="bx bx-check-circle text-3xl text-green-500"></i>
              <div>
                <p className="text-sm text-gray-600">Completed</p>
                <p className="text-2xl font-bold text-gray-800">
                  {sessions.filter(s => s.submitted).length}
                </p>
              </div>
            </div>
          </div>
          <div className="bg-white rounded-lg shadow-md p-6">
            <div className="flex items-center gap-3">
              <i className="bx bx-time-five text-3xl text-yellow-500"></i>
              <div>
                <p className="text-sm text-gray-600">In Progress</p>
                <p className="text-2xl font-bold text-gray-800">
                  {sessions.filter(s => !s.submitted).length}
                </p>
              </div>
            </div>
          </div>
          <div className="bg-white rounded-lg shadow-md p-6">
            <div className="flex items-center gap-3">
              <i className="bx bx-error-circle text-3xl text-red-500"></i>
              <div>
                <p className="text-sm text-gray-600">Avg Violations</p>
                <p className="text-2xl font-bold text-gray-800">
                  {(sessions.reduce((sum, s) => sum + s.violations, 0) / sessions.length || 0).toFixed(1)}
                </p>
              </div>
            </div>
          </div>
        </div>

        {/* Participant Table */}
        <div className="bg-white rounded-lg shadow-md overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gray-50 border-b">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Participant
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Progress
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Accuracy
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Avg Time/Q
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Violations
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {stats.map((participant, idx) => (
                  <tr key={idx} className="hover:bg-gray-50">
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="flex items-center gap-3">
                        <i className="bx bx-user-circle text-2xl text-gray-400"></i>
                        <div>
                          <p className="font-medium text-gray-900">{participant.name}</p>
                          <p className="text-sm text-gray-500">
                            {participant.matric || participant.phone}
                          </p>
                        </div>
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="flex items-center gap-2">
                        <div className="w-24 bg-gray-200 rounded-full h-2">
                          <div
                            className="bg-blue-600 h-2 rounded-full"
                            style={{ width: `${participant.progress}%` }}
                          ></div>
                        </div>
                        <span className="text-sm text-gray-600">{participant.progress}%</span>
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className={`font-semibold ${
                        participant.accuracy >= 70 ? 'text-green-600' :
                        participant.accuracy >= 50 ? 'text-yellow-600' :
                        'text-red-600'
                      }`}>
                        {participant.accuracy}%
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-gray-700">
                      {participant.avgTimePerQuestion}s
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className={`px-2 py-1 text-xs font-semibold rounded-full ${
                        participant.violations === 0 ? 'bg-green-100 text-green-800' :
                        participant.violations < 3 ? 'bg-yellow-100 text-yellow-800' :
                        'bg-red-100 text-red-800'
                      }`}>
                        {participant.violations}
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      {participant.submitted ? (
                        <span className="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                          Submitted
                        </span>
                      ) : (
                        <span className="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                          Active
                        </span>
                      )}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      {!participant.submitted && (
                        <button
                          onClick={() => handleAddTime(participant)}
                          className="flex items-center gap-1 px-3 py-1 text-sm bg-green-500 text-white rounded hover:bg-green-600 transition-colors"
                        >
                          <i className="bx bx-plus-circle"></i>
                          Add Time
                        </button>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  );
}
