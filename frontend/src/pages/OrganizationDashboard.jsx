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

  if (loading) return <Layout><div>Loading...</div></Layout>;
  if (!stats) return <Layout><div>No stats available</div></Layout>;

  return (
    <Layout>
      <div className="p-4 sm:p-6">
        <h1 className="text-2xl sm:text-3xl font-bold mb-6">Organization Dashboard</h1>
        
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-6 sm:mb-8">
          <StatCard 
            title="Total Users" 
            value={stats.total_users} 
            icon={<Users className="w-8 h-8 text-blue-500" />}
            color="bg-blue-50"
          />
          <StatCard 
            title="Active Sessions" 
            value={stats.active_sessions} 
            icon={<CheckCircle className="w-8 h-8 text-green-500" />}
            color="bg-green-50"
          />
          <StatCard 
            title="Upcoming Sessions" 
            value={stats.upcoming_sessions} 
            icon={<Calendar className="w-8 h-8 text-purple-500" />}
            color="bg-purple-50"
          />
          <StatCard 
            title="Total Sessions" 
            value={stats.total_sessions} 
            icon={<Clock className="w-8 h-8 text-orange-500" />}
            color="bg-orange-50"
          />
        </div>

        <div className="bg-white rounded-lg shadow p-4 sm:p-6">
          <h2 className="text-lg sm:text-xl font-semibold mb-4">Organization Details</h2>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <p className="text-gray-500 text-sm">Organization Name</p>
              <p className="font-medium text-lg">{user?.organization?.name || 'N/A'}</p>
            </div>
            <div>
              <p className="text-gray-500 text-sm">Code</p>
              <p className="font-medium text-lg">{user?.organization?.code || 'N/A'}</p>
            </div>
             <div>
              <p className="text-gray-500 text-sm">Role</p>
              <p className="font-medium text-lg capitalize">{user?.role?.replace('_', ' ') || 'N/A'}</p>
            </div>
          </div>
        </div>
      </div>
    </Layout>
  );
};

const StatCard = ({ title, value, icon, color }) => (
  <div className={`${color} rounded-lg p-4 sm:p-6 flex items-center shadow-sm`}>
    <div className="mr-3 sm:mr-4 bg-white p-2 sm:p-3 rounded-full shadow-sm">{icon}</div>
    <div>
      <p className="text-gray-500 text-sm font-medium">{title}</p>
      <p className="text-xl sm:text-2xl font-bold text-gray-800">{value}</p>
    </div>
  </div>
);

export default OrganizationDashboard;
