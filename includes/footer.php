</main><!-- /main -->

<!-- Bouton retour en haut de page -->
<button id="btn-top" class="btn-top" aria-label="Retour en haut de la page" title="Retour en haut" style="display:none;">&#8679;</button>

<footer class="footer" role="contentinfo">
    <div class="container">

        <div class="footer-grille">

            <!-- Colonne 1 : À propos -->
            <div class="footer-col footer-col-brand">
                <div class="footer-logo">&#9992; Voyage<span>Vista</span></div>
                <p class="footer-desc">
                    La plateforme de voyage pensée pour les étudiants en mobilité internationale.
                    Erasmus, gap year, échanges universitaires — planifiez l'aventure de votre vie.
                </p>
                <address class="footer-contact" style="font-style:normal;">
                    <div class="footer-contact-item">
                        <span aria-hidden="true">&#9993;</span>
                        <a href="mailto:<?= htmlspecialchars(EMAIL_CONTACT) ?>"><?= htmlspecialchars(EMAIL_CONTACT) ?></a>
                    </div>
                    <div class="footer-contact-item">
                        <span aria-hidden="true">&#128222;</span>
                        <a href="tel:<?= preg_replace('/\s+/', '', TEL_CONTACT) ?>"><?= htmlspecialchars(TEL_CONTACT) ?></a>
                    </div>
                </address>
            </div>

            <!-- Colonne 2 : Explorer -->
            <div class="footer-col">
                <h3 class="footer-titre-col">Explorer</h3>
                <ul class="footer-liens">
                    <li><a href="<?= URL_BASE ?>/destinations/liste.php">Toutes les destinations</a></li>
                    <li><a href="<?= URL_BASE ?>/transports/recherche.php">Rechercher un transport</a></li>
                    <li><a href="<?= URL_BASE ?>/hebergements/liste.php">Hébergements</a></li>
                    <li><a href="<?= URL_BASE ?>/activites/liste.php">Activités</a></li>
                </ul>
            </div>

            <!-- Colonne 3 : Espace Étudiant -->
            <div class="footer-col">
                <h3 class="footer-titre-col">Espace Étudiant</h3>
                <ul class="footer-liens">
                    <li><a href="<?= URL_BASE ?>/etudiant/universites.php">Universités Erasmus</a></li>
                    <li><a href="<?= URL_BASE ?>/etudiant/logements.php">Logements étudiants</a></li>
                    <li><a href="<?= URL_BASE ?>/etudiant/budget.php">Calculateur de budget</a></li>
                </ul>
            </div>

            <!-- Colonne 4 : Mon Compte -->
            <div class="footer-col">
                <h3 class="footer-titre-col">Mon Compte</h3>
                <ul class="footer-liens">
                    <?php if (isset($_SESSION['utilisateur_id'])): ?>
                        <li><a href="<?= URL_BASE ?>/itineraires/mon_itineraire.php">Mon itinéraire</a></li>
                        <li><a href="<?= URL_BASE ?>/reservations/panier.php">Mon panier</a></li>
                        <li><a href="<?= URL_BASE ?>/notifications/mes_notifications.php">Mes notifications</a></li>
                        <li><a href="<?= URL_BASE ?>/auth/deconnexion.php" class="footer-lien-deconnexion">Déconnexion</a></li>
                    <?php else: ?>
                        <li><a href="<?= URL_BASE ?>/auth/inscription.php">Créer un compte</a></li>
                    <?php endif; ?>
                </ul>
            </div>

        </div><!-- /.footer-grille -->

        <!-- Séparateur -->
        <hr class="footer-hr">

        <!-- Bas de page -->
        <div class="footer-bas">
            <span class="footer-copy">
                &copy; <?= date('Y') ?> <?= htmlspecialchars(NOM_SITE) ?> &mdash; Projet pédagogique ING2 ECE Paris
            </span>
            <span class="footer-mention">
                Fait avec &#10084; par des étudiants, pour des étudiants &nbsp;|&nbsp; v<?= htmlspecialchars(VERSION) ?>
            </span>
        </div>

    </div><!-- /.container -->
</footer>

<!-- JavaScript principal -->
<script src="<?= URL_BASE ?>/js/main.js"></script>

<!-- Script inline : bouton retour en haut -->
<script>
(function () {
    var btnTop = document.getElementById('btn-top');
    if (!btnTop) { return; }

    window.addEventListener('scroll', function () {
        btnTop.style.display = window.scrollY > 400 ? 'flex' : 'none';
    }, { passive: true });

    btnTop.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}());
</script>

</body>
</html>
