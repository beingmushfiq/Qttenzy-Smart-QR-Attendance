
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Load .env file manually if checking locally, but in CI/CD vars are usually in process.env
// We'll read .env for local dev if it exists
const envPath = path.resolve(__dirname, '../.env');
console.log(`Checking for .env at: ${envPath}`);

if (fs.existsSync(envPath)) {
  console.log('Found .env file, loading variables...');
  const envConfig = fs.readFileSync(envPath, 'utf8');
  console.log('File content length:', envConfig.length);
  console.log('First 50 chars:', JSON.stringify(envConfig.substring(0, 50)));
  envConfig.split('\n').forEach(line => {
    const match = line.match(/^([^=]+)=(.*)$/);
    if (match) {
      const key = match[1].trim();
      const value = match[2].trim();
      console.log(`Parsed input: key="${key}", value="${value}"`);
      if (!process.env[key]) {
        process.env[key] = value;
      }
    }
  });
} else {
    console.log('No .env file found.');
}

const REQUIRED_VARS = [
  'VITE_API_BASE_URL'
];

console.log('🔍 Checking environment variables...');

const missing = [];

REQUIRED_VARS.forEach(key => {
  if (!process.env[key]) {
    missing.push(key);
  }
});

if (missing.length > 0) {
  console.error('\n❌ Error: Missing required environment variables:');
  missing.forEach(key => console.error(`   - ${key}`));
  console.error('\nPlease set these variables in your deployment environment (Railway/Vercel) or .env file.\n');
  process.exit(1);
}

console.log('✅ Environment variables check passed.\n');
