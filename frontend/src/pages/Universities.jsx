import { useState, useEffect, useCallback } from 'react'
import { Link } from 'react-router-dom'
import api from '../services/api'
import styles from './Universities.module.css'

export default function Universities() {
  const [universities, setUniversities] = useState([])
  // Options disponibles pour les filtres (récupérées une seule fois au chargement initial)
  const [filterOptions, setFilterOptions] = useState({ countries: [], langues: [] })
  const [selected, setSelected] = useState({ country: '', langue: '' })
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  const fetchUniversities = useCallback((filters) => {
    setLoading(true)
    setError(null)
    const params = {}
    if (filters.country) params.country = filters.country
    if (filters.langue)  params.langue  = filters.langue

    api.get('/universities', { params })
      .then(res => {
        setUniversities(res.data.data ?? [])
        // On ne met à jour les options de filtres que si elles ne sont pas encore chargées
        if (res.data.filters && filterOptions.countries.length === 0) {
          setFilterOptions(res.data.filters)
        }
      })
      .catch(() => setError('Impossible de charger les universités partenaires.'))
      .finally(() => setLoading(false))
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  // Chargement initial
  useEffect(() => {
    fetchUniversities(selected)
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  // Rechargement à chaque changement de filtre
  useEffect(() => {
    fetchUniversities(selected)
  }, [selected, fetchUniversities])

  const handleFilter = e => {
    const { name, value } = e.target
    setSelected(prev => ({ ...prev, [name]: value }))
  }

  const resetFilters = () => setSelected({ country: '', langue: '' })
  const hasActiveFilters = selected.country || selected.langue

  return (
    <div className={styles.page}>
      <div className={styles.pageHeader}>
        <h1>Universités partenaires</h1>
        <p>Explorez nos partenaires Erasmus en Europe et prenez contact directement.</p>
      </div>

      {/* Barre de filtres */}
      <div className={styles.filterBar}>
        <label htmlFor="filter-country">Pays</label>
        <select
          id="filter-country"
          name="country"
          value={selected.country}
          onChange={handleFilter}
        >
          <option value="">Tous les pays</option>
          {filterOptions.countries.map(c => (
            <option key={c} value={c}>{c}</option>
          ))}
        </select>

        <label htmlFor="filter-langue">Langue</label>
        <select
          id="filter-langue"
          name="langue"
          value={selected.langue}
          onChange={handleFilter}
        >
          <option value="">Toutes les langues</option>
          {filterOptions.langues.map(l => (
            <option key={l} value={l}>{l}</option>
          ))}
        </select>

        {hasActiveFilters && (
          <button className="btn" onClick={resetFilters}>
            Réinitialiser
          </button>
        )}

        {!loading && (
          <span className={styles.filterCount}>
            {universities.length} université{universities.length !== 1 ? 's' : ''}
          </span>
        )}
      </div>

      {/* États */}
      {loading && <p className={styles.state}>Chargement des universités...</p>}
      {error   && <p className={styles.stateError}>{error}</p>}
      {!loading && !error && universities.length === 0 && (
        <p className={styles.state}>Aucune université ne correspond à ces critères.</p>
      )}

      {/* Grille de cartes */}
      {!loading && !error && universities.length > 0 && (
        <ul className={styles.grid}>
          {universities.map(uni => (
            <li key={uni.id} className={styles.card}>
              <div className={styles.cardTop}>
                {uni.erasmus_code && (
                  <span className={styles.erasmusCode}>{uni.erasmus_code}</span>
                )}
                <span className={styles.countryTag}>{uni.country}</span>
              </div>

              <h2>{uni.name}</h2>
              <p className={styles.cardCity}>{uni.city}</p>
              {uni.langue && (
                <p className={styles.cardLangue}>Enseignement : {uni.langue}</p>
              )}
              {uni.description && (
                <p className={styles.cardDesc}>
                  {uni.description.length > 130
                    ? uni.description.slice(0, 130) + '…'
                    : uni.description}
                </p>
              )}

              {Number(uni.nb_etudiants_etrangers) > 0 && (
                <p className={styles.cardMeta}>
                  {Number(uni.nb_etudiants_etrangers).toLocaleString('fr-FR')} étudiants étrangers
                </p>
              )}

              <Link
                to={`/universities/${uni.id}`}
                className={`btn btn-primary ${styles.btnCard}`}
              >
                Voir la fiche
              </Link>
            </li>
          ))}
        </ul>
      )}
    </div>
  )
}
