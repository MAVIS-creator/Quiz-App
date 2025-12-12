import { useEffect, useRef, useState } from 'react';
import { io, Socket } from 'socket.io-client';
import {
  loadCocoModel,
  detectObjectViolations,
  detectHeadTilt,
  detectAudioViolation,
  sendViolationAlert,
  captureScreenshot,
  type ViolationType,
  type ProctorAlert,
} from '../utils/proctoring';

export const useSmartProctor = (
  sessionIdentifier: string | null,
  enabled: boolean = true
) => {
  const socketRef = useRef<Socket | null>(null);
  const videoRef = useRef<HTMLVideoElement | null>(null);
  const audioContextRef = useRef<AudioContext | null>(null);
  const analyserRef = useRef<AnalyserNode | null>(null);
  const mediaRecorderRef = useRef<MediaRecorder | null>(null);
  const cocoModelRef = useRef<any>(null);

  const [violations, setViolations] = useState<ProctorAlert[]>([]);
  const [isMonitoring, setIsMonitoring] = useState(false);
  const [alertMessage, setAlertMessage] = useState<string | null>(null);

  const violationCounterRef = useRef<Map<ViolationType, number>>(new Map());
  const lastAlertRef = useRef<Map<ViolationType, number>>(new Map());

  useEffect(() => {
    if (!enabled || !sessionIdentifier) return;

    // Initialize Socket.io
    socketRef.current = io('http://localhost:3001', {
      reconnection: true,
      reconnectionDelay: 1000,
      reconnectionAttempts: 5,
    });

    socketRef.current.on('connect', () => {
      socketRef.current?.emit('student_join', { identifier: sessionIdentifier });
      setIsMonitoring(true);
    });

    socketRef.current.on('receive_punishment', (data) => {
      handlePunishment(data);
    });

    socketRef.current.on('disconnect', () => {
      setIsMonitoring(false);
    });

    return () => {
      socketRef.current?.disconnect();
    };
  }, [sessionIdentifier, enabled]);

  const handlePunishment = (punishment: any) => {
    switch (punishment.type) {
      case 'warn':
        setAlertMessage(punishment.message || 'Warning: Stay focused!');
        document.documentElement.style.backgroundColor = 'rgba(255, 255, 0, 0.2)';
        setTimeout(() => {
          document.documentElement.style.backgroundColor = '';
        }, 2000);
        break;

      case 'deduct_time':
        setAlertMessage(`⏳ ${punishment.value} minutes deducted`);
        break;

      case 'deduct_points':
        setAlertMessage(`📉 ${punishment.value} points deducted`);
        break;

      case 'kick':
        setAlertMessage('🚫 You have been disqualified!');
        setTimeout(() => {
          sessionStorage.removeItem('quizSession');
          window.location.href = '/';
        }, 2000);
        break;
    }
  };

  const checkViolations = async () => {
    if (!videoRef.current || !enabled) return;

    try {
      // Load COCO model if needed
      if (!cocoModelRef.current) {
        cocoModelRef.current = await loadCocoModel();
      }

      // Check for object violations
      if (cocoModelRef.current && videoRef.current.readyState === HTMLMediaElement.HAVE_ENOUGH_DATA) {
        const { violations: detectedViolations } = await detectObjectViolations(
          videoRef.current,
          cocoModelRef.current
        );

        for (const vType of detectedViolations as ViolationType[]) {
          incrementViolationCounter(vType);
        }
      }

      // Check head tilt
      const { lookingAway } = detectHeadTilt(videoRef.current);
      if (lookingAway) {
        incrementViolationCounter('looking_away');
      }

      // Check audio
      if (analyserRef.current) {
        const { isSpeaking, isWhispering } = detectAudioViolation(
          audioContextRef.current!,
          analyserRef.current
        );

        if (isSpeaking) {
          incrementViolationCounter('speaking');
        }
        if (isWhispering) {
          incrementViolationCounter('whispering');
        }
      }
    } catch (err) {
      console.error('Proctor check error:', err);
    }
  };

  const incrementViolationCounter = (violationType: ViolationType) => {
    const map = violationCounterRef.current;
    const current = map.get(violationType) || 0;
    map.set(violationType, current + 1);

    // 3-second rule: only alert if violation persists
    if (current >= 3) {
      const lastAlert = lastAlertRef.current.get(violationType) || 0;
      if (Date.now() - lastAlert > 5000) {
        triggerAlert(violationType);
        lastAlertRef.current.set(violationType, Date.now());
        map.set(violationType, 0); // Reset counter
      }
    }
  };

  const triggerAlert = async (violationType: ViolationType) => {
    const screenshot = captureScreenshot(videoRef.current!);
    const severity = violationType === 'speaking' || violationType === 'phone' ? 'alert' : 'warning';

    const alert: ProctorAlert = {
      violationType,
      timestamp: new Date().toISOString(),
      severity,
      evidence: screenshot,
    };

    setViolations((prev) => [...prev, alert]);
    setAlertMessage(`⚠️ Violation detected: ${violationType}`);

    // Send to admin via Socket
    await sendViolationAlert(socketRef.current, sessionIdentifier || '', alert);
  };

  const startMonitoring = async () => {
    try {
      const stream = await navigator.mediaDevices.getUserMedia({
        video: {
          width: { ideal: 320, max: 480 },
          height: { ideal: 240, max: 360 },
          frameRate: { ideal: 2, max: 3 },
        },
        audio: true,
      });

      if (videoRef.current) {
        videoRef.current.srcObject = stream;
        await videoRef.current.play();
      }

      // Setup audio analysis
      const audioCtx = new (window.AudioContext || (window as any).webkitAudioContext)();
      audioContextRef.current = audioCtx;

      const analyser = audioCtx.createAnalyser();
      analyser.fftSize = 256;
      analyserRef.current = analyser;

      const source = audioCtx.createMediaStreamSource(stream);
      source.connect(analyser);

      // Setup media recorder for audio evidence
      const audioStream = new MediaStream(stream.getAudioTracks());
      const recorder = new MediaRecorder(audioStream);
      mediaRecorderRef.current = recorder;

      // Check violations every 1 second (3-frame rule at 2fps)
      const monitorInterval = setInterval(checkViolations, 1000);

      return () => {
        clearInterval(monitorInterval);
        stream.getTracks().forEach((t) => t.stop());
      };
    } catch (err) {
      console.error('Failed to start monitoring:', err);
      setAlertMessage('Camera/Microphone access required for proctoring');
    }
  };

  return {
    videoRef,
    isMonitoring,
    violations,
    alertMessage,
    startMonitoring,
    setAlertMessage,
  };
};
