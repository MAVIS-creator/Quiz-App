import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import Swal from 'sweetalert2';
import { questions } from '../data/questions';
import { saveSession, getPendingExtension, acknowledgeExtension } from '../utils/sessionStore';
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
  const [timeLeft, setTimeLeft] = useState(3600); // 60 minutes
  const [currentQuestionIndex, setCurrentQuestionIndex] = useState(0);
  const [answers, setAnswers] = useState<Record<number, string>>({});
  const [violations, setViolations] = useState(0);
  const [quizQuestions] = useState(() => shuffleArray(questions).slice(0, 40));
  const [shuffledOptions] = useState(() => 
    quizQuestions.map(q => shuffleArray(q.options))
  );
  const [questionTimings, setQuestionTimings] = useState<QuestionTiming[]>([]);
  const [questionStartTime, setQuestionStartTime] = useState(Date.now());

  const sessionData = JSON.parse(sessionStorage.getItem('quizSession') || 'null');
  
  useEffect(() => {
    if (!sessionData) {
      navigate('/');
      return;
    }

    // Check for pending time extensions
    const checkExtension = () => {
      const pending = getPendingExtension(sessionData.matric || sessionData.phone);
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
        setViolations(prev => {
          const newCount = prev + 1;
          if (newCount >= 3) {
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
              text: `Tab switch detected. Violation ${newCount} of 3.`,
              icon: 'warning',
              confirmButtonColor: '#f59e0b',
              timer: 3000
            });
          }
          return newCount;
        });
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

  useEffect(() => {
    // Save session every 10 seconds
    const saveInterval = setInterval(() => {
      if (sessionData) {
        saveSession({
          ...sessionData,
          answers,
          violations,
          questionTimings,
          lastSaved: new Date().toISOString()
        });
      }
    }, 10000);

    return () => clearInterval(saveInterval);
  }, [answers, violations, questionTimings, sessionData]);

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
        violations,
        questionTimings,
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
              {violations > 0 && (
                <div className="flex items-center gap-1 text-red-600 text-sm mt-1">
                  <i className="bx bx-error-circle"></i>
                  <span>Violations: {violations}/3</span>
                </div>
              )}
            </div>
          </div>

          {/* Progress Bar */}
          <div className="w-full bg-gray-200 rounded-full h-2">
            <div 
              className="bg-blue-600 h-2 rounded-full transition-all duration-300"
              style={{ width: `${progress}%` }}
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
            {currentOptions.map((option, idx) => {
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
    </div>
  );
}
