import { useEffect, useState } from 'react';
import { useAuthStore } from '../store/authStore';
import { authAPI } from '../services/api/auth';
import { userAPI } from '../services/api/user';
import { toast } from 'react-toastify';
import GlassCard from '../components/common/GlassCard';
import FaceEnrollment from '../components/face/FaceEnrollment';

const Profile = () => {
  const { user, updateUser } = useAuthStore();
  const [profile, setProfile] = useState(null);
  const [loading, setLoading] = useState(true);
  const [editing, setEditing] = useState(false);
  const [showFaceEnrollment, setShowFaceEnrollment] = useState(false);
  const [formData, setFormData] = useState({
    name: '',
    phone: '',
    avatar: ''
  });

  useEffect(() => {
    fetchProfile();
  }, []);

  const fetchProfile = async () => {
    try {
      setLoading(true);
      // Use auth/me endpoint to get current user
      const response = await authAPI.me();
      const userData = response.data || response;
      setProfile(userData);
      setFormData({
        name: userData.name || '',
        phone: userData.phone || '',
        avatar: userData.avatar || ''
      });
    } catch (error) {
      console.error('Profile error:', error);
      toast.error('Failed to load profile');
    } finally {
      setLoading(false);
    }
  };

  const handleUpdate = async (e) => {
    e.preventDefault();
    try {
      const response = await userAPI.updateProfile(formData);
      updateUser(response.data);
      setProfile(response.data);
      setEditing(false);
      toast.success('Profile updated successfully');
    } catch (error) {
      toast.error(error.message || 'Failed to update profile');
    }
  };

  const handleFaceEnrolled = (descriptor) => {
    setShowFaceEnrollment(false);
    toast.success('Face enrolled successfully!');
    fetchProfile(); // Refresh profile
  };

  if (loading) {
    return <div className="text-center p-8">Loading profile...</div>;
  }

  if (!profile) {
    return <div className="text-center p-8">Failed to load profile</div>;
  }

  return (
    <div className="space-y-6 sm:space-y-8 pb-8 sm:pb-10">
      <div>
        <h1 className="text-3xl sm:text-4xl font-extrabold text-white mb-2 tracking-tight">Your Profile</h1>
        <p className="text-white/40 font-medium tracking-tight text-sm sm:text-base">Manage your personal information and biometric data.</p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-8">
        {/* Main Info */}
        <div className="lg:col-span-2 space-y-6 sm:space-y-8">
          <GlassCard className="relative overflow-hidden border border-white/5">
            <div className="absolute -top-24 -right-24 w-64 h-64 bg-premium-secondary/10 blur-[80px]"></div>
            
            <div className="flex flex-col md:flex-row items-center gap-8 mb-10 relative z-10">
              <div className="relative group">
                {profile.avatar ? (
                  <img
                    src={profile.avatar}
                    alt={profile.name}
                    className="w-32 h-32 rounded-3xl object-cover ring-4 ring-white/10"
                  />
                ) : (
                  <div className="w-32 h-32 rounded-3xl bg-gradient-to-br from-premium-primary to-premium-secondary flex items-center justify-center text-4xl font-bold text-white shadow-xl">
                    {profile.name?.charAt(0).toUpperCase()}
                  </div>
                )}
                <div className="absolute -bottom-2 -right-2 w-8 h-8 sm:w-10 sm:h-10 bg-white rounded-xl sm:rounded-2xl flex items-center justify-center text-dark shadow-lg border-2 sm:border-4 border-dark group-hover:scale-110 transition-transform cursor-pointer text-sm sm:text-base">
                  📸
                </div>
              </div>

              <div className="text-center md:text-left">
                <h2 className="text-2xl sm:text-3xl font-bold text-white mb-1">{profile.name}</h2>
                <p className="text-white/40 font-medium mb-3 sm:mb-4 text-sm sm:text-base">{profile.email}</p>
                <div className="flex flex-wrap justify-center md:justify-start gap-2">
                  <span className="px-4 py-1.5 bg-premium-primary/20 text-premium-primary rounded-xl text-xs font-bold uppercase tracking-wider border border-premium-primary/20">
                    {profile.role}
                  </span>
                  <span className="px-4 py-1.5 bg-premium-accent/20 text-premium-accent rounded-xl text-xs font-bold uppercase tracking-wider border border-premium-accent/20">
                    Active
                  </span>
                </div>
              </div>

              {!editing && (
                <button
                  onClick={() => setEditing(true)}
                  className="md:ml-auto px-5 sm:px-6 py-2 sm:py-2.5 rounded-2xl bg-white/5 hover:bg-white/10 text-white font-semibold transition-all border border-white/10 text-sm sm:text-base"
                >
                  Edit Profile
                </button>
              )}
            </div>

            <div className="border-t border-white/5 pt-8 relative z-10">
              {editing ? (
                <form onSubmit={handleUpdate} className="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                  <div className="space-y-2">
                    <label className="text-sm font-bold text-white/40 ml-1">Full Name</label>
                    <input
                      type="text"
                      value={formData.name}
                      onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                      className="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-3.5 text-white focus:outline-none focus:ring-2 focus:ring-premium-primary/50 transition-all"
                      required
                    />
                  </div>
                  <div className="space-y-2">
                    <label className="text-sm font-bold text-white/40 ml-1">Phone Number</label>
                    <input
                      type="tel"
                      value={formData.phone}
                      onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                      className="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-3.5 text-white focus:outline-none focus:ring-2 focus:ring-premium-primary/50 transition-all"
                    />
                  </div>
                  <div className="sm:col-span-2 flex flex-col sm:flex-row gap-3 pt-4">
                    <button type="submit" className="bg-gradient-premium text-white font-bold px-8 py-3.5 rounded-2xl shadow-lg shadow-premium-primary/20 hover:scale-[1.02] transition-all">
                      Save Changes
                    </button>
                    <button
                      type="button"
                      onClick={() => setEditing(false)}
                      className="bg-white/5 text-white font-bold px-8 py-3.5 rounded-2xl hover:bg-white/10 transition-all"
                    >
                      Cancel
                    </button>
                  </div>
                </form>
              ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                  <div className="space-y-1">
                    <p className="text-xs font-black text-white/20 uppercase tracking-widest">Phone Number</p>
                    <p className="text-lg text-white/80 font-medium">{profile.phone || 'Not provided'}</p>
                  </div>
                  <div className="space-y-1">
                    <p className="text-xs font-black text-white/20 uppercase tracking-widest">Member Since</p>
                    <p className="text-lg text-white/80 font-medium">{new Date(profile.created_at).toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                  </div>
                  {profile.last_login_at && (
                    <div className="space-y-1">
                      <p className="text-xs font-black text-white/20 uppercase tracking-widest">Last Activity</p>
                      <p className="text-lg text-white/80 font-medium">{new Date(profile.last_login_at).toLocaleString()}</p>
                    </div>
                  )}
                </div>
              )}
            </div>
          </GlassCard>

          {/* Biometric Stats or extra cards can go here */}
        </div>

        {/* Sidebar Cards */}
        <div className="space-y-6 sm:space-y-8">
          <GlassCard className="border border-white/5 relative overflow-hidden group">
            <div className="flex items-center gap-4 mb-6">
              <div className="w-12 h-12 rounded-2xl bg-premium-primary/20 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                👤
              </div>
              <h3 className="text-xl font-bold text-white">Face Data</h3>
            </div>

            {profile.face_enrolled ? (
              <div className="space-y-6">
                <div className="bg-premium-accent/10 border border-premium-accent/20 rounded-2xl p-4 flex items-center gap-3">
                  <span className="text-premium-accent text-xl">✓</span>
                  <p className="text-premium-accent font-bold text-sm tracking-tight">Biometric Profile Active</p>
                </div>
                <button
                  onClick={() => setShowFaceEnrollment(true)}
                  className="w-full py-3.5 rounded-2xl bg-white/5 hover:bg-white/10 text-white/80 text-sm font-bold transition-all border border-white/5"
                >
                  Manage Face Data
                </button>
              </div>
            ) : (
              <div className="space-y-6">
                <div className="bg-yellow-500/10 border border-yellow-500/20 rounded-2xl p-4 flex items-center gap-3">
                  <span className="text-yellow-500 text-xl">⚠</span>
                  <p className="text-yellow-500 font-bold text-sm tracking-tight">Security Action Required</p>
                </div>
                <p className="text-white/40 text-sm leading-relaxed">
                  Enroll your face to enable high-security biometric attendance verification.
                </p>
                <button
                  onClick={() => setShowFaceEnrollment(true)}
                  className="w-full bg-gradient-premium text-white font-bold py-3.5 rounded-2xl shadow-lg shadow-premium-primary/20 hover:scale-[1.02] transition-all"
                >
                  Enroll Now
                </button>
              </div>
            )}
          </GlassCard>

          <GlassCard className="border border-white/5">
            <div className="flex items-center gap-4 mb-6">
              <div className="w-12 h-12 rounded-2xl bg-premium-secondary/20 flex items-center justify-center text-2xl">
                🛡️
              </div>
              <h3 className="text-xl font-bold text-white">Security</h3>
            </div>
            <div className="space-y-4">
              <div className="flex items-center justify-between p-3 rounded-xl bg-white/5 border border-white/5">
                <span className="text-white/60 text-sm font-medium">WebAuthn</span>
                <span className="text-xs font-black text-white/20 uppercase tracking-widest">Soon</span>
              </div>
              <div className="flex items-center justify-between p-3 rounded-xl bg-white/5 border border-white/5 opacity-50">
                <span className="text-white/60 text-sm font-medium">2FA</span>
                <span className="text-xs font-black text-white/20 uppercase tracking-widest">Locked</span>
              </div>
            </div>
          </GlassCard>
        </div>
      </div>

      {showFaceEnrollment && (
        <FaceEnrollment
          onEnrolled={handleFaceEnrolled}
          onClose={() => setShowFaceEnrollment(false)}
        />
      )}
    </div>
  );
};

export default Profile;
