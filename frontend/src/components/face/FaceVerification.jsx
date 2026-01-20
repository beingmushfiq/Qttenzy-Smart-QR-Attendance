import { useEffect, useState } from 'react';
import { useFaceRecognition } from '../../hooks/useFaceRecognition';

const FaceVerification = ({ enrolledDescriptor, onVerify, onClose }) => {
  const { modelsLoaded, loading, error, videoRef, canvasRef, captureFace, compareFaces } = useFaceRecognition();
  const [verifying, setVerifying] = useState(false);
  const [matchResult, setMatchResult] = useState(null);
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

  const handleVerify = async () => {
    if (!enrolledDescriptor) {
      alert('No enrolled face found. Please enroll your face first.');
      return;
    }

    setVerifying(true);
    setMatchResult(null);

    try {
      const currentDescriptor = await captureFace();
      const result = await compareFaces(enrolledDescriptor, currentDescriptor, 0.6);
      setMatchResult(result);
      onVerify(result);
    } catch (error) {
      alert(error.message || 'Face verification failed');
    } finally {
      setVerifying(false);
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

  if (error) {
    return (
      <div className="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50">
        <div className="bg-white rounded-lg p-6">
          <p className="text-red-500">{error}</p>
          <button onClick={onClose} className="mt-4 px-4 py-2 bg-gray-500 text-white rounded">
            Close
          </button>
        </div>
      </div>
    );
  }

  if (!modelsLoaded) {
    return (
      <div className="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50">
        <div className="bg-white rounded-lg p-6">
          <p>Failed to load models</p>
        </div>
      </div>
    );
  }

  return (
    <div className="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50">
      <div className="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <div className="flex justify-between items-center mb-4">
          <h2 className="text-xl font-bold">Face Verification</h2>
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

        {matchResult && (
          <div className={`mt-4 p-3 rounded ${
            matchResult.match 
              ? 'bg-green-100 border border-green-400 text-green-700' 
              : 'bg-red-100 border border-red-400 text-red-700'
          }`}>
            <p className="font-semibold">
              Match Score: {matchResult.score.toFixed(2)}%
            </p>
            <p className="text-sm mt-1">
              {matchResult.match ? '✓ Face Verified' : '✗ Face Not Matched'}
            </p>
          </div>
        )}

        <div className="mt-4 flex gap-2">
          <button
            onClick={handleVerify}
            disabled={verifying}
            className="flex-1 bg-blue-500 text-white py-2 rounded hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {verifying ? 'Verifying...' : 'Verify Face'}
          </button>
          <button
            onClick={onClose}
            className="flex-1 bg-gray-500 text-white py-2 rounded hover:bg-gray-600"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  );
};

export default FaceVerification;

