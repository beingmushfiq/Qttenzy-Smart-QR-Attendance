import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { toast } from 'react-toastify';
import QRScanner from '../qr/QRScanner';
import FaceVerification from '../face/FaceVerification';
import { useGeolocation } from '../../hooks/useGeolocation';
import { attendanceAPI } from '../../services/api/attendance';
import { userAPI } from '../../services/api/user';
import { useAuthStore } from '../../store/authStore';
import GlassCard from '../common/GlassCard';

const AttendanceScanner = ({ sessionId: propSessionId, onClose, onSuccess }) => {
  const [step, setStep] = useState('loading'); // loading -> face -> qr -> gps -> submit
  const [qrCode, setQrCode] = useState(null);
  const [scannedSessionId, setScannedSessionId] = useState(propSessionId);
  const [faceResult, setFaceResult] = useState(null);
  const [enrolledDescriptor, setEnrolledDescriptor] = useState(null);
  const [submitting, setSubmitting] = useState(false);
  const navigate = useNavigate();
  const { user } = useAuthStore();
  const { location, error: locationError, getCurrentLocation, loading: locationLoading } = useGeolocation();

  useEffect(() => {
    const checkEnrollment = async () => {
      try {
        // First check local storage for speed
        const storedDescriptor = localStorage.getItem('face_descriptor');
        
        if (storedDescriptor) {
          setEnrolledDescriptor(JSON.parse(storedDescriptor));
          setStep('face');
        } else {
          // If not in local, try fetching profile (maybe enrolled on another device)
          const profile = await userAPI.getProfile();
          if (profile.face_enrolled && profile.face_descriptor) {
             const desc = JSON.parse(profile.face_descriptor);
             // Cache it
             localStorage.setItem('face_descriptor', JSON.stringify(desc));
             setEnrolledDescriptor(desc);
             setStep('face');
          } else {
            toast.warning('You must enroll your face first!');
            navigate('/profile');
          }
        }
      } catch (error) {
        console.error('Failed to fetch enrollment:', error);
        toast.error('Could not verify enrollment status');
        navigate('/profile');
      }
    };
    checkEnrollment();
  }, [navigate]);

  const handleFaceVerified = (result) => {
    setFaceResult(result);
    if (result.match) {
      toast.success('Face Verified! Please scan the QR code.');
      setStep('qr');
    }
  };

  const handleQRScanned = (code) => {
    // Expected format: SESSION_{ID}_{TIMESTAMP}_{RANDOM}
    // Or just a raw string if legacy.
    // Try to extract session ID if not provided via props
    if (!scannedSessionId) {
      const match = code.match(/^SESSION_(\d+)_/);
      if (match && match[1]) {
        setScannedSessionId(parseInt(match[1]));
        console.log('Extracted Session ID:', match[1]);
      } else {
        // Fallback: If we can't extract ID and don't have prop, we can't proceed really.
        // But maybe the backend can handle it if we send just code?
        // AttendanceService expects session_id.
        // We will proceed and hope backend validation handles it or user selected session (if we add selection later)
        console.warn('Could not extract Session ID from QR');
      }
    }
    
    setQrCode(code);
    setStep('gps');
    getCurrentLocation();
  };

  const handleSubmit = async () => {
    const finalSessionId = scannedSessionId || propSessionId;

    if (!finalSessionId) {
      toast.error('Invalid QR Code: Could not identify session.');
      setStep('qr'); // Retry scan
      return;
    }

    if (!qrCode || !faceResult || !location) {
      toast.error('Please complete all verification steps');
      return;
    }

    setSubmitting(true);
    try {
      if (!enrolledDescriptor) {
        toast.error('Face enrollment missing.');
        return;
      }

      await attendanceAPI.verify({
        session_id: finalSessionId,
        qr_code: qrCode,
        face_descriptor: enrolledDescriptor,
        location: {
          lat: location.lat,
          lng: location.lng,
          accuracy: location.accuracy
        }
      });

      if (onSuccess) {
        onSuccess();
      } else {
        toast.success('Attendance verified successfully!');
        navigate('/attendance');
      }
    } catch (error) {
      toast.error(error.message || 'Verification failed');
      // If error, maybe go back to QR step? Or stay here?
      // Stay on GPS step to allow retry submission
    } finally {
      setSubmitting(false);
    }
  };

  const steps = [
    { id: 'face', icon: '👤', label: 'Face Auth' },
    { id: 'qr', icon: '📱', label: 'Scan QR' },
    { id: 'gps', icon: '📍', label: 'Location' },
  ];

  if (step === 'loading') {
     return <div className="text-center p-10 text-white">Checking enrollment...</div>;
  }

  return (
    <div className="max-w-2xl mx-auto space-y-8 animate-in fade-in zoom-in-95 duration-500 text-left">
      <div className="flex justify-between items-center">
        <div>
          <h2 className="text-3xl font-extrabold text-white mb-2 tracking-tight">Identity Verification</h2>
          <p className="text-white/40 font-medium tracking-tight">Multi-factor biometric attendance check</p>
        </div>
        <button onClick={onClose} className="text-white/40 hover:text-white px-4 py-2">
          Cancel
        </button>
      </div>

      <GlassCard className="border border-white/10 relative overflow-hidden">
        <div className="absolute -top-24 -left-24 w-48 h-48 bg-premium-primary/10 blur-[60px]"></div>
        
        {/* Progress System */}
        <div className="flex justify-between items-center mb-12 relative z-10 px-4">
          {steps.map((s, idx) => {
            const isCompleted = steps.findIndex(x => x.id === step) > idx;
            const isActive = step === s.id;
            return (
              <div key={s.id} className="flex flex-col items-center flex-1 relative">
                <div className={`w-12 h-12 rounded-2xl flex items-center justify-center text-xl z-10 transition-all duration-500 border ${
                  isCompleted ? 'bg-premium-accent/20 border-premium-accent/30 text-premium-accent' :
                  isActive ? 'bg-gradient-premium border-white/20 text-white shadow-lg shadow-premium-primary/30 scale-110' :
                  'bg-white/5 border-white/5 text-white/20'
                }`}>
                  {isCompleted ? '✓' : s.icon}
                </div>
                <p className={`mt-3 text-[10px] font-black uppercase tracking-widest ${isActive ? 'text-premium-primary' : 'text-white/20'}`}>
                  {s.label}
                </p>
                {idx < steps.length - 1 && (
                  <div className={`absolute left-1/2 top-6 w-full h-[2px] transition-colors duration-500 -z-0 ${isCompleted ? 'bg-premium-accent' : 'bg-white/5'}`}></div>
                )}
              </div>
            );
          })}
        </div>

        <div className="relative z-10 min-h-[300px]">
          {step === 'face' && enrolledDescriptor && (
            <div className="animate-in fade-in duration-500">
               <div className="text-center mb-4">
                 <p className="text-white font-bold">Verify your identity</p>
                 <p className="text-white/40 text-sm">Look at the camera to unlock QR scanner</p>
               </div>
              <FaceVerification
                enrolledDescriptor={enrolledDescriptor}
                onVerify={handleFaceVerified}
                onClose={onClose}
              />
            </div>
          )}

          {step === 'qr' && (
            <div className="animate-in fade-in duration-500">
              <div className="text-center mb-4">
                 <p className="text-white font-bold">Scan Session QR</p>
                 <p className="text-white/40 text-sm">Face verified. Now scan the class code.</p>
               </div>
              <QRScanner onScan={handleQRScanned} onClose={onClose} />
              {/* Back button needed in QRScanner? onClose passed handles it */}
            </div>
          )}

          {step === 'gps' && (
            <div className="py-10 text-center animate-in slide-in-from-bottom-4 duration-500">
              {locationLoading ? (
                <div className="space-y-6">
                  <div className="relative w-20 h-20 mx-auto">
                    <div className="absolute inset-0 border-4 border-white/5 rounded-full"></div>
                    <div className="absolute inset-0 border-4 border-t-premium-primary border-transparent rounded-full animate-spin"></div>
                  </div>
                  <p className="text-white font-bold tracking-tight">Syncing GPS Coordinates...</p>
                </div>
              ) : locationError ? (
                <div className="space-y-6">
                  <div className="w-20 h-20 bg-red-400/10 text-red-400 rounded-3xl flex items-center justify-center text-4xl mx-auto border border-red-400/20">⚠</div>
                  <p className="text-red-400 font-bold">{locationError}</p>
                  <button onClick={getCurrentLocation} className="px-8 py-3 bg-white/5 hover:bg-white/10 text-white rounded-2xl border border-white/10 transition-all font-bold">
                    Retry Location Sync
                  </button>
                </div>
              ) : location ? (
                <div className="space-y-8">
                  <div className="w-24 h-24 bg-premium-accent/10 text-premium-accent rounded-[2rem] flex items-center justify-center text-4xl mx-auto border border-premium-accent/20 animate-bounce">📍</div>
                  <div className="p-6 rounded-3xl bg-premium-accent/5 border border-premium-accent/10">
                    <p className="text-premium-accent font-black uppercase tracking-widest text-xs mb-1">Position Locked</p>
                    <p className="text-white font-bold tracking-tight">Accuracy: {location.accuracy?.toFixed(0)}m</p>
                    {scannedSessionId && <p className="text-white/40 text-xs mt-2">Session ID: {scannedSessionId}</p>}
                  </div>
                  <button
                    onClick={handleSubmit}
                    disabled={submitting}
                    className="w-full bg-gradient-premium text-white font-black py-4 rounded-2xl shadow-xl shadow-premium-primary/20 hover:scale-[1.02] active:scale-[0.98] transition-all disabled:opacity-50"
                  >
                    {submitting ? 'Authenticating Identity...' : 'Finalize Attendance'}
                  </button>
                </div>
              ) : null}
            </div>
          )}
        </div>
      </GlassCard>
    </div>
  );
};

export default AttendanceScanner;
