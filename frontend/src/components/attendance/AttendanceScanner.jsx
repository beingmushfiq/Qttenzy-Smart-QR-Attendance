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
  const [step, setStep] = useState('select'); // select -> authenticate -> gps -> submit
  const [authMethod, setAuthMethod] = useState(null); // 'qr' or 'face'
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
        // Try to get from API first
        const response = await userAPI.getFaceEnrollment();
        if (response.success && response.data.face_descriptor) {
          setEnrolledDescriptor(response.data.face_descriptor);
          // Update localStorage as cache
          localStorage.setItem('face_descriptor', JSON.stringify(response.data.face_descriptor));
          return;
        }
      } catch (error) {
        console.log('API fetch failed, trying localStorage fallback');
      }
      
      // Fallback to localStorage
      const storedDescriptor = localStorage.getItem('face_descriptor');
      if (storedDescriptor) {
        setEnrolledDescriptor(JSON.parse(storedDescriptor));
      }
    };
    fetchEnrolledFace();
  }, []);

  const handleMethodSelect = (method) => {
    setAuthMethod(method);
    setStep('authenticate');
  };

  const handleQRScanned = (code) => {
    setQrCode(code);
    setStep('gps');
    getCurrentLocation();
  };

  const handleFaceVerified = (result) => {
    setFaceResult(result);
    if (result.match) {
      setStep('gps');
      getCurrentLocation();
    }
  };

  const handleSubmit = async () => {
    if (!location) {
      toast.error('Please wait for GPS location');
      return;
    }

    setSubmitting(true);
    try {
      const payload = {
        session_id: sessionId,
        location: {
          lat: location.lat,
          lng: location.lng,
          accuracy: location.accuracy
        }
      };

      // Add authentication data based on selected method
      if (authMethod === 'qr' && qrCode) {
        payload.qr_code = qrCode;
      } else if (authMethod === 'face' && enrolledDescriptor) {
        payload.face_descriptor = enrolledDescriptor;
      } else {
        toast.error('Authentication data missing');
        return;
      }

      await attendanceAPI.verify(payload);

      toast.success('Attendance verified successfully!');
      navigate('/attendance');
    } catch (error) {
      toast.error(error.message || 'Verification failed');
    } finally {
      setSubmitting(false);
    }
  };

  const authMethods = [
    { id: 'qr', icon: '📱', label: 'Scan QR', desc: 'Scan session QR code' },
    { id: 'face', icon: '👤', label: 'Face Auth', desc: 'Verify with face recognition', disabled: !enrolledDescriptor },
  ];

  return (
    <div className="max-w-2xl mx-auto space-y-8 animate-in fade-in zoom-in-95 duration-500 text-left">
      <div className="text-center">
        <h2 className="text-3xl font-extrabold text-white mb-2 tracking-tight">Mark Attendance</h2>
        <p className="text-white/40 font-medium tracking-tight">
          {step === 'select' ? 'Choose your authentication method' : 'Multi-factor biometric attendance check'}
        </p>
      </div>

      <GlassCard className="border border-white/10 relative overflow-hidden">
        <div className="absolute -top-24 -left-24 w-48 h-48 bg-premium-primary/10 blur-[60px]"></div>
        
        <div className="relative z-10">
          {step === 'select' && (
            <div className="py-8 space-y-6 animate-in fade-in duration-500">
              <h3 className="text-center text-white/60 font-bold uppercase tracking-widest text-xs mb-8">
                Select Authentication Method
              </h3>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {authMethods.map((method) => (
                  <button
                    key={method.id}
                    onClick={() => !method.disabled && handleMethodSelect(method.id)}
                    disabled={method.disabled}
                    className={`p-6 rounded-2xl border-2 transition-all duration-300 ${
                      method.disabled
                        ? 'bg-white/5 border-white/10 opacity-50 cursor-not-allowed'
                        : 'bg-white/5 border-white/20 hover:border-premium-primary hover:bg-premium-primary/10 hover:scale-105 active:scale-95'
                    }`}
                  >
                    <div className="text-5xl mb-4">{method.icon}</div>
                    <h4 className="text-white font-bold text-lg mb-1">{method.label}</h4>
                    <p className="text-white/40 text-sm">{method.desc}</p>
                    {method.disabled && (
                      <p className="text-red-400 text-xs mt-2">Face not enrolled</p>
                    )}
                  </button>
                ))}
              </div>
              <button
                onClick={() => navigate('/sessions')}
                className="w-full mt-6 px-6 py-3 bg-white/5 hover:bg-white/10 text-white rounded-2xl border border-white/10 transition-all font-bold"
              >
                Cancel
              </button>
            </div>
          )}

          {step === 'authenticate' && authMethod === 'qr' && (
            <div className="animate-in fade-in duration-500">
              <QRScanner onScan={handleQRScanned} onClose={() => setStep('select')} />
            </div>
          )}

          {step === 'authenticate' && authMethod === 'face' && enrolledDescriptor && (
            <div className="animate-in fade-in duration-500">
              <FaceVerification
                enrolledDescriptor={enrolledDescriptor}
                onVerify={handleFaceVerified}
                onClose={() => setStep('select')}
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
