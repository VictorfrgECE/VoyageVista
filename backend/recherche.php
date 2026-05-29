<?php
session_start();
require_once '../config/connexion.php';
require_once '../includes/fonctions.php';

// Destinations disponibles pour les suggestions
$destinations = $pdo->query("SELECT id, nom, pays FROM DESTINATIONS ORDER BY nom")->fetchAll();

// Lieux de départ distincts
$departsDisponibles = $pdo->query(
    "SELECT DISTINCT lieu_depart FROM TRANSPORTS ORDER BY lieu_depart"
)->fetchAll(PDO::FETCH_COLUMN);

$titrePage = 'Recherche de transports';
require_once '../includes/header.php';
?>

<div class="page-entete">
    <div class="container">
        <h1>✈ Rechercher un transport</h1>
        <p>Trouvez le meilleur trajet vers votre destination</p>
    </div>
</div>

<div class="container section">
    <div style="max-width:760px; margin:0 auto;">

        <div class="boite" style="border-top:4px solid var(--bleu);">
            <h2 style="font-size:1.1rem; margin-bottom:1.5rem;">🔍 Vos critères</h2>

            <form action="resultats.php" method="GET" novalidate>

                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:1rem;">

                    <div class="champ-groupe">
                        <label for="depart">Ville de départ</label>
                        <input type="text" id="depart" name="depart" class="champ"
                               value="Paris" placeholder="Paris, Lyon…"
                               list="liste-departs">
                        <datalist id="liste-departs">
                            <?php foreach ($departsDisponibles as $d): ?>
                                <option value="<?= securiser($d) ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>

                    <div class="champ-groupe">
                        <label for="destination">Destination</label>
                        <input type="text" id="destination" name="destination" class="champ"
                               placeholder="Barcelone, Tokyo…"
                               list="liste-destinations">
                        <datalist id="liste-destinations">
                            <?php foreach ($destinations as $d): ?>
                                <option value="<?= securiser($d['nom']) ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>

                    <div class="champ-groupe">
                        <label for="date_depart">Date de départ</label>
                        <input type="date" id="date_depart" name="date_depart" class="champ" 
                               min="<?= date('Y-m-d') ?>" required>
                    </div>

                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:1rem;">

                    <div class="champ-groupe">
                        <label for="type">Type de transport</label>
                        <select id="type" name="type" class="champ">
                            <option value="">Tous</option>
                            <option value="avion">✈ Avion</option>
                            <option value="train">🚂 Train</option>
                            <option value="bus">🚌 Bus</option>
                            <option value="ferry">⛴ Ferry</option>
                            <option value="voiture">🚗 Voiture</option>
                        </select>
                    </div>

                    <div class="champ-groupe">
                        <label for="budget_max">Budget max (€)</label>
                        <select id="budget_max" name="budget_max" class="champ">
                            <option value="">Sans limite</option>
                            <option value="50">Moins de 50 €</option>
                            <option value="100">Moins de 100 €</option>
                            <option value="200">Moins de 200 €</option>
                            <option value="500">Moins de 500 €</option>
                            <option value="700">Moins de 700 €</option>
                        </select>
                    </div>

                    <div class="champ-groupe">
                        <label for="tri">Trier par</label>
                        <select id="tri" name="tri" class="champ">
                            <option value="prix_asc">Prix croissant</option>
                            <option value="prix_desc">Prix décroissant</option>
                            <option value="compagnie">Compagnie A–Z</option>
                        </select>
                    </div>

                </div>

                <button type="submit" class="btn btn-primaire btn-grand" style="margin-top:0.5rem;">
                    Rechercher les transports →
                </button>

            </form>
        </div>

        <div style="margin-top:2.5rem;">
            <h2 style="font-size:1.1rem; margin-bottom:1rem;">🌍 Destinations populaires</h2>
            <div style="display:flex; flex-wrap:wrap; gap:0.5rem;">
                <?php foreach ($destinations as $d): ?>
                    <a href="resultats.php?destination=<?= urlencode($d['nom']) ?>&depart=Paris"
                       style="padding:0.45rem 1rem; background:white; border:2px solid var(--bordure);
                              border-radius:50px; font-size:0.85rem; font-weight:600; color:var(--texte);
                              transition:all 0.2s; text-decoration:none;"
                       onmouseover="this.style.borderColor='var(--bleu)'; this.style.color='var(--bleu)';"
                       onmouseout="this.style.borderColor='var(--bordure)'; this.style.color='var(--texte)';">
                        <?= securiser($d['nom']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
