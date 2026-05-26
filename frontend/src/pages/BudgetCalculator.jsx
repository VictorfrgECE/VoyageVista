import { useState, useMemo } from 'react'
import { Link } from 'react-router-dom'
import { useAuth } from '../contexts/AuthContext'
import api from '../services/api'
import styles from './BudgetCalculator.module.css'

// Coûts de référence aller-retour par type de transport (en euros)
const TRANSPORT_OPTIONS = [
  { value: 'avion',     label: 'Avion',            cost: 300 },
  { value: 'train',     label: 'Train',            cost: 180 },
  { value: 'interrail', label: 'Interrail (pass)', cost: 260 },
  { value: 'bus',       label: 'Bus / Flixbus',    cost: 90  },
]

// Coûts de logement par nuit selon le type d'hébergement étudiant
const LOGEMENT_OPTIONS = [
  { value: 'residence',  label: 'Résidence universitaire', costPerNight: 20 },
  { value: 'colocation', label: 'Colocation',              costPerNight: 18 },
  { value: 'auberge',    label: 'Auberge de jeunesse',     costPerNight: 25 },
  { value: 'hotel',      label: 'Hôtel',                   costPerNight: 65 },
]

// Constantes de coût journalier et par activité (valeurs moyennes étudiantes)
const COUT_PAR_ACTIVITE       = 35
const VIE_QUOTIDIENNE_PAR_JOUR = 28

export default function BudgetCalculator() {
  const { user } = useAuth()

  const [form, setForm] = useState({
    destination:    '',
    nb_jours:       14,
    type_transport: 'avion',
    type_logement:  'residence',
    nb_activites:   5,
  })

  // États de la sauvegarde : idle → saving → success | error
  const [saveState, setSaveState] = useState('idle')
  const [saveError, setSaveError] = useState(null)

  const handleChange = e => {
    const { name, value } = e.target
    // Conversion entière pour les champs numériques
    const parsed = (name === 'nb_jours' || name === 'nb_activites')
      ? Math.max(0, parseInt(value, 10) || 0)
      : value
    setForm(prev => ({ ...prev, [name]: parsed }))
  }

  // Calcul instantané du budget à chaque changement de formulaire
  const budget = useMemo(() => {
    const nbJours      = Math.max(1, form.nb_jours)
    const transportObj = TRANSPORT_OPTIONS.find(o => o.value === form.type_transport)
    const logementObj  = LOGEMENT_OPTIONS.find(o => o.value === form.type_logement)

    const transport      = transportObj?.cost ?? 0
    const logement       = (logementObj?.costPerNight ?? 0) * nbJours
    const activites      = form.nb_activites * COUT_PAR_ACTIVITE
    const vieQuotidienne = VIE_QUOTIDIENNE_PAR_JOUR * nbJours
    const total          = transport + logement + activites + vieQuotidienne

    return { transport, logement, activites, vieQuotidienne, total }
  }, [form])

  // Calcule la largeur (%) d'une barre par rapport au total
  const pct = value =>
    budget.total > 0 ? `${Math.round((value / budget.total) * 100)}%` : '0%'

  // Envoi vers POST /api/budget_estimations (token Bearer envoyé automatiquement via intercepteur)
  const handleSave = async () => {
    if (!form.destination.trim()) {
      setSaveError('Veuillez saisir une destination avant de sauvegarder.')
      setSaveState('error')
      return
    }
    setSaveState('saving')
    setSaveError(null)
    try {
      await api.post('/budget_estimations', {
        destination:               form.destination.trim(),
        nb_jours:                  Math.max(1, form.nb_jours),
        transport:                 budget.transport,
        logement:                  budget.logement,
        activites:                 budget.activites,
        vie_quotidienne_par_jour:  VIE_QUOTIDIENNE_PAR_JOUR,
      })
      setSaveState('success')
    } catch (err) {
      setSaveError(err.response?.data?.error ?? 'Erreur lors de la sauvegarde.')
      setSaveState('error')
    }
  }

  return (
    <div className={styles.page}>
      <h1 className={styles.title}>Calculateur de budget étudiant</h1>
      <p className={styles.subtitle}>
        Estimez le coût total de votre séjour en quelques secondes. Le calcul
        se met à jour en temps réel.
      </p>

      <div className={styles.layout}>

        {/* ── Formulaire de paramètres ── */}
        <section className={styles.formPanel}>
          <h2>Paramètres du séjour</h2>

          <label className={styles.field}>
            Destination
            <input
              type="text"
              name="destination"
              value={form.destination}
              onChange={handleChange}
              placeholder="Ex : Barcelone, Berlin, Prague…"
            />
          </label>

          <label className={styles.field}>
            Durée du séjour (jours)
            <input
              type="number"
              name="nb_jours"
              value={form.nb_jours}
              onChange={handleChange}
              min="1"
              max="365"
            />
          </label>

          <label className={styles.field}>
            Type de transport
            <select name="type_transport" value={form.type_transport} onChange={handleChange}>
              {TRANSPORT_OPTIONS.map(o => (
                <option key={o.value} value={o.value}>
                  {o.label} — ~{o.cost} €
                </option>
              ))}
            </select>
          </label>

          <label className={styles.field}>
            Type de logement
            <select name="type_logement" value={form.type_logement} onChange={handleChange}>
              {LOGEMENT_OPTIONS.map(o => (
                <option key={o.value} value={o.value}>
                  {o.label} — ~{o.costPerNight} €/nuit
                </option>
              ))}
            </select>
          </label>

          <label className={styles.field}>
            Nombre d'activités prévues
            <input
              type="number"
              name="nb_activites"
              value={form.nb_activites}
              onChange={handleChange}
              min="0"
              max="200"
            />
            <span className={styles.hint}>~{COUT_PAR_ACTIVITE} € par activité (visite, sortie, sport…)</span>
          </label>
        </section>

        {/* ── Résultats et répartition ── */}
        <section className={styles.resultPanel}>
          <h2>Estimation budgétaire</h2>

          {/* Total mis en avant */}
          <div className={styles.totalBox}>
            <span className={styles.totalLabel}>Total estimé</span>
            <span className={styles.totalAmount}>
              {budget.total.toLocaleString('fr-FR')} €
            </span>
            {form.destination && (
              <span className={styles.totalDest}>pour {form.destination}</span>
            )}
          </div>

          {/* Répartition visuelle par catégorie */}
          <div className={styles.breakdown}>
            {[
              { label: 'Transport',       amount: budget.transport,      color: '#4f46e5' },
              { label: 'Logement',        amount: budget.logement,       color: '#16a34a' },
              { label: 'Activités',       amount: budget.activites,      color: '#ea580c' },
              { label: 'Vie quotidienne', amount: budget.vieQuotidienne, color: '#9333ea' },
            ].map(({ label, amount, color }) => (
              <div key={label} className={styles.barRow}>
                <div className={styles.barLabel}>
                  <span>{label}</span>
                  <span className={styles.barAmount}>{amount.toLocaleString('fr-FR')} €</span>
                </div>
                <div className={styles.barTrack}>
                  <div
                    className={styles.barFill}
                    style={{ width: pct(amount), backgroundColor: color }}
                  />
                </div>
                <span className={styles.barPct}>{pct(amount)}</span>
              </div>
            ))}
          </div>

          {/* Zone de sauvegarde */}
          <div className={styles.saveArea}>
            {user ? (
              <>
                {saveState === 'success' && (
                  <p className={styles.successMsg}>
                    ✓ Estimation sauvegardée dans votre compte.{' '}
                    <button
                      className={styles.resetBtn}
                      onClick={() => { setSaveState('idle'); setSaveError(null) }}
                    >
                      Nouvelle estimation
                    </button>
                  </p>
                )}
                {saveState === 'error' && saveError && (
                  <p className={styles.errorMsg}>{saveError}</p>
                )}
                {saveState !== 'success' && (
                  <button
                    className="btn btn-primary"
                    style={{ width: '100%' }}
                    onClick={handleSave}
                    disabled={saveState === 'saving'}
                  >
                    {saveState === 'saving' ? 'Sauvegarde en cours…' : 'Sauvegarder mon estimation'}
                  </button>
                )}
              </>
            ) : (
              <p className={styles.loginPrompt}>
                <Link to="/login">Connectez-vous</Link> pour sauvegarder vos estimations et les retrouver plus tard.
              </p>
            )}
          </div>
        </section>
      </div>
    </div>
  )
}
