import { useState } from 'react'
import { useNavigate, Link } from 'react-router-dom'
import { useAuthStore } from '../store/authStore'
import { authAPI } from '../services/api/auth'
import { toast } from 'react-toastify'

const Login = () => {
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [loading, setLoading] = useState(false)
  const navigate = useNavigate()
  const login = useAuthStore((state) => state.login)

  const handleSubmit = async (e) => {
    e.preventDefault()
    setLoading(true)

    try {
      const response = await authAPI.login({ email, password })
      const userData = response.data.user;
      login(userData, response.data.token)
      toast.success('Login successful!')
      
      // Role-based redirect
      if (userData.role === 'admin') {
        navigate('/admin/dashboard')
      } else if (userData.role === 'organization_admin') {
        navigate('/org-dashboard')
      } else {
        navigate('/dashboard')
      }
    } catch (error) {
      toast.error(error.message || 'Login failed')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="min-h-screen flex items-center justify-center p-4 sm:p-6 relative overflow-hidden">
      <div className="w-full max-w-md z-10">
        <div className="glass rounded-[2rem] sm:rounded-[2.5rem] p-6 sm:p-10 border border-white/20 shadow-2xl relative overflow-hidden">
          {/* Decorative inner glow */}
          <div className="absolute -top-24 -right-24 w-48 h-48 bg-premium-primary/20 blur-[60px]"></div>
          
          <div className="relative z-10 text-center mb-10">
          <h1 className="text-3xl sm:text-4xl font-extrabold tracking-tight mb-2">
              <span className="text-white">Qt</span>
              <span className="text-premium-primary">tenzy</span>
            </h1>
            <p className="text-white/60 font-medium text-sm sm:text-base">Smart QR Attendance</p>
          </div>

          <form onSubmit={handleSubmit} className="space-y-6 relative z-10">
            <div className="space-y-4">
              <div className="group relative">
                <input
                  type="email"
                  required
                  className="w-full bg-white/5 border border-white/10 rounded-2xl px-4 sm:px-5 py-3.5 sm:py-4 text-white placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-premium-primary/50 focus:border-premium-primary/50 transition-all outline-none text-sm sm:text-base"
                  placeholder="Email Address"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                />
              </div>
              <div className="group relative">
                <input
                  type="password"
                  required
                  className="w-full bg-white/5 border border-white/10 rounded-2xl px-4 sm:px-5 py-3.5 sm:py-4 text-white placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-premium-primary/50 focus:border-premium-primary/50 transition-all outline-none text-sm sm:text-base"
                  placeholder="Password"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                />
              </div>
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full bg-gradient-premium text-white font-bold py-3.5 sm:py-4 rounded-2xl shadow-lg shadow-premium-primary/20 hover:scale-[1.02] active:scale-[0.98] transition-all disabled:opacity-50 disabled:hover:scale-100 text-sm sm:text-base"
            >
              {loading ? (
                <span className="flex items-center justify-center gap-2">
                  <svg className="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle><path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                  Signing in...
                </span>
              ) : 'Sign In'}
            </button>
          </form>

          <p className="text-center mt-6 text-white/40 text-sm">
            Don't have an account?{' '}
            <Link to="/register" className="text-premium-primary hover:text-premium-primary/80 font-semibold transition-colors">
              Create Account
            </Link>
          </p>

          <p className="text-center mt-8 text-white/40 text-sm">
            &copy; 2026 Qttenzy. All rights reserved.
          </p>
        </div>
      </div>
    </div>
  )
}

export default Login

