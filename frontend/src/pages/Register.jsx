import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import api from '../services/api'
import { useAuth } from '../contexts/AuthContext'
import styles from './Auth.module.css'

const ROLE_REDIRECTS = {
  admin:       '/admin',
  prestataire: '/dashboard',
  etudiant:    '/home',
}

export default function Register() {
  const navigate = useNavigate()
  const { login } = useAuth()
  const [form, setForm] = useState({ name: '', email: '', password: '', role: 'etudiant' })
  const [error, setError] = useState(null)
  const [loading, setLoading] = useState(false)

  const handleChange = e => setForm(f => ({ ...f, [e.target.name]: e.target.value }))

  // Validation côté client (première ligne de défense avant l'envoi au backend)
  const validate = () => {
    if (form.name.trim().length < 2) return 'Le nom doit contenir au moins 2 caractères.'
    if (!form.email.includes('@')) return 'Adresse email invalide.'
    if (form.password.length < 8) return 'Le mot de passe doit contenir au moins 8 caractères.'
    return null
  }

  const handleSubmit = async e => {
    e.preventDefault()
    const validationError = validate()
    if (validationError) { setError(validationError); return }
    setLoading(true)
    setError(null)
    try {
      const res = await api.post('/auth/register', form)
      // Connexion automatique après inscription
      login(res.data.token, res.data.user)
      navigate(ROLE_REDIRECTS[res.data.user.role] ?? '/')
    } catch (err) {
      setError(err.response?.data?.error ?? "Erreur lors de l'inscription.")
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className={styles.wrapper}>
      <form className={styles.form} onSubmit={handleSubmit}>
        <h1>Créer un compte</h1>
        {error && <p className={styles.error}>{error}</p>}
        <label>
          Nom complet
          <input
            type="text"
            name="name"
            value={form.name}
            onChange={handleChange}
            required
            minLength={2}
          />
        </label>
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
            minLength={8}
          />
        </label>
        <label>
          Je suis...
          <select name="role" value={form.role} onChange={handleChange}>
            <option value="etudiant">Étudiant</option>
            <option value="prestataire">Prestataire (université, logement, activités)</option>
          </select>
        </label>
        <button type="submit" className="btn btn-primary" disabled={loading}>
          {loading ? 'Création...' : "S'inscrire"}
        </button>
        <p className={styles.switch}>
          Déjà un compte ? <Link to="/login">Se connecter</Link>
        </p>
      </form>
    </div>
  )
}
