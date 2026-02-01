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
  return apiClient.get("/organizations");
};

/**
 * Get all organizations with filters (admin only)
 */
export const getOrganizationsAdmin = async (params = {}) => {
  return apiClient.get("/admin/organizations", { params });
};

/**
 * Get a single organization by ID
 */
export const getOrganization = async (id) => {
  return apiClient.get(`/admin/organizations/${id}`);
};

/**
 * Create a new organization
 */
export const createOrganization = async (data) => {
  return apiClient.post("/admin/organizations", data);
};

/**
 * Update an organization
 */
export const updateOrganization = async (id, data) => {
  return apiClient.put(`/admin/organizations/${id}`, data);
};

/**
 * Delete an organization
 */
export const deleteOrganization = async (id) => {
  return apiClient.delete(`/admin/organizations/${id}`);
};

/**
 * Restore a deleted organization
 */
export const restoreOrganization = async (id) => {
  return apiClient.post(`/admin/organizations/${id}/restore`);
};

/**
 * Toggle organization active status
 */
export const toggleOrganizationStatus = async (id) => {
  return apiClient.post(
    `/admin/organizations/${id}/toggle-status`,
  );
};

/**
 * Get organization statistics
 */
export const getOrganizationStatistics = async (id) => {
  return apiClient.get(`/admin/organizations/${id}/statistics`);
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
