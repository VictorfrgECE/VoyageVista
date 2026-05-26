import { useAuth } from '../contexts/AuthContext'
import styles from './Dashboard.module.css'

// Page réservée aux prestataires : gestion des offres (logements, activités, transports)
export default function Dashboard() {
  const { user } = useAuth()

  return (
    <div className={styles.page}>
      <div className={styles.header}>
        <h1>Espace prestataire</h1>
        <span className={styles.badge}>Prestataire</span>
      </div>
      <p className={styles.welcome}>
        Bienvenue, <strong>{user?.name}</strong>
      </p>
      <div className={styles.grid}>
        <div className={styles.card}>
          <h2>Mes logements</h2>
          <p>Gérer vos offres de logement étudiant.</p>
        </div>
        <div className={styles.card}>
          <h2>Mes activités</h2>
          <p>Publier et modifier vos activités.</p>
        </div>
        <div className={styles.card}>
          <h2>Réservations</h2>
          <p>Consulter les demandes reçues.</p>
        </div>
        <div className={styles.card}>
          <h2>Mon profil</h2>
          <p>Mettre à jour vos informations.</p>
        </div>
      </div>
    </div>
  )
}
