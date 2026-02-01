import { useState, useEffect } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { authAPI } from '../services/api/auth';
import { getOrganizations } from '../services/api/organization';
import { toast } from 'react-toastify';

const Register = () => {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    phone: '',
    student_id: '',
    organization_id: '',
    role: 'student',
    create_organization: false
  });
  const [organizations, setOrganizations] = useState([]);
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();

  useEffect(() => {
    // Fetch organizations for dropdown
    const fetchOrganizations = async () => {
      try {
        const response = await getOrganizations();
        // API client already unwraps to { success, data }, so use response.data
        console.log('Organizations response:', response);
        setOrganizations(response.data || []);
      } catch (error) {
        console.error('Failed to fetch organizations:', error);
        toast.error('Failed to load organizations');
      }
    };
    fetchOrganizations();
  }, []);

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value
    });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (formData.password !== formData.password_confirmation) {
      toast.error('Passwords do not match');
      return;
    }

    setLoading(true);

    try {
      await authAPI.register(formData);
      toast.success('Registration successful! Please wait for admin approval.');
      navigate('/login');
    } catch (error) {
      toast.error(error.message || 'Registration failed');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center p-4 sm:p-6 relative overflow-hidden">
      <div className="w-full max-w-2xl z-10">
        <div className="glass rounded-[2rem] sm:rounded-[2.5rem] p-6 sm:p-10 border border-white/20 shadow-2xl relative overflow-hidden">
          {/* Decorative inner glow */}
          <div className="absolute -top-24 -right-24 w-48 h-48 bg-premium-primary/20 blur-[60px]"></div>
          
          <div className="flex items-center justify-between mb-4">
            <h1 className="text-3xl sm:text-4xl font-extrabold tracking-tight">
              <span className="text-white">Create </span>
              <span className="text-premium-primary">Account</span>
            </h1>
          </div>
          <p className="text-white/60 font-medium text-sm sm:text-base mb-6">Join Qttenzy Smart Attendance</p>

          <form onSubmit={handleSubmit} className="space-y-4 sm:space-y-5 relative z-10">
            
            {/* Organization Toggle */}
            <div className="bg-white/5 border border-white/10 rounded-2xl p-4 mb-4">
                <div className="flex items-center justify-between">
                    <span className="text-white font-medium">I want to create a new Organization</span>
                    <label className="relative inline-flex items-center cursor-pointer">
                        <input 
                            type="checkbox" 
                            className="sr-only peer"
                            checked={formData.create_organization}
                            onChange={(e) => {
                                setFormData({
                                    ...formData, 
                                    create_organization: e.target.checked,
                                    organization_id: e.target.checked ? '' : formData.organization_id, // Clear org selection if creating
                                    role: e.target.checked ? 'organization_admin' : 'student' // Auto-set role
                                });
                            }}
                        />
                        <div className="w-11 h-6 bg-white/10 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-premium-primary rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-premium-primary"></div>
                    </label>
                </div>
                {formData.create_organization && (
                    <p className="text-xs text-white/50 mt-2">
                        You will be registered as the <strong>Organization Admin</strong>.
                    </p>
                )}
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
              
              {/* Organization Creation Fields */}
              {formData.create_organization ? (
                  <>
                    <div className="group relative sm:col-span-2">
                        <input
                        type="text"
                        name="organization_name"
                        required
                        className="w-full bg-white/5 border border-white/10 rounded-2xl px-4 sm:px-5 py-3.5 sm:py-4 text-white placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-premium-primary/50 focus:border-premium-primary/50 transition-all outline-none text-sm sm:text-base"
                        placeholder="Organization Name"
                        value={formData.organization_name || ''}
                        onChange={handleChange}
                        />
                    </div>
                    
                    <div className="group relative">
                        <input
                        type="text"
                        name="organization_address"
                        className="w-full bg-white/5 border border-white/10 rounded-2xl px-4 sm:px-5 py-3.5 sm:py-4 text-white placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-premium-primary/50 focus:border-premium-primary/50 transition-all outline-none text-sm sm:text-base"
                        placeholder="Address (Optional)"
                        value={formData.organization_address || ''}
                        onChange={handleChange}
                        />
                    </div>

                    <div className="group relative">
                        <input
                        type="text"
                        name="organization_phone"
                        className="w-full bg-white/5 border border-white/10 rounded-2xl px-4 sm:px-5 py-3.5 sm:py-4 text-white placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-premium-primary/50 focus:border-premium-primary/50 transition-all outline-none text-sm sm:text-base"
                        placeholder="Org Phone (Optional)"
                        value={formData.organization_phone || ''}
                        onChange={handleChange}
                        />
                    </div>
                  </>
              ) : (
                /* Organization Selection */
                <div className="group relative sm:col-span-2">
                    <select
                    name="organization_id"
                    required={!formData.create_organization}
                    className="w-full bg-white/5 border border-white/10 rounded-2xl px-4 sm:px-5 py-3.5 sm:py-4 text-white focus:outline-none focus:ring-2 focus:ring-premium-primary/50 focus:border-premium-primary/50 transition-all outline-none text-sm sm:text-base"
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
              )}

              {/* Full Name */}
              <div className="group relative">
                <input
                  type="text"
                  name="name"
                  required
                  className="w-full bg-white/5 border border-white/10 rounded-2xl px-4 sm:px-5 py-3.5 sm:py-4 text-white placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-premium-primary/50 focus:border-premium-primary/50 transition-all outline-none text-sm sm:text-base"
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
                  className="w-full bg-white/5 border border-white/10 rounded-2xl px-4 sm:px-5 py-3.5 sm:py-4 text-white placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-premium-primary/50 focus:border-premium-primary/50 transition-all outline-none text-sm sm:text-base"
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
                  className="w-full bg-white/5 border border-white/10 rounded-2xl px-4 sm:px-5 py-3.5 sm:py-4 text-white placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-premium-primary/50 focus:border-premium-primary/50 transition-all outline-none text-sm sm:text-base"
                  placeholder="Phone Number (Optional)"
                  value={formData.phone}
                  onChange={handleChange}
                />
              </div>

              {/* Student ID - Hide if creating an org */}
              {!formData.create_organization && (
                  <div className="group relative">
                    <input
                      type="text"
                      name="student_id"
                      className="w-full bg-white/5 border border-white/10 rounded-2xl px-4 sm:px-5 py-3.5 sm:py-4 text-white placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-premium-primary/50 focus:border-premium-primary/50 transition-all outline-none text-sm sm:text-base"
                      placeholder="Student/Employee ID (Optional)"
                      value={formData.student_id}
                      onChange={handleChange}
                    />
                  </div>
              )}

              {/* Role - Hide if creating an org (auto-set) */}
              {!formData.create_organization && (
                  <div className="group relative sm:col-span-2">
                    <select
                      name="role"
                      required
                      className="w-full bg-white/5 border border-white/10 rounded-2xl px-4 sm:px-5 py-3.5 sm:py-4 text-white focus:outline-none focus:ring-2 focus:ring-premium-primary/50 focus:border-premium-primary/50 transition-all outline-none text-sm sm:text-base"
                      value={formData.role}
                      onChange={handleChange}
                    >
                      <option value="student" className="bg-dark text-white">Student</option>
                      <option value="teacher" className="bg-dark text-white">Teacher</option>
                      <option value="event_manager" className="bg-dark text-white">Event Manager</option>
                      <option value="coordinator" className="bg-dark text-white">Co-ordinator</option>
                    </select>
                  </div>
              )}

              {/* Password */}
              <div className="group relative">
                <input
                  type="password"
                  name="password"
                  required
                  minLength="8"
                  className="w-full bg-white/5 border border-white/10 rounded-2xl px-4 sm:px-5 py-3.5 sm:py-4 text-white placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-premium-primary/50 focus:border-premium-primary/50 transition-all outline-none text-sm sm:text-base"
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
                  className="w-full bg-white/5 border border-white/10 rounded-2xl px-4 sm:px-5 py-3.5 sm:py-4 text-white placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-premium-primary/50 focus:border-premium-primary/50 transition-all outline-none text-sm sm:text-base"
                  placeholder="Confirm Password"
                  value={formData.password_confirmation}
                  onChange={handleChange}
                />
              </div>
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full bg-gradient-premium text-white font-bold py-3.5 sm:py-4 rounded-2xl shadow-lg shadow-premium-primary/20 hover:scale-[1.02] active:scale-[0.98] transition-all disabled:opacity-50 disabled:hover:scale-100 mt-5 sm:mt-6 text-sm sm:text-base"
            >
              {loading ? (
                <span className="flex items-center justify-center gap-2">
                  <svg className="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle><path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                  {formData.create_organization ? 'Creating Organization...' : 'Creating Account...'}
                </span>
              ) : (formData.create_organization ? 'Create Organization' : 'Create Account')}
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
  );
};

export default Register;
