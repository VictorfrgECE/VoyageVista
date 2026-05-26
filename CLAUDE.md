# VoyageVista — Guide pour Claude Code

## Contexte projet
Application web dynamique de planification de voyages (ECE ING2 2026).
Stack imposée : HTML/CSS, JavaScript, React (frontend), PHP (backend), MySQL (BDD).
Architecture client-serveur stricte, séparation frontend/backend obligatoire.

## Fonctionnalités à développer (par priorité)
1. Auth : inscription, connexion, sessions PHP, 3 rôles (Admin / Prestataire / Voyageur)
2. Catalogue destinations : recherche, filtres, tri, catégories
3. Transport : recherche vols/bus/train/voiture, disponibilités, dates cohérentes
4. Hébergements : disponibilités, réservation, gestion accès concurrents
5. Activités : inscription, capacités, contraintes temporelles
6. Itinéraires : composition transport + hébergement + activités
7. Notifications : réservations, modifications, rappels
8. Panier + paiement simulé

## Rôles utilisateurs
- Admin : gestion globale, modération des contenus
- Prestataire : publie destinations, hébergements, activités
- Voyageur : recherche, réserve, compose des itinéraires

## Contraintes techniques obligatoires
- Validation données côté client ET côté PHP (jamais l'un sans l'autre)
- Requêtes préparées PDO (protection injections SQL)
- Protection XSS sur tous les affichages
- Gestion sessions PHP pour auth
- Code commenté et lisible (évalué en soutenance)
- Commits réguliers : feat:, fix:, docs:

## Structure du projet
- /frontend : React (composants, pages, styles)
- /backend : PHP (API REST, retourne du JSON)
- /database : scripts SQL (init.sql + seed.sql avec données de test)

## Commandes
- Frontend : cd frontend && npm run dev
- Backend : cd backend && php -S localhost:8000
- BDD : MySQL port 3306, base "voyagevista"

## Important pour la soutenance
Chaque fonctionnalité doit être expliquable par l'équipe.
Ne pas générer du code que l'on ne comprend pas.
Toujours expliquer les choix techniques dans les commentaires.
