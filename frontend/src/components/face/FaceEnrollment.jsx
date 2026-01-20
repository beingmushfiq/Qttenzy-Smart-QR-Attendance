import { useEffect, useState } from 'react';
import { useFaceRecognition } from '../../hooks/useFaceRecognition';
import { userAPI } from '../../services/api/user';

const FaceEnrollment = ({ onEnrolled, onClose }) => {
  const { modelsLoaded, loading, error, videoRef, canvasRef, captureFace } = useFaceRecognition();
  const [enrolling, setEnrolling] = useState(false);
  const [stream, setStream] = useState(null);

  useEffect(() => {
    if (modelsLoaded && videoRef.current) {
      navigator.mediaDevices.getUserMedia({ 
        video: { 
          facingMode: 'user',
          width: { ideal: 640 },
          height: { ideal: 480 }
        } 
      })
        .then(mediaStream => {
          videoRef.current.srcObject = mediaStream;
          setStream(mediaStream);
        })
        .catch(err => {
          console.error('Error accessing camera:', err);
        });
    }

    return () => {
      if (stream) {
        stream.getTracks().forEach(track => track.stop());
      }
    };
  }, [modelsLoaded]);

  const handleEnroll = async () => {
    setEnrolling(true);

    try {
      const faceDescriptor = await captureFace();
      
      // Send to backend
      await userAPI.enrollFace({
        face_descriptor: faceDescriptor,
        image: null // Could capture image if needed
      });

      // Store descriptor locally for quick access
      localStorage.setItem('face_descriptor', JSON.stringify(faceDescriptor));

      onEnrolled(faceDescriptor);
      alert('Face enrolled successfully!');
    } catch (error) {
      alert(error.message || 'Face enrollment failed');
    } finally {
      setEnrolling(false);
    }
  };

  if (loading) {
    return (
      <div className="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50">
        <div className="bg-white rounded-lg p-6">
          <p>Loading face recognition models...</p>
        </div>
      </div>
    );
  }

  if (error || !modelsLoaded) {
    return (
      <div className="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50">
        <div className="bg-white rounded-lg p-6">
          <p className="text-red-500">{error || 'Failed to load models'}</p>
          <button onClick={onClose} className="mt-4 px-4 py-2 bg-gray-500 text-white rounded">
            Close
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50">
      <div className="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <div className="flex justify-between items-center mb-4">
          <h2 className="text-xl font-bold">Enroll Your Face</h2>
          <button
            onClick={onClose}
            className="text-gray-500 hover:text-gray-700"
          >
            ✕
          </button>
        </div>

        <div className="relative bg-black rounded-lg overflow-hidden">
          <video 
            ref={videoRef} 
            autoPlay 
            playsInline 
            className="w-full h-auto"
          />
          <canvas ref={canvasRef} className="hidden" />
        </div>

        <p className="mt-4 text-sm text-gray-600">
          Ensure your face is clearly visible and well-lit. Click "Enroll Face" when ready.
        </p>

        <div className="mt-4 flex gap-2">
          <button
            onClick={handleEnroll}
            disabled={enrolling}
            className="flex-1 bg-blue-500 text-white py-2 rounded hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {enrolling ? 'Enrolling...' : 'Enroll Face'}
          </button>
          <button
            onClick={onClose}
            className="flex-1 bg-gray-500 text-white py-2 rounded hover:bg-gray-600"
          >
            Cancel
          </button>
        </div>
      </div>
    </div>
  );
};

export default FaceEnrollment;

