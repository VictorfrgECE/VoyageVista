import { Link, NavLink } from 'react-router-dom'
import styles from './Navbar.module.css'

export default function Navbar() {
  return (
    <nav className={styles.navbar}>
      <div className={styles.brand}>
        <Link to="/">VoyageVista</Link>
      </div>
      <ul className={styles.links}>
        <li><NavLink to="/" end>Accueil</NavLink></li>
        <li><NavLink to="/destinations">Destinations</NavLink></li>
      </ul>
      <div className={styles.auth}>
        <Link to="/login" className="btn">Connexion</Link>
        <Link to="/register" className="btn btn-primary">S'inscrire</Link>
      </div>
    </nav>
  )
}
