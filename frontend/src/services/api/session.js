import apiClient from './client';

export const sessionAPI = {
  getAll: async (params = {}) => {
    return apiClient.get('/sessions', { params });
  },
  
  getById: async (id) => {
    return apiClient.get(`/sessions/${id}`);
  },
  
  create: async (data) => {
    return apiClient.post('/sessions', data);
  },
  
  update: async (id, data) => {
    return apiClient.put(`/sessions/${id}`, data);
  },
  
  delete: async (id) => {
    return apiClient.delete(`/sessions/${id}`);
  },
  
  getQR: async (id, data = {}) => {
    return apiClient.get(`/sessions/${id}/qr`, { params: data });
  }
};

