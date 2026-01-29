import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom'
import { ToastContainer } from 'react-toastify'
import 'react-toastify/dist/ReactToastify.css'

// Pages
import Login from './pages/Login'
import Register from './pages/Register'
import Dashboard from './pages/Dashboard'
import Sessions from './pages/Sessions'
import Attendance from './pages/Attendance'
import Profile from './pages/Profile'
import Admin from './pages/Admin'
import Organizations from './pages/Organizations'
import PaymentCallback from './pages/PaymentCallback'
import PaymentDemo from './pages/PaymentDemo'
import OrganizationDashboard from './pages/OrganizationDashboard'
import OrgUsers from './pages/OrgUsers'

// Components
import Layout from './components/common/Layout'

// Middleware
import ProtectedRoute from './middleware/ProtectedRoute'
import RoleRoute from './middleware/RoleRoute'

function App() {
  return (
    <Router>
      <div className="min-h-screen bg-dark overflow-hidden relative">
        {/* Animated Background Shapes */}
        <div className="bg-shape w-64 h-64 sm:w-80 sm:h-80 lg:w-96 lg:h-96 bg-premium-primary top-[-10%] left-[-5%]"></div>
        <div className="bg-shape w-80 h-80 sm:w-96 sm:h-96 lg:w-[500px] lg:h-[500px] bg-premium-secondary bottom-[-10%] right-[-5%] animation-delay-2000"></div>
        <div className="bg-shape w-48 h-48 sm:w-56 sm:h-56 lg:w-64 lg:h-64 bg-premium-accent top-[20%] right-[10%] opacity-20"></div>

        <Routes>
          {/* Public Routes */}
          <Route path="/login" element={<Login />} />
          <Route path="/register" element={<Register />} />
          
          {/* Protected Routes */}
          <Route
            path="/dashboard"
            element={
              <ProtectedRoute>
                <Layout>
                  <Dashboard />
                </Layout>
              </ProtectedRoute>
            }
          />
          <Route
             path="/org-dashboard"
             element={
               <RoleRoute allowedRoles={['organization_admin']}>
                 <Layout>
                   <OrganizationDashboard />
                 </Layout>
               </RoleRoute>
             }
           />
          <Route
             path="/org-users"
             element={
               <RoleRoute allowedRoles={['organization_admin']}>
                 <Layout>
                   <OrgUsers />
                 </Layout>
               </RoleRoute>
             }
           />
          <Route
            path="/sessions/*"
            element={
              <ProtectedRoute>
                <Layout>
                  <Sessions />
                </Layout>
              </ProtectedRoute>
            }
          />
          <Route
            path="/attendance"
            element={
              <ProtectedRoute>
                <Layout>
                  <Attendance />
                </Layout>
              </ProtectedRoute>
            }
          />
          <Route
            path="/profile"
            element={
              <ProtectedRoute>
                <Layout>
                  <Profile />
                </Layout>
              </ProtectedRoute>
            }
          />
          
          <Route
            path="/payment/callback/:gateway"
            element={
              <ProtectedRoute>
                <Layout>
                  <PaymentCallback />
                </Layout>
              </ProtectedRoute>
            }
          />
          
          <Route
            path="/payment/demo"
            element={
              <ProtectedRoute>
                <PaymentDemo />
              </ProtectedRoute>
            }
          />

          {/* Admin Routes */}
          <Route
            path="/admin/*"
            element={
              <RoleRoute allowedRoles={['admin']}>
                <Layout>
                  <Admin />
                </Layout>
              </RoleRoute>
            }
          />
          
          <Route
            path="/organizations"
            element={
              <RoleRoute allowedRoles={['admin']}>
                <Layout>
                  <Organizations />
                </Layout>
              </RoleRoute>
            }
          />
          
          {/* Default Route */}
          <Route path="/" element={<Navigate to="/dashboard" replace />} />
        </Routes>
        
        <ToastContainer
          position="top-right"
          autoClose={3000}
          hideProgressBar={false}
          newestOnTop={false}
          closeOnClick
          rtl={false}
          pauseOnFocusLoss
          draggable
          pauseOnHover
        />
      </div>
    </Router>
  )
}

export default App

