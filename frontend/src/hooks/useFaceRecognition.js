import { useState, useEffect, useRef } from 'react';
import * as faceapi from 'face-api.js';

const MODEL_URL = import.meta.env.VITE_FACE_API_MODELS_PATH || '/models';

export const useFaceRecognition = () => {
  const [modelsLoaded, setModelsLoaded] = useState(false);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const videoRef = useRef(null);
  const canvasRef = useRef(null);

  useEffect(() => {
    const loadModels = async () => {
      try {
        setLoading(true);
        setError(null);

        await Promise.all([
          faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
          faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
          faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL),
          faceapi.nets.faceExpressionNet.loadFromUri(MODEL_URL)
        ]);

        setModelsLoaded(true);
        setLoading(false);
      } catch (error) {
        console.error('Error loading face models:', error);
        setError('Failed to load face recognition models');
        setLoading(false);
      }
    };

    loadModels();
  }, []);

  const captureFace = async () => {
    if (!modelsLoaded || !videoRef.current) {
      throw new Error('Face models not loaded or video not ready');
    }

    try {
      const detection = await faceapi
        .detectSingleFace(videoRef.current, new faceapi.TinyFaceDetectorOptions())
        .withFaceLandmarks()
        .withFaceDescriptor();

      if (!detection) {
        throw new Error('No face detected. Please ensure your face is visible.');
      }

      return Array.from(detection.descriptor);
    } catch (error) {
      throw new Error(error.message || 'Failed to capture face');
    }
  };

  const compareFaces = async (descriptor1, descriptor2, threshold = 0.6) => {
    if (!descriptor1 || !descriptor2) {
      throw new Error('Descriptors are required');
    }

    if (descriptor1.length !== descriptor2.length) {
      throw new Error('Descriptor dimensions do not match');
    }

    const distance = faceapi.euclideanDistance(descriptor1, descriptor2);
    const match = distance < threshold;
    const score = (1 - distance) * 100;
    
    return { 
      match, 
      score: Math.max(0, Math.min(100, score)), 
      distance 
    };
  };

  const drawFaceBox = (canvas, detection) => {
    if (!canvas || !detection) return;

    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    const box = detection.detection.box;
    ctx.strokeStyle = '#00ff00';
    ctx.lineWidth = 2;
    ctx.strokeRect(box.x, box.y, box.width, box.height);
  };

  return {
    modelsLoaded,
    loading,
    error,
    videoRef,
    canvasRef,
    captureFace,
    compareFaces,
    drawFaceBox
  };
};

