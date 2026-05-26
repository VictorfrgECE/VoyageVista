import { Routes, Route, Navigate } from 'react-router-dom'
import { AuthProvider } from './contexts/AuthContext'
import Navbar from './components/Navbar'
import ProtectedRoute from './components/ProtectedRoute'
import Home from './pages/Home'
import Destinations from './pages/Destinations'
import Login from './pages/Login'
import Register from './pages/Register'
import Admin from './pages/Admin'
import Dashboard from './pages/Dashboard'

export default function App() {
  return (
    // AuthProvider enveloppe l'app pour que tous les composants accèdent au contexte auth
    <AuthProvider>
      <Navbar />
      <main>
        <Routes>
          {/* Pages publiques accessibles sans connexion */}
          <Route path="/" element={<Home />} />
          <Route path="/destinations" element={<Destinations />} />
          <Route path="/login" element={<Login />} />
          <Route path="/register" element={<Register />} />

          {/* Pages protégées par rôle — redirige vers /login si non connecté */}
          <Route path="/home" element={
            <ProtectedRoute role="etudiant"><Home /></ProtectedRoute>
          } />
          <Route path="/admin" element={
            <ProtectedRoute role="admin"><Admin /></ProtectedRoute>
          } />
          <Route path="/dashboard" element={
            <ProtectedRoute role="prestataire"><Dashboard /></ProtectedRoute>
          } />

          {/* Toute route inconnue redirige vers l'accueil */}
          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </main>
    </AuthProvider>
  )
}
