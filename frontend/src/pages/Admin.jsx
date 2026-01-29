import { useState, useEffect } from 'react'
import { Routes, Route, Link, useLocation } from 'react-router-dom'
import { adminAPI } from '../services/api/admin'
import { toast } from 'react-toastify'
import GlassCard from '../components/common/GlassCard'

// Admin Dashboard Component
const AdminDashboard = () => {
  const [stats, setStats] = useState(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    fetchDashboard()
    const interval = setInterval(fetchDashboard, 30000) // Refresh every 30s
    return () => clearInterval(interval)
  }, [])

  const fetchDashboard = async () => {
    try {
      const response = await adminAPI.dashboard()
      setStats(response.data)
    } catch (error) {
      toast.error('Failed to load dashboard')
    } finally {
      setLoading(false)
    }
  }

  if (loading) {
    return <div className="flex items-center justify-center h-64 text-white/50">Loading dashboard...</div>
  }

  const statCards = [
    { title: 'Total Users', value: stats?.total_users || 0, icon: '👥', color: 'from-blue-500 to-indigo-600' },
    { title: 'Students', value: stats?.total_students || 0, icon: '🎓', color: 'from-emerald-400 to-teal-500' },
    { title: 'Teachers', value: stats?.total_teachers || 0, icon: '👨‍🏫', color: 'from-purple-500 to-pink-500' },
    { title: 'Pending Approvals', value: stats?.pending_user_approvals || 0, icon: '⏳', color: 'from-orange-400 to-red-500' },
    { title: 'Pending Attendance', value: stats?.pending_attendance_approvals || 0, icon: '✋', color: 'from-yellow-400 to-orange-500' },
    { title: "Today's Sessions", value: stats?.todays_sessions || 0, icon: '📅', color: 'from-cyan-400 to-blue-500' },
  ]

  return (
    <div className="space-y-6 sm:space-y-8">
      <div>
        <h1 className="text-3xl sm:text-4xl font-extrabold text-white mb-2 tracking-tight">
          Admin <span className="text-gradient font-black">Dashboard</span> 🛡️
        </h1>
        <p className="text-white/40 font-medium text-sm sm:text-base">
          Manage users, sessions, and attendance approvals
        </p>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
        {statCards.map((stat) => (
          <GlassCard key={stat.title} className="group overflow-hidden border border-white/5">
            <div className="flex items-center gap-4">
              <div className={`w-12 h-12 sm:w-16 sm:h-16 rounded-2xl bg-gradient-to-br ${stat.color} flex items-center justify-center text-2xl sm:text-3xl shadow-lg transform group-hover:rotate-6 transition-transform`}>
                {stat.icon}
              </div>
              <div>
                <p className="text-white/60 text-sm font-medium">{stat.title}</p>
                <p className="text-2xl sm:text-3xl font-bold text-white">{stat.value}</p>
              </div>
            </div>
          </GlassCard>
        ))}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <GlassCard className="border border-premium-primary/20">
          <h3 className="text-xl font-bold text-white mb-4">Quick Actions</h3>
          <div className="space-y-3">
            <Link to="/admin/users" className="block">
              <button className="w-full bg-white/5 hover:bg-white/10 text-white font-semibold py-3 px-4 rounded-xl transition-all text-left flex items-center justify-between">
                <span>👥 Manage Users</span>
                <span>→</span>
              </button>
            </Link>
            <Link to="/admin/attendances" className="block">
              <button className="w-full bg-white/5 hover:bg-white/10 text-white font-semibold py-3 px-4 rounded-xl transition-all text-left flex items-center justify-between">
                <span>✅ Pending Attendances</span>
                {stats?.pending_attendance_approvals > 0 && (
                  <span className="bg-red-500 text-white text-xs px-2 py-1 rounded-full">
                    {stats.pending_attendance_approvals}
                  </span>
                )}
                <span>→</span>
              </button>
            </Link>
            <Link to="/admin/reports" className="block">
              <button className="w-full bg-white/5 hover:bg-white/10 text-white font-semibold py-3 px-4 rounded-xl transition-all text-left flex items-center justify-between">
                <span>📊 View Reports</span>
                <span>→</span>
              </button>
            </Link>
          </div>
        </GlassCard>

        <GlassCard className="border border-white/10">
          <h3 className="text-xl font-bold text-white mb-4">Today's Activity</h3>
          <div className="space-y-4">
            <div className="flex items-center justify-between">
              <span className="text-white/60">Sessions Today</span>
              <span className="text-white font-bold text-xl">{stats?.todays_sessions || 0}</span>
            </div>
            <div className="flex items-center justify-between">
              <span className="text-white/60">Attendances Marked</span>
              <span className="text-white font-bold text-xl">{stats?.todays_attendances || 0}</span>
            </div>
            <div className="flex items-center justify-between">
              <span className="text-white/60">Pending Approvals</span>
              <span className="text-orange-400 font-bold text-xl">{stats?.pending_attendance_approvals || 0}</span>
            </div>
          </div>
        </GlassCard>
      </div>
    </div>
  )
}

// Admin Users Component
const AdminUsers = () => {
  const [users, setUsers] = useState([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    fetchUsers()
  }, [])

  const fetchUsers = async () => {
    try {
      const response = await adminAPI.getUsers()
      setUsers(response.data || [])
    } catch (error) {
      toast.error('Failed to load users')
    } finally {
      setLoading(false)
    }
  }

  const handleApprove = async (userId) => {
    try {
      await adminAPI.updateUserStatus(userId, { is_approved: true })
      toast.success('User approved successfully')
      fetchUsers()
    } catch (error) {
      toast.error('Failed to approve user')
    }
  }

  if (loading) {
    return <div className="flex items-center justify-center h-64 text-white/50">Loading users...</div>
  }

  return (
    <div className="space-y-4 sm:space-y-6">
      <div>
        <h1 className="text-3xl sm:text-4xl font-extrabold text-white mb-2 tracking-tight">
          User <span className="text-gradient font-black">Management</span> 👥
        </h1>
        <p className="text-white/40 font-medium text-sm sm:text-base">
          Approve, manage, and monitor all users
        </p>
      </div>

      <GlassCard>
        <div className="overflow-x-auto -mx-4 sm:-mx-6">
          <div className="inline-block min-w-full align-middle px-4 sm:px-6">
            <table className="min-w-full">
            <thead>
              <tr className="border-b border-white/10">
                <th className="text-left py-3 px-4 text-white/60 font-semibold">Name</th>
                <th className="text-left py-3 px-4 text-white/60 font-semibold">Email</th>
                <th className="text-left py-3 px-4 text-white/60 font-semibold">Role</th>
                <th className="text-left py-3 px-4 text-white/60 font-semibold">Status</th>
                <th className="text-left py-3 px-4 text-white/60 font-semibold">Actions</th>
              </tr>
            </thead>
            <tbody>
              {users.map((user) => (
                <tr key={user.id} className="border-b border-white/5 hover:bg-white/5">
                  <td className="py-3 px-4 text-white">{user.name}</td>
                  <td className="py-3 px-4 text-white/60">{user.email}</td>
                  <td className="py-3 px-4">
                    <span className="px-2 py-1 rounded-lg bg-blue-500/20 text-blue-300 text-xs font-semibold">
                      {user.role}
                    </span>
                  </td>
                  <td className="py-3 px-4">
                    {user.is_approved ? (
                      <span className="px-2 py-1 rounded-lg bg-green-500/20 text-green-300 text-xs font-semibold">
                        Approved
                      </span>
                    ) : (
                      <span className="px-2 py-1 rounded-lg bg-orange-500/20 text-orange-300 text-xs font-semibold">
                        Pending
                      </span>
                    )}
                  </td>
                  <td className="py-3 px-4">
                    {!user.is_approved && (
                      <button
                        onClick={() => handleApprove(user.id)}
                        className="px-3 py-1 rounded-lg bg-green-500/20 hover:bg-green-500/30 text-green-300 text-sm font-semibold transition-all"
                      >
                        Approve
                      </button>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
            </table>
          </div>
        </div>
      </GlassCard>
    </div>
  )
}

// Admin Attendances Component
const AdminAttendances = () => {
  const [attendances, setAttendances] = useState([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    fetchPendingAttendances()
  }, [])

  const fetchPendingAttendances = async () => {
    try {
      const response = await adminAPI.getPendingAttendances()
      setAttendances(response.data || [])
    } catch (error) {
      toast.error('Failed to load attendances')
    } finally {
      setLoading(false)
    }
  }

  const handleApprove = async (id) => {
    try {
      await adminAPI.approveAttendance(id, { status: 'present' })
      toast.success('Attendance approved')
      fetchPendingAttendances()
    } catch (error) {
      toast.error('Failed to approve attendance')
    }
  }

  const handleReject = async (id) => {
    try {
      await adminAPI.rejectAttendance(id, { reason: 'Invalid verification' })
      toast.success('Attendance rejected')
      fetchPendingAttendances()
    } catch (error) {
      toast.error('Failed to reject attendance')
    }
  }

  if (loading) {
    return <div className="flex items-center justify-center h-64 text-white/50">Loading attendances...</div>
  }

  return (
    <div className="space-y-4 sm:space-y-6">
      <div>
        <h1 className="text-3xl sm:text-4xl font-extrabold text-white mb-2 tracking-tight">
          Pending <span className="text-gradient font-black">Attendances</span> ✋
        </h1>
        <p className="text-white/40 font-medium text-sm sm:text-base">
          Review and approve attendance requests
        </p>
      </div>

      {attendances.length === 0 ? (
        <GlassCard className="text-center py-12">
          <p className="text-white/40 text-lg">No pending attendances</p>
        </GlassCard>
      ) : (
        <div className="space-y-3 sm:space-y-4">
          {attendances.map((attendance) => (
            <GlassCard key={attendance.id} className="border border-white/10">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-0">
                <div>
                  <h3 className="text-white font-bold">{attendance.user?.name}</h3>
                  <p className="text-white/60 text-sm">{attendance.session?.title}</p>
                  <p className="text-white/40 text-xs mt-1">
                    {new Date(attendance.verified_at).toLocaleString()}
                  </p>
                </div>
                <div className="flex flex-col sm:flex-row gap-2">
                  <button
                    onClick={() => handleApprove(attendance.id)}
                    className="px-4 py-2 rounded-xl bg-green-500/20 hover:bg-green-500/30 text-green-300 font-semibold transition-all"
                  >
                    Approve
                  </button>
                  <button
                    onClick={() => handleReject(attendance.id)}
                    className="px-4 py-2 rounded-xl bg-red-500/20 hover:bg-red-500/30 text-red-300 font-semibold transition-all"
                  >
                    Reject
                  </button>
                </div>
              </div>
            </GlassCard>
          ))}
        </div>
      )}
    </div>
  )
}

// Admin Reports Component  
const AdminReports = () => {
  const handleExport = async (type) => {
    try {
      const response = type === 'attendance' 
        ? await adminAPI.exportAttendanceReport({ format: 'csv' })
        : await adminAPI.exportSessionReport({ format: 'csv' })
      
      // Create download link
      const url = window.URL.createObjectURL(new Blob([response]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `${type}_report_${Date.now()}.csv`)
      document.body.appendChild(link)
      link.click()
      link.remove()
      
      toast.success('Report downloaded successfully')
    } catch (error) {
      toast.error('Failed to download report')
    }
  }

  return (
    <div className="space-y-4 sm:space-y-6">
      <div>
        <h1 className="text-3xl sm:text-4xl font-extrabold text-white mb-2 tracking-tight">
          Reports & <span className="text-gradient font-black">Analytics</span> 📊
        </h1>
        <p className="text-white/40 font-medium text-sm sm:text-base">
          Export and analyze attendance data
        </p>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
        <GlassCard className="border border-white/10">
          <h3 className="text-xl font-bold text-white mb-4">Attendance Report</h3>
          <p className="text-white/60 mb-6">Export all attendance records with filters</p>
          <button
            onClick={() => handleExport('attendance')}
            className="w-full bg-gradient-premium text-white font-bold py-3 rounded-xl hover:scale-[1.02] transition-all"
          >
            Download CSV
          </button>
        </GlassCard>

        <GlassCard className="border border-white/10">
          <h3 className="text-xl font-bold text-white mb-4">Session Report</h3>
          <p className="text-white/60 mb-6">Export session statistics and attendance rates</p>
          <button
            onClick={() => handleExport('session')}
            className="w-full bg-gradient-premium text-white font-bold py-3 rounded-xl hover:scale-[1.02] transition-all"
          >
            Download CSV
          </button>
        </GlassCard>
      </div>
    </div>
  )
}

// Main Admin Component with Routing
const Admin = () => {
  const location = useLocation()
  
  const navItems = [
    { path: '/admin', label: 'Dashboard', icon: '📊' },
    { path: '/admin/users', label: 'Users', icon: '👥' },
    { path: '/admin/attendances', label: 'Attendances', icon: '✋' },
    { path: '/admin/reports', label: 'Reports', icon: '📈' },
  ]

  return (
    <div className="space-y-6">
      {/* Admin Navigation */}
      <div className="flex gap-2 overflow-x-auto pb-2 -mx-4 px-4 sm:mx-0 sm:px-0">
        {navItems.map((item) => (
          <Link key={item.path} to={item.path}>
            <button
              className={`px-3 sm:px-4 py-2 rounded-xl font-semibold transition-all whitespace-nowrap text-sm sm:text-base ${
                location.pathname === item.path
                  ? 'bg-gradient-premium text-white'
                  : 'bg-white/5 text-white/60 hover:bg-white/10'
              }`}
            >
              {item.icon} {item.label}
            </button>
          </Link>
        ))}
      </div>

      {/* Admin Routes */}
      <Routes>
        <Route index element={<AdminDashboard />} />
        <Route path="users" element={<AdminUsers />} />
        <Route path="attendances" element={<AdminAttendances />} />
        <Route path="reports" element={<AdminReports />} />
      </Routes>
    </div>
  )
}

export default Admin
