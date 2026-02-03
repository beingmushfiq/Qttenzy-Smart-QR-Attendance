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
          <div className="fixed inset-0 z-[60] bg-black/90 backdrop-blur-sm flex items-center justify-center">
              <div className="text-white text-center">
                  <div className="w-12 h-12 border-4 border-white/20 border-t-premium-primary rounded-full animate-spin mx-auto mb-4"></div>
                  <p>Loading face recognition models...</p>
              </div>
          </div>
      );
  }

  if (error) {
      return (
          <div className="fixed inset-0 z-50 bg-black/90 flex items-center justify-center p-4">
              <div className="bg-white/10 backdrop-blur-md rounded-2xl p-6 max-w-sm w-full border border-white/10 text-center">
                  <div className="text-red-400 text-4xl mb-4">⚠️</div>
                  <p className="text-red-200 mb-6">{error}</p>
                  <button onClick={onClose} className="px-6 py-2 bg-white/10 hover:bg-white/20 rounded-xl text-white transition-all">
                      Close
                  </button>
              </div>
          </div>
      );
  }

  return (
    <div className="fixed inset-0 z-[60] bg-black text-white flex flex-col justify-center items-center p-4 animate-in fade-in duration-300">
      {/* Background Overlay */}
      <div className="absolute inset-0 bg-gradient-radial from-premium-primary/20 to-black opacity-40 pointer-events-none"></div>
      
      <div className="bg-dark/90 backdrop-blur-xl border border-white/10 rounded-3xl p-6 max-w-lg w-full relative shadow-2xl">
        <button
            onClick={onClose}
            className="absolute top-4 right-4 p-2 rounded-full hover:bg-white/10 transition-colors z-20"
        >
            ✕
        </button>

        <div className="text-center mb-6">
             <h2 className="text-2xl font-bold tracking-tight mb-2">Face Verification</h2>
             <p className="text-white/60 text-sm">Verify your identity to proceed</p>
        </div>

        <div className="relative w-full aspect-square sm:aspect-video bg-black rounded-2xl overflow-hidden mb-6 border-2 border-white/10 shadow-inner group">
          <video 
            ref={videoRef} 
            autoPlay 
            playsInline 
            muted
            className="w-full h-full object-cover transform scale-x-[-1]"
          />
          <canvas ref={canvasRef} className="hidden" />
          
          {/* Animated Scanning Overlay */}
          <div className="absolute inset-0 pointer-events-none flex items-center justify-center">
              <div className={`w-48 h-48 sm:w-64 sm:h-64 border-2 rounded-full relative transition-colors duration-500
                  ${matchResult?.match ? 'border-green-500 shadow-[0_0_30px_rgba(34,197,94,0.5)]' : 
                    matchResult?.match === false ? 'border-red-500 shadow-[0_0_30px_rgba(239,68,68,0.5)]' : 
                    'border-premium-primary/50'}`}>
                  
                  {!matchResult && (
                      <>
                        <div className="absolute inset-0 border-t-4 border-premium-primary rounded-full animate-spin-slow opacity-50"></div>
                        <div className="absolute inset-0 flex items-center justify-center">
                            <div className="w-2 h-2 bg-premium-primary rounded-full animate-ping"></div>
                        </div>
                      </>
                  )}
              </div>
          </div>
          
          {/* Status Overlay */}
          {verifying && (
              <div className="absolute inset-0 bg-black/50 flex items-center justify-center backdrop-blur-sm">
                  <div className="text-white font-bold tracking-widest uppercase animate-pulse">Verifying...</div>
              </div>
          )}
        </div>

        {/* Result Feedback */}
        {matchResult && (
          <div className={`mb-6 p-4 rounded-xl border flex items-center gap-3 animate-in slide-in-from-top-2 ${
            matchResult.match 
              ? 'bg-green-500/10 border-green-500/30 text-green-400' 
              : 'bg-red-500/10 border-red-500/30 text-red-400'
          }`}>
            <span className="text-2xl">{matchResult.match ? '✅' : '❌'}</span>
            <div>
                <p className="font-bold">
                  {matchResult.match ? 'Identity Verified' : 'Verification Failed'}
                </p>
                <p className="text-xs opacity-80">
                  Match Score: {matchResult.score.toFixed(1)}%
                </p>
            </div>
          </div>
        )}

        <div className="flex gap-4">
          <button
            onClick={handleVerify}
            disabled={verifying || !modelsLoaded}
            className="flex-1 bg-gradient-premium text-white font-bold py-3.5 rounded-xl shadow-lg shadow-premium-primary/20 hover:scale-[1.02] active:scale-[0.98] transition-all disabled:opacity-50 disabled:hover:scale-100"
          >
            {verifying ? 'Scanning...' : matchResult?.match ? 'Verified' : 'Verify Face'}
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

export default FaceVerification;
