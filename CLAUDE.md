# VoyageVista — Plateforme de voyage pour étudiants

## Contexte projet
Application web dynamique de planification de voyages
spécialisée pour les étudiants (ECE ING2 2026).
Positionnement : aider les étudiants à organiser
séjours académiques, échanges Erasmus, voyages de fin d'études.
Stack : React (frontend) + PHP API REST (backend) + MySQL

## Rôles utilisateurs
- Admin : modération globale, gestion des utilisateurs
- Prestataire : universités partenaires, agences logement
  étudiant, organisateurs d'activités jeunes
- Etudiant (voyageur) : planifie son séjour,
  contacte des universités, trouve logement étudiant

## Fonctionnalités standard
1. Auth : inscription/connexion, sessions PHP, 3 rôles
2. Catalogue destinations : filtres par budget étudiant,
   type de séjour (Erasmus, tourisme, stage)
3. Transport : recherche avec filtre petit budget,
   carte jeune, Interrail
4. Hébergements : résidences étudiantes, colocations,
   auberges jeunesse (pas que les hôtels)
5. Activités : activités étudiantes, soirées,
   visites culturelles, sport
6. Itinéraires : composition séjour complet
7. Notifications : rappels visa, inscription université,
   deadlines Erasmus
8. Panier + paiement simulé

## Fonctionnalités spécifiques étudiants
9. Annuaire universités : fiche par université
   (pays, langue, procédure admission, contacts),
   formulaire de contact simulé
10. Logement étudiant : résidences, colocations,
    filtres (budget/distance campus)
11. Calculateur budget étudiant : estimation coût
    total du séjour (transport + logement + activités
    + vie quotidienne)
12. Guide visa/démarches : infos administratives
    par destination (visa, CAF, sécurité sociale)
13. Communauté étudiante : avis et notes laissés
    par d'autres étudiants

## Contraintes techniques obligatoires (ECE)
- Séparation stricte frontend React / backend PHP
- Validation données côté client ET côté PHP
- Requêtes préparées PDO (protection injections SQL)
- Protection XSS sur tous les affichages
- Sessions PHP pour authentification
- Code commenté et lisible (évalué en soutenance)
- Commits réguliers : feat:, fix:, docs:
- Pas de templates complets tout faits

## Structure projet
- /frontend : React + Vite (composants, pages, styles)
- /backend : PHP API REST (retourne JSON)
- /database : init.sql + seed.sql (données test réalistes)

## Commandes
- Frontend : cd frontend && npm run dev (port 5173)
- Backend : cd backend && php -S localhost:8000
- BDD : MySQL port 3306, base "voyagevista"

## Style de code
- Commente chaque fonction PHP et composant React
- Nomme les variables en anglais, commentaires en français
- Explique toujours le pourquoi des choix de sécurité
- Chaque fonctionnalité doit être expliquable en soutenance

## Important
Ne pas générer de code que l'équipe ne comprend pas.
Toujours expliquer les choix techniques au fur et à mesure.