import { Link, NavLink, useNavigate } from 'react-router-dom'
import { useAuth } from '../contexts/AuthContext'
import api from '../services/api'
import styles from './Navbar.module.css'

export default function Navbar() {
  const { user, logout } = useAuth()
  const navigate = useNavigate()

  const handleLogout = async () => {
    // Notifie le backend pour détruire la session PHP côté serveur
    try { await api.post('/auth/logout') } catch { /* ignore si hors ligne */ }
    // Purge le state et localStorage côté client
    logout()
    navigate('/login')
  }

  return (
    <nav className={styles.navbar}>
      <div className={styles.brand}>
        <Link to="/">VoyageVista</Link>
      </div>
      <ul className={styles.links}>
        <li><NavLink to="/" end>Accueil</NavLink></li>
        <li><NavLink to="/destinations">Destinations</NavLink></li>
        <li><NavLink to="/universities">Universités</NavLink></li>
      </ul>
      <div className={styles.auth}>
        {user ? (
          <>
            <span className={styles.username}>{user.name}</span>
            <button className="btn" onClick={handleLogout}>Déconnexion</button>
          </>
        ) : (
          <>
            <Link to="/login" className="btn">Connexion</Link>
            <Link to="/register" className="btn btn-primary">S'inscrire</Link>
          </>
        )}
      </div>
    </nav>
  )
}
