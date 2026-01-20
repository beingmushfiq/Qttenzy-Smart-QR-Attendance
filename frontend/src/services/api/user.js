import apiClient from './client';

export const userAPI = {
  getProfile: async () => {
    return apiClient.get('/user/profile');
  },
  
  updateProfile: async (data) => {
    return apiClient.put('/user/profile', data);
  },
  
  enrollFace: async (data) => {
    return apiClient.post('/user/face-enroll', data);
  },
  
  registerWebAuthn: async (data) => {
    return apiClient.post('/user/webauthn/register', data);
  }
};

