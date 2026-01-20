import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { sessionAPI } from '../../services/api/session';
import { toast } from 'react-toastify';

const SessionCreate = () => {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(false);
  const [formData, setFormData] = useState({
    title: '',
    description: '',
    start_time: '',
    end_time: '',
    location_lat: '',
    location_lng: '',
    location_name: '',
    radius_meters: 100,
    session_type: 'open',
    requires_payment: false,
    payment_amount: '',
    max_attendees: ''
  });

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      const payload = {
        ...formData,
        location_lat: parseFloat(formData.location_lat),
        location_lng: parseFloat(formData.location_lng),
        radius_meters: parseInt(formData.radius_meters),
        requires_payment: formData.requires_payment,
        payment_amount: formData.requires_payment ? parseFloat(formData.payment_amount) : null,
        max_attendees: formData.max_attendees ? parseInt(formData.max_attendees) : null
      };

      await sessionAPI.create(payload);
      toast.success('Session created successfully!');
      navigate('/sessions');
    } catch (error) {
      toast.error(error.message || 'Failed to create session');
    } finally {
      setLoading(false);
    }
  };

  const handleGetCurrentLocation = () => {
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        (position) => {
          setFormData({
            ...formData,
            location_lat: position.coords.latitude.toFixed(8),
            location_lng: position.coords.longitude.toFixed(8)
          });
          toast.success('Location captured');
        },
        (error) => {
          toast.error('Failed to get location');
        }
      );
    } else {
      toast.error('Geolocation not supported');
    }
  };

  return (
    <div className="max-w-4xl mx-auto p-6">
      <div className="bg-white rounded-lg shadow-lg p-6">
        <div className="flex justify-between items-center mb-6">
          <h1 className="text-3xl font-bold">Create Session</h1>
          <button
            onClick={() => navigate('/sessions')}
            className="text-gray-600 hover:text-gray-800"
          >
            ← Back
          </button>
        </div>

        <form onSubmit={handleSubmit} className="space-y-6">
          {/* Basic Information */}
          <div>
            <h2 className="text-xl font-semibold mb-4">Basic Information</h2>
            <div className="space-y-4">
              <div>
                <label className="block text-sm font-medium mb-1">
                  Title <span className="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  value={formData.title}
                  onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                  className="w-full border rounded px-3 py-2"
                  required
                />
              </div>

              <div>
                <label className="block text-sm font-medium mb-1">Description</label>
                <textarea
                  value={formData.description}
                  onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                  className="w-full border rounded px-3 py-2"
                  rows="4"
                />
              </div>
            </div>
          </div>

          {/* Date & Time */}
          <div>
            <h2 className="text-xl font-semibold mb-4">Date & Time</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium mb-1">
                  Start Time <span className="text-red-500">*</span>
                </label>
                <input
                  type="datetime-local"
                  value={formData.start_time}
                  onChange={(e) => setFormData({ ...formData, start_time: e.target.value })}
                  className="w-full border rounded px-3 py-2"
                  required
                />
              </div>

              <div>
                <label className="block text-sm font-medium mb-1">
                  End Time <span className="text-red-500">*</span>
                </label>
                <input
                  type="datetime-local"
                  value={formData.end_time}
                  onChange={(e) => setFormData({ ...formData, end_time: e.target.value })}
                  className="w-full border rounded px-3 py-2"
                  required
                />
              </div>
            </div>
          </div>

          {/* Location */}
          <div>
            <h2 className="text-xl font-semibold mb-4">Location</h2>
            <div className="space-y-4">
              <div>
                <label className="block text-sm font-medium mb-1">Location Name</label>
                <input
                  type="text"
                  value={formData.location_name}
                  onChange={(e) => setFormData({ ...formData, location_name: e.target.value })}
                  className="w-full border rounded px-3 py-2"
                  placeholder="e.g., Dhaka University"
                />
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium mb-1">
                    Latitude <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="number"
                    step="any"
                    value={formData.location_lat}
                    onChange={(e) => setFormData({ ...formData, location_lat: e.target.value })}
                    className="w-full border rounded px-3 py-2"
                    required
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium mb-1">
                    Longitude <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="number"
                    step="any"
                    value={formData.location_lng}
                    onChange={(e) => setFormData({ ...formData, location_lng: e.target.value })}
                    className="w-full border rounded px-3 py-2"
                    required
                  />
                </div>
              </div>

              <button
                type="button"
                onClick={handleGetCurrentLocation}
                className="text-sm text-blue-600 hover:text-blue-700"
              >
                📍 Use Current Location
              </button>

              <div>
                <label className="block text-sm font-medium mb-1">
                  Radius (meters)
                </label>
                <input
                  type="number"
                  value={formData.radius_meters}
                  onChange={(e) => setFormData({ ...formData, radius_meters: e.target.value })}
                  className="w-full border rounded px-3 py-2"
                  min="10"
                  max="10000"
                />
              </div>
            </div>
          </div>

          {/* Session Settings */}
          <div>
            <h2 className="text-xl font-semibold mb-4">Session Settings</h2>
            <div className="space-y-4">
              <div>
                <label className="block text-sm font-medium mb-1">
                  Session Type <span className="text-red-500">*</span>
                </label>
                <select
                  value={formData.session_type}
                  onChange={(e) => setFormData({ ...formData, session_type: e.target.value })}
                  className="w-full border rounded px-3 py-2"
                  required
                >
                  <option value="open">Open</option>
                  <option value="pre_registered">Pre-registered</option>
                  <option value="admin_approved">Admin Approved</option>
                </select>
              </div>

              <div>
                <label className="block text-sm font-medium mb-1">
                  Max Attendees
                </label>
                <input
                  type="number"
                  value={formData.max_attendees}
                  onChange={(e) => setFormData({ ...formData, max_attendees: e.target.value })}
                  className="w-full border rounded px-3 py-2"
                  min="1"
                />
              </div>

              <div className="flex items-center">
                <input
                  type="checkbox"
                  id="requires_payment"
                  checked={formData.requires_payment}
                  onChange={(e) => setFormData({ ...formData, requires_payment: e.target.checked })}
                  className="mr-2"
                />
                <label htmlFor="requires_payment" className="text-sm font-medium">
                  Requires Payment
                </label>
              </div>

              {formData.requires_payment && (
                <div>
                  <label className="block text-sm font-medium mb-1">
                    Payment Amount <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="number"
                    step="0.01"
                    value={formData.payment_amount}
                    onChange={(e) => setFormData({ ...formData, payment_amount: e.target.value })}
                    className="w-full border rounded px-3 py-2"
                    min="0"
                    required={formData.requires_payment}
                  />
                </div>
              )}
            </div>
          </div>

          {/* Submit Buttons */}
          <div className="flex gap-4">
            <button
              type="submit"
              disabled={loading}
              className="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 disabled:opacity-50"
            >
              {loading ? 'Creating...' : 'Create Session'}
            </button>
            <button
              type="button"
              onClick={() => navigate('/sessions')}
              className="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600"
            >
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default SessionCreate;

