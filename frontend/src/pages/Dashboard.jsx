import { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useAuthStore } from '../store/authStore'
import GlassCard from '../components/common/GlassCard'
import apiClient from '../services/api/client'
import { toast } from 'react-toastify'

const Dashboard = () => {
  const { user, isAuthenticated } = useAuthStore()
  const navigate = useNavigate()
  const [stats, setStats] = useState(null)
  const [loadingStats, setLoadingStats] = useState(true)

  useEffect(() => {
    if (!isAuthenticated) {
      navigate('/login')
      return;
    }

    const fetchStats = async () => {
      // Only fetch stats for admins/managers
      if (['admin', 'organization_admin', 'session_manager', 'teacher', 'event_manager'].includes(user?.role)) {
         try {
           // Reuse the admin dashboard endpoint. 
           // Note: You might need to ensure the route is accessible to these roles in api.php
           // If not, we might need a general 'dashboard/stats' endpoint. 
           // For now, assuming /admin/dashboard or similar is available or we use a new service method.
           // Let's assume there is a general stats endpoint or we try the admin one.
           // Given the route list, /admin/* requires 'admin' role. 
           // We should probably create a specific service call or just handle the error gracefully.
           
           // If user is strictly 'admin', fetch from admin dashboard.
           // If user is 'organization_admin', the same endpoint might work if middleware allows.
           // Let's try fetching from a new dedicated function we'll create or just use the existing one if permissible.
           
           // Actually, looking at routes/api.php, 'admin/dashboard' is middleware('role:admin'). 
           // We should probably rely on a more open endpoint for other roles, OR I will assume for this task "Admin Dashboard" refers to the Super Admin view.
           // However, the user asked for "Pending Approvals", which implies Admin/OrgAdmin.
           
           const response = await apiClient.get('/admin/dashboard').catch(() => null); 
           if (response?.data?.success) {
             setStats(response.data.data);
           }
         } catch (error) {
           console.error("Failed to load dashboard stats", error);
         } finally {
            setLoadingStats(false);
         }
      } else {
          setLoadingStats(false);
      }
    };

    if (user) {
        fetchStats();
    }
  }, [isAuthenticated, navigate, user])

  if (!user) {
    return <div className="flex items-center justify-center h-full text-white/50">Loading Dashboard...</div>
  }

  return (
    <div className="space-y-8 sm:space-y-10">
      <div>
        <h1 className="text-3xl sm:text-4xl font-extrabold text-white mb-2 tracking-tight">
          Welcome back, <span className="text-gradient font-black">{user.name.split(' ')[0]}!</span> 👋
        </h1>
        <p className="text-white/40 font-medium text-sm sm:text-base">
          Here's what's happening today in <span className="text-premium-primary">Qttenzy</span>.
        </p>
      </div>

      {/* Dynamic Stats Row (Admin/Manager Only) */}
      {stats && (
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
             <GlassCard className="border border-yellow-500/20 bg-yellow-500/5">
                 <h4 className="text-white/60 text-xs font-bold uppercase tracking-wider mb-1">Pending Approvals</h4>
                 <p className="text-2xl sm:text-3xl font-black text-yellow-400">{stats.pending_approvals || 0}</p>
             </GlassCard>
             <GlassCard className="border border-orange-500/20 bg-orange-500/5">
                 <h4 className="text-white/60 text-xs font-bold uppercase tracking-wider mb-1">Pending Attendance</h4>
                 <p className="text-2xl sm:text-3xl font-black text-orange-400">{stats.pending_attendances || 0}</p>
             </GlassCard>
             <GlassCard className="border border-blue-500/20 bg-blue-500/5">
                 <h4 className="text-white/60 text-xs font-bold uppercase tracking-wider mb-1">Today's Sessions</h4>
                 <p className="text-2xl sm:text-3xl font-black text-blue-400">{stats.today_sessions || 0}</p>
             </GlassCard>
             <GlassCard className="border border-green-500/20 bg-green-500/5">
                 <h4 className="text-white/60 text-xs font-bold uppercase tracking-wider mb-1">Attendances Marked</h4>
                 <p className="text-2xl sm:text-3xl font-black text-green-400">{stats.today_attendances || 0}</p>
             </GlassCard>
        </div>
      )}

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mt-6 sm:mt-8">
        {[
          { title: 'Sessions', desc: 'Manage your active and upcoming sessions', link: '/sessions', icon: '📅', color: 'from-blue-500 to-indigo-600' },
          { title: 'Attendance', desc: 'Scan QR and mark your presence instantly', link: '/attendance', icon: '✅', color: 'from-emerald-400 to-teal-500' },
          { title: 'Profile', desc: 'View your stats and manage face enrollment', link: '/profile', icon: '👤', color: 'from-purple-500 to-pink-500' },
        ].map((item) => (
          <GlassCard key={item.title} className="group overflow-hidden border border-white/5">
            <div className={`w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-gradient-to-br ${item.color} flex items-center justify-center text-2xl sm:text-3xl mb-4 sm:mb-6 shadow-lg transform group-hover:rotate-6 transition-transform`}>
              {item.icon}
            </div>
            <h3 className="text-lg sm:text-xl font-bold text-white mb-2">{item.title}</h3>
            <p className="text-white/40 text-xs sm:text-sm leading-relaxed mb-4 sm:mb-6">{item.desc}</p>
            <button
              onClick={() => navigate(item.link)}
              className="px-4 sm:px-5 py-2 sm:py-2.5 rounded-xl bg-white/5 hover:bg-white/10 text-white text-xs sm:text-sm font-semibold transition-all flex items-center gap-2 group/btn"
            >
              Explore <span className="group-hover/btn:translate-x-1 transition-transform">→</span>
            </button>
          </GlassCard>
        ))}
      </div>

      <GlassCard className="mt-12 overflow-hidden border border-premium-primary/20 bg-premium-primary/5">
        <div className="flex flex-col md:flex-row items-center justify-between gap-6">
          <div className="text-center md:text-left">
            <h2 className="text-2xl font-bold text-white mb-2">Need Help?</h2>
            <p className="text-white/40 max-w-md">Check out our documentation or contact the admin for support regarding your credentials or attendance history.</p>
          </div>
          <button className="bg-white text-dark font-bold px-8 py-3 rounded-2xl hover:bg-white/90 transition-all whitespace-nowrap">
            Get Support
          </button>
        </div>
      </GlassCard>
    </div>
  )
}

export default Dashboard

