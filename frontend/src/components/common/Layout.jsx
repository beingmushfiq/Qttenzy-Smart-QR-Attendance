import { Link, useNavigate } from 'react-router-dom';
import { useAuthStore } from '../../store/authStore';
import { authAPI } from '../../services/api/auth';
import { toast } from 'react-toastify';

const Layout = ({ children }) => {
  const { user, logout, isAuthenticated } = useAuthStore();
  const navigate = useNavigate();

  const handleLogout = async () => {
    try {
      await authAPI.logout();
      logout();
      toast.success('Logged out successfully');
      navigate('/login');
    } catch (error) {
      logout();
      navigate('/login');
    }
  };

  if (!isAuthenticated) {
    return children;
  }

  return (
    <div className="min-h-screen">
      {/* Sidebar */}
      <aside className="fixed left-4 top-4 bottom-4 w-64 glass-dark rounded-3xl text-white z-50 border border-white/10">
        <div className="p-8">
          <h1 className="text-3xl font-extrabold mb-10 tracking-tight">
            Qt<span className="text-premium-primary">tenzy</span>
          </h1>
          <nav className="space-y-3">
            {[
              { to: '/dashboard', label: 'Dashboard', icon: '📊' },
              { to: '/sessions', label: 'Sessions', icon: '📅' },
              { to: '/attendance', label: 'Attendance', icon: '✅' },
              { to: '/profile', label: 'Profile', icon: '👤' },
            ].map((item) => (
              <Link
                key={item.to}
                to={item.to}
                className="flex items-center gap-3 px-5 py-3 rounded-xl hover:bg-white/10 transition-all duration-200 group"
              >
                <span className="text-xl group-hover:scale-110 transition-transform">{item.icon}</span>
                <span className="font-medium">{item.label}</span>
              </Link>
            ))}
            
            {(user?.role === 'admin' || user?.role === 'session_manager') && (
              <Link
                to="/admin"
                className="flex items-center gap-3 px-5 py-3 rounded-xl hover:bg-white/10 transition-all duration-200 group text-premium-accent"
              >
                <span className="text-xl group-hover:scale-110 transition-transform">🛡️</span>
                <span className="font-medium">Admin Panel</span>
              </Link>
            )}
          </nav>
        </div>

        <div className="absolute bottom-8 w-full px-8">
          <div className="mb-6 p-4 rounded-2xl bg-white/5 border border-white/10">
            <p className="text-sm font-semibold text-white/90 truncate">{user?.name}</p>
            <p className="text-xs text-white/50 truncate">{user?.email}</p>
          </div>
          <button
            onClick={handleLogout}
            className="w-full bg-red-500/80 backdrop-blur-md text-white px-4 py-3 rounded-xl hover:bg-red-600 transition-all font-semibold shadow-lg shadow-red-500/20"
          >
            Logout
          </button>
        </div>
      </aside>

      {/* Main Content */}
      <main className="ml-72 p-8 h-screen overflow-y-auto">
        {children}
      </main>
    </div>
  );
};

export default Layout;

