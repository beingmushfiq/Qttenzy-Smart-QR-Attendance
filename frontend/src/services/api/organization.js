import apiClient from "./client";

/**
 * Organization API Service
 *
 * Handles all organization-related API calls
 */

/**
 * Get all active organizations (public - for registration)
 */
export const getOrganizations = async () => {
  const response = await apiClient.get("/organizations");
  return response.data;
};

/**
 * Get all organizations with filters (admin only)
 */
export const getOrganizationsAdmin = async (params = {}) => {
  const response = await apiClient.get("/admin/organizations", { params });
  return response.data;
};

/**
 * Get a single organization by ID
 */
export const getOrganization = async (id) => {
  const response = await apiClient.get(`/admin/organizations/${id}`);
  return response.data;
};

/**
 * Create a new organization
 */
export const createOrganization = async (data) => {
  const response = await apiClient.post("/admin/organizations", data);
  return response.data;
};

/**
 * Update an organization
 */
export const updateOrganization = async (id, data) => {
  const response = await apiClient.put(`/admin/organizations/${id}`, data);
  return response.data;
};

/**
 * Delete an organization
 */
export const deleteOrganization = async (id) => {
  const response = await apiClient.delete(`/admin/organizations/${id}`);
  return response.data;
};

/**
 * Restore a deleted organization
 */
export const restoreOrganization = async (id) => {
  const response = await apiClient.post(`/admin/organizations/${id}/restore`);
  return response.data;
};

/**
 * Toggle organization active status
 */
export const toggleOrganizationStatus = async (id) => {
  const response = await apiClient.post(
    `/admin/organizations/${id}/toggle-status`,
  );
  return response.data;
};

/**
 * Get organization statistics
 */
export const getOrganizationStatistics = async (id) => {
  const response = await apiClient.get(`/admin/organizations/${id}/statistics`);
  return response.data;
};

export default {
  getOrganizations,
  getOrganizationsAdmin,
  getOrganization,
  createOrganization,
  updateOrganization,
  deleteOrganization,
  restoreOrganization,
  toggleOrganizationStatus,
  getOrganizationStatistics,
};
