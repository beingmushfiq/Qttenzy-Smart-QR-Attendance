# Qttenzy Frontend
## React + Vite Application

---

## 🚀 Quick Start

```bash
# Install dependencies
npm install

# Copy environment file
cp .env.example .env

# Edit .env with your API URL
# VITE_API_BASE_URL=http://localhost:8000/api/v1

# Start development server
npm run dev
```

The app will be available at `http://localhost:5173`

---

## 📁 Project Structure

```
frontend/
├── public/              # Static assets
│   └── models/         # Face-API.js models (download separately)
├── src/
│   ├── components/     # React components
│   ├── pages/          # Page components
│   ├── hooks/          # Custom React hooks
│   ├── services/       # API services
│   ├── store/          # Zustand state management
│   ├── middleware/     # Route protection
│   ├── utils/          # Utility functions
│   ├── App.jsx         # Main app component
│   └── main.jsx        # Entry point
├── package.json
├── vite.config.js
└── tailwind.config.js
```

---

## 🛠️ Available Scripts

- `npm run dev` - Start development server
- `npm run build` - Build for production
- `npm run preview` - Preview production build
- `npm run lint` - Lint code

---

## 📦 Key Dependencies

- **React 18** - UI library
- **Vite** - Build tool
- **Tailwind CSS** - Styling
- **Zustand** - State management
- **React Router** - Routing
- **Axios** - HTTP client
- **ZXing** - QR code scanning
- **Face-API.js** - Face recognition
- **React Hook Form** - Form handling

---

## 🔧 Configuration

### Environment Variables

Edit `frontend/.env`:

```env
VITE_API_BASE_URL=http://localhost:8000/api/v1
VITE_APP_NAME=Qttenzy
VITE_FACE_API_MODELS_PATH=/models
```

### Face-API Models

Download Face-API.js models to `public/models/`:
- tiny_face_detector_model-weights_manifest.json
- tiny_face_detector_model-shard1
- face_landmark_68_model-weights_manifest.json
- face_landmark_68_model-shard1
- face_recognition_model-weights_manifest.json
- face_recognition_model-shard1
- face_recognition_model-shard2

Download from: https://github.com/justadudewhohacks/face-api.js-models

---

## 📚 Documentation

See [docs/FRONTEND.md](../docs/FRONTEND.md) for complete frontend development guide.

---

## 🐛 Troubleshooting

### Port already in use
Edit `vite.config.js` and change the port:
```js
server: { port: 5174 }
```

### Face-API models not loading
- Ensure models are in `public/models/`
- Check browser console for errors
- Verify `VITE_FACE_API_MODELS_PATH` in `.env`

### API connection errors
- Verify backend is running
- Check `VITE_API_BASE_URL` in `.env`
- Check CORS configuration in backend

---

## 🚀 Deployment

### Build for Production
```bash
npm run build
```

Output will be in `dist/` directory.

### Deploy to Vercel
```bash
vercel --prod
```

### Deploy to Netlify
```bash
netlify deploy --prod --dir=dist
```

See [docs/DEPLOYMENT.md](../docs/DEPLOYMENT.md) for detailed deployment instructions.

