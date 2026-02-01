import React, { useState, useEffect } from 'react';
import { useAuthStore } from '../store/authStore';
import apiClient from '../services/api/client';
import Layout from '../components/common/Layout';
import { Users, Calendar, CheckCircle, Clock } from 'lucide-react';

const OrganizationDashboard = () => {
  const { user } = useAuthStore();
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchStats = async () => {
      if (user?.organization_id) {
        try {
          const response = await apiClient.get(`/organizations/${user.organization_id}/statistics`);
          // apiClient unwraps to { success: true, data: {...} }
          setStats(response.data || response);
        } catch (error) {
          console.error('Failed to fetch org stats', error);
        } finally {
          setLoading(false);
        }
      } else {
        setLoading(false);
      }
    };

    fetchStats();
  }, [user]);

  if (loading) return (
    <Layout>
        <div className="flex items-center justify-center h-full min-h-[50vh]">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-premium-primary"></div>
        </div>
    </Layout>
  );

  if (!stats) return (
     <Layout>
        <div className="flex flex-col items-center justify-center min-h-[50vh] text-center">
            <div className="glass p-8 rounded-3xl border border-white/10">
                <p className="text-white/60 text-lg">No statistics available yet.</p>
            </div>
        </div>
     </Layout>
  );

  return (
    <Layout>
      <div className="p-4 sm:p-6 space-y-6 sm:space-y-8">
        {/* Header */}
        <div>
            <h1 className="text-2xl sm:text-3xl font-bold text-white mb-2">Dashboard</h1>
            <p className="text-white/60">Welcome back, {user?.name}</p>
        </div>
        
        {/* Stats Grid */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
          <StatCard 
            title="Total Users" 
            value={stats.total_users} 
            icon={<Users className="w-6 h-6 text-blue-400" />}
            colorClass="from-blue-500/10 to-blue-600/5 border-blue-500/20"
            iconBg="bg-blue-500/20"
          />
          <StatCard 
            title="Active Sessions" 
            value={stats.active_sessions} 
            icon={<CheckCircle className="w-6 h-6 text-green-400" />}
            colorClass="from-green-500/10 to-green-600/5 border-green-500/20"
            iconBg="bg-green-500/20"
          />
          <StatCard 
            title="Upcoming Sessions" 
            value={stats.upcoming_sessions} 
            icon={<Calendar className="w-6 h-6 text-purple-400" />}
            colorClass="from-purple-500/10 to-purple-600/5 border-purple-500/20"
            iconBg="bg-purple-500/20"
          />
          <StatCard 
            title="Total Sessions" 
            value={stats.total_sessions} 
            icon={<Clock className="w-6 h-6 text-orange-400" />}
            colorClass="from-orange-500/10 to-orange-600/5 border-orange-500/20"
            iconBg="bg-orange-500/20"
          />
        </div>

        {/* Details Card */}
        <div className="glass rounded-3xl p-6 sm:p-8 border border-white/10">
          <h2 className="text-lg sm:text-xl font-semibold text-white mb-6">Organization Details</h2>
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-6">
            <div className="p-4 rounded-2xl bg-white/5 border border-white/5 transition-colors hover:bg-white/10">
              <p className="text-white/40 text-sm mb-1">Organization Name</p>
              <p className="font-medium text-lg text-white">{user?.organization?.name || 'N/A'}</p>
            </div>
            <div className="p-4 rounded-2xl bg-white/5 border border-white/5 transition-colors hover:bg-white/10">
              <p className="text-white/40 text-sm mb-1">Organization Code</p>
              <p className="font-mono text-lg text-premium-primary tracking-wider">{user?.organization?.code || 'N/A'}</p>
            </div>
             <div className="p-4 rounded-2xl bg-white/5 border border-white/5 transition-colors hover:bg-white/10">
              <p className="text-white/40 text-sm mb-1">Your Role</p>
              <p className="font-medium text-lg text-white capitalize">{user?.role?.replace('_', ' ') || 'N/A'}</p>
            </div>
          </div>
        </div>
      </div>
    </Layout>
  );
};

const StatCard = ({ title, value, icon, colorClass, iconBg }) => (
  <div className={`relative overflow-hidden rounded-3xl p-6 border ${colorClass} bg-gradient-to-br transition-all hover:scale-[1.02] group`}>
    <div className="flex items-center justify-between mb-4">
        <div className={`p-3 rounded-2xl ${iconBg} backdrop-blur-md transition-transform group-hover:scale-110`}>
            {icon}
        </div>
    </div>
    <div>
      <p className="text-white/50 text-sm font-medium mb-1">{title}</p>
      <p className="text-2xl sm:text-3xl font-bold text-white">{value}</p>
    </div>
  </div>
);

export default OrganizationDashboard;
