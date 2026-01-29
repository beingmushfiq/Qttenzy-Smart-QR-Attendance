import { Link, useNavigate } from 'react-router-dom';
import { useAuthStore } from '../../store/authStore';
import { authAPI } from '../../services/api/auth';
import { toast } from 'react-toastify';
import { useState } from 'react';

const Layout = ({ children }) => {
  const { user, logout, isAuthenticated } = useAuthStore();
  const navigate = useNavigate();
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

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

  const navItems = [
    { to: '/dashboard', label: 'Dashboard', icon: '📊' },
    { to: '/sessions', label: 'Sessions', icon: '📅' },
    { to: '/attendance', label: 'Attendance', icon: '✅' },
    { to: '/profile', label: 'Profile', icon: '👤' },
  ];

  const SidebarContent = () => (
    <>
      <div className="p-6 sm:p-8">
        <h1 className="text-2xl sm:text-3xl font-extrabold mb-8 sm:mb-10 tracking-tight">
          Qt<span className="text-premium-primary">tenzy</span>
        </h1>
        <nav className="space-y-2 sm:space-y-3">
          {navItems.map((item) => (
            <Link
              key={item.to}
              to={item.to}
              onClick={() => setMobileMenuOpen(false)}
              className="flex items-center gap-3 px-4 sm:px-5 py-2.5 sm:py-3 rounded-xl hover:bg-white/10 transition-all duration-200 group"
            >
              <span className="text-lg sm:text-xl group-hover:scale-110 transition-transform">{item.icon}</span>
              <span className="font-medium text-sm sm:text-base">{item.label}</span>
            </Link>
          ))}
          
          {user?.role === 'admin' && (
            <Link
              to="/organizations"
              onClick={() => setMobileMenuOpen(false)}
              className="flex items-center gap-3 px-4 sm:px-5 py-2.5 sm:py-3 rounded-xl hover:bg-white/10 transition-all duration-200 group text-premium-primary"
            >
              <span className="text-lg sm:text-xl group-hover:scale-110 transition-transform">🏢</span>
              <span className="font-medium text-sm sm:text-base">Organizations</span>
            </Link>
          )}

          {user?.role === 'organization_admin' && (
            <>
              <Link
                to="/org-dashboard"
                onClick={() => setMobileMenuOpen(false)}
                className="flex items-center gap-3 px-4 sm:px-5 py-2.5 sm:py-3 rounded-xl hover:bg-white/10 transition-all duration-200 group text-premium-primary"
              >
                <span className="text-lg sm:text-xl group-hover:scale-110 transition-transform">📊</span>
                <span className="font-medium text-sm sm:text-base">Org Dashboard</span>
              </Link>
              <Link
                to="/org-users"
                onClick={() => setMobileMenuOpen(false)}
                className="flex items-center gap-3 px-4 sm:px-5 py-2.5 sm:py-3 rounded-xl hover:bg-white/10 transition-all duration-200 group text-premium-primary"
              >
                <span className="text-lg sm:text-xl group-hover:scale-110 transition-transform">👥</span>
                <span className="font-medium text-sm sm:text-base">Users</span>
              </Link>
            </>
          )}
          
          {(user?.role === 'admin' || user?.role === 'session_manager' || user?.role === 'organization_admin') && (
            <Link
              to="/admin"
              onClick={() => setMobileMenuOpen(false)}
              className="flex items-center gap-3 px-4 sm:px-5 py-2.5 sm:py-3 rounded-xl hover:bg-white/10 transition-all duration-200 group text-premium-accent"
            >
              <span className="text-lg sm:text-xl group-hover:scale-110 transition-transform">🛡️</span>
              <span className="font-medium text-sm sm:text-base">Admin Panel</span>
            </Link>
          )}
        </nav>
      </div>

      <div className="absolute bottom-6 sm:bottom-8 w-full px-6 sm:px-8">
        <div className="mb-4 sm:mb-6 p-3 sm:p-4 rounded-2xl bg-white/5 border border-white/10">
          <p className="text-sm font-semibold text-white/90 truncate">{user?.name}</p>
          <p className="text-xs text-white/50 truncate">{user?.email}</p>
        </div>
        <button
          onClick={handleLogout}
          className="w-full bg-red-500/80 backdrop-blur-md text-white px-4 py-2.5 sm:py-3 rounded-xl hover:bg-red-600 transition-all font-semibold shadow-lg shadow-red-500/20 text-sm sm:text-base"
        >
          Logout
        </button>
      </div>
    </>
  );

  return (
    <div className="min-h-screen">
      {/* Mobile Menu Button */}
      <button
        onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
        className="lg:hidden fixed top-4 left-4 z-50 w-12 h-12 bg-glass-dark backdrop-blur-md rounded-2xl border border-white/10 flex items-center justify-center text-white hover:bg-white/10 transition-all"
        aria-label="Toggle menu"
      >
        {mobileMenuOpen ? (
          <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
          </svg>
        ) : (
          <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        )}
      </button>

      {/* Mobile Backdrop */}
      {mobileMenuOpen && (
        <div
          className="lg:hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-40"
          onClick={() => setMobileMenuOpen(false)}
        />
      )}

      {/* Mobile Sidebar */}
      <aside
        className={`
          lg:hidden fixed left-0 top-0 bottom-0 w-72 glass-dark rounded-r-3xl text-white z-50 border-r border-white/10
          transform transition-transform duration-300 ease-in-out
          ${mobileMenuOpen ? 'translate-x-0' : '-translate-x-full'}
        `}
      >
        <SidebarContent />
      </aside>

      {/* Desktop Sidebar */}
      <aside className="hidden lg:block fixed left-4 top-4 bottom-4 w-64 glass-dark rounded-3xl text-white z-50 border border-white/10">
        <SidebarContent />
      </aside>

      {/* Main Content */}
      <main className="lg:ml-72 p-4 sm:p-6 lg:p-8 min-h-screen overflow-y-auto">
        {children}
      </main>
    </div>
  );
};

export default Layout;
