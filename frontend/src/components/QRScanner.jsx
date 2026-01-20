import { useState, useRef, useEffect } from 'react'
import { Html5QrcodeScanner } from 'html5-qrcode'
import { toast } from 'react-toastify'

const QRScanner = ({ onScan, onError }) => {
  const [scanning, setScanning] = useState(false)
  const scannerRef = useRef(null)
  const html5QrcodeScannerRef = useRef(null)

  useEffect(() => {
    if (scanning && scannerRef.current && !html5QrcodeScannerRef.current) {
      html5QrcodeScannerRef.current = new Html5QrcodeScanner(
        "qr-reader",
        { 
          fps: 10,
          qrbox: { width: 250, height: 250 },
          aspectRatio: 1.0
        },
        false
      )

      html5QrcodeScannerRef.current.render(
        (decodedText) => {
          // Success callback
          toast.success('QR Code scanned successfully!')
          onScan(decodedText)
          stopScanning()
        },
        (error) => {
          // Error callback (optional, fires frequently)
          // Only log actual errors, not scanning attempts
          if (error && !error.includes('NotFoundException')) {
            console.error('QR Scan error:', error)
          }
        }
      )
    }

    return () => {
      if (html5QrcodeScannerRef.current) {
        html5QrcodeScannerRef.current.clear().catch(console.error)
        html5QrcodeScannerRef.current = null
      }
    }
  }, [scanning, onScan])

  const startScanning = () => {
    setScanning(true)
  }

  const stopScanning = () => {
    if (html5QrcodeScannerRef.current) {
      html5QrcodeScannerRef.current.clear().catch(console.error)
      html5QrcodeScannerRef.current = null
    }
    setScanning(false)
  }

  return (
    <div className="space-y-4">
      {!scanning ? (
        <button
          onClick={startScanning}
          className="w-full bg-gradient-premium text-white font-bold py-4 rounded-2xl shadow-lg shadow-premium-primary/20 hover:scale-[1.02] active:scale-[0.98] transition-all"
        >
          📷 Start QR Scanner
        </button>
      ) : (
        <div className="space-y-4">
          <div 
            id="qr-reader" 
            ref={scannerRef}
            className="rounded-2xl overflow-hidden border-2 border-premium-primary/30"
          ></div>
          <button
            onClick={stopScanning}
            className="w-full bg-red-500/20 hover:bg-red-500/30 text-red-300 font-bold py-3 rounded-2xl transition-all"
          >
            Stop Scanning
          </button>
        </div>
      )}
    </div>
  )
}

export default QRScanner
