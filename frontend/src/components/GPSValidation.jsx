import { useState, useEffect } from 'react'
import { toast } from 'react-toastify'

const GPSValidation = ({ sessionLocation, onValidate, onError }) => {
  const [loading, setLoading] = useState(false)
  const [location, setLocation] = useState(null)
  const [distance, setDistance] = useState(null)
  const [isValid, setIsValid] = useState(null)

  const calculateDistance = (lat1, lon1, lat2, lon2) => {
    // Haversine formula to calculate distance in meters
    const R = 6371e3 // Earth's radius in meters
    const φ1 = (lat1 * Math.PI) / 180
    const φ2 = (lat2 * Math.PI) / 180
    const Δφ = ((lat2 - lat1) * Math.PI) / 180
    const Δλ = ((lon2 - lon1) * Math.PI) / 180

    const a =
      Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
      Math.cos(φ1) * Math.cos(φ2) * Math.sin(Δλ / 2) * Math.sin(Δλ / 2)
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))

    return R * c // Distance in meters
  }

  const getCurrentLocation = () => {
    setLoading(true)

    if (!navigator.geolocation) {
      toast.error('Geolocation is not supported by your browser')
      if (onError) onError(new Error('Geolocation not supported'))
      setLoading(false)
      return
    }

    navigator.geolocation.getCurrentPosition(
      (position) => {
        const userLocation = {
          latitude: position.coords.latitude,
          longitude: position.coords.longitude,
          accuracy: position.coords.accuracy
        }
        setLocation(userLocation)

        if (sessionLocation) {
          const dist = calculateDistance(
            userLocation.latitude,
            userLocation.longitude,
            sessionLocation.latitude,
            sessionLocation.longitude
          )
          setDistance(Math.round(dist))

          const radiusMeters = sessionLocation.radius || 100 // Default 100m
          const valid = dist <= radiusMeters
          setIsValid(valid)

          if (valid) {
            toast.success(`Location verified! (${Math.round(dist)}m away)`)
            onValidate({
              latitude: userLocation.latitude,
              longitude: userLocation.longitude,
              distance: Math.round(dist),
              valid: true
            })
          } else {
            toast.error(`Too far from session location! (${Math.round(dist)}m away, max ${radiusMeters}m)`)
            onValidate({
              latitude: userLocation.latitude,
              longitude: userLocation.longitude,
              distance: Math.round(dist),
              valid: false
            })
          }
        }

        setLoading(false)
      },
      (error) => {
        console.error('Geolocation error:', error)
        let errorMessage = 'Failed to get location'
        
        switch (error.code) {
          case error.PERMISSION_DENIED:
            errorMessage = 'Location permission denied'
            break
          case error.POSITION_UNAVAILABLE:
            errorMessage = 'Location information unavailable'
            break
          case error.TIMEOUT:
            errorMessage = 'Location request timed out'
            break
        }
        
        toast.error(errorMessage)
        if (onError) onError(error)
        setLoading(false)
      },
      {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0
      }
    )
  }

  return (
    <div className="space-y-4">
      <button
        onClick={getCurrentLocation}
        disabled={loading}
        className="w-full bg-gradient-premium text-white font-bold py-4 rounded-2xl shadow-lg shadow-premium-primary/20 hover:scale-[1.02] active:scale-[0.98] transition-all disabled:opacity-50 disabled:hover:scale-100"
      >
        {loading ? (
          <span className="flex items-center justify-center gap-2">
            <svg className="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
              <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Getting Location...
          </span>
        ) : '📍 Verify GPS Location'}
      </button>

      {location && (
        <div className="bg-white/5 border border-white/10 rounded-2xl p-4 space-y-2">
          <div className="flex items-center justify-between">
            <span className="text-white/60 text-sm">Your Location</span>
            <span className="text-white text-sm font-mono">
              {location.latitude.toFixed(6)}, {location.longitude.toFixed(6)}
            </span>
          </div>
          
          {distance !== null && (
            <>
              <div className="flex items-center justify-between">
                <span className="text-white/60 text-sm">Distance</span>
                <span className="text-white text-sm font-bold">
                  {distance}m
                </span>
              </div>
              
              <div className="flex items-center justify-between">
                <span className="text-white/60 text-sm">Status</span>
                <span className={`text-sm font-bold ${isValid ? 'text-green-400' : 'text-red-400'}`}>
                  {isValid ? '✓ Within Range' : '✗ Out of Range'}
                </span>
              </div>
            </>
          )}
          
          <div className="flex items-center justify-between">
            <span className="text-white/60 text-sm">Accuracy</span>
            <span className="text-white/40 text-xs">
              ±{Math.round(location.accuracy)}m
            </span>
          </div>
        </div>
      )}

      <p className="text-white/40 text-xs text-center">
        Allow location access when prompted
      </p>
    </div>
  )
}

export default GPSValidation
