import * as cocoSsd from '@tensorflow-models/coco-ssd';
import type { Socket } from 'socket.io-client';

export type ViolationType = 'phone' | 'book' | 'person' | 'looking_away' | 'no_face' | 'speaking' | 'whispering';

export type ProctorAlert = {
  violationType: ViolationType;
  timestamp: string;
  severity: 'warning' | 'alert';
  evidence?: string; // base64 image or audio blob
};

let cocoModel: any = null;
let isModelLoaded = false;

export const loadCocoModel = async () => {
  if (isModelLoaded) return cocoModel;
  try {
    cocoModel = await cocoSsd.load();
    isModelLoaded = true;
    return cocoModel;
  } catch (err) {
    console.error('Failed to load COCO model:', err);
    return null;
  }
};

export const detectObjectViolations = async (
  videoElement: HTMLVideoElement,
  model: any
): Promise<{ violations: string[]; confidence: number }> => {
  const violations: string[] = [];

  try {
    const predictions = await model.estimateObjects(videoElement);

    for (const prediction of predictions) {
      const className = prediction.class.toLowerCase();

      if (
        className.includes('phone') ||
        className.includes('cell') ||
        className.includes('mobile')
      ) {
        violations.push('phone');
      }

      if (className.includes('book') || className.includes('notebook')) {
        violations.push('book');
      }

      if (
        className.includes('person') &&
        predictions.length > 1
      ) {
        violations.push('person');
      }
    }

    return { violations, confidence: 0.85 };
  } catch (err) {
    console.error('Detection error:', err);
    return { violations, confidence: 0 };
  }
};

export const detectHeadTilt = (videoElement: HTMLVideoElement): { lookingAway: boolean; direction: string } => {
  // Simplified head tilt detection based on video frame analysis
  // In production, use @tensorflow-models/face-landmarks-detection
  const canvas = document.createElement('canvas');
  canvas.width = videoElement.videoWidth;
  canvas.height = videoElement.videoHeight;
  const ctx = canvas.getContext('2d');

  if (!ctx) return { lookingAway: false, direction: 'center' };

  ctx.drawImage(videoElement, 0, 0);
  const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
  const data = imageData.data;

  // Detect skin tone on left vs right (simple heuristic)
  let leftBrightness = 0,
    rightBrightness = 0;
  for (let i = 0; i < data.length; i += 4) {
    const r = data[i],
      g = data[i + 1],
      b = data[i + 2];
    const brightness = (r + g + b) / 3;
    const pixelIndex = i / 4;
    const x = pixelIndex % canvas.width;

    if (x < canvas.width / 2) {
      leftBrightness += brightness;
    } else {
      rightBrightness += brightness;
    }
  }

  const diff = Math.abs(leftBrightness - rightBrightness);
  const lookingAway = diff > 10000;
  const direction = leftBrightness > rightBrightness ? 'left' : 'right';

  return { lookingAway, direction };
};

export const detectAudioViolation = (
  _audioContext: AudioContext,
  analyser: AnalyserNode
): { isSpeaking: boolean; isWhispering: boolean; level: number } => {
  const dataArray = new Uint8Array(analyser.frequencyBinCount);
  analyser.getByteFrequencyData(dataArray);

  let sum = 0;
  for (let i = 0; i < dataArray.length; i++) {
    sum += dataArray[i];
  }
  const level = sum / dataArray.length;

  const isSpeaking = level > 50;
  const isWhispering = level > 15 && level <= 50;

  return { isSpeaking, isWhispering, level };
};

export const recordAudioClip = async (mediaRecorder: MediaRecorder, durationMs: number = 10000) => {
  return new Promise<Blob>((resolve) => {
    const chunks: BlobPart[] = [];
    const tempRecorder = mediaRecorder;

    const onDataAvailable = (e: BlobEvent) => {
      chunks.push(e.data);
    };

    tempRecorder.addEventListener('dataavailable', onDataAvailable);
    tempRecorder.start();

    setTimeout(() => {
      tempRecorder.stop();
      tempRecorder.removeEventListener('dataavailable', onDataAvailable);
      const blob = new Blob(chunks, { type: 'audio/webm' });
      resolve(blob);
    }, durationMs);
  });
};

export const captureScreenshot = (videoElement: HTMLVideoElement): string => {
  const canvas = document.createElement('canvas');
  canvas.width = videoElement.videoWidth;
  canvas.height = videoElement.videoHeight;
  const ctx = canvas.getContext('2d');
  if (!ctx) return '';

  ctx.drawImage(videoElement, 0, 0);
  return canvas.toDataURL('image/jpeg', 0.7);
};

export const sendViolationAlert = async (
  socket: Socket | null,
  sessionIdentifier: string,
  violation: ProctorAlert
) => {
  if (!socket) return;

  socket.emit('violation_alert', {
    identifier: sessionIdentifier,
    violationType: violation.violationType,
    timestamp: violation.timestamp,
    severity: violation.severity,
    evidence: violation.evidence?.substring(0, 50000), // Limit size
  });
};
