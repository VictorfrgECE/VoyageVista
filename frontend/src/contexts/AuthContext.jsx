import { createContext, useContext, useState, useCallback } from 'react'

const AuthContext = createContext(null)

// Fournit l'état d'authentification (user, login, logout) à toute l'application
export function AuthProvider({ children }) {
  // Restaure l'utilisateur depuis localStorage au rechargement de page
  const [user, setUser] = useState(() => {
    try {
      const stored = localStorage.getItem('user')
      return stored ? JSON.parse(stored) : null
    } catch {
      return null
    }
  })

  // Stocke token + données utilisateur et met à jour le state global
  const login = useCallback((token, userData) => {
    localStorage.setItem('token', token)
    localStorage.setItem('user', JSON.stringify(userData))
    setUser(userData)
  }, [])

  // Purge le stockage local et réinitialise le state
  const logout = useCallback(() => {
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    setUser(null)
  }, [])

  return (
    <AuthContext.Provider value={{ user, login, logout }}>
      {children}
    </AuthContext.Provider>
  )
}

// Hook utilitaire : accès au contexte depuis n'importe quel composant enfant
export function useAuth() {
  const ctx = useContext(AuthContext)
  if (!ctx) throw new Error('useAuth doit être utilisé dans AuthProvider')
  return ctx
}
