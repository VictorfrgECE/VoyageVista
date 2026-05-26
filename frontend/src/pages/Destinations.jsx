import { useEffect, useState } from 'react'
import api from '../services/api'
import styles from './Destinations.module.css'

export default function Destinations() {
  const [destinations, setDestinations] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  useEffect(() => {
    api.get('/destinations')
      .then(res => setDestinations(res.data.data ?? []))
      .catch(() => setError('Impossible de charger les destinations.'))
      .finally(() => setLoading(false))
  }, [])

  if (loading) return <p className={styles.state}>Chargement...</p>
  if (error)   return <p className={styles.state}>{error}</p>

  return (
    <div className="container" style={{ padding: '2rem 1rem' }}>
      <h1 className={styles.title}>Destinations</h1>
      {destinations.length === 0 ? (
        <p className={styles.state}>Aucune destination disponible pour l'instant.</p>
      ) : (
        <ul className={styles.grid}>
          {destinations.map(d => (
            <li key={d.id} className={styles.card}>
              {d.image_url && <img src={d.image_url} alt={d.name} />}
              <div className={styles.cardBody}>
                <h2>{d.name}</h2>
                <span>{d.country}</span>
                <p>{d.description}</p>
              </div>
            </li>
          ))}
        </ul>
      )}
    </div>
  )
}
