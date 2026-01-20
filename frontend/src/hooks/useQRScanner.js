import { useState, useEffect, useRef } from 'react';
import { BrowserMultiFormatReader } from '@zxing/library';

export const useQRScanner = (onScan) => {
  const [scanning, setScanning] = useState(false);
  const [error, setError] = useState(null);
  const videoRef = useRef(null);
  const codeReader = useRef(new BrowserMultiFormatReader());

  const startScanning = async () => {
    try {
      setScanning(true);
      setError(null);
      
      const devices = await codeReader.current.listVideoInputDevices();
      
      if (devices.length === 0) {
        throw new Error('No camera devices found');
      }

      const selectedDevice = devices[0]?.deviceId;
      
      codeReader.current.decodeFromVideoDevice(
        selectedDevice,
        videoRef.current,
        (result, err) => {
          if (result) {
            const text = result.getText();
            onScan(text);
            stopScanning();
          }
          if (err && err.name !== 'NotFoundException') {
            setError(err.message);
          }
        }
      );
    } catch (err) {
      setError(err.message || 'Camera access denied or not available');
      setScanning(false);
    }
  };

  const stopScanning = () => {
    if (codeReader.current) {
      codeReader.current.reset();
    }
    setScanning(false);
  };

  useEffect(() => {
    return () => {
      if (codeReader.current) {
        codeReader.current.reset();
      }
    };
  }, []);

  return {
    videoRef,
    scanning,
    error,
    startScanning,
    stopScanning
  };
};

