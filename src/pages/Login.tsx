import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { participants } from '../data/participants';
import Swal from 'sweetalert2';

export default function Login() {
  const [credential, setCredential] = useState('');
  const navigate = useNavigate();

  const handleLogin = () => {
    const raw = credential.trim();
    const clean = (value: string) => value.replace(/\s+/g, '').toLowerCase();
    const cleanDigits = (value: string) => value.replace(/\D/g, '');

    const normalizedMatric = clean(raw);
    const normalizedPhone = cleanDigits(raw);

    const found = participants.find((p) => {
      const matricMatch = p.matric ? clean(p.matric) === normalizedMatric : false;
      const phoneMatch = cleanDigits(p.phone) === normalizedPhone && normalizedPhone.length > 0;
      return matricMatch || phoneMatch;
    });

    if (!found) {
      Swal.fire({
        icon: 'error',
        title: 'Access Denied',
        text: 'Not on the roster. Use your matric number or phone (for the no-matric entry).',
        confirmButtonColor: '#6366f1',
      });
      return;
    }

    const sessionPayload = {
      matric: found.matric,
      phone: found.phone,
      name: found.name,
      startTime: new Date().toISOString(),
      answers: {},
      questionTimings: [],
      violations: 0,
      submitted: false
    };

    sessionStorage.setItem('quizSession', JSON.stringify(sessionPayload));
    localStorage.setItem('currentUser', JSON.stringify(found));
    setCredential('');
    navigate('/quiz');
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 text-slate-100 flex items-center justify-center px-4">
      <div className="w-full max-w-md">
        <div className="card-surface p-8">
          <h1 className="text-3xl font-bold text-center text-slate-50 mb-2">Quiz App</h1>
          <p className="text-center text-slate-400 mb-8">Please fill in the required fields</p>

          <div className="space-y-4">
            <div className="space-y-2">
              <label className="text-sm text-slate-300 flex items-center gap-2">
                <i className="bx bx-id-card text-brand-400"></i>
                Matric or phone
              </label>
              <input
                value={credential}
                onChange={(e) => setCredential(e.target.value)}
                onKeyDown={(e) => e.key === 'Enter' && handleLogin()}
                className="w-full rounded-lg border border-slate-800 bg-slate-900 px-4 py-3 text-slate-100 focus:border-brand-400 focus:outline-none"
                placeholder="e.g. 2025000831 or 8084434242"
              />
            </div>
          </div>

          <button onClick={handleLogin} className="mt-8 w-full button-primary flex items-center justify-center gap-2">
            <i className="bx bx-log-in-circle"></i>
            Enter quiz
          </button>
        </div>
        
        {/* Footer */}
        <div className="mt-8 text-center">
          <p className="text-sm text-slate-400">
            © 2025 <span className="bg-gradient-to-r from-yellow-400 to-blue-400 bg-clip-text text-transparent font-semibold">MAVIS</span>. All rights reserved.
          </p>
        </div>
      </div>
    </div>
  );
}
