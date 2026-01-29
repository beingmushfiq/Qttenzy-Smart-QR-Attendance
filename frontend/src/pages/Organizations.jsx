import { useState, useEffect } from 'react'
import { toast } from 'react-toastify'
import {
  getOrganizationsAdmin,
  createOrganization,
  updateOrganization,
  deleteOrganization,
  restoreOrganization,
  toggleOrganizationStatus,
  getOrganizationStatistics
} from '../services/api/organization'

const Organizations = () => {
  const [organizations, setOrganizations] = useState([])
  const [loading, setLoading] = useState(true)
  const [showModal, setShowModal] = useState(false)
  const [editingOrg, setEditingOrg] = useState(null)
  const [searchTerm, setSearchTerm] = useState('')
  const [showDeleted, setShowDeleted] = useState(false)
  const [formData, setFormData] = useState({
    name: '',
    code: '',
    email: '',
    phone: '',
    address: '',
    is_active: true,
    settings: {
      timezone: 'Asia/Dhaka',
      late_threshold_minutes: 15,
      face_confidence_threshold: 0.7,
      gps_radius_meters: 100
    }
  })

  useEffect(() => {
    fetchOrganizations()
  }, [showDeleted])

  const fetchOrganizations = async () => {
    try {
      setLoading(true)
      const params = {
        with_trashed: showDeleted
      }
      const response = await getOrganizationsAdmin(params)
      setOrganizations(response.data || [])
    } catch (error) {
      toast.error('Failed to fetch organizations')
      console.error(error)
    } finally {
      setLoading(false)
    }
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    
    try {
      if (editingOrg) {
        await updateOrganization(editingOrg.id, formData)
        toast.success('Organization updated successfully')
      } else {
        await createOrganization(formData)
        toast.success('Organization created successfully')
      }
      
      setShowModal(false)
      resetForm()
      fetchOrganizations()
    } catch (error) {
      toast.error(error.response?.data?.message || 'Operation failed')
    }
  }

  const handleEdit = (org) => {
    setEditingOrg(org)
    setFormData({
      name: org.name,
      code: org.code,
      email: org.email || '',
      phone: org.phone || '',
      address: org.address || '',
      is_active: org.is_active,
      settings: org.settings || {
        timezone: 'Asia/Dhaka',
        late_threshold_minutes: 15,
        face_confidence_threshold: 0.7,
        gps_radius_meters: 100
      }
    })
    setShowModal(true)
  }

  const handleDelete = async (id) => {
    if (!confirm('Are you sure you want to delete this organization?')) return
    
    try {
      await deleteOrganization(id)
      toast.success('Organization deleted successfully')
      fetchOrganizations()
    } catch (error) {
      toast.error(error.response?.data?.message || 'Failed to delete organization')
    }
  }

  const handleRestore = async (id) => {
    try {
      await restoreOrganization(id)
      toast.success('Organization restored successfully')
      fetchOrganizations()
    } catch (error) {
      toast.error('Failed to restore organization')
    }
  }

  const handleToggleStatus = async (id) => {
    try {
      await toggleOrganizationStatus(id)
      toast.success('Organization status updated')
      fetchOrganizations()
    } catch (error) {
      toast.error('Failed to update status')
    }
  }

  const resetForm = () => {
    setFormData({
      name: '',
      code: '',
      email: '',
      phone: '',
      address: '',
      is_active: true,
      settings: {
        timezone: 'Asia/Dhaka',
        late_threshold_minutes: 15,
        face_confidence_threshold: 0.7,
        gps_radius_meters: 100
      }
    })
    setEditingOrg(null)
  }

  const filteredOrganizations = organizations.filter(org =>
    org.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    org.code.toLowerCase().includes(searchTerm.toLowerCase())
  )

  return (
    <div className="p-4 sm:p-6">
      {/* Header */}
      <div className="mb-4 sm:mb-6">
        <h1 className="text-2xl sm:text-3xl font-bold text-white mb-2">Organization Management</h1>
        <p className="text-white/60 text-sm sm:text-base">Manage organizations and their settings</p>
      </div>

      {/* Actions Bar */}
      <div className="flex flex-col sm:flex-row gap-3 sm:gap-4 mb-4 sm:mb-6">
        <input
          type="text"
          placeholder="Search organizations..."
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
          className="flex-1 min-w-[200px] bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 sm:py-2 text-white placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-premium-primary/50 text-sm sm:text-base"
        />
        
        <button
          onClick={() => {
            resetForm()
            setShowModal(true)
          }}
          className="bg-gradient-premium text-white px-5 sm:px-6 py-2.5 sm:py-2 rounded-xl font-semibold hover:scale-105 transition-transform text-sm sm:text-base whitespace-nowrap"
        >
          + Add Organization
        </button>

        <button
          onClick={() => setShowDeleted(!showDeleted)}
          className={`px-5 sm:px-6 py-2.5 sm:py-2 rounded-xl font-semibold transition-all text-sm sm:text-base whitespace-nowrap ${
            showDeleted
              ? 'bg-premium-primary text-white'
              : 'bg-white/5 text-white/60 hover:bg-white/10'
          }`}
        >
          {showDeleted ? 'Hide Deleted' : 'Show Deleted'}
        </button>
      </div>

      {/* Organizations Grid */}
      {loading ? (
        <div className="flex items-center justify-center py-20">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-premium-primary"></div>
        </div>
      ) : (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
          {filteredOrganizations.map((org) => (
            <div
              key={org.id}
              className={`glass rounded-2xl p-6 border ${
                org.deleted_at
                  ? 'border-red-500/30 bg-red-500/5'
                  : 'border-white/10'
              } hover:border-premium-primary/50 transition-all`}
            >
              {/* Organization Header */}
              <div className="flex items-start justify-between mb-4">
                <div className="flex-1">
                  <h3 className="text-xl font-bold text-white mb-1">{org.name}</h3>
                  <p className="text-premium-primary font-mono text-sm">{org.code}</p>
                </div>
                
                <div className="flex items-center gap-2">
                  {org.deleted_at ? (
                    <span className="px-3 py-1 bg-red-500/20 text-red-400 rounded-full text-xs font-semibold">
                      Deleted
                    </span>
                  ) : (
                    <span
                      className={`px-3 py-1 rounded-full text-xs font-semibold ${
                        org.is_active
                          ? 'bg-green-500/20 text-green-400'
                          : 'bg-gray-500/20 text-gray-400'
                      }`}
                    >
                      {org.is_active ? 'Active' : 'Inactive'}
                    </span>
                  )}
                </div>
              </div>

              {/* Organization Details */}
              <div className="space-y-2 mb-4 text-sm">
                {org.email && (
                  <div className="flex items-center gap-2 text-white/60">
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <span>{org.email}</span>
                  </div>
                )}
                
                {org.phone && (
                  <div className="flex items-center gap-2 text-white/60">
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                    <span>{org.phone}</span>
                  </div>
                )}

                {/* Stats */}
                <div className="flex gap-4 mt-4 pt-4 border-t border-white/10">
                  <div className="text-center">
                    <div className="text-2xl font-bold text-premium-primary">{org.users_count || 0}</div>
                    <div className="text-xs text-white/40">Users</div>
                  </div>
                  <div className="text-center">
                    <div className="text-2xl font-bold text-premium-primary">{org.sessions_count || 0}</div>
                    <div className="text-xs text-white/40">Sessions</div>
                  </div>
                </div>
              </div>

              {/* Actions */}
              <div className="flex gap-2">
                {org.deleted_at ? (
                  <button
                    onClick={() => handleRestore(org.id)}
                    className="flex-1 bg-green-500/20 text-green-400 px-4 py-2 rounded-xl font-semibold hover:bg-green-500/30 transition-all"
                  >
                    Restore
                  </button>
                ) : (
                  <>
                    <button
                      onClick={() => handleEdit(org)}
                      className="flex-1 bg-white/5 text-white px-4 py-2 rounded-xl font-semibold hover:bg-white/10 transition-all"
                    >
                      Edit
                    </button>
                    <button
                      onClick={() => handleToggleStatus(org.id)}
                      className="flex-1 bg-premium-primary/20 text-premium-primary px-4 py-2 rounded-xl font-semibold hover:bg-premium-primary/30 transition-all"
                    >
                      {org.is_active ? 'Deactivate' : 'Activate'}
                    </button>
                    <button
                      onClick={() => handleDelete(org.id)}
                      className="bg-red-500/20 text-red-400 px-4 py-2 rounded-xl font-semibold hover:bg-red-500/30 transition-all"
                    >
                      Delete
                    </button>
                  </>
                )}
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Modal */}
      {showModal && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 sm:p-6 z-50">
          <div className="glass rounded-2xl sm:rounded-3xl p-6 sm:p-8 max-w-2xl w-full max-h-[90vh] overflow-y-auto border border-white/20">
            <h2 className="text-xl sm:text-2xl font-bold text-white mb-4 sm:mb-6">
              {editingOrg ? 'Edit Organization' : 'Create Organization'}
            </h2>

            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label className="block text-white/60 text-sm mb-2">Organization Name *</label>
                  <input
                    type="text"
                    required
                    value={formData.name}
                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                    className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-premium-primary/50"
                    placeholder="e.g., Dhaka University"
                  />
                </div>

                <div>
                  <label className="block text-white/60 text-sm mb-2">Organization Code *</label>
                  <input
                    type="text"
                    required
                    value={formData.code}
                    onChange={(e) => setFormData({ ...formData, code: e.target.value.toUpperCase() })}
                    className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-premium-primary/50"
                    placeholder="e.g., DU"
                  />
                </div>

                <div>
                  <label className="block text-white/60 text-sm mb-2">Email</label>
                  <input
                    type="email"
                    value={formData.email}
                    onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                    className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-premium-primary/50"
                    placeholder="contact@organization.com"
                  />
                </div>

                <div>
                  <label className="block text-white/60 text-sm mb-2">Phone</label>
                  <input
                    type="tel"
                    value={formData.phone}
                    onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                    className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-premium-primary/50"
                    placeholder="+880-XXX-XXXX"
                  />
                </div>
              </div>

              <div>
                <label className="block text-white/60 text-sm mb-2">Address</label>
                <textarea
                  value={formData.address}
                  onChange={(e) => setFormData({ ...formData, address: e.target.value })}
                  rows="2"
                  className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-premium-primary/50"
                  placeholder="Full address"
                />
              </div>

              {/* Settings */}
              <div className="border-t border-white/10 pt-4 mt-4">
                <h3 className="text-white font-semibold mb-4">Settings</h3>
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-white/60 text-sm mb-2">Late Threshold (minutes)</label>
                    <input
                      type="number"
                      min="0"
                      max="60"
                      value={formData.settings.late_threshold_minutes}
                      onChange={(e) => setFormData({
                        ...formData,
                        settings: { ...formData.settings, late_threshold_minutes: parseInt(e.target.value) }
                      })}
                      className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-premium-primary/50"
                    />
                  </div>

                  <div>
                    <label className="block text-white/60 text-sm mb-2">GPS Radius (meters)</label>
                    <input
                      type="number"
                      min="10"
                      max="1000"
                      value={formData.settings.gps_radius_meters}
                      onChange={(e) => setFormData({
                        ...formData,
                        settings: { ...formData.settings, gps_radius_meters: parseInt(e.target.value) }
                      })}
                      className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-premium-primary/50"
                    />
                  </div>

                  <div>
                    <label className="block text-white/60 text-sm mb-2">Face Confidence Threshold</label>
                    <input
                      type="number"
                      min="0"
                      max="1"
                      step="0.05"
                      value={formData.settings.face_confidence_threshold}
                      onChange={(e) => setFormData({
                        ...formData,
                        settings: { ...formData.settings, face_confidence_threshold: parseFloat(e.target.value) }
                      })}
                      className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-premium-primary/50"
                    />
                  </div>
                </div>
              </div>

              {/* Active Status */}
              <div className="flex items-center gap-3">
                <input
                  type="checkbox"
                  id="is_active"
                  checked={formData.is_active}
                  onChange={(e) => setFormData({ ...formData, is_active: e.target.checked })}
                  className="w-5 h-5 rounded bg-white/5 border-white/10 text-premium-primary focus:ring-premium-primary/50"
                />
                <label htmlFor="is_active" className="text-white">Active Organization</label>
              </div>

              {/* Actions */}
              <div className="flex gap-3 pt-4">
                <button
                  type="button"
                  onClick={() => {
                    setShowModal(false)
                    resetForm()
                  }}
                  className="flex-1 bg-white/5 text-white px-6 py-3 rounded-xl font-semibold hover:bg-white/10 transition-all"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="flex-1 bg-gradient-premium text-white px-6 py-3 rounded-xl font-semibold hover:scale-105 transition-transform"
                >
                  {editingOrg ? 'Update' : 'Create'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  )
}

export default Organizations
