import * as cocoSsd from '@tensorflow-models/coco-ssd';
import * as faceLandmarksDetection from '@tensorflow-models/face-landmarks-detection';
import '@tensorflow/tfjs-backend-webgl';
import type { Socket } from 'socket.io-client';

export type ViolationType = 'phone' | 'book' | 'person' | 'looking_away' | 'no_face' | 'speaking' | 'whispering';

export type ProctorAlert = {
  violationType: ViolationType;
  timestamp: string;
  severity: 'warning' | 'alert';
  evidence?: string; // base64 image or audio blob
};

let cocoModel: any = null;
let faceModel: faceLandmarksDetection.FaceLandmarksDetector | null = null;
let isModelLoaded = false;
let isFaceModelLoaded = false;

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

export const loadFaceModel = async () => {
  if (isFaceModelLoaded) return faceModel;
  try {
    const model = faceLandmarksDetection.SupportedModels.MediaPipeFaceMesh;
    const detectorConfig: faceLandmarksDetection.MediaPipeFaceMeshMediaPipeModelConfig = {
      runtime: 'mediapipe',
      solutionPath: 'https://cdn.jsdelivr.net/npm/@mediapipe/face_mesh',
      refineLandmarks: true,
    };
    faceModel = await faceLandmarksDetection.createDetector(model, detectorConfig);
    isFaceModelLoaded = true;
    return faceModel;
  } catch (err) {
    console.error('Failed to load Face Landmarks model:', err);
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

export const detectHeadTilt = async (
  videoElement: HTMLVideoElement,
  detector: faceLandmarksDetection.FaceLandmarksDetector | null
): Promise<{ lookingAway: boolean; direction: string; noFace: boolean }> => {
  if (!detector) {
    return { lookingAway: false, direction: 'center', noFace: true };
  }

  try {
    const faces = await detector.estimateFaces(videoElement, { flipHorizontal: false });

    if (!faces || faces.length === 0) {
      return { lookingAway: true, direction: 'unknown', noFace: true };
    }

    const face = faces[0];
    const keypoints = face.keypoints;

    // Get key facial landmarks for pose estimation
    // Nose tip (1), Left eye (133), Right eye (362), Left ear (234), Right ear (454)
    const noseTip = keypoints.find(kp => kp.name === 'noseTip') || keypoints[1];
    const leftEye = keypoints.find(kp => kp.name === 'leftEye') || keypoints[133];
    const rightEye = keypoints.find(kp => kp.name === 'rightEye') || keypoints[362];
    const leftEar = keypoints[234];
    const rightEar = keypoints[454];

    if (!noseTip || !leftEye || !rightEye) {
      return { lookingAway: false, direction: 'center', noFace: false };
    }

    // Calculate eye center
    const eyeCenterX = (leftEye.x + rightEye.x) / 2;
    const eyeCenterY = (leftEye.y + rightEye.y) / 2;

    // Calculate horizontal deviation (nose relative to eye center)
    const horizontalDeviation = noseTip.x - eyeCenterX;
    const eyeDistance = Math.abs(rightEye.x - leftEye.x);

    // Normalized deviation (as percentage of eye distance)
    const normalizedDeviation = (horizontalDeviation / eyeDistance) * 100;

    // Check for head rotation based on ear visibility and nose position
    const leftEarVisible = leftEar && leftEar.x > 0;
    const rightEarVisible = rightEar && rightEar.x > 0;

    let direction = 'center';
    let lookingAway = false;

    // Threshold for looking away detection
    const MILD_THRESHOLD = 15; // degrees equivalent

    if (Math.abs(normalizedDeviation) > MILD_THRESHOLD) {
      lookingAway = true;
      if (normalizedDeviation > 0) {
        direction = 'right';
      } else {
        direction = 'left';
      }
    }

    // Additional check: if one ear is much more visible than the other
    if (!leftEarVisible && rightEarVisible) {
      lookingAway = true;
      direction = 'right';
    } else if (leftEarVisible && !rightEarVisible) {
      lookingAway = true;
      direction = 'left';
    }

    // Vertical check (looking up or down)
    const verticalDeviation = noseTip.y - eyeCenterY;
    const faceHeight = Math.abs(keypoints[10].y - keypoints[152].y); // forehead to chin
    const normalizedVerticalDeviation = (verticalDeviation / faceHeight) * 100;

    if (Math.abs(normalizedVerticalDeviation) > 25) {
      lookingAway = true;
      direction = normalizedVerticalDeviation > 0 ? 'down' : 'up';
    }

    return { lookingAway, direction, noFace: false };
  } catch (err) {
    console.error('Face detection error:', err);
    return { lookingAway: false, direction: 'center', noFace: true };
  }
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
