import { useEffect, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { toast } from 'react-toastify';
import apiClient from '../services/api/client';
import GlassCard from '../components/common/GlassCard';

const PaymentDemo = () => {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const [processing, setProcessing] = useState(false);
  const [countdown, setCountdown] = useState(3);

  const paymentId = searchParams.get('payment_id');
  const amount = searchParams.get('amount');
  const gateway = searchParams.get('gateway');
  const transactionId = searchParams.get('transaction_id');

  useEffect(() => {
    // Auto-complete payment after countdown
    const timer = setInterval(() => {
      setCountdown((prev) => {
        if (prev <= 1) {
          clearInterval(timer);
          completePayment();
          return 0;
        }
        return prev - 1;
      });
    }, 1000);

    return () => clearInterval(timer);
  }, []);

  const completePayment = async () => {
    try {
      setProcessing(true);
      
      // Call webhook to mark payment as completed
      await apiClient.post(`/payment/webhook/demo`, {
        payment_id: paymentId,
        transaction_id: transactionId,
        status: 'completed'
      });

      toast.success('Payment completed successfully!');
      
      // Redirect back to sessions
      setTimeout(() => {
        navigate('/sessions');
      }, 1000);
    } catch (error) {
      console.error('Payment completion error:', error);
      toast.error('Payment completion failed');
    } finally {
      setProcessing(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center p-6">
      <GlassCard className="max-w-md w-full text-center">
        <div className="mb-8">
          <div className="w-20 h-20 mx-auto mb-6 rounded-full bg-premium-accent/20 flex items-center justify-center">
            <span className="text-4xl">💳</span>
          </div>
          
          <h1 className="text-3xl font-bold text-white mb-2">Demo Payment</h1>
          <p className="text-white/60 text-sm uppercase tracking-widest font-bold">
            {gateway === 'sslcommerz' ? 'SSLCommerz' : 'Stripe'} Gateway
          </p>
        </div>

        <div className="space-y-4 mb-8">
          <div className="p-4 rounded-2xl bg-white/5 border border-white/10">
            <p className="text-white/40 text-xs uppercase mb-1">Amount</p>
            <p className="text-3xl font-black text-white">${amount}</p>
          </div>

          <div className="p-4 rounded-2xl bg-white/5 border border-white/10">
            <p className="text-white/40 text-xs uppercase mb-1">Transaction ID</p>
            <p className="text-white/60 font-mono text-sm">{transactionId}</p>
          </div>
        </div>

        {countdown > 0 ? (
          <div className="space-y-4">
            <div className="text-center">
              <p className="text-white/60 mb-2">Auto-completing in</p>
              <div className="text-6xl font-black text-premium-accent">{countdown}</div>
            </div>
            <div className="w-full h-2 bg-white/10 rounded-full overflow-hidden">
              <div 
                className="h-full bg-gradient-premium transition-all duration-1000"
                style={{ width: `${((3 - countdown) / 3) * 100}%` }}
              />
            </div>
          </div>
        ) : (
          <div className="flex items-center justify-center gap-2 text-premium-accent">
            <div className="animate-spin h-5 w-5 border-2 border-premium-accent/20 border-t-premium-accent rounded-full" />
            <span className="font-bold">Processing payment...</span>
          </div>
        )}

        <div className="mt-8 p-4 rounded-xl bg-yellow-500/10 border border-yellow-500/20">
          <p className="text-yellow-500 text-xs font-bold uppercase">
            ⚠️ Demo Mode - No real payment processed
          </p>
        </div>
      </GlassCard>
    </div>
  );
};

export default PaymentDemo;
