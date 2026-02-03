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
      onEnrolled(faceDescriptor);
      alert('Face enrolled successfully!');
    } catch (error) {
      console.error('Enrollment error:', error);
      const errorMessage = error.response?.data?.message || error.response?.data?.error || error.message || 'Face enrollment failed';
      alert(errorMessage);
    } finally {
      setEnrolling(false);
    }
  };

  if (loading) {
    return (
      <div className="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4">
        <div className="bg-white rounded-lg p-4 sm:p-6">
          <p>Loading face recognition models...</p>
        </div>
      </div>
    );
  }

  if (error || !modelsLoaded) {
    return (
      <div className="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4">
        <div className="bg-white rounded-lg p-4 sm:p-6">
          <p className="text-red-500">{error || 'Failed to load models'}</p>
          <button onClick={onClose} className="mt-4 px-4 py-2 bg-gray-500 text-white rounded">
            Close
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="fixed inset-0 z-50 bg-black text-white flex flex-col justify-center items-center p-4">
      {/* Background Overlay */}
      <div className="absolute inset-0 bg-gradient-premium opacity-10 pointer-events-none"></div>
      
      <div className="bg-dark/90 backdrop-blur-xl border border-white/10 rounded-3xl p-6 max-w-lg w-full relative shadow-2xl">
        <button
            onClick={onClose}
            className="absolute top-4 right-4 p-2 rounded-full hover:bg-white/10 transition-colors"
        >
            ✕
        </button>

        <div className="text-center mb-6">
             <h2 className="text-2xl font-bold tracking-tight mb-2">Face Enrollment</h2>
             <p className="text-white/60 text-sm">Position your face clearly within the frame.</p>
        </div>

        <div className="relative w-full aspect-square sm:aspect-video bg-black rounded-2xl overflow-hidden mb-6 border-2 border-white/10 shadow-inner">
          <video 
            ref={videoRef} 
            autoPlay 
            playsInline 
            muted
            className="w-full h-full object-cover transform scale-x-[-1]" // Mirror effect
          />
          <canvas ref={canvasRef} className="hidden" />
          
          {/* Face Frame Overlay */}
          <div className="absolute inset-0 pointer-events-none flex items-center justify-center">
              <div className="w-48 h-48 sm:w-64 sm:h-64 border-2 border-premium-primary/50 rounded-full relative">
                  <div className="absolute inset-0 border-t-4 border-premium-primary rounded-full animate-spin-slow opacity-50"></div>
                  <div className="absolute inset-0 flex items-center justify-center">
                      <div className="w-2 h-2 bg-premium-primary rounded-full animate-ping"></div>
                  </div>
              </div>
          </div>
        </div>

        {error && (
            <div className="mb-4 p-3 bg-red-500/10 border border-red-500/20 rounded-xl text-red-200 text-sm text-center">
                {error}
            </div>
        )}

        <div className="flex gap-4">
          <button
            onClick={handleEnroll}
            disabled={enrolling || !modelsLoaded}
            className="flex-1 bg-gradient-premium text-white font-bold py-3.5 rounded-xl shadow-lg shadow-premium-primary/20 hover:scale-[1.02] active:scale-[0.98] transition-all disabled:opacity-50 disabled:hover:scale-100"
          >
            {enrolling ? 'Processing...' : 'Capture & Enroll'}
          </button>
          <button
            onClick={onClose}
            className="px-6 py-3.5 rounded-xl bg-white/5 text-white border border-white/10 hover:bg-white/10 transition-all font-semibold"
          >
            Cancel
          </button>
        </div>
        
        {!modelsLoaded && (
            <p className="text-center text-xs text-white/30 mt-4 animate-pulse">Initializing face recognition models...</p>
        )}
      </div>
    </div>
  );
};

export default FaceEnrollment;

