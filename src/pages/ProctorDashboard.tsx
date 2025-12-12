import { useState, useEffect } from 'react';
import { io, Socket } from 'socket.io-client';
import Swal from 'sweetalert2';

type Violation = {
  type: string;
  timestamp: string;
  severity: 'warning' | 'alert';
  evidence?: string;
};

type StudentViolations = {
  identifier: string;
  violations: Violation[];
};

export default function ProctorDashboard() {
  const [socketRef, setSocketRef] = useState<Socket | null>(null);
  const [studentViolations, setStudentViolations] = useState<StudentViolations[]>([]);
  const [selectedStudent, setSelectedStudent] = useState<string | null>(null);
  const [autoRefresh, setAutoRefresh] = useState(true);

  useEffect(() => {
    const socket = io('http://localhost:3001', {
      reconnection: true,
      reconnectionAttempts: 5,
    });

    socket.on('connect', () => {
      socket.emit('admin_join');
    });

    socket.on('active_students', (students) => {
      setStudentViolations(students);
    });

    socket.on('violation_update', (data) => {
      setStudentViolations((prev) => {
        const idx = prev.findIndex((s) => s.identifier === data.identifier);
        if (idx >= 0) {
          prev[idx].violations = data.violations;
          return [...prev];
        }
        return [...prev, data];
      });
    });

    setSocketRef(socket);

    return () => {
      socket.disconnect();
    };
  }, []);

  const issuePunishment = (identifier: string | null, punishmentType: string, value?: number) => {
    if (!identifier || !socketRef) return;

    socketRef.emit('punishment_command', {
      identifier,
      punishmentType,
      value,
      message: getPunishmentMessage(punishmentType, value),
    });

    Swal.fire({
      title: 'Command Sent',
      text: `${punishmentType} issued to student`,
      icon: 'success',
      timer: 1500,
      showConfirmButton: false,
    });
  };

  const getPunishmentMessage = (type: string, value?: number) => {
    switch (type) {
      case 'warn':
        return '⚠️ Please focus on the exam';
      case 'deduct_time':
        return `⏳ ${value} minutes deducted`;
      case 'deduct_points':
        return `📉 ${value} points deducted`;
      case 'kick':
        return '🚫 You have been disqualified';
      default:
        return 'Action taken';
    }
  };

  const selectedData = studentViolations.find((s) => s.identifier === selectedStudent);
  const alertCount = selectedData?.violations.filter((v) => v.severity === 'alert').length || 0;

  return (
    <div className="min-h-screen bg-gray-100 p-6">
      <div className="max-w-7xl mx-auto">
        <div className="bg-white rounded-lg shadow-md p-6 mb-6">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-3xl font-bold text-gray-800">Proctor Command Center</h1>
              <p className="text-gray-600 mt-1">Real-time integrity monitoring & enforcement</p>
            </div>
            <label className="flex items-center gap-2 text-sm text-gray-700">
              <input
                type="checkbox"
                checked={autoRefresh}
                onChange={(e) => setAutoRefresh(e.target.checked)}
              />
              Auto-refresh violations
            </label>
          </div>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-6">
          {studentViolations.map((student) => {
            const totalViolations = student.violations.length;
            const alertViolations = student.violations.filter((v) => v.severity === 'alert').length;
            const status = alertViolations > 2 ? 'critical' : alertViolations > 0 ? 'caution' : 'clear';

            const statusColor =
              status === 'critical'
                ? 'bg-red-100 border-red-300'
                : status === 'caution'
                ? 'bg-yellow-100 border-yellow-300'
                : 'bg-green-100 border-green-300';

            return (
              <div
                key={student.identifier}
                onClick={() => setSelectedStudent(student.identifier)}
                className={`cursor-pointer border-2 rounded-lg p-4 transition-all ${
                  selectedStudent === student.identifier
                    ? 'ring-2 ring-blue-500'
                    : ''
                } ${statusColor}`}
              >
                <div className="flex items-center justify-between mb-2">
                  <p className="font-semibold text-gray-800">{student.identifier}</p>
                  <span
                    className={`w-3 h-3 rounded-full ${
                      status === 'critical'
                        ? 'bg-red-600 animate-pulse'
                        : status === 'caution'
                        ? 'bg-yellow-600'
                        : 'bg-green-600'
                    }`}
                  ></span>
                </div>
                <p className="text-2xl font-bold text-gray-900">{totalViolations}</p>
                <p className="text-xs text-gray-600">violations • {alertViolations} alerts</p>
              </div>
            );
          })}
        </div>

        {selectedData && (
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div className="lg:col-span-2 bg-white rounded-lg shadow-md p-6">
              <h2 className="text-lg font-semibold mb-4">Violation Record</h2>

              {selectedData.violations.length === 0 ? (
                <p className="text-gray-600">No violations recorded</p>
              ) : (
                <div className="space-y-3 max-h-96 overflow-y-auto">
                  {selectedData.violations.map((v, idx) => (
                    <div
                      key={idx}
                      className={`border-l-4 p-3 rounded ${
                        v.severity === 'alert'
                          ? 'border-red-500 bg-red-50'
                          : 'border-yellow-500 bg-yellow-50'
                      }`}
                    >
                      <div className="flex items-center justify-between mb-2">
                        <p className="font-semibold text-gray-800">{v.type}</p>
                        <span
                          className={`text-xs px-2 py-1 rounded font-semibold ${
                            v.severity === 'alert'
                              ? 'bg-red-200 text-red-800'
                              : 'bg-yellow-200 text-yellow-800'
                          }`}
                        >
                          {v.severity}
                        </span>
                      </div>
                      <p className="text-xs text-gray-600">
                        {new Date(v.timestamp).toLocaleTimeString()}
                      </p>
                      {v.evidence && (
                        <img
                          src={v.evidence}
                          alt="Evidence"
                          className="mt-2 w-full h-32 object-cover rounded border"
                        />
                      )}
                    </div>
                  ))}
                </div>
              )}
            </div>

            <div className="bg-white rounded-lg shadow-md p-6">
              <h3 className="text-lg font-semibold mb-4">Actions</h3>
              <div className="space-y-2">
                <button
                  onClick={() => issuePunishment(selectedStudent, 'warn')}
                  className="w-full px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600 text-sm font-semibold flex items-center gap-2 justify-center"
                >
                  <i className="bx bx-info-circle"></i>
                  Warn
                </button>
                <button
                  onClick={() => issuePunishment(selectedStudent, 'deduct_time', 5)}
                  className="w-full px-4 py-2 bg-orange-500 text-white rounded hover:bg-orange-600 text-sm font-semibold flex items-center gap-2 justify-center"
                >
                  <i className="bx bx-time-five"></i>
                  -5 Min
                </button>
                <button
                  onClick={() => issuePunishment(selectedStudent, 'deduct_points', 10)}
                  className="w-full px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 text-sm font-semibold flex items-center gap-2 justify-center"
                >
                  <i className="bx bx-down-arrow-circle"></i>
                  -10 Pts
                </button>
                <button
                  onClick={() => {
                    if (window.confirm('Disqualify this student?')) {
                      issuePunishment(selectedStudent, 'kick');
                    }
                  }}
                  className="w-full px-4 py-2 bg-red-700 text-white rounded hover:bg-red-800 text-sm font-semibold flex items-center gap-2 justify-center"
                >
                  <i className="bx bx-x-circle"></i>
                  Kick
                </button>
              </div>

              <div className="mt-6 pt-4 border-t">
                <p className="text-xs text-gray-600 mb-2">Summary</p>
                <div className="text-sm space-y-1 text-gray-700">
                  <p>
                    Total Violations:{' '}
                    <span className="font-bold">{selectedData.violations.length}</span>
                  </p>
                  <p>
                    Alert Level:{' '}
                    <span className={`font-bold ${alertCount > 2 ? 'text-red-600' : 'text-yellow-600'}`}>
                      {alertCount > 2 ? 'Critical' : alertCount > 0 ? 'Caution' : 'Clear'}
                    </span>
                  </p>
                </div>
              </div>
            </div>
          </div>
        )}

        {!selectedStudent && (
          <div className="bg-white rounded-lg shadow-md p-12 text-center">
            <i className="bx bx-smile text-6xl text-gray-400 mb-4"></i>
            <p className="text-gray-600">Select a student to view violations and manage enforcement</p>
          </div>
        )}
      </div>
    </div>
  );
}
