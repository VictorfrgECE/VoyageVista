# VoyageVista — Guide pour Claude Code

## Stack technique
- Frontend : React (Vite ou CRA)
- Backend : PHP 8+ (API REST)
- Base de données : MySQL
- Séparation stricte frontend/backend (architecture client-serveur)

## Contraintes projet (ECE ING2)
- TOUJOURS séparer frontend et backend
- Validation des données côté client ET côté serveur (PHP)
- Gestion des sessions PHP pour l'authentification
- Prévenir les failles XSS et injections SQL (PDO avec requêtes préparées)
- Code lisible, commenté, maintenable (on sera évalués dessus)
- Commits réguliers et explicites (convention : feat:, fix:, docs:)

## Architecture
- /frontend : composants React, pages, styles
- /backend : routes PHP, contrôleurs, modèles
- /backend/api : endpoints REST (répondent en JSON)
- /database : scripts SQL (init.sql, seed.sql)

## Rôles utilisateurs
- Admin : gestion globale, modération
- Prestataire : propose destinations, hébergements, activités
- Voyageur : recherche, réserve, compose des itinéraires

## Fonctionnalités prioritaires
1. Auth (inscription/connexion/sessions)
2. Catalogue destinations avec recherche/filtres
3. Transport + hébergements avec disponibilités
4. Panier de voyage + validation (paiement simulé)
5. Notifications

## Commandes utiles
- `cd frontend && npm run dev` : lancer le frontend React
- `cd backend && php -S localhost:8000` : lancer le backend PHP
- Base de données : MySQL sur port 3306, BDD "voyagevista"

## Ce que Claude NE doit PAS faire
- Ne pas utiliser de templates complets tout faits (interdit par le sujet)
- Ne pas générer de code que l'équipe ne comprend pas
- Toujours expliquer les choix techniques dans les commentaires
