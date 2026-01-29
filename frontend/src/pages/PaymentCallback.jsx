import { useEffect, useState } from 'react'
import { useSearchParams, useNavigate, Link } from 'react-router-dom'
import { toast } from 'react-toastify'

const PaymentCallback = () => {
  const [searchParams] = useSearchParams()
  const navigate = useNavigate()
  const [status, setStatus] = useState('processing')
  
  useEffect(() => {
    const processCallback = async () => {
      // Get params based on gateway
      const gatewayStatus = searchParams.get('status')
      
      if (gatewayStatus === 'success' || gatewayStatus === 'VALID') {
        setStatus('success')
        toast.success('Payment successful! Registration confirmed.')
        
        // Wait briefly then redirect
        setTimeout(() => {
          navigate('/sessions')
        }, 3000)
      } else if (gatewayStatus === 'cancel' || gatewayStatus === 'FAILED' || gatewayStatus === 'CANCELLED') {
        setStatus('failed')
        toast.error('Payment failed or cancelled.')
      } else {
        setStatus('failed')
        toast.error('Unknown payment status.')
      }
    }

    processCallback()
  }, [searchParams, navigate])

  return (
    <div className="min-h-screen flex items-center justify-center p-6">
      <div className="max-w-md w-full glass rounded-3xl p-8 border border-white/10 text-center">
        {status === 'processing' && (
          <div className="flex flex-col items-center">
            <div className="animate-spin rounded-full h-16 w-16 border-b-2 border-premium-primary mb-6"></div>
            <h2 className="text-2xl font-bold text-white mb-2">Processing Payment</h2>
            <p className="text-white/60">Please wait while we verify your transaction...</p>
          </div>
        )}

        {status === 'success' && (
          <div className="flex flex-col items-center">
            <div className="w-16 h-16 bg-green-500/20 rounded-full flex items-center justify-center mb-6">
              <svg className="w-8 h-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <h2 className="text-2xl font-bold text-white mb-2">Payment Successful!</h2>
            <p className="text-white/60 mb-6">Your registration has been confirmed. You will be redirected shortly.</p>
            <Link to="/sessions" className="bg-white/10 text-white px-6 py-2 rounded-xl hover:bg-white/20 transition-all">
              Go to Sessions
            </Link>
          </div>
        )}

        {status === 'failed' && (
          <div className="flex flex-col items-center">
            <div className="w-16 h-16 bg-red-500/20 rounded-full flex items-center justify-center mb-6">
              <svg className="w-8 h-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M6 18L18 6M6 6l12 12" />
              </svg>
            </div>
            <h2 className="text-2xl font-bold text-white mb-2">Payment Failed</h2>
            <p className="text-white/60 mb-6">The transaction was cancelled or failed. Please try again.</p>
            <Link to="/sessions" className="bg-white/10 text-white px-6 py-2 rounded-xl hover:bg-white/20 transition-all">
              Return to Sessions
            </Link>
          </div>
        )}
      </div>
    </div>
  )
}

export default PaymentCallback
