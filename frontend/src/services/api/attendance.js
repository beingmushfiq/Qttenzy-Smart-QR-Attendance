import apiClient from './client';

export const attendanceAPI = {
  verify: async (data) => {
    return apiClient.post('/attendance/verify', data);
  },
  
  getHistory: async (params = {}) => {
    return apiClient.get('/attendance/history', { params });
  },
  
  getSessionAttendance: async (sessionId, params = {}) => {
    return apiClient.get(`/attendance/session/${sessionId}`, { params });
  }
};

