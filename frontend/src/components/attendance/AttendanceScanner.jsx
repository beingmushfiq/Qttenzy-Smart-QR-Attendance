import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { toast } from 'react-toastify';
import { useGeolocation } from '../../hooks/useGeolocation';
import { useFaceRecognition } from '../../hooks/useFaceRecognition';
import { attendanceAPI } from '../../services/api/attendance';
import { userAPI } from '../../services/api/user';
import { useAuthStore } from '../../store/authStore';
import QRScanner from '../qr/QRScanner';

const AttendanceScanner = ({ sessionId: initialSessionId }) => {
  const [step, setStep] = useState('select'); // select, scan, face, processing, success, fail
  const [scannedData, setScannedData] = useState(null);
  const [sessionData, setSessionData] = useState({ id: initialSessionId });
  const [verificationStatus, setVerificationStatus] = useState(null); // 'processing', 'success', 'error'
  const [errorMessage, setErrorMessage] = useState('');
  const [enrolledDescriptor, setEnrolledDescriptor] = useState(null);
  const [authMethod, setAuthMethod] = useState(null); // 'qr' or 'face'

  const navigate = useNavigate();
  const { location, getCurrentLocation } = useGeolocation();
  const { modelsLoaded, captureFace, compareFaces, videoRef, canvasRef } = useFaceRecognition();
  
  // Load enrolled face on mount
  useEffect(() => {
    const loadFace = async () => {
      try {
        const response = await userAPI.getFaceEnrollment();
        if (response.success && response.data.face_descriptor) {
          setEnrolledDescriptor(response.data.face_descriptor);
        }
      } catch (err) {
        console.warn("Could not load enrolled face:", err);
      }
    };
    loadFace();
  }, []);

  // Update session data if prop changes
  useEffect(() => {
      if (initialSessionId) {
          setSessionData({ id: initialSessionId });
      }
  }, [initialSessionId]);

  // Initialize camera when entering face step
  useEffect(() => {
    if (step === 'face' && modelsLoaded && videoRef.current) {
        navigator.mediaDevices.getUserMedia({ 
            video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } } 
        })
        .then(stream => {
            videoRef.current.srcObject = stream;
        })
        .catch(err => {
            console.error("Camera Error:", err);
            toast.error("Could not access camera");
        });
    }
  }, [step, modelsLoaded, videoRef]);

  const handleQRScan = (data) => {
    // Expected: SESSION_{id}_... or just {id}
    let sid = null;
    if (data.startsWith('SESSION_')) {
        const parts = data.split('_');
        sid = parts[1];
    } else {
        sid = parseInt(data);
    }

    if (!sid || isNaN(sid)) {
        toast.error("Invalid QR Code");
        return;
    }

    if (initialSessionId && parseInt(initialSessionId) !== parseInt(sid)) {
        toast.error("QR Code does not match this session!");
        return;
    }

    setScannedData(data);
    
    // QR Flow: Immediate Submit
    submitAttendance(sid, null, data);
  };

  const handleFaceVerify = async () => {
    if (!modelsLoaded) {
        toast.error("Face models not loaded yet. Please wait.");
        return;
    }
    
    try {
        const currentDescriptor = await captureFace();
        
        // Match against enrolled face
        const result = await compareFaces(enrolledDescriptor, currentDescriptor);
        
        if (result.match) {
            toast.success("Identity Verified");
            // Face Flow: Submit with Face Data
            submitAttendance(sessionData.id, currentDescriptor, null);
        } else {
            toast.error("Face not recognized. Please try again.");
        }
    } catch (err) {
        toast.error(err.message || "Verification Failed");
    }
  };

  const submitAttendance = async (sessionId, faceDesc, qrCode) => {
    setStep('processing');
    setVerificationStatus('processing');
    
    try {
        // Get location if possible (don't block heavily if not critical, but good to have)
        let finalLocation = location;
        if (!finalLocation) {
             // Maybe try one last quick fetch or just proceed null
        }

        const payload = {
            session_id: sessionId,
            location: finalLocation ? {
                lat: finalLocation.lat,
                lng: finalLocation.lng
            } : null
        };

        if (faceDesc) {
            payload.face_descriptor = faceDesc;
        }
        if (qrCode) {
            payload.qr_code = qrCode;
        }

        await attendanceAPI.verify(payload);
        
        setVerificationStatus('success');
        setTimeout(() => navigate('/attendance'), 2000);
    } catch (err) {
        console.error(err);
        setVerificationStatus('error');
        setErrorMessage(err.response?.data?.message || err.message || "Submission Failed");
        // Allow retry
        // setStep('fail'); // or stay on processing screen with error
    }
  };

  const handleBack = () => {
      if (step === 'select') {
          navigate(-1);
      } else {
          setStep('select');
          setVerificationStatus(null);
          setErrorMessage('');
      }
  };

  return (
    <div className="fixed inset-0 z-50 bg-black text-white flex flex-col font-sans">
       {/* Top Bar */}
       <div className="absolute top-0 left-0 right-0 z-20 p-6 flex justify-between items-start bg-gradient-to-b from-black/80 to-transparent pointer-events-none">
           <div className="pointer-events-auto">
               <h2 className="text-2xl font-bold tracking-tight">
                   {step === 'select' ? 'Mark Attendance' : 
                    step === 'scan' ? 'Scan QR' : 
                    step === 'face' ? 'Face Verification' : 'Processing'}
               </h2>
               <p className="text-white/60 text-sm">
                   {step === 'select' ? 'Choose authentication method' : 
                    step === 'scan' ? 'Align QR code in frame' : 
                    step === 'face' ? 'Look at the camera' : 'Please wait...'}
               </p>
           </div>
           <button onClick={handleBack} className="pointer-events-auto p-2 rounded-full bg-white/10 hover:bg-white/20 backdrop-blur-md transition-all">
               ✕
           </button>
       </div>

       {/* Main Content */}
       <div className="flex-1 relative flex items-center justify-center bg-gray-900 overflow-hidden">
           
           {/* Background Ambiance */}
           <div className="absolute inset-0 bg-gradient-radial from-premium-primary/20 to-black opacity-30"></div>

           {/* SELECTION STEP */}
           {step === 'select' && (
               <div className="w-full max-w-md px-6 space-y-6 relative z-10 animate-fade-in-up">
                   
                   {/* QR Option */}
                   <button 
                       onClick={() => { setAuthMethod('qr'); setStep('scan'); }}
                       className="w-full group relative overflow-hidden rounded-3xl bg-white/5 border border-white/10 p-8 hover:bg-white/10 transition-all hover:scale-[1.02] active:scale-[0.98]"
                   >
                       <div className="absolute inset-0 bg-gradient-to-r from-transparent via-white/5 to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000"></div>
                       <div className="flex items-center justify-between">
                           <div className="flex flex-col text-left">
                               <span className="text-3xl mb-2">📷</span>
                               <span className="text-xl font-bold text-white">Scan QR Code</span>
                               <span className="text-sm text-white/50">Standard method using venue QR</span>
                           </div>
                           <div className="w-12 h-12 rounded-full bg-white/10 flex items-center justify-center text-white/50 group-hover:bg-premium-primary group-hover:text-white transition-colors">
                               ➔
                           </div>
                       </div>
                   </button>

                   {/* Face Option */}
                   <div className="relative">
                       <button 
                           onClick={() => { setAuthMethod('face'); setStep('face'); }}
                           disabled={!sessionData.id || !enrolledDescriptor}
                           className={`w-full group relative overflow-hidden rounded-3xl border p-8 transition-all hover:scale-[1.02] active:scale-[0.98]
                               ${(sessionData.id && enrolledDescriptor)
                                   ? 'bg-gradient-to-br from-premium-primary/10 to-premium-accent/5 border-premium-primary/20 hover:border-premium-primary/50 cursor-pointer' 
                                   : 'bg-white/2 border-white/5 opacity-50 cursor-not-allowed'}`}
                       >
                           <div className="flex items-center justify-between">
                               <div className="flex flex-col text-left">
                                   <span className="text-3xl mb-2">👤</span>
                                   <span className="text-xl font-bold text-white">Face Verification</span>
                                   <span className="text-sm text-white/50">Fast & Secure biometric check</span>
                               </div>
                               <div className={`w-12 h-12 rounded-full flex items-center justify-center transition-colors
                                   ${(sessionData.id && enrolledDescriptor) ? 'bg-premium-primary/20 text-premium-primary group-hover:bg-premium-primary group-hover:text-white' : 'bg-white/5'}`}>
                                   {(sessionData.id && enrolledDescriptor) ? '➔' : '🔒'}
                               </div>
                           </div>
                       </button>
                       <div className="absolute -bottom-6 left-0 right-0 text-center space-y-1">
                           {!sessionData.id && (
                               <span className="block text-xs text-red-400 bg-black/50 px-3 py-1 rounded-full backdrop-blur-sm mx-auto w-max">
                                   Select a session first
                               </span>
                           )}
                           {!enrolledDescriptor && (
                               <button 
                                   onClick={() => navigate('/profile')} // Assuming profile has enrollment
                                   className="block text-xs text-yellow-400 bg-black/50 px-3 py-1 rounded-full backdrop-blur-sm mx-auto w-max hover:text-yellow-300 underline"
                               >
                                   Face ID not set up. Click to Enroll.
                               </button>
                           )}
                       </div>
                   </div>

               </div>
           )}

           {/* SCAN STEP */}
           {step === 'scan' && (
               <div className="w-full h-full relative">
                   <QRScanner onScan={handleQRScan} />
                   
                   {/* Overlay Frame */}
                   <div className="absolute inset-0 pointer-events-none flex items-center justify-center">
                       <div className="w-64 h-64 border-2 border-white/30 rounded-3xl relative">
                           <div className="absolute top-0 left-0 w-8 h-8 border-t-4 border-l-4 border-premium-primary -mt-1 -ml-1 rounded-tl-2xl"></div>
                           <div className="absolute top-0 right-0 w-8 h-8 border-t-4 border-r-4 border-premium-primary -mt-1 -mr-1 rounded-tr-2xl"></div>
                           <div className="absolute bottom-0 left-0 w-8 h-8 border-b-4 border-l-4 border-premium-primary -mb-1 -ml-1 rounded-bl-2xl"></div>
                           <div className="absolute bottom-0 right-0 w-8 h-8 border-b-4 border-r-4 border-premium-primary -mb-1 -mr-1 rounded-br-2xl"></div>
                           <div className="absolute inset-0 bg-premium-primary/5 animate-pulse"></div>
                       </div>
                   </div>
               </div>
           )}

           {/* FACE STEP */}
           {step === 'face' && (
               <div className="w-full h-full relative flex flex-col items-center justify-center p-4">
                   <div className="relative w-full max-w-sm aspect-square bg-black rounded-full overflow-hidden border-4 border-white/10 shadow-2xl mb-8">
                        {/* We use a hidden FaceEnrollment-style video or just reusing logic? 
                            Let's use a simple Video element here hooked to useFaceRecognition 
                            Use the hook's videoRef directly on a video element. 
                        */}
                       <video 
                           ref={videoRef}
                           // Better: AttendanceScanner uses the hook, passes ref here.
                           // Actually the hook exports videoRef. We need to attach it.
                           // BUT useFaceRecognition is a hook, so we need to initialize it in this component 
                           // (which we did at top) and attach ref here.
                           autoPlay playsInline muted 
                           className="w-full h-full object-cover transform scale-x-[-1]"
                       />
                       
                       <canvas ref={canvasRef} className="hidden" />

                       {/* Face Overlay */}
                       <div className="absolute inset-0 border-[3px] border-dashed border-white/20 rounded-full animate-spin-slow-reverse"></div>
                       <div className="absolute inset-4 border-[3px] border-premium-primary rounded-full animate-pulse-slow shadow-[0_0_30px_rgba(59,130,246,0.5)]"></div>
                   </div>

                   <button 
                       onClick={handleFaceVerify}
                       className="w-full max-w-xs bg-gradient-premium text-white font-bold py-4 rounded-2xl shadow-xl shadow-premium-primary/30 hover:scale-105 active:scale-95 transition-all text-lg"
                   >
                       Verify Identity
                   </button>
                   
                   <p className="mt-4 text-white/50 text-sm">
                       Ensure good lighting and remove accessories
                   </p>
               </div>
           )}

           {/* PROCESSING / SUCCESS / FAIL */}
           {(step === 'processing' || verificationStatus) && (
               <div className="absolute inset-0 z-50 bg-black/90 backdrop-blur-xl flex items-center justify-center p-8">
                   <div className="text-center space-y-6 max-w-sm">
                       
                       {verificationStatus === 'processing' && (
                           <>
                               <div className="w-20 h-20 border-4 border-white/10 border-t-premium-primary rounded-full animate-spin mx-auto"></div>
                               <h3 className="text-2xl font-bold text-white">Verifying...</h3>
                               <p className="text-white/50">Checking your credentials</p>
                           </>
                       )}

                       {verificationStatus === 'success' && (
                           <>
                               <div className="w-24 h-24 bg-green-500 rounded-full flex items-center justify-center mx-auto animate-bounce-slow shadow-lg shadow-green-500/30">
                                   <span className="text-5xl">✓</span>
                               </div>
                               <h3 className="text-3xl font-bold text-white">Attendance Marked!</h3>
                               <p className="text-green-400">You are good to go.</p>
                           </>
                       )}

                       {verificationStatus === 'error' && (
                           <>
                               <div className="w-24 h-24 bg-red-500/20 border-2 border-red-500 rounded-full flex items-center justify-center mx-auto text-red-500 mb-2">
                                   <span className="text-5xl">✕</span>
                               </div>
                               <h3 className="text-2xl font-bold text-white">Verification Failed</h3>
                               <p className="text-white/60 text-sm bg-white/5 p-4 rounded-xl border border-white/10">
                                   {errorMessage}
                               </p>
                               <div className="flex gap-4 pt-2">
                                   <button 
                                       onClick={() => { setStep('select'); setVerificationStatus(null); }}
                                       className="flex-1 bg-white/10 hover:bg-white/20 py-3 rounded-xl font-medium transition-colors"
                                   >
                                       Back to Menu
                                   </button>
                                   <button 
                                       onClick={() => { setStep(authMethod); setVerificationStatus(null); }}
                                       className="flex-1 bg-premium-primary hover:bg-premium-primary/80 py-3 rounded-xl font-medium transition-colors"
                                   >
                                       Try Again
                                   </button>
                               </div>
                           </>
                       )}

                   </div>
               </div>
           )}

       </div>
    </div>
  );
};

export default AttendanceScanner;
