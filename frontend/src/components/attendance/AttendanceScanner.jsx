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
    if (result.verified) {
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
          {step === 'select' && 'Choose your authentication method'}
          {step === 'authenticate' && `Complete ${authMethod === 'qr' ? 'QR' : 'face'} authentication`}
          {step === 'gps' && 'Verify your location'}
        </p>
      </div>

      <GlassCard className="border border-white/10 relative overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-br from-premium-primary/5 via-transparent to-premium-accent/5 pointer-events-none"></div>
        
        <div className="relative z-10">
          {step === 'select' && (
            <div className="py-8 space-y-6 animate-in fade-in duration-500">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {authMethods.map((method) => (
                  <button
                    key={method.id}
                    onClick={() => !method.disabled && handleMethodSelect(method.id)}
                    disabled={method.disabled}
                    className={`group relative p-8 rounded-3xl border-2 transition-all duration-300 ${
                      method.disabled
                        ? 'border-white/5 bg-white/[0.02] cursor-not-allowed opacity-40'
                        : 'border-white/10 bg-white/5 hover:bg-white/10 hover:border-premium-primary/30 hover:scale-105 cursor-pointer'
                    }`}
                  >
                    <div className="text-center space-y-4">
                      <div className="text-6xl mb-4 transform group-hover:scale-110 transition-transform duration-300">
                        {method.icon}
                      </div>
                      <h3 className="text-xl font-bold text-white tracking-tight">{method.label}</h3>
                      <p className="text-white/40 text-sm">{method.desc}</p>
                      {method.disabled && (
                        <p className="text-red-400 text-xs mt-2">Not enrolled</p>
                      )}
                    </div>
                  </button>
                ))}
              </div>
              <div className="text-center pt-4">
                <button
                  onClick={() => navigate('/attendance')}
                  className="text-white/40 hover:text-white transition-colors text-sm font-medium"
                >
                  Cancel
                </button>
              </div>
            </div>
          )}

          {step === 'authenticate' && authMethod === 'qr' && (
            <div className="animate-in fade-in duration-500">
              <QRScanner
                onScan={handleQRScanned}
                onClose={() => setStep('select')}
              />
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
                  <div className="relative w-24 h-24 mx-auto">
                    <div className="absolute inset-0 rounded-full border-4 border-premium-primary/20"></div>
                    <div className="absolute inset-0 rounded-full border-4 border-t-premium-primary animate-spin"></div>
                    <div className="absolute inset-0 flex items-center justify-center text-3xl">📍</div>
                  </div>
                  <p className="text-white font-bold tracking-tight">Acquiring GPS Location...</p>
                  <p className="text-white/40 text-sm">Please ensure location services are enabled</p>
                </div>
              ) : locationError ? (
                <div className="space-y-6">
                  <div className="text-6xl">⚠️</div>
                  <p className="text-red-400 font-bold">Location Error</p>
                  <p className="text-white/60 text-sm max-w-md mx-auto">{locationError}</p>
                  <button
                    onClick={getCurrentLocation}
                    className="px-8 py-3 rounded-2xl bg-premium-primary/20 border border-premium-primary/30 text-premium-primary font-bold hover:bg-premium-primary/30 transition-all"
                  >
                    Retry
                  </button>
                </div>
              ) : location ? (
                <div className="space-y-8 animate-in zoom-in-95 duration-500">
                  <div className="text-6xl">✅</div>
                  <div className="p-6 rounded-3xl bg-premium-accent/5 border border-premium-accent/10">
                    <p className="text-premium-accent font-black uppercase tracking-widest text-xs mb-1">Position Locked</p>
                    <p className="text-white font-bold tracking-tight">Accuracy: {location.accuracy?.toFixed(0)}m</p>
                  </div>
                  <button
                    onClick={handleSubmit}
                    disabled={submitting}
                    className="w-full px-8 py-4 rounded-2xl bg-gradient-premium text-white font-bold text-lg shadow-lg shadow-premium-primary/30 hover:shadow-premium-primary/50 hover:scale-105 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100"
                  >
                    {submitting ? 'Submitting...' : 'Submit Attendance'}
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
