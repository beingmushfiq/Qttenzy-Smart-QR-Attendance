import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { sessionAPI } from '../../services/api/session';
import { toast } from 'react-toastify';
import Layout from '../common/Layout';

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

  const inputClasses = "w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-3 text-white placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-premium-primary/50 focus:border-premium-primary/50 transition-all outline-none";
  const labelClasses = "block text-sm font-medium mb-2 text-white/80";

  return (
    <Layout>
      <div className="max-w-4xl mx-auto">
        <div className="glass rounded-[2rem] p-8 border border-white/10 relative overflow-hidden">
             {/* Decorative inner glow */}
             <div className="absolute -top-24 -right-24 w-48 h-48 bg-premium-primary/20 blur-[60px]"></div>

          <div className="flex justify-between items-center mb-8 relative z-10">
            <div>
                <h1 className="text-3xl font-extrabold tracking-tight">
                    <span className="text-white">Create </span>
                    <span className="text-premium-primary">Session</span>
                </h1>
                <p className="text-white/60 mt-2">Set up a new attendance session</p>
            </div>
            
            <button
              onClick={() => navigate('/sessions')}
              className="text-white/60 hover:text-white px-4 py-2 rounded-xl hover:bg-white/10 transition-all"
            >
              ← Back to Sessions
            </button>
          </div>

          <form onSubmit={handleSubmit} className="space-y-8 relative z-10">
            {/* Basic Information */}
            <div className="space-y-5">
              <h2 className="text-xl font-bold text-white border-b border-white/10 pb-2">Basic Information</h2>
              <div className="space-y-5">
                <div>
                  <label className={labelClasses}>
                    Title <span className="text-premium-primary">*</span>
                  </label>
                  <input
                    type="text"
                    value={formData.title}
                    onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                    className={inputClasses}
                    placeholder="e.g. Introduction to Computer Science"
                    required
                  />
                </div>

                <div>
                  <label className={labelClasses}>Description</label>
                  <textarea
                    value={formData.description}
                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                    className={inputClasses}
                    rows="4"
                    placeholder="Session details..."
                  />
                </div>
              </div>
            </div>

            {/* Date & Time */}
            <div className="space-y-5">
              <h2 className="text-xl font-bold text-white border-b border-white/10 pb-2">Date & Time</h2>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                  <label className={labelClasses}>
                    Start Time <span className="text-premium-primary">*</span>
                  </label>
                  <input
                    type="datetime-local"
                    value={formData.start_time}
                    onChange={(e) => setFormData({ ...formData, start_time: e.target.value })}
                    className={inputClasses}
                    required
                  />
                </div>

                <div>
                  <label className={labelClasses}>
                    End Time <span className="text-premium-primary">*</span>
                  </label>
                  <input
                    type="datetime-local"
                    value={formData.end_time}
                    onChange={(e) => setFormData({ ...formData, end_time: e.target.value })}
                    className={inputClasses}
                    required
                  />
                </div>
              </div>
            </div>

            {/* Location */}
            <div className="space-y-5">
              <h2 className="text-xl font-bold text-white border-b border-white/10 pb-2">Location</h2>
              <div className="space-y-5">
                <div>
                  <label className={labelClasses}>Location Name</label>
                  <input
                    type="text"
                    value={formData.location_name}
                    onChange={(e) => setFormData({ ...formData, location_name: e.target.value })}
                    className={inputClasses}
                    placeholder="e.g., Dhaka University"
                  />
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                  <div>
                    <label className={labelClasses}>
                      Latitude <span className="text-premium-primary">*</span>
                    </label>
                    <input
                      type="number"
                      step="any"
                      value={formData.location_lat}
                      onChange={(e) => setFormData({ ...formData, location_lat: e.target.value })}
                      className={inputClasses}
                      required
                    />
                  </div>

                  <div>
                    <label className={labelClasses}>
                      Longitude <span className="text-premium-primary">*</span>
                    </label>
                    <input
                      type="number"
                      step="any"
                      value={formData.location_lng}
                      onChange={(e) => setFormData({ ...formData, location_lng: e.target.value })}
                      className={inputClasses}
                      required
                    />
                  </div>
                </div>

                <button
                  type="button"
                  onClick={handleGetCurrentLocation}
                  className="text-sm text-premium-primary hover:text-premium-primary/80 font-semibold flex items-center gap-2"
                >
                  <span className="bg-premium-primary/20 p-2 rounded-lg">📍</span> 
                  Get Current Location
                </button>

                <div>
                  <label className={labelClasses}>
                    Radius (meters)
                  </label>
                  <input
                    type="number"
                    value={formData.radius_meters}
                    onChange={(e) => setFormData({ ...formData, radius_meters: e.target.value })}
                    className={inputClasses}
                    min="10"
                    max="10000"
                  />
                </div>
              </div>
            </div>

            {/* Session Settings */}
            <div className="space-y-5">
              <h2 className="text-xl font-bold text-white border-b border-white/10 pb-2">Start Settings</h2>
              <div className="space-y-5">
                <div>
                  <label className={labelClasses}>
                    Session Type <span className="text-premium-primary">*</span>
                  </label>
                  <select
                    value={formData.session_type}
                    onChange={(e) => setFormData({ ...formData, session_type: e.target.value })}
                    className={inputClasses}
                    required
                  >
                    <option value="open" className="bg-dark text-white">Open</option>
                    <option value="pre_registered" className="bg-dark text-white">Pre-registered</option>
                    <option value="admin_approved" className="bg-dark text-white">Admin Approved</option>
                  </select>
                </div>

                <div>
                  <label className={labelClasses}>
                    Max Attendees
                  </label>
                  <input
                    type="number"
                    value={formData.max_attendees}
                    onChange={(e) => setFormData({ ...formData, max_attendees: e.target.value })}
                    className={inputClasses}
                    min="1"
                    placeholder="Unlimited"
                  />
                </div>

                <div className="flex items-center gap-3 p-4 bg-white/5 rounded-2xl border border-white/10">
                  <input
                    type="checkbox"
                    id="requires_payment"
                    checked={formData.requires_payment}
                    onChange={(e) => setFormData({ ...formData, requires_payment: e.target.checked })}
                    className="w-5 h-5 rounded border-gray-300 text-premium-primary focus:ring-premium-primary"
                  />
                  <label htmlFor="requires_payment" className="text-base font-medium text-white cursor-pointer select-none">
                    Requires Payment to Join
                  </label>
                </div>

                {formData.requires_payment && (
                  <div className="animate-in fade-in slide-in-from-top-2">
                    <label className={labelClasses}>
                      Payment Amount <span className="text-premium-primary">*</span>
                    </label>
                    <div className="relative">
                        <span className="absolute left-4 top-3.5 text-white/50">$</span>
                        <input
                            type="number"
                            step="0.01"
                            value={formData.payment_amount}
                            onChange={(e) => setFormData({ ...formData, payment_amount: e.target.value })}
                            className={`${inputClasses} pl-8`}
                            min="0"
                            required={formData.requires_payment}
                        />
                    </div>
                  </div>
                )}
              </div>
            </div>

            {/* Submit Buttons */}
            <div className="flex gap-4 pt-4">
              <button
                type="submit"
                disabled={loading}
                className="flex-1 bg-gradient-premium text-white font-bold py-4 rounded-xl shadow-lg shadow-premium-primary/20 hover:scale-[1.02] active:scale-[0.98] transition-all disabled:opacity-50 disabled:hover:scale-100"
              >
                {loading ? 'Creating Session...' : 'Create Session'}
              </button>
              <button
                type="button"
                onClick={() => navigate('/sessions')}
                className="px-8 py-4 rounded-xl bg-white/5 text-white border border-white/10 hover:bg-white/10 transition-all font-semibold"
              >
                Cancel
              </button>
            </div>
          </form>
        </div>
      </div>
    </Layout>
  );
};

export default SessionCreate;


