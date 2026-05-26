import { useState, useEffect } from 'react'
import { useParams, Link } from 'react-router-dom'
import api from '../services/api'
import { useAuth } from '../contexts/AuthContext'
import styles from './Universities.module.css'

// Traduction lisible des types de logement
const HOUSING_LABELS = {
  residence:     'Résidence univ.',
  colocation:    'Colocation',
  studio:        'Studio',
  auberge:       'Auberge',
  famille_hote:  "Famille d'accueil",
}

export default function UniversityDetail() {
  const { id } = useParams()
  const { user } = useAuth()

  const [uni, setUni] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  // État du formulaire de contact — pré-rempli avec les infos de l'utilisateur connecté
  const [form, setForm] = useState({
    sender_name:  user?.name  ?? '',
    sender_email: '',
    subject:      '',
    message:      '',
  })
  const [contactState, setContactState] = useState('idle') // idle | sending | success | error
  const [contactError, setContactError] = useState(null)

  useEffect(() => {
    api.get(`/universities/${id}`)
      .then(res => setUni(res.data.data))
      .catch(() => setError('Université introuvable ou serveur indisponible.'))
      .finally(() => setLoading(false))
  }, [id])

  const handleFormChange = e =>
    setForm(prev => ({ ...prev, [e.target.name]: e.target.value }))

  // Validation côté client avant envoi (double vérification avec le backend)
  const validate = () => {
    if (!form.sender_name.trim())  return 'Le nom est requis.'
    if (!form.sender_email.trim()) return 'L\'email est requis.'
    if (!form.subject.trim())      return 'Le sujet est requis.'
    if (form.message.trim().length < 20) return 'Le message doit contenir au moins 20 caractères.'
    return null
  }

  const handleContactSubmit = async e => {
    e.preventDefault()
    const err = validate()
    if (err) { setContactError(err); setContactState('error'); return }

    setContactState('sending')
    setContactError(null)
    try {
      await api.post(`/universities/${id}/contact`, {
        ...form,
        user_id: user?.id ?? null,
      })
      setContactState('success')
      // Réinitialise le formulaire en gardant le nom/email de l'utilisateur
      setForm({ sender_name: user?.name ?? '', sender_email: '', subject: '', message: '' })
    } catch (err) {
      setContactState('error')
      setContactError(err.response?.data?.error ?? "Erreur lors de l'envoi.")
    }
  }

  if (loading) return <p className={styles.state}>Chargement...</p>

  if (error || !uni) {
    return (
      <div className={styles.detailPage}>
        <p className={styles.stateError}>{error ?? 'Université introuvable.'}</p>
        <Link to="/universities" className="btn" style={{ marginTop: '1rem' }}>
          ← Retour à la liste
        </Link>
      </div>
    )
  }

  return (
    <div className={styles.detailPage}>
      <Link to="/universities" className={styles.backLink}>
        ← Toutes les universités
      </Link>

      {/* En-tête de la fiche */}
      <div className={styles.detailHeader}>
        <div className={styles.detailTitle}>
          {uni.erasmus_code && (
            <span className={styles.erasmusCode}>{uni.erasmus_code}</span>
          )}
          <h1>{uni.name}</h1>
          <p className={styles.cardCity}>{uni.city}, {uni.country}</p>
        </div>
        <div className={styles.detailActions}>
          {uni.website && (
            <a
              href={uni.website}
              target="_blank"
              rel="noreferrer"
              className="btn"
            >
              Site officiel ↗
            </a>
          )}
        </div>
      </div>

      {/* Corps : deux colonnes (infos + contact) */}
      <div className={styles.detailBody}>

        {/* Colonne principale */}
        <div className={styles.detailMain}>

          {/* Présentation */}
          {uni.description && (
            <section className={styles.section}>
              <h2>À propos</h2>
              <p className={styles.sectionText}>{uni.description}</p>
            </section>
          )}

          {/* Informations pratiques */}
          <section className={styles.section}>
            <h2>Informations pratiques</h2>
            <dl className={styles.infoGrid}>
              <div className={styles.infoItem}>
                <dt>Code Erasmus</dt>
                <dd>{uni.erasmus_code ?? '—'}</dd>
              </div>
              <div className={styles.infoItem}>
                <dt>Langue(s) d'enseignement</dt>
                <dd>{uni.langue ?? '—'}</dd>
              </div>
              <div className={styles.infoItem}>
                <dt>Étudiants étrangers</dt>
                <dd>
                  {Number(uni.nb_etudiants_etrangers) > 0
                    ? Number(uni.nb_etudiants_etrangers).toLocaleString('fr-FR')
                    : '—'}
                </dd>
              </div>
              <div className={styles.infoItem}>
                <dt>Contact relations internationales</dt>
                <dd>{uni.email_contact ?? '—'}</dd>
              </div>
            </dl>
          </section>

          {/* Logements étudiants associés */}
          {uni.housing && uni.housing.length > 0 && (
            <section className={styles.section}>
              <h2>Logements étudiants disponibles</h2>
              <ul className={styles.housingList}>
                {uni.housing.map(h => (
                  <li key={h.id} className={styles.housingItem}>
                    <div className={styles.housingItemHeader}>
                      <strong>{h.name}</strong>
                      <span className={styles.housingTypeBadge}>
                        {HOUSING_LABELS[h.type] ?? h.type}
                      </span>
                    </div>
                    <p className={styles.housingMeta}>
                      {h.address}
                      {h.distance_campus_km && ` · ${h.distance_campus_km} km du campus`}
                      {h.available_rooms > 0 && ` · ${h.available_rooms} place(s)`}
                    </p>
                    <p className={styles.housingPrice}>
                      {h.price_per_month
                        ? `${parseFloat(h.price_per_month).toFixed(0)} €/mois`
                        : h.prix_nuit
                          ? `${parseFloat(h.prix_nuit).toFixed(0)} €/nuit`
                          : '—'}
                    </p>
                    {h.description && (
                      <p className={styles.housingDesc}>{h.description}</p>
                    )}
                  </li>
                ))}
              </ul>
            </section>
          )}
        </div>

        {/* Sidebar : formulaire de contact */}
        <aside className={styles.contactPanel}>
          <h2>Contacter l'université</h2>
          <p className={styles.contactNote}>
            Simulation d'une prise de contact avec le bureau des relations
            internationales. Votre message sera enregistré.
          </p>

          {contactState === 'success' ? (
            <div className={styles.successBox}>
              ✓ Votre message a bien été transmis au bureau des relations
              internationales de {uni.name}.
              <button onClick={() => setContactState('idle')}>
                Envoyer un nouveau message
              </button>
            </div>
          ) : (
            <form onSubmit={handleContactSubmit} className={styles.contactForm} noValidate>
              {contactState === 'error' && contactError && (
                <p className={styles.formError}>{contactError}</p>
              )}

              <label>
                Nom complet
                <input
                  type="text"
                  name="sender_name"
                  value={form.sender_name}
                  onChange={handleFormChange}
                  placeholder="Votre nom et prénom"
                  required
                />
              </label>

              <label>
                Email
                <input
                  type="email"
                  name="sender_email"
                  value={form.sender_email}
                  onChange={handleFormChange}
                  placeholder="votre@email.fr"
                  required
                />
              </label>

              <label>
                Sujet
                <input
                  type="text"
                  name="subject"
                  value={form.subject}
                  onChange={handleFormChange}
                  placeholder="Ex : Candidature Erasmus 2026–2027"
                  required
                />
              </label>

              <label>
                Message
                <textarea
                  name="message"
                  value={form.message}
                  onChange={handleFormChange}
                  rows={5}
                  minLength={20}
                  placeholder="Décrivez votre demande (mobilité, logement, procédure d'admission…)"
                  required
                />
              </label>

              <button
                type="submit"
                className="btn btn-primary"
                disabled={contactState === 'sending'}
                style={{ width: '100%' }}
              >
                {contactState === 'sending' ? 'Envoi en cours…' : 'Envoyer le message'}
              </button>
            </form>
          )}
        </aside>
      </div>
    </div>
  )
}
