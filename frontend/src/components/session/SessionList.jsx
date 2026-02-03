import { useEffect, useState } from 'react';
import { sessionAPI } from '../../services/api/session';
import { useNavigate } from 'react-router-dom';
import { useAuthStore } from '../../store/authStore';
import { toast } from 'react-toastify';
import GlassCard from '../common/GlassCard';

const SessionList = () => {
  const { user } = useAuthStore();
  const [sessions, setSessions] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filters, setFilters] = useState({
    status: '',
    type: '',
    search: ''
  });
  const navigate = useNavigate();

  useEffect(() => {
    fetchSessions();
  }, [filters]);

  const fetchSessions = async () => {
    try {
      setLoading(true);
      // Only send non-empty filters
      const activeFilters = {};
      if (filters.status) activeFilters.status = filters.status;
      if (filters.type) activeFilters.type = filters.type;
      if (filters.search) activeFilters.search = filters.search;
      
      const response = await sessionAPI.getAll(activeFilters);
      console.log('Sessions response:', response);
      // Handle both paginated and non-paginated responses
      const sessionsData = response.data?.data || response.data || response || [];
      setSessions(Array.isArray(sessionsData) ? sessionsData : sessionsData.data || []);
    } catch (error) {
      console.error('Error fetching sessions:', error);
      toast.error('Failed to load sessions');
    } finally {
      setLoading(false);
    }
  };

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
  };

  if (loading && sessions.length === 0) {
    return <div className="flex items-center justify-center h-64 text-white/50">Loading sessions...</div>;
  }

  return (
    <div className="space-y-6 sm:space-y-10">
      <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 sm:gap-6">
        <div>
          <h1 className="text-3xl sm:text-4xl font-extrabold text-white mb-2 tracking-tight">Sessions</h1>
          <p className="text-white/40 font-medium tracking-tight text-sm sm:text-base">Discover and participate in active attendance sessions.</p>
        </div>
        {(user?.role === 'admin' || user?.role === 'session_manager' || user?.role === 'organization_admin' || user?.role === 'teacher' || user?.role === 'event_manager') && (
          <button
            onClick={() => navigate('/sessions/create')}
            className="bg-gradient-premium text-white px-6 sm:px-8 py-3 sm:py-3.5 rounded-2xl font-bold shadow-lg shadow-premium-primary/20 hover:scale-[1.02] transition-all whitespace-nowrap text-sm sm:text-base"
          >
            Create New Session
          </button>
        )}
      </div>

      {/* Filters */}
      <GlassCard className="!p-4 sm:!p-8 border border-white/5">
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-8">
          <div className="space-y-2">
            <label className="text-xs font-black text-white/20 uppercase tracking-widest ml-1">Status</label>
            <select
              value={filters.status}
              onChange={(e) => setFilters({ ...filters, status: e.target.value })}
              className="w-full bg-white/5 border border-white/10 rounded-2xl px-4 sm:px-5 py-3 sm:py-3.5 text-white/80 focus:outline-none focus:ring-2 focus:ring-premium-primary/50 transition-all appearance-none cursor-pointer text-sm sm:text-base"
            >
              <option value="" className="bg-dark">All Statuses</option>
              <option value="draft" className="bg-dark">Draft</option>
              <option value="active" className="bg-dark">Active</option>
              <option value="completed" className="bg-dark">Completed</option>
            </select>
          </div>
          <div className="space-y-2">
            <label className="text-xs font-black text-white/20 uppercase tracking-widest ml-1">Type</label>
            <select
              value={filters.type}
              onChange={(e) => setFilters({ ...filters, type: e.target.value })}
              className="w-full bg-white/5 border border-white/10 rounded-2xl px-4 sm:px-5 py-3 sm:py-3.5 text-white/80 focus:outline-none focus:ring-2 focus:ring-premium-primary/50 transition-all appearance-none cursor-pointer text-sm sm:text-base"
            >
              <option value="" className="bg-dark">All Types</option>
              <option value="open" className="bg-dark">Open</option>
              <option value="pre_registered" className="bg-dark">Pre-registered</option>
              <option value="admin_approved" className="bg-dark">Admin Approved</option>
            </select>
          </div>
          <div className="space-y-2">
            <label className="text-xs font-black text-white/20 uppercase tracking-widest ml-1">Quick Search</label>
            <input
              type="text"
              value={filters.search}
              onChange={(e) => setFilters({ ...filters, search: e.target.value })}
              placeholder="Topic, location, or host..."
              className="w-full bg-white/5 border border-white/10 rounded-2xl px-4 sm:px-5 py-3 sm:py-3.5 text-white placeholder-white/20 focus:outline-none focus:ring-2 focus:ring-premium-primary/50 transition-all text-sm sm:text-base"
            />
          </div>
        </div>
      </GlassCard>

      {/* Sessions Grid */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-8">
        {sessions.map((session) => (
          <GlassCard
            key={session.id}
            className="group hover:border-premium-primary/30 border-white/5 transition-all cursor-pointer relative overflow-hidden"
          >
            {/* Status Badge */}
            <div className="absolute top-6 right-6">
              <span className={`px-4 py-1.5 text-[10px] font-black uppercase tracking-widest rounded-xl border ${
                session.status === 'active' ? 'bg-premium-accent/10 text-premium-accent border-premium-accent/20 shadow-[0_0_15px_rgba(16,185,129,0.1)]' :
                session.status === 'completed' ? 'bg-white/5 text-white/30 border-white/10' :
                'bg-yellow-500/10 text-yellow-500 border-yellow-500/20'
              }`}>
                {session.status}
              </span>
            </div>

            <h3 className="text-xl sm:text-2xl font-bold text-white mb-3 group-hover:text-premium-primary transition-colors pr-16 sm:pr-20">{session.title}</h3>
            
            <p className="text-white/40 text-sm leading-relaxed mb-8 line-clamp-2 font-medium">
              {session.description || 'No description provided for this session.'}
            </p>

            <div className="space-y-4 mb-8">
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-lg">📅</div>
                <div>
                  <p className="text-[10px] font-black text-white/20 uppercase tracking-widest">Starts At</p>
                  <p className="text-sm text-white/80 font-bold">{formatDate(session.start_time)}</p>
                </div>
              </div>
              
              {session.location_name && (
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-lg">📍</div>
                  <div>
                    <p className="text-[10px] font-black text-white/20 uppercase tracking-widest">Location</p>
                    <p className="text-sm text-white/80 font-bold">{session.location_name}</p>
                  </div>
                </div>
              )}
            </div>

            <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 pt-6 border-t border-white/5">
              {session.requires_payment ? (
                <div className="text-premium-primary font-bold text-lg">
                  ${session.payment_amount}
                </div>
              ) : (
                <div className="text-premium-accent font-bold text-sm tracking-tight">Free Access</div>
              )}
              
              <button
                onClick={(e) => {
                  e.stopPropagation();
                  navigate(`/sessions/${session.id}`);
                }}
                className="px-5 sm:px-6 py-2 sm:py-2.5 rounded-xl bg-white/5 group-hover:bg-premium-primary group-hover:text-white text-white/60 text-xs sm:text-sm font-bold transition-all border border-white/5 group-hover:border-premium-primary group-hover:shadow-lg group-hover:shadow-premium-primary/20 whitespace-nowrap"
              >
                View Details
              </button>
            </div>
          </GlassCard>
        ))}
      </div>

      {sessions.length === 0 && !loading && (
        <GlassCard className="text-center py-24 border border-white/5">
          <div className="text-5xl mb-6">🔍</div>
          <h2 className="text-2xl font-bold text-white mb-2">No sessions found</h2>
          <p className="text-white/30 max-w-sm mx-auto">Try adjusting your filters or search keywords to find what you're looking for.</p>
        </GlassCard>
      )}
    </div>
  );
};

export default SessionList;
