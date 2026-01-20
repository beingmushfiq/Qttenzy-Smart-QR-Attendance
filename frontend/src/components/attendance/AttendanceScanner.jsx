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

const AttendanceScanner = ({ sessionId }) => {
  const [step, setStep] = useState('qr'); // qr -> face -> gps -> submit
  const [qrCode, setQrCode] = useState(null);
  const [faceResult, setFaceResult] = useState(null);
  const [enrolledDescriptor, setEnrolledDescriptor] = useState(null);
  const [submitting, setSubmitting] = useState(false);
  const navigate = useNavigate();
  const { user } = useAuthStore();
  const { location, error: locationError, getCurrentLocation, loading: locationLoading } = useGeolocation();

  useEffect(() => {
    const fetchEnrolledFace = async () => {
      try {
        const response = await userAPI.getProfile();
        const storedDescriptor = localStorage.getItem('face_descriptor');
        if (storedDescriptor) {
          setEnrolledDescriptor(JSON.parse(storedDescriptor));
        }
      } catch (error) {
        console.error('Failed to fetch enrolled face:', error);
      }
    };
    fetchEnrolledFace();
  }, []);

  const handleQRScanned = (code) => {
    setQrCode(code);
    setStep('face');
  };

  const handleFaceVerified = (result) => {
    setFaceResult(result);
    if (result.match) {
      setStep('gps');
      getCurrentLocation();
    }
  };

  const handleSubmit = async () => {
    if (!qrCode || !faceResult || !location) {
      toast.error('Please complete all verification steps');
      return;
    }

    setSubmitting(true);
    try {
      if (!enrolledDescriptor) {
        toast.error('Face enrollment not found.');
        setStep('face');
        return;
      }

      await attendanceAPI.verify({
        session_id: sessionId,
        qr_code: qrCode,
        face_descriptor: enrolledDescriptor,
        location: {
          lat: location.lat,
          lng: location.lng,
          accuracy: location.accuracy
        }
      });

      toast.success('Attendance verified successfully!');
      navigate('/attendance');
    } catch (error) {
      toast.error(error.message || 'Verification failed');
    } finally {
      setSubmitting(false);
    }
  };

  const steps = [
    { id: 'qr', icon: '📱', label: 'Scan QR' },
    { id: 'face', icon: '👤', label: 'Face Auth' },
    { id: 'gps', icon: '📍', label: 'Location' },
  ];

  return (
    <div className="max-w-2xl mx-auto space-y-8 animate-in fade-in zoom-in-95 duration-500 text-left">
      <div className="text-center">
        <h2 className="text-3xl font-extrabold text-white mb-2 tracking-tight">Identity Verification</h2>
        <p className="text-white/40 font-medium tracking-tight">Multi-factor biometric attendance check</p>
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

        <div className="relative z-10">
          {step === 'qr' && (
            <div className="animate-in fade-in duration-500">
              <QRScanner onScan={handleQRScanned} onClose={() => navigate('/sessions')} />
            </div>
          )}

          {step === 'face' && enrolledDescriptor && (
            <div className="animate-in fade-in duration-500">
              <FaceVerification
                enrolledDescriptor={enrolledDescriptor}
                onVerify={handleFaceVerified}
                onClose={() => setStep('qr')}
              />
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
