import { QRCodeSVG } from 'qrcode.react';

const QRDisplay = ({ code, size = 256 }) => {
  if (!code) {
    return (
      <div className="flex items-center justify-center w-full h-full bg-gray-100 rounded">
        <p className="text-gray-500">No QR code available</p>
      </div>
    );
  }

  return (
    <div className="flex flex-col items-center p-4 bg-white rounded-lg">
      <QRCodeSVG
        value={code}
        size={size}
        level="M"
        includeMargin={true}
      />
      <p className="mt-4 text-xs text-gray-600 font-mono break-all text-center max-w-xs">
        {code}
      </p>
    </div>
  );
};

export default QRDisplay;

