import apiClient from './client'

export const adminAPI = {
  // Dashboard
  dashboard: async () => {
    return apiClient.get('/admin/dashboard')
  },

  // User Management
  getUsers: async (params) => {
    return apiClient.get('/admin/users', { params })
  },

  updateUserStatus: async (userId, data) => {
    return apiClient.put(`/admin/users/${userId}/status`, data)
  },

  deleteUser: async (userId) => {
    return apiClient.delete(`/admin/users/${userId}`)
  },

  // Attendance Management
  getPendingAttendances: async (params) => {
    return apiClient.get('/admin/attendances/pending', { params })
  },

  approveAttendance: async (attendanceId, data) => {
    return apiClient.put(`/admin/attendances/${attendanceId}/approve`, data)
  },

  rejectAttendance: async (attendanceId, data) => {
    return apiClient.put(`/admin/attendances/${attendanceId}/reject`, data)
  },

  deleteAttendance: async (attendanceId) => {
    return apiClient.delete(`/admin/attendances/${attendanceId}`)
  },

  overrideAttendance: async (attendanceId, data) => {
    return apiClient.put(`/admin/attendances/${attendanceId}/override`, data)
  },

  getAttendanceLogs: async (attendanceId) => {
    return apiClient.get(`/admin/attendances/${attendanceId}/logs`)
  },

  // Analytics
  getAttendanceTrends: async (params) => {
    return apiClient.get('/admin/analytics/attendance-trends', { params })
  },

  getSessionStats: async (params) => {
    return apiClient.get('/admin/analytics/session-stats', { params })
  },

  getUserSummary: async (userId, params) => {
    return apiClient.get(`/admin/analytics/user-summary/${userId}`, { params })
  },

  // Reports
  getAttendanceReport: async (params) => {
    return apiClient.get('/admin/reports/attendance', { params })
  },

  exportAttendanceReport: async (params) => {
    return apiClient.get('/admin/reports/attendance/export', { 
      params,
      responseType: 'blob' 
    })
  },

  exportSessionReport: async (params) => {
    return apiClient.get('/admin/reports/sessions/export', { 
      params,
      responseType: 'blob' 
    })
  }
}
