import { useState, useEffect, useRef } from 'react';
import { BrowserMultiFormatReader } from '@zxing/library';

export const useQRScanner = (onScan) => {
  const [scanning, setScanning] = useState(false);
  const [error, setError] = useState(null);
  const [devices, setDevices] = useState([]);
  const [currentDeviceId, setCurrentDeviceId] = useState(null);
  
  const videoRef = useRef(null);
  const codeReader = useRef(new BrowserMultiFormatReader());

  const startScanning = async (deviceId = null) => {
    try {
      setScanning(true);
      setError(null);
      
      const availableDevices = await codeReader.current.listVideoInputDevices();
      setDevices(availableDevices);
      
      if (availableDevices.length === 0) {
        throw new Error('No camera devices found');
      }

      // Select device: explicit argument -> current state -> last available (often back camera) -> first available
      let selectedDevice = deviceId;
      if (!selectedDevice) {
          if (currentDeviceId && availableDevices.find(d => d.deviceId === currentDeviceId)) {
              selectedDevice = currentDeviceId;
          } else {
             // Try to find a back camera
             const backCamera = availableDevices.find(device => device.label.toLowerCase().includes('back') || device.label.toLowerCase().includes('environment'));
             selectedDevice = backCamera ? backCamera.deviceId : availableDevices[0].deviceId;
          }
      }
      
      setCurrentDeviceId(selectedDevice);

      codeReader.current.decodeFromVideoDevice(
        selectedDevice,
        videoRef.current,
        (result, err) => {
          if (result) {
            const text = result.getText();
            onScan(text);
            stopScanning();
          }
           if (err && err.name !== 'NotFoundException' && !err.message?.includes('No MultiFormat Readers')) {
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

  const switchCamera = () => {
    if (devices.length < 2) return;
    
    const currentIndex = devices.findIndex(d => d.deviceId === currentDeviceId);
    const nextIndex = (currentIndex + 1) % devices.length;
    const nextDevice = devices[nextIndex];
    
    stopScanning();
    // Allow a small delay for the camera to release
    setTimeout(() => {
        startScanning(nextDevice.deviceId);
    }, 200);
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
    devices,
    currentDeviceId,
    startScanning,
    stopScanning,
    switchCamera
  };
};

