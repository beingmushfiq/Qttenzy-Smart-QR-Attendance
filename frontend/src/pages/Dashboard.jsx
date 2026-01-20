import { useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { useAuthStore } from '../store/authStore'
import GlassCard from '../components/common/GlassCard'

const Dashboard = () => {
  const { user, isAuthenticated } = useAuthStore()
  const navigate = useNavigate()

  useEffect(() => {
    if (!isAuthenticated) {
      navigate('/login')
    }
  }, [isAuthenticated, navigate])

  if (!user) {
    return <div className="flex items-center justify-center h-full text-white/50">Loading Dashboard...</div>
  }

  return (
    <div className="space-y-10">
      <div>
        <h1 className="text-4xl font-extrabold text-white mb-2 tracking-tight">
          Welcome back, <span className="text-gradient font-black">{user.name.split(' ')[0]}!</span> 👋
        </h1>
        <p className="text-white/40 font-medium">
          Here's what's happening today in <span className="text-premium-primary">Qttenzy</span>.
        </p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-8">
        {[
          { title: 'Sessions', desc: 'Manage your active and upcoming sessions', link: '/sessions', icon: '📅', color: 'from-blue-500 to-indigo-600' },
          { title: 'Attendance', desc: 'Scan QR and mark your presence instantly', link: '/attendance', icon: '✅', color: 'from-emerald-400 to-teal-500' },
          { title: 'Profile', desc: 'View your stats and manage face enrollment', link: '/profile', icon: '👤', color: 'from-purple-500 to-pink-500' },
        ].map((item) => (
          <GlassCard key={item.title} className="group overflow-hidden border border-white/5">
            <div className={`w-14 h-14 rounded-2xl bg-gradient-to-br ${item.color} flex items-center justify-center text-3xl mb-6 shadow-lg transform group-hover:rotate-6 transition-transform`}>
              {item.icon}
            </div>
            <h3 className="text-xl font-bold text-white mb-2">{item.title}</h3>
            <p className="text-white/40 text-sm leading-relaxed mb-6">{item.desc}</p>
            <button
              onClick={() => navigate(item.link)}
              className="px-5 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 text-white text-sm font-semibold transition-all flex items-center gap-2 group/btn"
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

