import { useEffect } from 'react';
import { createPortal } from 'react-dom';
import { useQRScanner } from '../../hooks/useQRScanner';

const QRScanner = ({ onScan, onClose }) => {
  const { 
    videoRef, 
    scanning, 
    error, 
    startScanning, 
    stopScanning, 
    switchCamera, 
    devices 
  } = useQRScanner(onScan);

  useEffect(() => {
    startScanning();
    return () => stopScanning();
  }, []);

  return createPortal(
    <div className="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-[9999] p-4">
      <div className="bg-white rounded-lg p-4 sm:p-6 max-w-md w-full relative z-50">
        <div className="flex justify-between items-center mb-4">
          <h2 className="text-lg sm:text-xl font-bold">Scan QR Code</h2>
          <button
            onClick={onClose}
            className="text-gray-500 hover:text-gray-700"
          >
            ✕
          </button>
        </div>
        
        <div className="relative bg-black rounded-lg overflow-hidden aspect-square sm:aspect-video">
          <video
            ref={videoRef}
            className="w-full h-full object-cover"
            autoPlay
            playsInline
            muted
          />
          
          {/* Camera Switch Button */}
          {devices.length > 1 && (
            <button
              onClick={switchCamera}
              className="absolute top-4 right-4 bg-black/50 hover:bg-black/70 text-white p-2 rounded-full backdrop-blur-sm transition-all z-20"
              title="Switch Camera"
            >
              <svg 
                xmlns="http://www.w3.org/2000/svg" 
                width="24" 
                height="24" 
                viewBox="0 0 24 24" 
                fill="none" 
                stroke="currentColor" 
                strokeWidth="2" 
                strokeLinecap="round" 
                strokeLinejoin="round" 
              >
                <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/>
                <path d="M12 13a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                <path d="M7 17l1.5-1.5"/>
                <path d="M17 17l-1.5-1.5"/>
                <path d="m7 17-1.5-1.5"/>
                <path d="m17 17 1.5-1.5"/>
              </svg>
            </button>
          )}

          <div className="absolute inset-0 border-4 border-blue-500 rounded-lg pointer-events-none">
            <div className="absolute top-0 left-0 w-8 h-8 border-t-4 border-l-4 border-blue-500"></div>
            <div className="absolute top-0 right-0 w-8 h-8 border-t-4 border-r-4 border-blue-500"></div>
            <div className="absolute bottom-0 left-0 w-8 h-8 border-b-4 border-l-4 border-blue-500"></div>
            <div className="absolute bottom-0 right-0 w-8 h-8 border-b-4 border-r-4 border-blue-500"></div>
          </div>
        </div>

        {error && (
          <div className="mt-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
            <p className="text-sm">{error}</p>
          </div>
        )}

        {scanning && !error && (
          <p className="mt-4 text-center text-gray-600 text-xs sm:text-sm">
            Position QR code within the frame
          </p>
        )}

        <button
          onClick={onClose}
          className="mt-4 w-full bg-gray-500 text-white py-2.5 sm:py-2 rounded hover:bg-gray-600 text-sm sm:text-base font-medium"
        >
          Close
        </button>
      </div>
    </div>,
    document.body
  );
};

export default QRScanner;

