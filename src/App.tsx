import { useEffect, useMemo, useRef, useState } from 'react';
import { questions, type Question } from './data/questions';

// Minimal FaceDetector typings to keep TypeScript happy on browsers that ship it.
type FaceDetectorResult = { boundingBox: DOMRectReadOnly };
type FaceDetectorConstructor = new (options?: {
  fastMode?: boolean;
  maxDetectedFaces?: number;
  scoreThreshold?: number;
}) => { detect: (image: CanvasImageSource) => Promise<FaceDetectorResult[]> };
type FaceStatus =
  | 'pending'
  | 'monitoring'
  | 'unsupported'
  | 'no-face'
  | 'ok'
  | 'error';

declare global {
  interface Window {
    FaceDetector?: FaceDetectorConstructor;
  }
}

const QUIZ_DURATION_SECONDS = 30 * 60;
const MAX_VIOLATIONS = 3;

const formatTime = (totalSeconds: number) => {
  const minutes = Math.floor(totalSeconds / 60)
    .toString()
    .padStart(2, '0');
  const seconds = (totalSeconds % 60).toString().padStart(2, '0');
  return `${minutes}:${seconds}`;
};

function App() {
  const [currentIndex, setCurrentIndex] = useState(0);
  const [answers, setAnswers] = useState<Record<number, string>>({});
  const [secondsLeft, setSecondsLeft] = useState(QUIZ_DURATION_SECONDS);
  const [showResults, setShowResults] = useState(false);
  const [violationCount, setViolationCount] = useState(0);
  const [violations, setViolations] = useState<string[]>([]);
  const [isLocked, setIsLocked] = useState(false);
  const [faceStatus, setFaceStatus] = useState<FaceStatus>('pending');

  const videoRef = useRef<HTMLVideoElement | null>(null);
  const faceDetectorRef = useRef<InstanceType<FaceDetectorConstructor> | null>(
    null
  );
  const missedFaceCountRef = useRef(0);
  const focusLossRef = useRef<number>(0);

  useEffect(() => {
    if (showResults || isLocked) return undefined;
    const interval = window.setInterval(() => {
      setSecondsLeft((prev: number) => {
        if (prev <= 1) {
          setShowResults(true);
          return 0;
        }
        return prev - 1;
      });
    }, 1000);
    return () => window.clearInterval(interval);
  }, [showResults, isLocked]);

  useEffect(() => {
    const handleVisibility = () => {
      if (document.hidden) recordViolation('Tab or window change detected');
    };
    const handleBlur = () => {
      // Avoid double counting blur+visibility in the same moment.
      const now = Date.now();
      if (now - focusLossRef.current < 400) return;
      focusLossRef.current = now;
      recordViolation('Window lost focus');
    };
    const handleBeforeUnload = (event: BeforeUnloadEvent) => {
      event.preventDefault();
      event.returnValue = '';
      return '';
    };

    document.addEventListener('visibilitychange', handleVisibility);
    window.addEventListener('blur', handleBlur);
    window.addEventListener('beforeunload', handleBeforeUnload);

    return () => {
      document.removeEventListener('visibilitychange', handleVisibility);
      window.removeEventListener('blur', handleBlur);
      window.removeEventListener('beforeunload', handleBeforeUnload);
    };
  }, []);

  useEffect(() => {
    // Tab/page/face guard
    let intervalId: number | undefined;
    let mediaStream: MediaStream | undefined;

    const startFaceMonitoring = async () => {
      if (!window.FaceDetector || !navigator.mediaDevices?.getUserMedia) {
        setFaceStatus('unsupported');
        return;
      }
      try {
        mediaStream = await navigator.mediaDevices.getUserMedia({ video: true });
        if (videoRef.current) {
          videoRef.current.srcObject = mediaStream;
          await videoRef.current.play();
        }
        faceDetectorRef.current = new window.FaceDetector({ maxDetectedFaces: 1 });
        setFaceStatus('monitoring');

        intervalId = window.setInterval(async () => {
          if (!faceDetectorRef.current || !videoRef.current) return;
          try {
            const faces = await faceDetectorRef.current.detect(videoRef.current);
            if (!faces || faces.length === 0) {
              const next = missedFaceCountRef.current + 1;
              missedFaceCountRef.current = next;
              if (next >= 3) {
                setFaceStatus('no-face');
                recordViolation('Face not detected for several checks');
                missedFaceCountRef.current = 0;
              }
            } else {
              missedFaceCountRef.current = 0;
              setFaceStatus('ok');
            }
          } catch (error) {
            console.error('Face detection error', error);
            setFaceStatus('error');
          }
        }, 4000);
      } catch (error) {
        console.warn('Camera permission or FaceDetector issue', error);
        setFaceStatus('error');
      }
    };

    startFaceMonitoring();

    return () => {
      if (intervalId) window.clearInterval(intervalId);
      if (mediaStream) mediaStream.getTracks().forEach((track) => track.stop());
    };
  }, []);

  const recordViolation = (reason: string) => {
    setViolations((prev: string[]) => [`${new Date().toLocaleTimeString()} · ${reason}`, ...prev].slice(0, 6));
    setViolationCount((prev: number) => {
      const next = prev + 1;
      if (next >= MAX_VIOLATIONS) {
        setIsLocked(true);
        setShowResults(true);
      }
      return next;
    });
  };

  const handleSelect = (question: Question, choice: string) => {
    if (showResults || isLocked) return;
    setAnswers((prev: Record<number, string>) => ({ ...prev, [question.id]: choice }));
  };

  const handleSubmit = () => {
    setShowResults(true);
  };

  const handlePrev = () => setCurrentIndex((prev: number) => Math.max(prev - 1, 0));
  const handleNext = () =>
    setCurrentIndex((prev: number) => Math.min(prev + 1, questions.length - 1));

  const score = useMemo(() => {
    return questions.reduce((total, q) => {
      if (answers[q.id] && answers[q.id] === q.answer) return total + 1;
      return total;
    }, 0);
  }, [answers]);

  const currentQuestion = questions[currentIndex];
  const progress = Math.round(((currentIndex + 1) / questions.length) * 100);
  const percentage = Math.round((score / questions.length) * 100);

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 text-slate-100 px-4 py-8">
      <div className="mx-auto max-w-5xl space-y-6">
        <header className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <div>
            <p className="text-sm uppercase tracking-[0.2em] text-slate-400">Secure Quiz</p>
            <h1 className="text-3xl font-bold">HTML/CSS Fundamentals</h1>
            <p className="text-slate-400">30-minute timer · {questions.length} questions · Auto lock after {MAX_VIOLATIONS} violations</p>
          </div>
          <div className="flex items-center gap-3">
            <div className="card-surface flex items-center gap-3 px-4 py-3">
              <div className="h-3 w-3 animate-pulse rounded-full bg-emerald-400" aria-hidden />
              <div>
                <p className="text-xs uppercase text-slate-400">Time left</p>
                <p className="text-xl font-semibold tabular-nums">{formatTime(secondsLeft)}</p>
              </div>
            </div>
            <div className="card-surface hidden items-center gap-2 px-4 py-3 sm:flex">
              <div className="flex -space-x-2">
                <span className="h-2 w-2 rounded-full bg-brand-400" aria-hidden />
                <span className="h-2 w-2 rounded-full bg-amber-400" aria-hidden />
              </div>
              <div>
                <p className="text-xs uppercase text-slate-400">Anti-cheat</p>
                <p className="text-sm font-semibold">Tab & face guard</p>
              </div>
            </div>
          </div>
        </header>

        <section className="grid gap-6 lg:grid-cols-[2fr_1fr]">
          <div className="card-surface p-5 sm:p-6">
            <div className="flex flex-wrap items-center gap-3">
              <div className="flex items-center gap-2 rounded-full bg-slate-800/70 px-3 py-1 text-xs font-semibold text-slate-200">
                <span className="h-2 w-2 rounded-full bg-brand-400" aria-hidden />
                Question {currentIndex + 1} of {questions.length}
              </div>
              <div className="flex items-center gap-2 rounded-full bg-slate-800/70 px-3 py-1 text-xs font-semibold text-slate-200">
                <span className="h-2 w-2 rounded-full bg-emerald-400" aria-hidden />
                {progress}% complete
              </div>
            </div>

            <div className="mt-4 h-2 w-full overflow-hidden rounded-full bg-slate-800">
              <div
                className="h-full bg-gradient-to-r from-brand-400 to-accent"
                style={{ width: `${progress}%` }}
                aria-hidden
              />
            </div>

            <div className="mt-6 space-y-4">
              <p className="text-xs uppercase tracking-[0.2em] text-slate-400">{currentQuestion.category}</p>
              <h2 className="text-2xl font-semibold leading-tight text-slate-50">{currentQuestion.prompt}</h2>
            </div>

            <div className="mt-6 grid gap-3">
              {currentQuestion.options.map((option) => {
                const selected = answers[currentQuestion.id] === option;
                const isCorrect = showResults && option === currentQuestion.answer;
                const isWrong = showResults && selected && !isCorrect;
                return (
                  <button
                    key={option}
                    onClick={() => handleSelect(currentQuestion, option)}
                    disabled={showResults || isLocked}
                    className={`w-full rounded-xl border px-4 py-3 text-left transition-colors duration-150 ${selected ? 'border-brand-400 bg-brand-400/10 text-brand-50' : 'border-slate-800 bg-slate-900/60 hover:border-brand-400/60 hover:bg-slate-900'} ${isCorrect ? 'border-emerald-400/70 bg-emerald-400/10' : ''} ${isWrong ? 'border-rose-400/70 bg-rose-400/10' : ''}`}
                  >
                    <span className="font-medium">{option}</span>
                  </button>
                );
              })}
            </div>

            <div className="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
              <div className="flex gap-2">
                <button
                  onClick={handlePrev}
                  disabled={currentIndex === 0 || isLocked}
                  className="button-primary bg-slate-800 text-slate-100 hover:bg-slate-700"
                >
                  Prev
                </button>
                <button
                  onClick={handleNext}
                  disabled={currentIndex === questions.length - 1 || isLocked}
                  className="button-primary"
                >
                  Next
                </button>
              </div>
              <div className="flex gap-2">
                <button
                  onClick={handleSubmit}
                  disabled={showResults || isLocked}
                  className="button-primary bg-emerald-500 hover:bg-emerald-400"
                >
                  Submit now
                </button>
                {isLocked && (
                  <span className="rounded-lg bg-rose-500/20 px-3 py-2 text-sm font-semibold text-rose-100">
                    Locked due to violations
                  </span>
                )}
              </div>
            </div>
          </div>

          <aside className="space-y-4">
            <div className="card-surface p-5 sm:p-6">
              <div className="flex items-center justify-between">
                <p className="text-sm font-semibold">Anti-cheating</p>
                <span className="rounded-full bg-slate-800 px-3 py-1 text-xs font-semibold text-slate-200">
                  {violationCount}/{MAX_VIOLATIONS} strikes
                </span>
              </div>
              <div className="mt-4 space-y-3 text-sm text-slate-300">
                <div className="flex items-center justify-between rounded-lg bg-slate-800/80 px-3 py-2">
                  <span className="flex items-center gap-2">
                    <span className="h-2 w-2 rounded-full bg-brand-400" aria-hidden />
                    Tab/Focus fence
                  </span>
                  <span className="text-xs text-emerald-300">Active</span>
                </div>
                <div className="flex items-center justify-between rounded-lg bg-slate-800/80 px-3 py-2">
                  <span className="flex items-center gap-2">
                    <span className="h-2 w-2 rounded-full bg-amber-400" aria-hidden />
                    Face presence
                  </span>
                  <span className="text-xs text-slate-200">{faceStatus}</span>
                </div>
                <div className="rounded-lg bg-slate-800/80 px-3 py-2 text-xs text-slate-400">
                  Leaving the tab, switching apps, or hiding face will add a strike. At {MAX_VIOLATIONS} strikes the quiz locks and auto-submits.
                </div>
              </div>
              <div className="mt-3 max-h-48 overflow-y-auto space-y-2 text-xs text-slate-300">
                {violations.length === 0 ? (
                  <p className="text-slate-500">No violations recorded.</p>
                ) : (
                  violations.map((item) => (
                    <div key={item} className="rounded-md bg-slate-800/70 px-3 py-2">
                      {item}
                    </div>
                  ))
                )}
              </div>
            </div>

            <div className="card-surface p-5 sm:p-6">
              <p className="text-sm font-semibold">Session notes</p>
              <ul className="mt-3 space-y-2 text-sm text-slate-300">
                <li>Keep your face in view; allow camera when prompted.</li>
                <li>Stay on this tab; switching windows adds strikes.</li>
                <li>Timer is 30 minutes. Auto-submit when time ends.</li>
              </ul>
            </div>

            {showResults && (
              <div className="card-surface p-5 sm:p-6">
                <p className="text-sm uppercase text-slate-400">Result</p>
                <h3 className="text-3xl font-bold text-slate-50">{score} / {questions.length}</h3>
                <p className="text-sm text-slate-300">{percentage}% accuracy</p>
                <div className="mt-4 flex items-center gap-2">
                  <div className="h-2 flex-1 overflow-hidden rounded-full bg-slate-800">
                    <div
                      className="h-full bg-gradient-to-r from-emerald-400 via-brand-400 to-accent"
                      style={{ width: `${percentage}%` }}
                      aria-hidden
                    />
                  </div>
                  <span className="text-xs font-semibold text-slate-200 tabular-nums">{percentage}%</span>
                </div>
              </div>
            )}
          </aside>
        </section>
      </div>

      <video
        ref={videoRef}
        className="pointer-events-none fixed bottom-4 right-4 h-20 w-20 rounded-lg border border-slate-800/70 bg-slate-950/90 object-cover opacity-0"
        muted
        playsInline
      />
    </div>
  );
}

export default App;
