import apiClient from './client'

/**
 * Payment API Service
 * 
 * Handles payment processing for sessions
 */

/**
 * Initiate a payment for a session
 * 
 * @param {object} data - { session_id, gateway }
 * @returns {Promise} Payment data including redirect URL
 */
export const initiatePayment = async (data) => {
  const response = await apiClient.post('/payment/initiate', data)
  return response.data
}

/**
 * Get payment status by ID
 * 
 * @param {string|number} id - Payment ID
 * @returns {Promise} Payment details
 */
export const getPaymentStatus = async (id) => {
  const response = await apiClient.get(`/payment/status/${id}`)
  return response.data
}

export default {
  initiatePayment,
  getPaymentStatus
}
