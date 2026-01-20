import { useState, useRef, useEffect } from 'react'
import * as faceapi from 'face-api.js'
import { toast } from 'react-toastify'

const FaceVerification = ({ onCapture, onError }) => {
  const [modelsLoaded, setModelsLoaded] = useState(false)
  const [capturing, setCapturing] = useState(false)
  const [stream, setStream] = useState(null)
  const videoRef = useRef(null)
  const canvasRef = useRef(null)

  useEffect(() => {
    loadModels()
    return () => {
      if (stream) {
        stream.getTracks().forEach(track => track.stop())
      }
    }
  }, [stream])

  const loadModels = async () => {
    try {
      const MODEL_URL = '/models' // Place face-api.js models in public/models
      await Promise.all([
        faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
        faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
        faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
      ])
      setModelsLoaded(true)
      toast.success('Face detection models loaded')
    } catch (error) {
      console.error('Failed to load models:', error)
      toast.error('Failed to load face detection models')
      if (onError) onError(error)
    }
  }

  const startCapture = async () => {
    if (!modelsLoaded) {
      toast.error('Face detection models not loaded yet')
      return
    }

    try {
      const mediaStream = await navigator.mediaDevices.getUserMedia({ 
        video: { width: 640, height: 480 } 
      })
      setStream(mediaStream)
      if (videoRef.current) {
        videoRef.current.srcObject = mediaStream
      }
      setCapturing(true)
    } catch (error) {
      console.error('Camera access error:', error)
      toast.error('Failed to access camera')
      if (onError) onError(error)
    }
  }

  const stopCapture = () => {
    if (stream) {
      stream.getTracks().forEach(track => track.stop())
      setStream(null)
    }
    setCapturing(false)
  }

  const captureFace = async () => {
    if (!videoRef.current) return

    try {
      const detection = await faceapi
        .detectSingleFace(videoRef.current, new faceapi.TinyFaceDetectorOptions())
        .withFaceLandmarks()
        .withFaceDescriptor()

      if (detection) {
        const descriptor = Array.from(detection.descriptor) // 128-dimensional array
        toast.success('Face captured successfully!')
        onCapture(descriptor)
        stopCapture()
      } else {
        toast.error('No face detected. Please try again.')
      }
    } catch (error) {
      console.error('Face capture error:', error)
      toast.error('Failed to capture face')
      if (onError) onError(error)
    }
  }

  return (
    <div className="space-y-4">
      {!capturing ? (
        <button
          onClick={startCapture}
          disabled={!modelsLoaded}
          className="w-full bg-gradient-premium text-white font-bold py-4 rounded-2xl shadow-lg shadow-premium-primary/20 hover:scale-[1.02] active:scale-[0.98] transition-all disabled:opacity-50 disabled:hover:scale-100"
        >
          {modelsLoaded ? '📸 Start Face Capture' : 'Loading Models...'}
        </button>
      ) : (
        <div className="space-y-4">
          <div className="relative rounded-2xl overflow-hidden border-2 border-premium-primary/30">
            <video
              ref={videoRef}
              autoPlay
              playsInline
              muted
              className="w-full h-auto"
            />
            <canvas
              ref={canvasRef}
              className="absolute top-0 left-0 w-full h-full"
            />
          </div>
          <div className="grid grid-cols-2 gap-3">
            <button
              onClick={captureFace}
              className="bg-green-500/20 hover:bg-green-500/30 text-green-300 font-bold py-3 rounded-xl transition-all"
            >
              Capture Face
            </button>
            <button
              onClick={stopCapture}
              className="bg-red-500/20 hover:bg-red-500/30 text-red-300 font-bold py-3 rounded-xl transition-all"
            >
              Cancel
            </button>
          </div>
        </div>
      )}
      
      <p className="text-white/40 text-xs text-center">
        Position your face in the center and ensure good lighting
      </p>
    </div>
  )
}

export default FaceVerification
