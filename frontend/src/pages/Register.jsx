import { useState, useEffect } from 'react'
import { useNavigate, Link } from 'react-router-dom'
import { authAPI } from '../services/api/auth'
import { toast } from 'react-toastify'
import apiClient from '../services/api/client'

const Register = () => {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    phone: '',
    student_id: '',
    organization_id: '',
    role: 'student'
  })
  const [organizations, setOrganizations] = useState([])
  const [loading, setLoading] = useState(false)
  const navigate = useNavigate()

  useEffect(() => {
    // Fetch organizations for dropdown
    const fetchOrganizations = async () => {
      try {
        const response = await apiClient.get('/organizations')
        setOrganizations(response.data || [])
      } catch (error) {
        console.error('Failed to fetch organizations:', error)
      }
    }
    fetchOrganizations()
  }, [])

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value
    })
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    
    if (formData.password !== formData.password_confirmation) {
      toast.error('Passwords do not match')
      return
    }

    setLoading(true)

    try {
      await authAPI.register(formData)
      toast.success('Registration successful! Please wait for admin approval.')
      navigate('/login')
    } catch (error) {
      toast.error(error.message || 'Registration failed')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="min-h-screen flex items-center justify-center p-6 relative overflow-hidden">
      <div className="w-full max-w-2xl z-10">
        <div className="glass rounded-[2.5rem] p-10 border border-white/20 shadow-2xl relative overflow-hidden">
          {/* Decorative inner glow */}
          <div className="absolute -top-24 -right-24 w-48 h-48 bg-premium-primary/20 blur-[60px]"></div>
          
          <div className="relative z-10 text-center mb-8">
            <h1 className="text-4xl font-extrabold tracking-tight mb-2">
              <span className="text-white">Create </span>
              <span className="text-premium-primary">Account</span>
            </h1>
            <p className="text-white/60 font-medium">Join Qttenzy Smart Attendance</p>
          </div>

          <form onSubmit={handleSubmit} className="space-y-5 relative z-10">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              {/* Full Name */}
              <div className="group relative">
                <input
                  type="text"
                  name="name"
                  required
                  className="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-premium-primary/50 focus:border-premium-primary/50 transition-all outline-none"
                  placeholder="Full Name"
                  value={formData.name}
                  onChange={handleChange}
                />
              </div>

              {/* Email */}
              <div className="group relative">
                <input
                  type="email"
                  name="email"
                  required
                  className="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-premium-primary/50 focus:border-premium-primary/50 transition-all outline-none"
                  placeholder="Email Address"
                  value={formData.email}
                  onChange={handleChange}
                />
              </div>

              {/* Phone */}
              <div className="group relative">
                <input
                  type="tel"
                  name="phone"
                  className="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-premium-primary/50 focus:border-premium-primary/50 transition-all outline-none"
                  placeholder="Phone Number (Optional)"
                  value={formData.phone}
                  onChange={handleChange}
                />
              </div>

              {/* Student ID */}
              <div className="group relative">
                <input
                  type="text"
                  name="student_id"
                  className="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-premium-primary/50 focus:border-premium-primary/50 transition-all outline-none"
                  placeholder="Student/Employee ID (Optional)"
                  value={formData.student_id}
                  onChange={handleChange}
                />
              </div>

              {/* Organization */}
              <div className="group relative">
                <select
                  name="organization_id"
                  required
                  className="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:ring-2 focus:ring-premium-primary/50 focus:border-premium-primary/50 transition-all outline-none"
                  value={formData.organization_id}
                  onChange={handleChange}
                >
                  <option value="" className="bg-dark text-white/50">Select Organization</option>
                  {organizations.map((org) => (
                    <option key={org.id} value={org.id} className="bg-dark text-white">
                      {org.name}
                    </option>
                  ))}
                </select>
              </div>

              {/* Role */}
              <div className="group relative">
                <select
                  name="role"
                  required
                  className="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:ring-2 focus:ring-premium-primary/50 focus:border-premium-primary/50 transition-all outline-none"
                  value={formData.role}
                  onChange={handleChange}
                >
                  <option value="student" className="bg-dark text-white">Student</option>
                  <option value="teacher" className="bg-dark text-white">Teacher</option>
                </select>
              </div>

              {/* Password */}
              <div className="group relative">
                <input
                  type="password"
                  name="password"
                  required
                  minLength="8"
                  className="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-premium-primary/50 focus:border-premium-primary/50 transition-all outline-none"
                  placeholder="Password (min 8 chars)"
                  value={formData.password}
                  onChange={handleChange}
                />
              </div>

              {/* Confirm Password */}
              <div className="group relative">
                <input
                  type="password"
                  name="password_confirmation"
                  required
                  minLength="8"
                  className="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-premium-primary/50 focus:border-premium-primary/50 transition-all outline-none"
                  placeholder="Confirm Password"
                  value={formData.password_confirmation}
                  onChange={handleChange}
                />
              </div>
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full bg-gradient-premium text-white font-bold py-4 rounded-2xl shadow-lg shadow-premium-primary/20 hover:scale-[1.02] active:scale-[0.98] transition-all disabled:opacity-50 disabled:hover:scale-100 mt-6"
            >
              {loading ? (
                <span className="flex items-center justify-center gap-2">
                  <svg className="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle><path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                  Creating Account...
                </span>
              ) : 'Create Account'}
            </button>
          </form>

          <p className="text-center mt-6 text-white/40 text-sm">
            Already have an account?{' '}
            <Link to="/login" className="text-premium-primary hover:text-premium-primary/80 font-semibold transition-colors">
              Sign In
            </Link>
          </p>

          <p className="text-center mt-8 text-white/40 text-xs">
            &copy; 2026 Qttenzy. All rights reserved.
          </p>
        </div>
      </div>
    </div>
  )
}

export default Register
