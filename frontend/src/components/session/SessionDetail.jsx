import { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { sessionAPI } from '../../services/api/session';
import { attendanceAPI } from '../../services/api/attendance';
import { initiatePayment } from '../../services/api/payment';
import { useAuthStore } from '../../store/authStore';
import { toast } from 'react-toastify';
import { QRCodeSVG } from 'qrcode.react';
import GlassCard from '../common/GlassCard';
import AttendanceScanner from '../attendance/AttendanceScanner';

const SessionDetail = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const { user } = useAuthStore();
  const [session, setSession] = useState(null);
  const [loading, setLoading] = useState(true);
  const [showQR, setShowQR] = useState(false);
  const [qrData, setQrData] = useState(null);
  const [showScanner, setShowScanner] = useState(false);
  const [showPaymentModal, setShowPaymentModal] = useState(false);
  const [processingPayment, setProcessingPayment] = useState(false);

  useEffect(() => {
    fetchSession();
  }, [id]);

  const fetchSession = async () => {
    try {
      setLoading(true);
      const response = await sessionAPI.getById(id);
      console.log('Session detail response:', response);
      // Extract session data properly
      const sessionData = response.data?.data || response.data || response;
      setSession(sessionData);
    } catch (error) {
      console.error('Session detail error:', error);
      toast.error('Failed to load session');
      navigate('/sessions');
    } finally {
      setLoading(false);
    }
  };

  const handleGetQR = async () => {
    try {
      setLoading(true);
      const response = await sessionAPI.getQR(id);
      setQrData(response.data);
      setShowQR(true);
    } catch (error) {
      const message = error.response?.data?.message || 'Failed to generate QR code';
      toast.error(message);
    } finally {
      setLoading(false);
    }
  };

  const handleMarkAttendance = () => {
    setShowScanner(true);
  };

  const handlePaymentInitiate = async (gateway) => {
    try {
      setProcessingPayment(true);
      const response = await initiatePayment({
        session_id: session.id,
        gateway: gateway
      });
      
      // Handle both nested and flat response structures
      const paymentData = response.data || response;
      
      if (paymentData.payment_url) {
        window.location.href = paymentData.payment_url;
      } else {
        toast.error('Failed to get payment URL');
      }
    } catch (error) {
      console.error('Payment initiation error:', error);
      toast.error(error.response?.data?.message || 'Payment initiation failed');
    } finally {
      setProcessingPayment(false);
      setShowPaymentModal(false);
    }
  };

  if (loading) {
    return <div className="text-center p-8">Loading...</div>;
  }

  if (!session) {
    return null;
  }

  const isAdminOrManager = user?.role === 'admin' || user?.role === 'session_manager';
  const canMarkAttendance = session.status === 'active' && !session.attendance_status;

  if (showScanner) {
    return <AttendanceScanner sessionId={parseInt(id)} />;
  }

  return (
    <div className="space-y-8 pb-10">
      <div className="flex justify-between items-center">
        <button
          onClick={() => navigate('/sessions')}
          className="flex items-center gap-2 text-white/40 hover:text-white transition-colors font-bold uppercase tracking-widest text-xs"
        >
          <span>←</span> Back to Sessions
        </button>

        {(user?.role === 'admin' || user?.role === 'organization_admin' || user?.role === 'session_manager' || (user?.role === 'teacher' && session.created_by === user.id)) && (
          <button
            onClick={() => navigate(`/sessions/edit/${id}`)}
            className="flex items-center gap-2 text-premium-primary hover:text-premium-primary/80 transition-colors font-bold uppercase tracking-widest text-xs bg-premium-primary/10 px-4 py-2 rounded-lg border border-premium-primary/20"
          >
            <span>✎</span> Edit Session
          </button>
        )}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div className="lg:col-span-2 space-y-8">
          <GlassCard className="relative overflow-hidden border border-white/5">
            <div className="absolute -top-32 -right-32 w-80 h-80 bg-premium-primary/10 blur-[100px]"></div>
            
            <div className="flex justify-between items-start mb-8 relative z-10">
              <div>
                <h1 className="text-4xl font-extrabold text-white mb-4 tracking-tight">{session.title}</h1>
                <span className={`px-4 py-1.5 text-[10px] font-black uppercase tracking-widest rounded-xl border ${
                  session.status === 'active' ? 'bg-premium-accent/10 text-premium-accent border-premium-accent/20' :
                  session.status === 'completed' ? 'bg-white/5 text-white/30 border-white/10' :
                  'bg-yellow-500/10 text-yellow-500 border-yellow-500/20'
                }`}>
                  {session.status}
                </span>
              </div>
            </div>

            {session.description && (
              <p className="text-white/60 text-lg leading-relaxed mb-10 relative z-10 font-medium">
                {session.description}
              </p>
            )}

            <div className="grid grid-cols-1 md:grid-cols-2 gap-8 relative z-10 pt-8 border-t border-white/5">
              <div className="space-y-6">
                <div>
                  <h3 className="text-xs font-black text-white/20 uppercase tracking-widest mb-4">Timing Details</h3>
                  <div className="space-y-3">
                    <div className="flex items-center justify-between p-3 rounded-2xl bg-white/5 border border-white/5">
                      <span className="text-white/40 text-sm font-bold uppercase">Starts</span>
                      <span className="text-white font-bold">{new Date(session.start_time).toLocaleString()}</span>
                    </div>
                    <div className="flex items-center justify-between p-3 rounded-2xl bg-white/5 border border-white/5">
                      <span className="text-white/40 text-sm font-bold uppercase">Ends</span>
                      <span className="text-white font-bold">{new Date(session.end_time).toLocaleString()}</span>
                    </div>
                  </div>
                </div>
              </div>

              <div className="space-y-6">
                <div>
                  <h3 className="text-xs font-black text-white/20 uppercase tracking-widest mb-4">Location Info</h3>
                  <div className="space-y-3">
                   {session.location?.name && (
                      <div className="p-3 rounded-2xl bg-white/5 border border-white/5">
                        <p className="text-white/40 text-[10px] font-black uppercase mb-1">Venue</p>
                        <p className="text-white font-bold">{session.location.name}</p>
                      </div>
                    )}
                    <div className="p-3 rounded-2xl bg-white/5 border border-white/5">
                      <p className="text-white/40 text-[10px] font-black uppercase mb-1">Verification Radius</p>
                      <p className="text-white font-bold">{session.radius_meters} Meters</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </GlassCard>
        </div>

        <div className="space-y-8">
          <GlassCard className="border border-premium-primary/20 bg-premium-primary/5">
            <h3 className="text-xl font-bold text-white mb-6">Attendance Actions</h3>
            
            <div className="space-y-4">
              {canMarkAttendance ? (
                <button
                  onClick={handleMarkAttendance}
                  className="w-full bg-gradient-premium text-white font-bold py-4 rounded-2xl shadow-lg shadow-premium-primary/20 hover:scale-[1.02] transition-all"
                >
                  Mark Attendance
                </button>
              ) : (
                <div className="p-4 rounded-2xl bg-white/5 border border-white/5 text-center">
                  <p className="text-white/20 text-xs font-black uppercase tracking-widest">Attendance window closed</p>
                </div>
              )}

              {isAdminOrManager && (
                <button
                  onClick={handleGetQR}
                  className="w-full bg-white/5 hover:bg-white/10 text-white font-bold py-4 rounded-2xl border border-white/10 transition-all"
                >
                  Generate Session QR
                </button>
              )}
            </div>
          </GlassCard>

          {session.requires_payment && (
            <GlassCard className="border border-premium-accent/20 bg-premium-accent/5">
              <div className="flex items-center justify-between mb-4">
                <span className="text-xs font-black text-premium-accent uppercase tracking-widest">Premium Entry</span>
                <span className="text-2xl font-black text-white">${session.payment_amount}</span>
              </div>
              {!session.registration_status && (
                <button 
                  onClick={() => setShowPaymentModal(true)}
                  className="w-full bg-premium-accent text-dark font-black py-4 rounded-2xl shadow-lg shadow-premium-accent/10 hover:scale-[1.02] transition-all"
                >
                  Pay & Register
                </button>
              )}
              {/* DEBUG INFO - TO BE REMOVED */}
               <div className="mt-2 p-2 bg-black/50 text-[10px] font-mono text-white/50">
                 Status: {JSON.stringify(session.registration_status)} | Payment: {JSON.stringify(session.requires_payment)}
               </div>
            </GlassCard>
          )}

          {showQR && qrData && (
            <GlassCard className="border border-white/10 animate-in fade-in slide-in-from-bottom-4 duration-500">
              <h3 className="text-xs font-black text-white/20 uppercase tracking-widest mb-6">Session QR Code</h3>
              <div className="flex flex-col items-center gap-6">
                <div className="p-6 bg-white rounded-3xl overflow-hidden shadow-2xl">
                  <QRCodeSVG 
                    value={qrData.qr_code}
                    size={256}
                    level="H"
                    includeMargin={true}
                  />
                </div>
                <div className="text-center w-full">
                  <p className="text-xs text-white/40 font-bold mb-2 uppercase">Scan this QR Code</p>
                  <p className="text-white/60 font-mono text-xs bg-white/5 py-3 px-4 rounded-xl border border-white/5 break-all">
                    {qrData.qr_code}
                  </p>
                </div>
                <div className="text-center w-full">
                  <p className="text-xs text-white/40 font-bold mb-2 uppercase">Valid Until</p>
                  <p className="text-white font-medium bg-white/5 py-2 rounded-xl border border-white/5">
                    {new Date(qrData.expires_at).toLocaleTimeString()}
                  </p>
                </div>
              </div>
            </GlassCard>
          )}
        </div>
      </div>

      {/* Payment Modal */}
      {showPaymentModal && (
        <div className="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-6">
          <div className="w-full max-w-md bg-dark border border-white/10 rounded-3xl p-8 relative">
            <button 
              onClick={() => setShowPaymentModal(false)}
              className="absolute top-4 right-4 text-white/40 hover:text-white"
            >
              <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
            
            <h2 className="text-2xl font-bold text-white mb-6">Select Payment Method</h2>
            
            <div className="space-y-4">
              <button
                disabled={processingPayment}
                onClick={() => handlePaymentInitiate('sslcommerz')}
                className="w-full p-4 rounded-2xl bg-white/5 border border-white/10 hover:bg-white/10 hover:border-premium-primary/50 transition-all flex items-center justify-between group"
              >
                <div className="flex items-center gap-4">
                  <div className="w-10 h-10 rounded-full bg-orange-500/20 flex items-center justify-center text-orange-500">
                    💳
                  </div>
                  <div className="text-left">
                    <p className="font-bold text-white">SSLCommerz</p>
                    <p className="text-xs text-white/40">Cards, Mobile Banking</p>
                  </div>
                </div>
                <span className="text-white/20 group-hover:text-white transition-colors">→</span>
              </button>

              <button
                disabled={processingPayment}
                onClick={() => handlePaymentInitiate('stripe')}
                className="w-full p-4 rounded-2xl bg-white/5 border border-white/10 hover:bg-white/10 hover:border-premium-primary/50 transition-all flex items-center justify-between group"
              >
                <div className="flex items-center gap-4">
                  <div className="w-10 h-10 rounded-full bg-blue-500/20 flex items-center justify-center text-blue-500">
                    S
                  </div>
                  <div className="text-left">
                    <p className="font-bold text-white">Stripe</p>
                    <p className="text-xs text-white/40">Credit/Debit Cards</p>
                  </div>
                </div>
                <span className="text-white/20 group-hover:text-white transition-colors">→</span>
              </button>
            </div>

            {processingPayment && (
              <div className="mt-6 text-center text-white/60 flex items-center justify-center gap-2">
                <div className="animate-spin h-4 w-4 border-2 border-white/20 border-t-white rounded-full"></div>
                <span>Processing secure payment...</span>
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
};

export default SessionDetail;

