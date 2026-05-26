import { useAuth } from '../contexts/AuthContext'
import styles from './Dashboard.module.css'

// Page réservée aux administrateurs : modération globale et gestion des utilisateurs
export default function Admin() {
  const { user } = useAuth()

  return (
    <div className={styles.page}>
      <div className={styles.header}>
        <h1>Panneau d'administration</h1>
        <span className={styles.badge}>Admin</span>
      </div>
      <p className={styles.welcome}>
        Bienvenue, <strong>{user?.name}</strong>
      </p>
      <div className={styles.grid}>
        <div className={styles.card}>
          <h2>Utilisateurs</h2>
          <p>Gérer les comptes, les rôles et les accès.</p>
        </div>
        <div className={styles.card}>
          <h2>Destinations</h2>
          <p>Modérer le catalogue de destinations.</p>
        </div>
        <div className={styles.card}>
          <h2>Prestataires</h2>
          <p>Valider et superviser les prestataires.</p>
        </div>
        <div className={styles.card}>
          <h2>Statistiques</h2>
          <p>Voir les métriques de la plateforme.</p>
        </div>
      </div>
    </div>
  )
}
