import { Navigate } from 'react-router-dom'
import { useAuth } from '../contexts/AuthContext'

// Protège une route : redirige vers /login si non connecté, vers / si le rôle ne correspond pas
export default function ProtectedRoute({ children, role }) {
  const { user } = useAuth()

  if (!user) return <Navigate to="/login" replace />
  if (role && user.role !== role) return <Navigate to="/" replace />

  return children
}
