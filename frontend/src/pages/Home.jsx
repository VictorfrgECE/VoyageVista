import { Link } from 'react-router-dom'
import styles from './Home.module.css'

export default function Home() {
  return (
    <div className={styles.home}>
      <section className={styles.hero}>
        <h1>Planifiez votre voyage idéal</h1>
        <p>Découvrez des destinations, comparez les transports et réservez vos hébergements en un seul endroit.</p>
        <Link to="/destinations" className="btn btn-primary">Explorer les destinations</Link>
      </section>
    </div>
  )
}
