import { Navigate } from 'react-router-dom'
import { useAuthStore } from '../store/authStore'

const RoleRoute = ({ children, allowedRoles = [] }) => {
  const { user, isAuthenticated } = useAuthStore()
  
  if (!isAuthenticated) {
    return <Navigate to="/login" />
  }
  
  if (allowedRoles.length > 0 && !allowedRoles.includes(user?.role)) {
    return <Navigate to="/dashboard" />
  }
  
  return children
}

export default RoleRoute

