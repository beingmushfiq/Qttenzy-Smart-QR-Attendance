# Frontend Development Guide
## React + Vite Application Structure

---

## 🚀 SETUP & INSTALLATION

### Prerequisites
- Node.js 18+ 
- npm/yarn/pnpm

### Initial Setup
```bash
cd frontend
npm install
cp .env.example .env
npm run dev
```

### Required Dependencies
```json
{
  "dependencies": {
    "react": "^18.2.0",
    "react-dom": "^18.2.0",
    "react-router-dom": "^6.20.0",
    "axios": "^1.6.2",
    "zustand": "^4.4.7",
    "react-hook-form": "^7.48.2",
    "yup": "^1.3.3",
    "@zxing/library": "^0.20.0",
    "qrcode.react": "^3.1.0",
    "face-api.js": "^0.22.2",
    "@tensorflow/tfjs": "^4.11.0",
    "tailwindcss": "^3.3.6",
    "autoprefixer": "^10.4.16",
    "postcss": "^8.4.32",
    "@headlessui/react": "^1.7.17",
    "@heroicons/react": "^2.1.1",
    "date-fns": "^2.30.0",
    "react-toastify": "^9.1.3",
    "jspdf": "^2.5.1",
    "xlsx": "^0.18.5"
  },
  "devDependencies": {
    "@vitejs/plugin-react": "^4.2.1",
    "vite": "^5.0.8",
    "eslint": "^8.55.0",
    "prettier": "^3.1.1"
  }
}
```

---

## 📁 COMPONENT TREE

```
App
├── Router
│   ├── Public Routes
│   │   ├── Login
│   │   ├── Register
│   │   └── ForgotPassword
│   │
│   └── Protected Routes
│       ├── Layout (Sidebar + Header)
│       │   ├── Dashboard
│       │   ├── Sessions
│       │   │   ├── SessionList
│       │   │   ├── SessionCreate (Admin/Manager)
│       │   │   ├── SessionDetail
│       │   │   └── SessionQR (Manager/Admin)
│       │   │
│       │   ├── Attendance
│       │   │   ├── AttendanceScanner
│       │   │   │   ├── QRScanner
│       │   │   │   ├── FaceVerification
│       │   │   │   └── GPSValidation
│       │   │   ├── AttendanceHistory
│       │   │   └── AttendanceCard
│       │   │
│       │   ├── Profile
│       │   │   ├── ProfileView
│       │   │   ├── ProfileEdit
│       │   │   ├── FaceEnrollment
│       │   │   └── BiometricSetup
│       │   │
│       │   └── Admin (Admin Only)
│       │       ├── AdminDashboard
│       │       ├── UserManagement
│       │       ├── SessionManagement
│       │       ├── Reports
│       │       └── Analytics
│       │
│       └── Payment Flow
│           ├── RegistrationForm
│           ├── PaymentForm
│           └── PaymentStatus
```

---

## 🎨 UI/UX WORKFLOW

### 1. Authentication Flow
```
Login Page → API Call → Store Token → Redirect to Dashboard
Register Page → API Call → Auto Login → Redirect to Dashboard
Forgot Password → Email Sent → Reset Page → Update Password
```

### 2. Attendance Flow
```
Session List → Select Session → Scan QR → 
Face Capture → GPS Check → WebAuthn (Optional) → 
Submit → Success/Error Toast → Redirect to History
```

### 3. Session Creation Flow (Admin/Manager)
```
Sessions Page → Create Button → Form Modal → 
Fill Details → Set Location (Map) → Generate QR → 
Publish Session → Success Message
```

### 4. Payment Flow
```
Session Detail → Register Button → Payment Form → 
Gateway Redirect → Webhook → Success Page → 
Registration Confirmed
```

---

## 🗂️ STATE MANAGEMENT (Zustand)

### Auth Store (`store/authStore.js`)
```javascript
import { create } from 'zustand';
import { persist } from 'zustand/middleware';

export const useAuthStore = create(
  persist(
    (set) => ({
      user: null,
      token: null,
      isAuthenticated: false,
      
      login: (user, token) => set({ 
        user, 
        token, 
        isAuthenticated: true 
      }),
      
      logout: () => set({ 
        user: null, 
        token: null, 
        isAuthenticated: false 
      }),
      
      updateUser: (userData) => set((state) => ({
        user: { ...state.user, ...userData }
      }))
    }),
    { name: 'auth-storage' }
  )
);
```

### Session Store (`store/sessionStore.js`)
```javascript
import { create } from 'zustand';

export const useSessionStore = create((set) => ({
  sessions: [],
  currentSession: null,
  loading: false,
  error: null,
  
  fetchSessions: async (filters) => {
    set({ loading: true, error: null });
    try {
      const response = await sessionAPI.getAll(filters);
      set({ sessions: response.data, loading: false });
    } catch (error) {
      set({ error: error.message, loading: false });
    }
  },
  
  setCurrentSession: (session) => set({ currentSession: session })
}));
```

### Attendance Store (`store/attendanceStore.js`)
```javascript
import { create } from 'zustand';

export const useAttendanceStore = create((set) => ({
  attendanceHistory: [],
  currentVerification: null,
  loading: false,
  
  verifyAttendance: async (data) => {
    set({ loading: true });
    try {
      const response = await attendanceAPI.verify(data);
      set({ loading: false });
      return response;
    } catch (error) {
      set({ loading: false });
      throw error;
    }
  },
  
  fetchHistory: async (filters) => {
    const response = await attendanceAPI.getHistory(filters);
    set({ attendanceHistory: response.data });
  }
}));
```

---

## 🔧 CUSTOM HOOKS

### useQRScanner (`hooks/useQRScanner.js`)
```javascript
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
      const selectedDevice = devices[0]?.deviceId;
      
      codeReader.current.decodeFromVideoDevice(
        selectedDevice,
        videoRef.current,
        (result, err) => {
          if (result) {
            onScan(result.getText());
            stopScanning();
          }
          if (err && err.name !== 'NotFoundException') {
            setError(err.message);
          }
        }
      );
    } catch (err) {
      setError('Camera access denied or not available');
      setScanning(false);
    }
  };

  const stopScanning = () => {
    codeReader.current.reset();
    setScanning(false);
  };

  useEffect(() => {
    return () => {
      codeReader.current.reset();
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
```

### useFaceRecognition (`hooks/useFaceRecognition.js`)
```javascript
import { useState, useEffect, useRef } from 'react';
import * as faceapi from 'face-api.js';

const MODEL_URL = '/models'; // Face-API models directory

export const useFaceRecognition = () => {
  const [modelsLoaded, setModelsLoaded] = useState(false);
  const [loading, setLoading] = useState(true);
  const videoRef = useRef(null);
  const canvasRef = useRef(null);

  useEffect(() => {
    const loadModels = async () => {
      try {
        await Promise.all([
          faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
          faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
          faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL),
          faceapi.nets.faceExpressionNet.loadFromUri(MODEL_URL)
        ]);
        setModelsLoaded(true);
        setLoading(false);
      } catch (error) {
        console.error('Error loading face models:', error);
        setLoading(false);
      }
    };
    loadModels();
  }, []);

  const captureFace = async () => {
    if (!modelsLoaded || !videoRef.current) return null;

    const detection = await faceapi
      .detectSingleFace(videoRef.current, new faceapi.TinyFaceDetectorOptions())
      .withFaceLandmarks()
      .withFaceDescriptor();

    if (!detection) {
      throw new Error('No face detected');
    }

    return detection.descriptor;
  };

  const compareFaces = async (descriptor1, descriptor2, threshold = 0.6) => {
    const distance = faceapi.euclideanDistance(descriptor1, descriptor2);
    const match = distance < threshold;
    const score = (1 - distance) * 100;
    
    return { match, score: Math.max(0, Math.min(100, score)), distance };
  };

  return {
    modelsLoaded,
    loading,
    videoRef,
    canvasRef,
    captureFace,
    compareFaces
  };
};
```

### useGeolocation (`hooks/useGeolocation.js`)
```javascript
import { useState, useEffect } from 'react';

export const useGeolocation = (options = {}) => {
  const [location, setLocation] = useState(null);
  const [error, setError] = useState(null);
  const [loading, setLoading] = useState(false);

  const getCurrentLocation = () => {
    if (!navigator.geolocation) {
      setError('Geolocation is not supported by your browser');
      return;
    }

    setLoading(true);
    navigator.geolocation.getCurrentPosition(
      (position) => {
        setLocation({
          lat: position.coords.latitude,
          lng: position.coords.longitude,
          accuracy: position.coords.accuracy,
          altitude: position.coords.altitude,
          heading: position.coords.heading,
          speed: position.coords.speed
        });
        setLoading(false);
      },
      (err) => {
        setError(err.message);
        setLoading(false);
      },
      {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0,
        ...options
      }
    );
  };

  const calculateDistance = (lat1, lng1, lat2, lng2) => {
    const R = 6371e3; // Earth radius in meters
    const φ1 = lat1 * Math.PI / 180;
    const φ2 = lat2 * Math.PI / 180;
    const Δφ = (lat2 - lat1) * Math.PI / 180;
    const Δλ = (lng2 - lng1) * Math.PI / 180;

    const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
              Math.cos(φ1) * Math.cos(φ2) *
              Math.sin(Δλ/2) * Math.sin(Δλ/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));

    return R * c; // Distance in meters
  };

  return {
    location,
    error,
    loading,
    getCurrentLocation,
    calculateDistance
  };
};
```

### useWebAuthn (`hooks/useWebAuthn.js`)
```javascript
import { useState } from 'react';

export const useWebAuthn = () => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const isSupported = () => {
    return window.PublicKeyCredential !== undefined;
  };

  const register = async (userId, userName) => {
    if (!isSupported()) {
      throw new Error('WebAuthn is not supported');
    }

    setLoading(true);
    setError(null);

    try {
      // Get challenge from server
      const challengeResponse = await fetch('/api/webauthn/register/challenge', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ userId, userName })
      });
      const challengeData = await challengeResponse.json();

      // Create credential
      const credential = await navigator.credentials.create({
        publicKey: {
          challenge: Uint8Array.from(challengeData.challenge, c => c.charCodeAt(0)),
          rp: {
            name: 'Qttenzy',
            id: window.location.hostname
          },
          user: {
            id: Uint8Array.from(userId, c => c.charCodeAt(0)),
            name: userName,
            displayName: userName
          },
          pubKeyCredParams: [{ alg: -7, type: 'public-key' }],
          authenticatorSelection: {
            authenticatorAttachment: 'platform',
            userVerification: 'preferred'
          },
          timeout: 60000,
          attestation: 'direct'
        }
      });

      // Send credential to server
      const registerResponse = await fetch('/api/webauthn/register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          credential: {
            id: credential.id,
            rawId: Array.from(new Uint8Array(credential.rawId)),
            response: {
              attestationObject: Array.from(new Uint8Array(credential.response.attestationObject)),
              clientDataJSON: Array.from(new Uint8Array(credential.response.clientDataJSON))
            },
            type: credential.type
          }
        })
      });

      const result = await registerResponse.json();
      setLoading(false);
      return result;
    } catch (err) {
      setError(err.message);
      setLoading(false);
      throw err;
    }
  };

  const authenticate = async (userId) => {
    if (!isSupported()) {
      throw new Error('WebAuthn is not supported');
    }

    setLoading(true);
    setError(null);

    try {
      // Get challenge from server
      const challengeResponse = await fetch('/api/webauthn/authenticate/challenge', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ userId })
      });
      const challengeData = await challengeResponse.json();

      // Authenticate
      const assertion = await navigator.credentials.get({
        publicKey: {
          challenge: Uint8Array.from(challengeData.challenge, c => c.charCodeAt(0)),
          allowCredentials: challengeData.allowCredentials.map(cred => ({
            id: Uint8Array.from(cred.id, c => c.charCodeAt(0)),
            type: 'public-key',
            transports: cred.transports
          })),
          timeout: 60000,
          userVerification: 'preferred'
        }
      });

      // Send assertion to server
      const authResponse = await fetch('/api/webauthn/authenticate', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          assertion: {
            id: assertion.id,
            rawId: Array.from(new Uint8Array(assertion.rawId)),
            response: {
              authenticatorData: Array.from(new Uint8Array(assertion.response.authenticatorData)),
              clientDataJSON: Array.from(new Uint8Array(assertion.response.clientDataJSON)),
              signature: Array.from(new Uint8Array(assertion.response.signature)),
              userHandle: assertion.response.userHandle ? 
                Array.from(new Uint8Array(assertion.response.userHandle)) : null
            },
            type: assertion.type
          }
        })
      });

      const result = await authResponse.json();
      setLoading(false);
      return result;
    } catch (err) {
      setError(err.message);
      setLoading(false);
      throw err;
    }
  };

  return {
    loading,
    error,
    isSupported,
    register,
    authenticate
  };
};
```

---

## 🌐 API SERVICES

### API Client Setup (`services/api/client.js`)
```javascript
import axios from 'axios';
import { useAuthStore } from '../../store/authStore';

const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api/v1',
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

// Request interceptor
apiClient.interceptors.request.use(
  (config) => {
    const token = useAuthStore.getState().token;
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// Response interceptor
apiClient.interceptors.response.use(
  (response) => response.data,
  async (error) => {
    if (error.response?.status === 401) {
      useAuthStore.getState().logout();
      window.location.href = '/login';
    }
    return Promise.reject(error.response?.data || error.message);
  }
);

export default apiClient;
```

### Attendance Service (`services/api/attendance.js`)
```javascript
import apiClient from './client';

export const attendanceAPI = {
  verify: async (data) => {
    return apiClient.post('/attendance/verify', data);
  },
  
  getHistory: async (params = {}) => {
    return apiClient.get('/attendance/history', { params });
  },
  
  getSessionAttendance: async (sessionId, params = {}) => {
    return apiClient.get(`/attendance/session/${sessionId}`, { params });
  }
};
```

---

## 🎯 CRITICAL COMPONENTS

### QR Scanner Component (`components/qr/QRScanner.jsx`)
```javascript
import { useEffect, useState } from 'react';
import { useQRScanner } from '../../hooks/useQRScanner';

const QRScanner = ({ onScan, onClose }) => {
  const { videoRef, scanning, error, startScanning, stopScanning } = useQRScanner(onScan);

  useEffect(() => {
    startScanning();
    return () => stopScanning();
  }, []);

  return (
    <div className="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50">
      <div className="bg-white rounded-lg p-6 max-w-md w-full">
        <h2 className="text-xl font-bold mb-4">Scan QR Code</h2>
        <div className="relative">
          <video
            ref={videoRef}
            className="w-full rounded-lg"
            autoPlay
            playsInline
          />
          <div className="absolute inset-0 border-4 border-blue-500 rounded-lg pointer-events-none" />
        </div>
        {error && <p className="text-red-500 mt-2">{error}</p>}
        <button
          onClick={onClose}
          className="mt-4 w-full bg-gray-500 text-white py-2 rounded"
        >
          Close
        </button>
      </div>
    </div>
  );
};

export default QRScanner;
```

### Face Verification Component (`components/face/FaceVerification.jsx`)
```javascript
import { useEffect, useState } from 'react';
import { useFaceRecognition } from '../../hooks/useFaceRecognition';

const FaceVerification = ({ enrolledDescriptor, onVerify, onClose }) => {
  const { modelsLoaded, loading, videoRef, canvasRef, captureFace, compareFaces } = useFaceRecognition();
  const [verifying, setVerifying] = useState(false);
  const [matchResult, setMatchResult] = useState(null);

  useEffect(() => {
    if (modelsLoaded && videoRef.current) {
      navigator.mediaDevices.getUserMedia({ video: true })
        .then(stream => {
          videoRef.current.srcObject = stream;
        });
    }
  }, [modelsLoaded]);

  const handleVerify = async () => {
    setVerifying(true);
    try {
      const currentDescriptor = await captureFace();
      const result = await compareFaces(enrolledDescriptor, currentDescriptor, 0.6);
      setMatchResult(result);
      onVerify(result);
    } catch (error) {
      alert(error.message);
    } finally {
      setVerifying(false);
    }
  };

  if (loading) return <div>Loading face recognition models...</div>;
  if (!modelsLoaded) return <div>Failed to load models</div>;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50">
      <div className="bg-white rounded-lg p-6 max-w-md w-full">
        <h2 className="text-xl font-bold mb-4">Face Verification</h2>
        <div className="relative">
          <video ref={videoRef} autoPlay playsInline className="w-full rounded-lg" />
          <canvas ref={canvasRef} className="hidden" />
        </div>
        {matchResult && (
          <div className={`mt-4 p-3 rounded ${matchResult.match ? 'bg-green-100' : 'bg-red-100'}`}>
            <p>Match Score: {matchResult.score.toFixed(2)}%</p>
            <p>{matchResult.match ? '✓ Verified' : '✗ Not Matched'}</p>
          </div>
        )}
        <div className="mt-4 flex gap-2">
          <button
            onClick={handleVerify}
            disabled={verifying}
            className="flex-1 bg-blue-500 text-white py-2 rounded"
          >
            {verifying ? 'Verifying...' : 'Verify Face'}
          </button>
          <button
            onClick={onClose}
            className="flex-1 bg-gray-500 text-white py-2 rounded"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  );
};

export default FaceVerification;
```

---

## 🎨 STYLING (Tailwind CSS)

### Configuration (`tailwind.config.js`)
```javascript
export default {
  content: ['./index.html', './src/**/*.{js,ts,jsx,tsx}'],
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#eff6ff',
          500: '#3b82f6',
          600: '#2563eb',
          700: '#1d4ed8'
        }
      }
    }
  },
  plugins: []
};
```

---

## 🔒 ROUTE PROTECTION

### Protected Route (`middleware/ProtectedRoute.jsx`)
```javascript
import { Navigate } from 'react-router-dom';
import { useAuthStore } from '../store/authStore';

const ProtectedRoute = ({ children }) => {
  const { isAuthenticated } = useAuthStore();
  return isAuthenticated ? children : <Navigate to="/login" />;
};

export default ProtectedRoute;
```

### Role-Based Route (`middleware/RoleRoute.jsx`)
```javascript
import { Navigate } from 'react-router-dom';
import { useAuthStore } from '../store/authStore';

const RoleRoute = ({ children, allowedRoles = [] }) => {
  const { user, isAuthenticated } = useAuthStore();
  
  if (!isAuthenticated) {
    return <Navigate to="/login" />;
  }
  
  if (allowedRoles.length > 0 && !allowedRoles.includes(user.role)) {
    return <Navigate to="/dashboard" />;
  }
  
  return children;
};

export default RoleRoute;
```

---

## 📱 ENVIRONMENT VARIABLES

### `.env.example`
```env
VITE_API_BASE_URL=http://localhost:8000/api/v1
VITE_APP_NAME=Qttenzy
VITE_FACE_API_MODELS_PATH=/models
VITE_GOOGLE_MAPS_API_KEY=your_google_maps_key
VITE_PAYMENT_GATEWAY=sslcommerz
```

---

## 🚀 BUILD & DEPLOYMENT

### Build for Production
```bash
npm run build
```

### Preview Production Build
```bash
npm run preview
```

### Deploy to Vercel
```bash
npm install -g vercel
vercel --prod
```

### Deploy to Netlify
```bash
npm install -g netlify-cli
netlify deploy --prod --dir=dist
```

