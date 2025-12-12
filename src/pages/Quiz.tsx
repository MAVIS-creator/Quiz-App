import { useState, useEffect, useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import Swal from 'sweetalert2';
import { questions } from '../data/questions';
import { saveSession, getPendingExtension, acknowledgeExtension } from '../utils/sessionStore';
import { useSmartProctor } from '../hooks/useSmartProctor';
import { API_BASE } from '../utils/api';
import type { QuestionTiming } from '../types/session';

function shuffleArray<T>(array: T[]): T[] {
  const shuffled = [...array];
  for (let i = shuffled.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
  }
  return shuffled;
}

export default function Quiz() {
  const navigate = useNavigate();
  const [questionCount, setQuestionCount] = useState(40);
  const [isLoading, setIsLoading] = useState(true);
  const [timeLeft, setTimeLeft] = useState(3600);
  const [currentQuestionIndex, setCurrentQuestionIndex] = useState(0);
  const [answers, setAnswers] = useState<Record<number, string>>({});
  const [quizQuestions, setQuizQuestions] = useState<any[]>([]);
  const [shuffledOptions, setShuffledOptions] = useState<any[]>([]);
  const [questionTimings, setQuestionTimings] = useState<QuestionTiming[]>([]);
  const [questionStartTime, setQuestionStartTime] = useState(Date.now());
  const [questionIds, setQuestionIds] = useState<number[]>([]);
  const [cameraError, setCameraError] = useState<string | null>(null);
  const videoRef = useRef<HTMLVideoElement | null>(null);

  const sessionData = JSON.parse(sessionStorage.getItem('quizSession') || 'null');

  // Smart Proctor system
  const {
    videoRef: proctorVideoRef,
    violations: proctorViolations,
    alertMessage,
    startMonitoring,
    setAlertMessage,
  } = useSmartProctor(sessionData?.matric || sessionData?.phone || null, true);

  useEffect(() => {
    const fetchConfig = async () => {
      try {
        const response = await fetch(`${API_BASE}/config`);
        const data = await response.json();
        const count = data.questionCount || 40;
        const examMinutes = data.examMinutes || 60;
        setQuestionCount(count);
        setTimeLeft(examMinutes * 60);

        const shuffled = shuffleArray(questions).slice(0, count);
        setQuizQuestions(shuffled);
        setQuestionIds(shuffled.map(q => q.id));
        setShuffledOptions(shuffled.map(q => shuffleArray(q.options)));
        setIsLoading(false);
      } catch (error) {
        console.error('Failed to fetch question count:', error);
        const count = 40;
        setQuestionCount(count);
        const shuffled = shuffleArray(questions).slice(0, count);
        setQuizQuestions(shuffled);
        setShuffledOptions(shuffled.map(q => shuffleArray(q.options)));
        setIsLoading(false);
      }
    };
    
    fetchConfig();
    const countInterval = setInterval(() => {
      fetch(`${API_BASE}/config`)
        .then(res => res.json())
        .then(data => {
          const newCount = data.questionCount || 40;
          const newExamMinutes = data.examMinutes || 60;
          if (answers && Object.keys(answers).length === 0 && newCount !== questionCount) {
            setQuestionCount(newCount);
            const shuffled = shuffleArray(questions).slice(0, newCount);
            setQuizQuestions(shuffled);
            setQuestionIds(shuffled.map(q => q.id));
            setShuffledOptions(shuffled.map(q => shuffleArray(q.options)));
          }
          // Only adjust time if we are early in the exam to avoid jarring resets
          setTimeLeft(prev => {
            const target = newExamMinutes * 60;
            if (prev > target) return target; // cap at configured time
            return prev;
          });
        })
        .catch(() => {});
    }, 5000);

    return () => clearInterval(countInterval);
  }, [answers, questionCount]);

  useEffect(() => {
    if (!sessionData) {
      navigate('/');
      return;
    }

    // Initial save so admin sees participant immediately
    saveSession({
      ...sessionData,
      answers,
      violations: proctorViolations.length,
      questionTimings,
      questionCount,
      questionIds,
      examMinutes: Math.round(timeLeft / 60),
      lastSaved: new Date().toISOString()
    });

    // Check for pending time extensions
    const checkExtension = async () => {
      const pending = await getPendingExtension(sessionData.matric || sessionData.phone);
      if (pending) {
        Swal.fire({
          title: 'Time Extended!',
          html: `<div class="text-left">
            <p class="mb-2"><strong>Additional Time:</strong> ${pending.minutesAdded} minutes</p>
            <p class="mb-2"><strong>Reason:</strong> ${pending.reason}</p>
            <p class="text-sm text-gray-600">Your timer has been updated.</p>
          </div>`,
          icon: 'info',
          confirmButtonText: 'Got it!',
          confirmButtonColor: '#3b82f6'
        }).then(() => {
          acknowledgeExtension(sessionData.matric || sessionData.phone);
          setTimeLeft(prev => prev + (pending.minutesAdded * 60));
        });
      }
    };

    checkExtension();
    const extensionCheck = setInterval(checkExtension, 5000);

    // Visibility change detection
    const handleVisibilityChange = () => {
      if (document.hidden) {
        setAlertMessage('⚠️ Tab switch detected - violation recorded!');
        setTimeout(() => setAlertMessage(null), 3000);
        if (proctorViolations.length >= 2) {
          Swal.fire({
            title: 'Quiz Terminated',
            text: 'You have exceeded the maximum number of violations (switching tabs/windows).',
            icon: 'error',
            confirmButtonColor: '#ef4444'
          }).then(() => {
            submitQuiz(true);
          });
        } else {
          Swal.fire({
            title: 'Warning!',
            text: `Tab switch detected. Violation ${proctorViolations.length + 1} of 3.`,
            icon: 'warning',
            confirmButtonColor: '#f59e0b',
            timer: 3000
          });
        }
      }
    };

    document.addEventListener('visibilitychange', handleVisibilityChange);

    return () => {
      document.removeEventListener('visibilitychange', handleVisibilityChange);
      clearInterval(extensionCheck);
    };
  }, [navigate, sessionData]);

  useEffect(() => {
    if (timeLeft <= 0) {
      Swal.fire({
        title: 'Time Up!',
        text: 'The quiz time has expired. Your answers will be submitted.',
        icon: 'info',
        confirmButtonColor: '#3b82f6'
      }).then(() => {
        submitQuiz(true);
      });
      return;
    }

    const timer = setInterval(() => {
      setTimeLeft(prev => prev - 1);
    }, 1000);

    return () => clearInterval(timer);
  }, [timeLeft]);

  // Smart Proctor initialization
  useEffect(() => {
    if (sessionData && !cameraError) {
      startMonitoring().catch((err) => {
        console.error('Proctor startup error:', err);
        setCameraError('Proctor system failed to initialize');
      });
    }
  }, [sessionData]);

  // Alert message cleanup
  useEffect(() => {
    if (alertMessage) {
      const timer = setTimeout(() => setAlertMessage(null), 3000);
      return () => clearTimeout(timer);
    }
  }, [alertMessage, setAlertMessage]);
  useEffect(() => {
    if (!sessionData) return;
    let stream: MediaStream | null = null;
    let captureInterval: number | undefined;

    const startCamera = async () => {
      try {
        stream = await navigator.mediaDevices.getUserMedia({
          video: {
            width: { ideal: 320, max: 480 },
            height: { ideal: 240, max: 360 },
            frameRate: { ideal: 2, max: 3 }
          },
          audio: false
        });
        if (videoRef.current) {
          videoRef.current.srcObject = stream;
          await videoRef.current.play();
        }

        const capture = async () => {
          const video = videoRef.current;
          if (!video || video.videoWidth === 0 || video.videoHeight === 0) return;

          const canvas = document.createElement('canvas');
          canvas.width = 320;
          canvas.height = Math.max(180, Math.round((video.videoHeight / video.videoWidth) * 320));
          const ctx = canvas.getContext('2d');
          if (!ctx) return;
          ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
          const dataUrl = canvas.toDataURL('image/jpeg', 0.4);

          try {
            await fetch(`${API_BASE}/snapshot`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({
                identifier: sessionData.matric || sessionData.phone,
                image: dataUrl,
              })
            });
          } catch (err) {
            // ignore upload errors to avoid breaking quiz
          }
        };

        capture();
        captureInterval = window.setInterval(capture, 500);
      } catch (err: any) {
        setCameraError(err?.message || 'Unable to access camera');
      }
    };

    startCamera();

    return () => {
      if (captureInterval) clearInterval(captureInterval);
      if (stream) {
        stream.getTracks().forEach(t => t.stop());
      }
    };
  }, [sessionData]);

  useEffect(() => {
    // Save session every 5 seconds for near real-time admin view
    const saveInterval = setInterval(async () => {
      if (sessionData) {
        await saveSession({
          ...sessionData,
          answers,
          violations: proctorViolations.length,
          questionTimings,
          questionCount,
          questionIds,
          examMinutes: Math.round(timeLeft / 60),
          lastSaved: new Date().toISOString()
        });
      }
    }, 5000);

    return () => clearInterval(saveInterval);
  }, [answers, proctorViolations, questionTimings, sessionData, questionCount]);

  const handleSelectOption = (option: string) => {
    // Record timing for this question
    const timeSpent = (Date.now() - questionStartTime) / 1000;
    const questionId = quizQuestions[currentQuestionIndex].id;
    
    setQuestionTimings(prev => {
      const existing = prev.filter(qt => qt.questionId !== questionId);
      return [...existing, {
        questionId,
        timeSpent,
        timestamp: new Date().toISOString()
      }];
    });

    setAnswers(prev => ({
      ...prev,
      [currentQuestionIndex]: option
    }));
  };

  const handleNext = () => {
    if (currentQuestionIndex < quizQuestions.length - 1) {
      setCurrentQuestionIndex(prev => prev + 1);
      setQuestionStartTime(Date.now());
    }
  };

  const handlePrevious = () => {
    if (currentQuestionIndex > 0) {
      setCurrentQuestionIndex(prev => prev - 1);
      setQuestionStartTime(Date.now());
    }
  };

  const submitQuiz = (forced = false) => {
    if (!forced) {
      const unanswered = quizQuestions.length - Object.keys(answers).length;
      if (unanswered > 0) {
        Swal.fire({
          title: 'Incomplete Quiz',
          text: `You have ${unanswered} unanswered questions. Submit anyway?`,
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Yes, Submit',
          cancelButtonText: 'Keep Working',
          confirmButtonColor: '#3b82f6',
          cancelButtonColor: '#6b7280'
        }).then(result => {
          if (result.isConfirmed) {
            finalizeSubmission();
          }
        });
        return;
      }
    }
    
    finalizeSubmission();
  };

  const finalizeSubmission = () => {
    if (sessionData) {
      saveSession({
        ...sessionData,
        answers,
        violations: proctorViolations.length,
        questionTimings,
        questionCount,
        questionIds,
        examMinutes: Math.round(timeLeft / 60),
        submitted: true,
        submittedAt: new Date().toISOString(),
        lastSaved: new Date().toISOString()
      });
    }

    Swal.fire({
      title: 'Quiz Submitted!',
      text: 'Your answers have been recorded. Thank you for participating.',
      icon: 'success',
      confirmButtonColor: '#10b981'
    }).then(() => {
      sessionStorage.removeItem('quizSession');
      navigate('/');
    });
  };

  const formatTime = (seconds: number) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
  };

  if (!sessionData) {
    return null;
  }

  const currentQuestion = quizQuestions[currentQuestionIndex];
  const currentOptions = shuffledOptions[currentQuestionIndex];
  const progress = ((currentQuestionIndex + 1) / quizQuestions.length) * 100;

  if (isLoading) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 flex items-center justify-center">
        <div className="bg-white rounded-lg shadow-md p-8 text-center">
          <i className="bx bx-loader-alt bx-spin text-6xl text-blue-500 mb-4"></i>
          <p className="text-lg text-gray-700">Loading quiz...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 py-8 px-4">
      <div className="max-w-4xl mx-auto">
        {/* Header */}
        <div className="bg-white rounded-lg shadow-md p-6 mb-6">
          <div className="flex justify-between items-center mb-4">
            <div>
              <h1 className="text-2xl font-bold text-gray-800">HTML & CSS Quiz</h1>
              <p className="text-gray-600">Participant: {sessionData.name}</p>
            </div>
            <div className="text-right">
              <div className="flex items-center gap-2 text-lg font-semibold">
                <i className="bx bx-time-five text-2xl text-blue-500"></i>
                <span className={timeLeft < 300 ? 'text-red-600' : 'text-gray-800'}>
                  {formatTime(timeLeft)}
                </span>
              </div>
              {proctorViolations.length > 0 && (
                <div className="flex items-center gap-1 text-red-600 text-sm mt-1">
                  <i className="bx bx-error-circle"></i>
                  <span>Violations: {proctorViolations.length}/3</span>
                </div>
              )}
            </div>
          </div>

          {/* Progress Bar */}
          <div className="w-full bg-gray-200 rounded-full h-2">
            <div
              className="bg-blue-600 h-2 rounded-full transition-all duration-300"
              style={{ width: `${progress}%` } as React.CSSProperties}
            ></div>
          </div>
          <p className="text-sm text-gray-600 mt-2">
            Question {currentQuestionIndex + 1} of {quizQuestions.length}
          </p>
        </div>

        {/* Question Card */}
        <div className="bg-white rounded-lg shadow-md p-8 mb-6">
          <div className="flex items-start gap-3 mb-6">
            <i className="bx bx-question-mark text-3xl text-purple-500 mt-1"></i>
            <div className="flex-1">
              <div className="text-sm text-gray-500 mb-2">{currentQuestion.category}</div>
              <h2 className="text-xl font-semibold text-gray-800 leading-relaxed">
                {currentQuestion.prompt}
              </h2>
            </div>
          </div>

          <div className="space-y-3">
            {currentOptions.map((option: string, idx: number) => {
              const isSelected = answers[currentQuestionIndex] === option;
              return (
                <button
                  key={idx}
                  onClick={() => handleSelectOption(option)}
                  className={`w-full text-left p-4 rounded-lg border-2 transition-all ${
                    isSelected
                      ? 'border-blue-500 bg-blue-50'
                      : 'border-gray-200 hover:border-blue-300 hover:bg-gray-50'
                  }`}
                >
                  <div className="flex items-center gap-3">
                    <div className={`w-5 h-5 rounded-full border-2 flex items-center justify-center ${
                      isSelected ? 'border-blue-500 bg-blue-500' : 'border-gray-300'
                    }`}>
                      {isSelected && <i className="bx bx-check text-white text-sm"></i>}
                    </div>
                    <span className="text-gray-700">{option}</span>
                  </div>
                </button>
              );
            })}
          </div>
        </div>

        {/* Navigation */}
        <div className="flex justify-between items-center">
          <button
            onClick={handlePrevious}
            disabled={currentQuestionIndex === 0}
            className="flex items-center gap-2 px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            <i className="bx bx-chevron-left text-xl"></i>
            Previous
          </button>

          <div className="flex gap-3">
            {currentQuestionIndex === quizQuestions.length - 1 ? (
              <button
                onClick={() => submitQuiz(false)}
                className="flex items-center gap-2 px-8 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors font-semibold"
              >
                <i className="bx bx-check-circle text-xl"></i>
                Submit Quiz
              </button>
            ) : (
              <button
                onClick={handleNext}
                className="flex items-center gap-2 px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
              >
                Next
                <i className="bx bx-chevron-right text-xl"></i>
              </button>
            )}
          </div>
        </div>

        {/* Question Grid */}
        <div className="mt-8 bg-white rounded-lg shadow-md p-6">
          <h3 className="text-lg font-semibold text-gray-800 mb-4">Question Navigator</h3>
          <div className="grid grid-cols-8 gap-2">
            {quizQuestions.map((_, idx) => (
              <button
                key={idx}
                onClick={() => {
                  setCurrentQuestionIndex(idx);
                  setQuestionStartTime(Date.now());
                }}
                className={`aspect-square rounded-lg text-sm font-medium transition-all ${
                  idx === currentQuestionIndex
                    ? 'bg-blue-500 text-white'
                    : answers[idx]
                    ? 'bg-green-100 text-green-700 border-2 border-green-300'
                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                }`}
              >
                {idx + 1}
              </button>
            ))}
          </div>
        </div>
      </div>

          <div className="flex items-center gap-3 bg-gray-50 border rounded-lg p-3 mb-4">
            <div className="w-3 h-3 rounded-full bg-green-500 animate-pulse"></div>
            <div className="flex-1">
              <p className="text-sm font-semibold text-gray-800">Smart Proctor Active</p>
              <p className="text-xs text-gray-600">AI monitoring for integrity • {proctorViolations.length} alert(s)</p>
              {alertMessage && (
                <p className="text-xs text-red-600 mt-1 font-semibold animate-pulse">{alertMessage}</p>
              )}
              {cameraError && <p className="text-xs text-orange-600 mt-1">⚠️ {cameraError}</p>}
            </div>
            {proctorViolations.length > 0 && (
              <div className="flex items-center gap-1 bg-red-100 text-red-800 px-2 py-1 rounded text-xs font-semibold">
                <i className="bx bx-alert"></i>
                {proctorViolations.length}
              </div>
            )}
            <video ref={proctorVideoRef} className="hidden" playsInline muted></video>
          </div>

      {/* Footer */}
      <footer className="mt-8 text-center pb-4">
        <p className="text-sm text-gray-600">
          © 2025 <span className="bg-gradient-to-r from-yellow-400 to-blue-400 bg-clip-text text-transparent font-semibold">MAVIS</span>. All rights reserved.
        </p>
      </footer>
    </div>
  );
}
