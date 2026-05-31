<?php
session_start();
require_once 'config/connexion.php';
require_once 'includes/fonctions.php';

// ----------------------------------------------------------------
//  Données pour la page
// ----------------------------------------------------------------

// Destinations populaires avec le prix minimum des transports disponibles
$stmt = $pdo->query(
    "SELECT d.*,
            MIN(t.prix) AS prix_depuis
     FROM DESTINATIONS d
     LEFT JOIN TRANSPORTS t ON t.destination_id = d.id
     GROUP BY d.id
     ORDER BY d.id
     LIMIT 8"
);
$destinations = $stmt->fetchAll();

// Nombre total de destinations, hébergements, utilisateurs (pour les stats)
$stats = [];
$stats['destinations']  = $pdo->query("SELECT COUNT(*) FROM DESTINATIONS")->fetchColumn();
$stats['hebergements']  = $pdo->query("SELECT COUNT(*) FROM HEBERGEMENTS")->fetchColumn();
$stats['utilisateurs']  = $pdo->query("SELECT COUNT(*) FROM UTILISATEURS")->fetchColumn();
$stats['activites']     = $pdo->query("SELECT COUNT(*) FROM ACTIVITES")->fetchColumn();

// Emoji par catégorie
$emojisCategorie = [
    'plage'    => '🏖',
    'ville'    => '🏙',
    'culture'  => '🏛',
    'nature'   => '🌿',
    'montagne' => '⛰',
    'aventure' => '🧗',
];

$titrePage       = 'Accueil';
$descriptionPage = 'VoyageVista — La plateforme de voyage pensée pour les étudiants en mobilité internationale. Trouvez vos destinations, transports et hébergements.';
require_once 'includes/header.php';
?>

<!-- ================================================================
     HERO
================================================================ -->
<section class="hero">
    <div class="container hero-contenu">
        <p style="color:rgba(255,255,255,0.75); font-size:0.9rem; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; margin-bottom:0.5rem;">
            ✈ La plateforme voyage des étudiants
        </p>
        <h1>Voyagez avec nous</h1>
        <p class="hero-sous-titre">Erasmus, gap year, échange universitaire — planifiez l'aventure de votre vie.</p>

        <!-- Barre de recherche -->
        <form action="destinations/liste.php" method="GET" class="hero-recherche">

            <!-- Tabs -->
            <div class="hero-recherche-tabs">
                <button type="button" class="hero-tab actif" data-tab="destinations">🌍 Destinations</button>
                <button type="button" class="hero-tab" data-tab="transports"
                        onclick="window.location='transports/recherche.php'">✈ Transports</button>
                <button type="button" class="hero-tab" data-tab="hebergements"
                        onclick="window.location='hebergements/liste.php'">🏨 Hébergements</button>
                <button type="button" class="hero-tab" data-tab="activites"
                        onclick="window.location='activites/liste.php'">🎭 Activités</button>
            </div>

            <!-- Champs de recherche -->
            <div class="hero-champs">

                <div class="hero-champ" style="flex:2;">
                    <label for="hero-destination">Destination</label>
                    <input
                        type="text"
                        id="hero-destination"
                        name="recherche"
                        class="champ"
                        placeholder="🔍 Paris, Barcelone, Tokyo…"
                        autocomplete="off"
                    >
                </div>

                <div class="hero-champ">
                    <label for="hero-categorie">Catégorie</label>
                    <select id="hero-categorie" name="categorie" class="champ">
                        <option value="">Toutes</option>
                        <option value="plage">🏖 Plage</option>
                        <option value="ville">🏙 Ville</option>
                        <option value="culture">🏛 Culture</option>
                        <option value="nature">🌿 Nature</option>
                        <option value="montagne">⛰ Montagne</option>
                        <option value="aventure">🧗 Aventure</option>
                    </select>
                </div>

                <div class="hero-champ">
                    <label for="hero-budget">Budget max</label>
                    <select id="hero-budget" name="budget_max" class="champ">
                        <option value="">Tous budgets</option>
                        <option value="100">Moins de 100 €</option>
                        <option value="300">Moins de 300 €</option>
                        <option value="500">Moins de 500 €</option>
                        <option value="1000">Moins de 1 000 €</option>
                    </select>
                </div>

                <div class="hero-champ">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-orange btn-grand" style="width:100%;">
                        Rechercher
                    </button>
                </div>

            </div>
        </form>
    </div>
</section>

<!-- ================================================================
     STATS RAPIDES
================================================================ -->
<section style="background:var(--bleu); padding:1.5rem 0;">
    <div class="container">
        <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; text-align:center;">
            <div>
                <div style="font-family:'Poppins',sans-serif; font-size:1.6rem; font-weight:800; color:#fff;">
                    <?= $stats['destinations'] ?>
                </div>
                <div style="color:rgba(255,255,255,0.75); font-size:0.82rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em;">
                    Destinations
                </div>
            </div>
            <div>
                <div style="font-family:'Poppins',sans-serif; font-size:1.6rem; font-weight:800; color:#fff;">
                    <?= $stats['hebergements'] ?>
                </div>
                <div style="color:rgba(255,255,255,0.75); font-size:0.82rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em;">
                    Hébergements
                </div>
            </div>
            <div>
                <div style="font-family:'Poppins',sans-serif; font-size:1.6rem; font-weight:800; color:#fff;">
                    <?= $stats['activites'] ?>
                </div>
                <div style="color:rgba(255,255,255,0.75); font-size:0.82rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em;">
                    Activités
                </div>
            </div>
            <div>
                <div style="font-family:'Poppins',sans-serif; font-size:1.6rem; font-weight:800; color:#fff;">
                    <?= $stats['utilisateurs'] ?>
                </div>
                <div style="color:rgba(255,255,255,0.75); font-size:0.82rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em;">
                    Voyageurs
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ================================================================
     CATÉGORIES
================================================================ -->
<section class="section" style="padding-bottom:0;">
    <div class="container">
        <div class="section-titre">
            <h2>Explorer par catégorie</h2>
            <p>Trouvez la destination qui correspond à votre style de voyage</p>
        </div>
        <div style="display:flex; flex-wrap:wrap; gap:0.75rem; justify-content:center;">
            <?php
            $categories = [
                ['valeur' => 'plage',    'label' => 'Plage',    'emoji' => '🏖', 'couleur' => '#0096C7'],
                ['valeur' => 'ville',    'label' => 'Ville',    'emoji' => '🏙', 'couleur' => '#4338CA'],
                ['valeur' => 'culture',  'label' => 'Culture',  'emoji' => '🏛', 'couleur' => '#9333EA'],
                ['valeur' => 'nature',   'label' => 'Nature',   'emoji' => '🌿', 'couleur' => '#16A34A'],
                ['valeur' => 'montagne', 'label' => 'Montagne', 'emoji' => '⛰', 'couleur' => '#C2410C'],
                ['valeur' => 'aventure', 'label' => 'Aventure', 'emoji' => '🧗', 'couleur' => '#B45309'],
            ];
            foreach ($categories as $cat): ?>
                <a href="destinations/liste.php?categorie=<?= urlencode($cat['valeur']) ?>"
                   style="display:inline-flex; align-items:center; gap:0.5rem;
                          padding:0.65rem 1.3rem; border-radius:50px;
                          background:white; border:2px solid <?= $cat['couleur'] ?>;
                          color:<?= $cat['couleur'] ?>; font-weight:700; font-size:0.88rem;
                          transition:all 0.2s; text-decoration:none;"
                   onmouseover="this.style.background='<?= $cat['couleur'] ?>'; this.style.color='#fff';"
                   onmouseout="this.style.background='white'; this.style.color='<?= $cat['couleur'] ?>';">
                    <?= $cat['emoji'] ?> <?= $cat['label'] ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ================================================================
     DESTINATIONS POPULAIRES
================================================================ -->
<section class="section">
    <div class="container">
        <div class="section-titre">
            <h2>Destinations populaires</h2>
            <p>Les coups de cœur de nos voyageurs étudiants</p>
        </div>

        <div class="grille">
            <?php foreach ($destinations as $dest): ?>
                <a href="destinations/detail.php?id=<?= $dest['id'] ?>"
                   style="text-decoration:none; color:inherit;">
                    <article class="carte">

                        <!-- Image ou placeholder coloré -->
                        <?php if ($dest['image_url'] && file_exists($dest['image_url'])): ?>
                            <img
                                src="<?= URL_BASE . '/' . securiser($dest['image_url']) ?>"
                                alt="<?= securiser($dest['nom']) ?>"
                                class="carte-image"
                                loading="lazy"
                            >
                        <?php else: ?>
                            <div class="carte-image-placeholder">
                                <?= $emojisCategorie[$dest['categorie']] ?? '🌍' ?>
                            </div>
                        <?php endif; ?>

                        <div class="carte-corps">
                            <div class="flex-entre" style="margin-bottom:0.4rem;">
                                <span class="carte-pays"><?= securiser($dest['pays']) ?></span>
                                <span class="badge-categorie badge-<?= securiser($dest['categorie']) ?>">
                                    <?= securiser($dest['categorie']) ?>
                                </span>
                            </div>

                            <h3 class="carte-nom"><?= securiser($dest['nom']) ?></h3>

                            <p class="carte-description"><?= securiser($dest['description']) ?></p>

                            <div class="carte-pied">
                                <div class="carte-prix">
                                    <span class="depuis">Depuis</span>
                                    <span class="montant">
                                        <?= $dest['prix_depuis'] ? formatPrix((float)$dest['prix_depuis']) : 'Prix variable' ?>
                                    </span>
                                </div>
                                <span class="btn btn-primaire btn-petit">Voir →</span>
                            </div>
                        </div>

                    </article>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="centrer mt-3">
            <a href="destinations/liste.php" class="btn btn-secondaire btn-grand">
                Voir toutes les destinations
            </a>
        </div>
    </div>
</section>

<!-- ================================================================
     ESPACE ÉTUDIANT (mise en avant)
================================================================ -->
<section class="section" style="background:linear-gradient(135deg,#EBF5FD,#F0FDF4); border-top:1px solid var(--bordure);">
    <div class="container">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:3rem; align-items:center;">

            <div>
                <p style="color:var(--bleu); font-weight:700; font-size:0.85rem; text-transform:uppercase; letter-spacing:0.08em; margin-bottom:0.5rem;">
                    🎓 Spécial Erasmus & Échanges
                </p>
                <h2 style="margin-bottom:1rem;">Un espace pensé pour les étudiants</h2>
                <p style="color:var(--texte-doux); margin-bottom:1.5rem;">
                    Universités partenaires, logements proches du campus, estimateur de budget étudiant
                    et infos visa — tout ce qu'il faut pour préparer votre mobilité internationale.
                </p>
                <div style="display:flex; flex-direction:column; gap:0.6rem; margin-bottom:1.75rem;">
                    <?php
                    $avantages = [
                        '🏫 Recherche d\'universités Erasmus par pays et langue',
                        '🏠 Logements étudiants proches des campus',
                        '💶 Calculateur de budget adapté aux petites bourses',
                        '🛂 Infos visa selon votre nationalité',
                    ];
                    foreach ($avantages as $av): ?>
                        <div style="display:flex; align-items:flex-start; gap:0.6rem; font-size:0.9rem;">
                            <span><?= $av ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <a href="etudiant/universites.php" class="btn btn-primaire">
                    Accéder à l'Espace Étudiant →
                </a>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                <?php
                $cartesetudiant = [
                    ['emoji' => '🏫', 'titre' => 'Universités', 'desc' => 'Partenaires Erasmus', 'lien' => 'etudiant/universites.php'],
                    ['emoji' => '🏠', 'titre' => 'Logements',   'desc' => 'Proches du campus',   'lien' => 'etudiant/logements.php'],
                    ['emoji' => '💶', 'titre' => 'Budget',       'desc' => 'Estimateur étudiant', 'lien' => 'etudiant/budget.php'],
                    ['emoji' => '🛂', 'titre' => 'Visa',         'desc' => 'Infos par destination','lien' => 'destinations/liste.php'],
                ];
                foreach ($cartesetudiant as $c): ?>
                    <a href="<?= URL_BASE ?>/<?= $c['lien'] ?>"
                       style="background:white; border-radius:12px; padding:1.25rem; text-align:center;
                              box-shadow:var(--ombre); text-decoration:none; color:inherit;
                              transition:transform 0.2s, box-shadow 0.2s; display:block;"
                       onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='var(--ombre-forte)';"
                       onmouseout="this.style.transform=''; this.style.boxShadow='var(--ombre)';">
                        <div style="font-size:1.8rem; margin-bottom:0.5rem;"><?= $c['emoji'] ?></div>
                        <div style="font-family:'Poppins',sans-serif; font-weight:700; font-size:0.92rem;">
                            <?= $c['titre'] ?>
                        </div>
                        <div style="font-size:0.78rem; color:var(--texte-doux); margin-top:0.2rem;">
                            <?= $c['desc'] ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

        </div>
    </div>
</section>

<!-- ================================================================
     POURQUOI VOYAGEVISTA
================================================================ -->
<section class="section">
    <div class="container">
        <div class="section-titre">
            <h2>Pourquoi choisir VoyageVista ?</h2>
            <p>Conçu par des étudiants, pour des étudiants</p>
        </div>

        <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:1.5rem;">
            <?php
            $arguments = [
                [
                    'emoji' => '💰',
                    'titre' => 'Budget maîtrisé',
                    'desc'  => 'Filtres par budget, alertes prix, hébergements à partir de 12 €/nuit. Voyager étudiant, c\'est possible.',
                ],
                [
                    'emoji' => '🗺',
                    'titre' => 'Itinéraire personnalisé',
                    'desc'  => 'Créez votre séjour sur mesure : transports, hébergements et activités réunis en un seul tableau de bord.',
                ],
                [
                    'emoji' => '🎓',
                    'titre' => 'Logistique Erasmus',
                    'desc'  => 'Universités partenaires, logements étudiants, calculateur de bourse — tout pour préparer votre mobilité.',
                ],
            ];
            foreach ($arguments as $arg): ?>
                <div style="background:white; border-radius:var(--rayon); padding:1.75rem; box-shadow:var(--ombre); text-align:center;">
                    <div style="font-size:2.2rem; margin-bottom:0.75rem;"><?= $arg['emoji'] ?></div>
                    <h3 style="margin-bottom:0.6rem; font-size:1.05rem;"><?= $arg['titre'] ?></h3>
                    <p style="color:var(--texte-doux); font-size:0.88rem; margin:0;"><?= $arg['desc'] ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ================================================================
     CTA FINAL (si non connecté)
================================================================ -->
<?php if (!estConnecte()): ?>
<section style="background:linear-gradient(135deg, var(--bleu) 0%, #023E8A 100%); padding:3.5rem 0; text-align:center;">
    <div class="container">
        <h2 style="color:white; margin-bottom:0.75rem;">Prêt à partir ?</h2>
        <p style="color:rgba(255,255,255,0.8); margin-bottom:1.75rem; font-size:1.05rem;">
            Créez votre compte gratuitement et commencez à planifier votre prochain voyage.
        </p>
        <div style="display:flex; gap:1rem; justify-content:center; flex-wrap:wrap;">
            <a href="auth/inscription.php" class="btn btn-orange btn-grand">Créer un compte gratuit</a>
            <a href="destinations/liste.php" class="btn btn-secondaire btn-grand" style="border-color:rgba(255,255,255,0.6); color:white;">
                Explorer les destinations
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
