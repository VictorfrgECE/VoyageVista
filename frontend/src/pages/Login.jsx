import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import api from '../services/api'
import { useAuth } from '../contexts/AuthContext'
import styles from './Auth.module.css'

// Correspondance rôle → page d'accueil après connexion
const ROLE_REDIRECTS = {
  admin:       '/admin',
  prestataire: '/dashboard',
  etudiant:    '/home',
}

export default function Login() {
  const navigate = useNavigate()
  const { login } = useAuth()
  const [form, setForm] = useState({ email: '', password: '' })
  const [error, setError] = useState(null)
  const [loading, setLoading] = useState(false)

  const handleChange = e => setForm(f => ({ ...f, [e.target.name]: e.target.value }))

  const handleSubmit = async e => {
    e.preventDefault()
    setLoading(true)
    setError(null)
    try {
      const res = await api.post('/auth/login', form)
      // Enregistre token + infos utilisateur dans le contexte global et localStorage
      login(res.data.token, res.data.user)
      // Redirige vers la page correspondant au rôle
      navigate(ROLE_REDIRECTS[res.data.user.role] ?? '/')
    } catch (err) {
      setError(err.response?.data?.error ?? 'Email ou mot de passe incorrect.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className={styles.wrapper}>
      <form className={styles.form} onSubmit={handleSubmit}>
        <h1>Connexion</h1>
        {error && <p className={styles.error}>{error}</p>}
        <label>
          Email
          <input
            type="email"
            name="email"
            value={form.email}
            onChange={handleChange}
            required
          />
        </label>
        <label>
          Mot de passe
          <input
            type="password"
            name="password"
            value={form.password}
            onChange={handleChange}
            required
          />
        </label>
        <button type="submit" className="btn btn-primary" disabled={loading}>
          {loading ? 'Connexion...' : 'Se connecter'}
        </button>
        <p className={styles.switch}>
          Pas encore de compte ? <Link to="/register">S'inscrire</Link>
        </p>
      </form>
    </div>
  )
}
