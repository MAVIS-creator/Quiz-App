import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { participants } from '../data/participants';
import Swal from 'sweetalert2';

export default function Login() {
  const [matricInput, setMatricInput] = useState('');
  const [phoneInput, setPhoneInput] = useState('');
  const navigate = useNavigate();

  const handleLogin = () => {
    const clean = (value: string) => value.replace(/\s+/g, '').toLowerCase();
    const cleanDigits = (value: string) => value.replace(/\D/g, '');

    const normalizedMatric = clean(matricInput);
    const normalizedPhone = cleanDigits(phoneInput);

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

    localStorage.setItem('currentUser', JSON.stringify(found));
    navigate('/quiz');
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 text-slate-100 px-4 py-10">
      <div className="mx-auto grid max-w-4xl gap-6">
        <div className="card-surface p-6 sm:p-8">
          <div className="flex items-center gap-3 mb-4">
            <i className="bx bx-shield-quarter text-4xl text-brand-400"></i>
            <div>
              <p className="text-sm uppercase tracking-[0.2em] text-slate-400">Secure Access</p>
              <h1 className="text-3xl font-bold text-slate-50">HTML/CSS Quiz Login</h1>
            </div>
          </div>
          <p className="mt-2 text-slate-300">Only the listed students can start. Enter matric number or phone. The person without a matric number must use their phone.</p>

          <div className="mt-6 grid gap-4 sm:grid-cols-2">
            <div className="space-y-2">
              <label className="text-sm text-slate-300 flex items-center gap-2">
                <i className="bx bx-id-card text-brand-400"></i>
                Matric number
              </label>
              <input
                value={matricInput}
                onChange={(e) => setMatricInput(e.target.value)}
                onKeyDown={(e) => e.key === 'Enter' && handleLogin()}
                className="w-full rounded-lg border border-slate-800 bg-slate-900 px-4 py-3 text-slate-100 focus:border-brand-400 focus:outline-none"
                placeholder="e.g. 2025000831"
              />
            </div>
            <div className="space-y-2">
              <label className="text-sm text-slate-300 flex items-center gap-2">
                <i className="bx bx-phone text-brand-400"></i>
                Phone (digits only)
              </label>
              <input
                value={phoneInput}
                onChange={(e) => setPhoneInput(e.target.value)}
                onKeyDown={(e) => e.key === 'Enter' && handleLogin()}
                className="w-full rounded-lg border border-slate-800 bg-slate-900 px-4 py-3 text-slate-100 focus:border-brand-400 focus:outline-none"
                placeholder="e.g. 8084434242"
              />
            </div>
          </div>

          <div className="mt-6 flex flex-wrap items-center gap-3">
            <button onClick={handleLogin} className="button-primary flex items-center gap-2">
              <i className="bx bx-log-in-circle"></i>
              Enter quiz
            </button>
            <div className="rounded-full bg-slate-800/80 px-3 py-2 text-xs text-slate-300 flex items-center gap-2">
              <i className="bx bx-lock-alt text-brand-400"></i>
              Anti-cheat active: tab fence + face presence
            </div>
          </div>
        </div>

        <div className="card-surface p-6">
          <p className="text-sm font-semibold text-slate-200 flex items-center gap-2">
            <i className="bx bx-list-ul text-brand-400"></i>
            Roster preview
          </p>
          <p className="text-sm text-slate-400">Only these students can sign in. Phone works for all; phone-only for the NIL matric entry.</p>
          <div className="mt-4 grid gap-2 sm:grid-cols-2">
            {participants.map((p) => (
              <div key={`${p.name}-${p.phone}`} className="rounded-lg border border-slate-800/70 bg-slate-900/60 px-3 py-2 text-sm text-slate-200">
                <p className="font-semibold flex items-center gap-2">
                  <i className="bx bx-user text-brand-400 text-xs"></i>
                  {p.name}
                </p>
                <p className="text-slate-400 text-xs">Matric: {p.matric ?? 'Use phone'}</p>
                <p className="text-slate-400 text-xs">Phone: {p.phone}</p>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}
