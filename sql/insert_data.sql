-- ============================================================
--  VoyageVista — Données de test
--  Mots de passe : Admin123! | User123! | Etudiant123!
-- ============================================================

USE voyagevista_db;

-- Vider les tables enfants en premier (respecte les FK)
DELETE FROM ESTIMATIONS_BUDGET;
DELETE FROM NOTIFICATIONS;
DELETE FROM ELEMENTS_ITINERAIRE;
DELETE FROM RESERVATIONS;
DELETE FROM ITINERAIRES;
DELETE FROM INFOS_VISA;
DELETE FROM LOGEMENTS_ETUDIANTS;
DELETE FROM UNIVERSITES;
DELETE FROM ACTIVITES;
DELETE FROM HEBERGEMENTS;
DELETE FROM TRANSPORTS;
DELETE FROM DESTINATIONS;
DELETE FROM UTILISATEURS;

-- Réinitialiser les auto-increment
ALTER TABLE UTILISATEURS        AUTO_INCREMENT = 1;
ALTER TABLE DESTINATIONS        AUTO_INCREMENT = 1;
ALTER TABLE TRANSPORTS          AUTO_INCREMENT = 1;
ALTER TABLE HEBERGEMENTS        AUTO_INCREMENT = 1;
ALTER TABLE ACTIVITES           AUTO_INCREMENT = 1;
ALTER TABLE UNIVERSITES         AUTO_INCREMENT = 1;
ALTER TABLE LOGEMENTS_ETUDIANTS AUTO_INCREMENT = 1;
ALTER TABLE INFOS_VISA          AUTO_INCREMENT = 1;
ALTER TABLE ITINERAIRES         AUTO_INCREMENT = 1;
ALTER TABLE ELEMENTS_ITINERAIRE AUTO_INCREMENT = 1;
ALTER TABLE RESERVATIONS        AUTO_INCREMENT = 1;
ALTER TABLE NOTIFICATIONS       AUTO_INCREMENT = 1;
ALTER TABLE ESTIMATIONS_BUDGET  AUTO_INCREMENT = 1;

-- ============================================================
--  UTILISATEURS (1 admin + 1 user + 1 etudiant)
-- ============================================================
INSERT INTO UTILISATEURS (nom, email, mot_de_passe, role) VALUES
('Administrateur', 'admin@voyagevista.fr',
 '$2y$10$kh6BZsYq27fPmBeNiSC8beKWCZimGYSSTlqXwbQz8I99oQi6lT9PG', 'admin'),
('Sophie Martin', 'sophie@example.fr',
 '$2y$10$It55TMqsTFdIymqfjKOsheu1Pn9kyHDqQAu1UmgHYw63zjS9hMZDS', 'user'),
('Lucas Durand', 'lucas@student.fr',
 '$2y$10$kSpHpDjCWbGwuP.j1Iy2neiQvDuE6HgwkA0NGA5unw4GVQ2Q6WDuy', 'etudiant');

-- ============================================================
--  DESTINATIONS (11 destinations variées)
-- ============================================================
INSERT INTO DESTINATIONS (nom, pays, description, categorie, latitude, longitude, image_url) VALUES
-- 1
('Paris', 'France',
 'La Ville Lumière, capitale romantique de l''art, de la mode et de la gastronomie. Incontournable pour tout voyageur en Europe.',
 'culture', 48.856613, 2.352222, 'img/destinations/paris.jpg'),
-- 2
('Barcelone', 'Espagne',
 'Une métropole vibrante entre mer et montagne, célèbre pour l''architecture de Gaudí, la Barceloneta et sa vie nocturne.',
 'plage', 41.385064, 2.173403, 'img/destinations/barcelone.jpg'),
-- 3
('Lisbonne', 'Portugal',
 'Capitale aux sept collines baignée de soleil, entre tramways vintage, azulejos colorés et fado mélancolique.',
 'ville', 38.722252, -9.139337, 'img/destinations/lisbonne.jpg'),
-- 4
('Prague', 'République tchèque',
 'La ville aux cent clochers. Un joyau médiéval préservé, idéal pour un séjour culturel à petit budget.',
 'culture', 50.075539, 14.437800, 'img/destinations/prague.jpg'),
-- 5
('Amsterdam', 'Pays-Bas',
 'La cité des canaux, des vélos et des musées mondiaux. Une ville ouverte et cosmopolite, très accessible en train.',
 'culture', 52.370216, 4.895168, 'img/destinations/amsterdam.jpg'),
-- 6
('Rome', 'Italie',
 'La Ville Éternelle, berceau de la civilisation occidentale. Chaque rue est un musée à ciel ouvert.',
 'culture', 41.902782, 12.496366, 'img/destinations/rome.jpg'),
-- 7
('Tokyo', 'Japon',
 'L''hyperville du futur entre tradition et modernité, temples shinto et quartiers néon, ramen et sushis.',
 'ville', 35.689487, 139.691711, 'img/destinations/tokyo.jpg'),
-- 8
('Bali', 'Indonésie',
 'L''île des dieux — rizières en terrasses, temples hindous, surf sur l''océan Indien et couchers de soleil sur Uluwatu.',
 'plage', -8.340539, 115.091949, 'img/destinations/bali.jpg'),
-- 9
('New York', 'États-Unis',
 'La ville qui ne dort jamais : skyline mythique, Central Park, Broadway et les meilleurs bagels du monde.',
 'ville', 40.712776, -74.005974, 'img/destinations/newyork.jpg'),
-- 10
('Bangkok', 'Thaïlande',
 'La capitale des temples dorés, des marchés flottants et du street-food épicé. Porte d''entrée de l''Asie du Sud-Est.',
 'aventure', 13.756331, 100.501762, 'img/destinations/bangkok.jpg'),
-- 11
('Berlin', 'Allemagne',
 'Capitale européenne de la culture alternative, de l''histoire contemporaine et de la scène musicale underground.',
 'culture', 52.520008, 13.404954, 'img/destinations/berlin.jpg');

-- ============================================================
--  TRANSPORTS (12 transports depuis Paris)
-- ============================================================
INSERT INTO TRANSPORTS (type, compagnie, lieu_depart, lieu_arrivee, prix, destination_id) VALUES
('train',  'Renfe/SNCF',         'Paris Gare de Lyon',   'Barcelone Sants',      89.00,  2),
('avion',  'TAP Air Portugal',   'Paris CDG',            'Lisbonne LIS',         79.00,  3),
('avion',  'EasyJet',            'Paris CDG',            'Prague PRG',           65.00,  4),
('train',  'Eurostar/Thalys',    'Paris Gare du Nord',   'Amsterdam Centraal',   59.00,  5),
('train',  'Trenitalia',         'Paris Gare de Lyon',   'Rome Termini',        119.00,  6),
('avion',  'Air France',         'Paris CDG',            'Tokyo NRT',           650.00,  7),
('avion',  'Singapore Airlines', 'Paris CDG',            'Bali DPS',            680.00,  8),
('avion',  'Air France',         'Paris CDG',            'New York JFK',        450.00,  9),
('avion',  'Thai Airways',       'Paris CDG',            'Bangkok BKK',         520.00, 10),
('train',  'Deutsche Bahn',      'Paris Gare de l''Est', 'Berlin Hbf',           49.00, 11),
('bus',    'FlixBus',            'Paris Bercy',          'Barcelone Nord',        35.00,  2),
('bus',    'FlixBus',            'Paris Bercy',          'Amsterdam Sloterdijk',  25.00,  5);

-- ============================================================
--  HEBERGEMENTS (22 hébergements, >= 2 par destination)
-- ============================================================
INSERT INTO HEBERGEMENTS (nom, type, prix_par_nuit, etoiles, description, destination_id) VALUES
-- Paris (1)
('Auberge Montmartre Backpackers', 'auberge', 22.00, NULL,
 'Auberge de jeunesse au coeur de Montmartre, ambiance internationale, petit-déjeuner inclus.', 1),
('Hôtel Bastille République', 'hotel', 85.00, 3,
 'Hôtel confortable à deux pas du Marais, idéal pour explorer Paris à pied.', 1),
-- Barcelone (2)
('Sant Jordi Hostel Arago', 'auberge', 18.00, NULL,
 'Auberge emblématique de Barcelone, rooftop avec vue, ambiance jeune et festive.', 2),
('Apartamentos Eixample Central', 'appartement', 65.00, NULL,
 'Appartements modernes dans le quartier Eixample, cuisine équipée et proximité du métro.', 2),
-- Lisbonne (3)
('Lisbon Lounge Hostel', 'auberge', 16.00, NULL,
 'Auberge primée, décor vintage portugais, au coeur du Bairro Alto.', 3),
('Apartamentos Alfama View', 'appartement', 72.00, NULL,
 'Appartements avec vue sur le Tage dans le quartier historique d''Alfama.', 3),
-- Prague (4)
('Czech Inn Hostel', 'auberge', 14.00, NULL,
 'Auberge design lauréate de prix internationaux, à 10 min à pied du centre historique.', 4),
('Hotel U Krize Smichov', 'hotel', 55.00, 2,
 'Hôtel simple et propre dans le quartier Smíchov, excellent rapport qualité-prix.', 4),
-- Amsterdam (5)
('Stayokay Amsterdam Vondelpark', 'auberge', 24.00, NULL,
 'Auberge officielle en plein Vondelpark, idéale pour profiter de la nature en ville.', 5),
('Appartement Jordaan Canal View', 'appartement', 110.00, NULL,
 'Appartement authentique avec vue sur le canal du quartier Jordaan.', 5),
-- Rome (6)
('The Yellow Hostel Roma', 'auberge', 19.00, NULL,
 'L''auberge la plus animée de Rome, bar rooftop, à 200m de la gare Termini.', 6),
('Hotel Artorius', 'hotel', 90.00, 3,
 'Hôtel boutique élégant à deux pas du Panthéon.', 6),
-- Tokyo (7)
('K''s House Tokyo Oasis', 'auberge', 26.00, NULL,
 'Référence des backpackers à Tokyo, staff bilingue, accès JR Yamanote.', 7),
('Aparthotel Shinjuku Stay', 'appartement', 95.00, NULL,
 'Studios modernes à Shinjuku, cuisine équipée, à 5 min du quartier Kabukicho.', 7),
-- Bali (8)
('Bamboo Bali Hostel Kuta', 'auberge', 12.00, NULL,
 'Auberge en bambou à Kuta, piscine, surf à 5 min à pied.', 8),
('Villa Ubud Hideaway', 'villa', 85.00, NULL,
 'Villa privée avec jardin tropical et piscine à Ubud, vue sur les rizières.', 8),
-- New York (9)
('HI NYC Hostel Upper West Side', 'auberge', 45.00, NULL,
 'La meilleure auberge de Manhattan, à deux pas de Central Park.', 9),
('Studio Brooklyn Williamsburg', 'appartement', 130.00, NULL,
 'Studio tendance à Williamsburg, Brooklyn, à 20 min de Manhattan en metro.', 9),
-- Bangkok (10)
('Lub d Bangkok Silom', 'auberge', 15.00, NULL,
 'Auberge design primée à Silom, proche du BTS et des temples.', 10),
('Siam Budget Hotel', 'hotel', 40.00, 2,
 'Hôtel propre et économique dans le quartier Siam, idéal pour les petits budgets.', 10),
-- Berlin (11)
('EastSeven Berlin Hostel', 'auberge', 17.00, NULL,
 'Auberge familiale à Prenzlauer Berg, petits-déjeuners faits maison.', 11),
('Apartment Mitte Art District', 'appartement', 78.00, NULL,
 'Appartement design dans le Mitte, à deux pas du Checkpoint Charlie.', 11);

-- ============================================================
--  ACTIVITES (17 activités)
-- ============================================================
INSERT INTO ACTIVITES (nom, prix, categorie, duree_heures, description, destination_id) VALUES
('Visite guidée du Louvre',         15.00, 'culture',     3.00,
 'Découverte des chefs-d''oeuvre du plus grand musée du monde avec un guide francophone.', 1),
('Croisière sur la Seine by night', 14.00, 'tourisme',    1.50,
 'Illuminations de Paris depuis la Seine, au départ du Pont d''Iéna.', 1),
('Visite de la Sagrada Família',    26.00, 'culture',     2.00,
 'Chef-d''oeuvre inachevé de Gaudí, visite coupe-file avec audioguide.', 2),
('Tour à vélo Barcelone',           25.00, 'sport',       3.00,
 'Parcours guidé le long de la Barceloneta et du Born à vélo électrique.', 2),
('Tour des azulejos de Lisbonne',   18.00, 'culture',     2.50,
 'Visite du Musée National de l''Azulejo et balade dans l''Alfama.', 3),
('Dégustation de Fado et Tapas',    35.00, 'gastronomie', 2.00,
 'Soirée fado dans un restaurant historique du Bairro Alto.', 3),
('Visite du Château de Prague',     15.00, 'culture',     3.00,
 'Exploration du plus grand château médiéval du monde, vue panoramique sur la ville.', 4),
('Visite du Rijksmuseum',           22.50, 'culture',     2.50,
 'Collection nationale néerlandaise : Rembrandt, Vermeer, objets d''art historiques.', 5),
('Visite du Colisée de Rome',       16.00, 'culture',     2.00,
 'Entrée coupe-file pour le Colisée et le Forum Romain.', 6),
('Cours de cuisine italienne',      55.00, 'gastronomie', 3.00,
 'Apprenez à faire des pâtes fraîches et un tiramisu avec un chef romain.', 6),
('Cérémonie du thé à Tokyo',        30.00, 'culture',     1.50,
 'Initiation traditionnelle à la cérémonie du thé dans un dojo de Harajuku.', 7),
('Cours de surf à Kuta Beach',      20.00, 'sport',       2.00,
 'Initiation au surf sur la célèbre plage de Kuta, moniteur certifié.', 8),
('Yoga au lever du soleil Ubud',    12.00, 'bien-etre',   1.50,
 'Séance de yoga au lever du soleil dans un studio entouré de rizières.', 8),
('Kayak des canaux Amsterdam',      18.00, 'sport',       2.00,
 'Explorez les canaux d''Amsterdam en kayak, guide inclus.', 5),
('Tour de Central Park en vélo',    15.00, 'sport',       2.00,
 'Location vélo + carte pour explorer Central Park en toute liberté.', 9),
('Visite du Grand Palais Royal',    10.00, 'culture',     2.00,
 'Complexe de temples bouddhistes et de palais royaux au coeur de Bangkok.', 10),
('Visite East Side Gallery Berlin',  0.00, 'culture',     1.50,
 'Le plus long fragment du Mur de Berlin transformé en galerie d''art à ciel ouvert.', 11);

-- ============================================================
--  UNIVERSITES (5 universités Erasmus)
-- ============================================================
INSERT INTO UNIVERSITES (nom, ville, pays, code_erasmus, langue, destination_id) VALUES
('Universitat de Barcelona',                'Barcelone', 'Espagne',           'E BARCELO01',  'Espagnol/Catalan',    2),
('Universidade de Lisboa',                  'Lisbonne',  'Portugal',          'P LISBOA01',   'Portugais',           3),
('Univerzita Karlova (Charles University)', 'Prague',    'République tchèque','CZ PRAHA07',   'Anglais/Tchèque',     4),
('Universiteit van Amsterdam',              'Amsterdam', 'Pays-Bas',          'NL AMSTERD01', 'Anglais/Néerlandais', 5),
('Freie Universität Berlin',                'Berlin',    'Allemagne',         'D BERLIN01',   'Allemand/Anglais',   11);

-- ============================================================
--  LOGEMENTS_ETUDIANTS (6 logements)
-- ============================================================
INSERT INTO LOGEMENTS_ETUDIANTS (nom, type, prix_par_mois, distance_campus_km, destination_id, universite_id) VALUES
('Résidence Universitaire Diagonal', 'residence', 420.00, 0.30,  2, 1),
('Colocation Eixample Estudiants',   'colocation', 350.00, 1.20, 2, 1),
('Residencia Universitaria Lisboa',  'residence', 380.00, 0.50,  3, 2),
('Studio Vinohrady Prague',          'studio',    320.00, 1.50,  4, 3),
('Colocation Jordaan Amsterdam',     'colocation', 550.00, 0.80, 5, 4),
('WG-Zimmer Prenzlauer Berg Berlin', 'colocation', 490.00, 1.00, 11, 5);

-- ============================================================
--  INFOS_VISA
-- ============================================================
INSERT INTO INFOS_VISA (zone_nationalite, visa_requis, type_visa, duree_max_jours, cout_eur, destination_id) VALUES
-- Destinations UE (pas de visa)
('UE', 0, NULL,                        NULL, NULL,  2),  -- Barcelone
('UE', 0, NULL,                        NULL, NULL,  3),  -- Lisbonne
('UE', 0, NULL,                        NULL, NULL,  4),  -- Prague
('UE', 0, NULL,                        NULL, NULL,  5),  -- Amsterdam
('UE', 0, NULL,                        NULL, NULL,  6),  -- Rome
('UE', 0, NULL,                        NULL, NULL, 11),  -- Berlin
-- Hors UE
('UE', 0, 'Exemption Schengen',         90, NULL,   9),  -- New York
('UE', 0, 'Visa on arrival',            30, NULL,   8),  -- Bali
('UE', 0, 'Visa on arrival / e-Visa',   30, NULL,  10),  -- Bangkok
('UE', 1, 'Tourist Visa',               90, 20.00,  7),  -- Tokyo
-- Hors UE (passeport non-européen)
('hors_UE', 1, 'Schengen Court Séjour', 90, 80.00,  2),
('hors_UE', 1, 'Schengen Court Séjour', 90, 80.00,  4),
('hors_UE', 1, 'B-2 Tourist Visa',     180, 185.00, 9);

-- ============================================================
--  ITINERAIRES de démonstration
-- ============================================================
INSERT INTO ITINERAIRES (titre, date_debut, date_fin, statut, budget_total, utilisateur_id) VALUES
('Escapade à Barcelone', '2026-07-10', '2026-07-17', 'confirme',  620.00, 2),
('Erasmus à Prague',     '2026-09-01', '2027-01-31', 'brouillon', 2800.00, 3);

-- ============================================================
--  RESERVATIONS de démonstration
-- ============================================================
INSERT INTO RESERVATIONS (type, reference_id, statut, prix_total, utilisateur_id, itineraire_id) VALUES
('transport',    1, 'confirme',  89.00, 2, 1),
('hebergement',  3, 'confirme', 126.00, 2, 1),
('activite',     3, 'confirme',  26.00, 2, 1);

-- ============================================================
--  NOTIFICATIONS de bienvenue
-- ============================================================
INSERT INTO NOTIFICATIONS (titre, message, type, utilisateur_id) VALUES
('Bienvenue sur VoyageVista !',
 'Votre compte a été créé avec succès. Explorez nos destinations et planifiez votre prochain voyage.',
 'info', 2),
('Bienvenue sur VoyageVista !',
 'Votre compte étudiant a été créé. Accédez à l''espace Étudiant pour trouver des universités et logements Erasmus.',
 'info', 3),
('Réservation confirmée — Barcelone',
 'Votre réservation pour le vol Paris vers Barcelone a bien été enregistrée. Bon voyage !',
 'confirmation', 2),
('Rappel : départ dans 7 jours',
 'Votre voyage à Barcelone commence le 10 juillet. Pensez à vérifier vos documents de voyage.',
 'rappel', 2);

-- ============================================================
--  ESTIMATION BUDGET (exemples)
-- ============================================================
INSERT INTO ESTIMATIONS_BUDGET (nom_destination, transport, logement, activites, nb_jours, total_calcule, utilisateur_id) VALUES
('Barcelone', 89.00, 126.00, 51.00, 7, 266.00, 2),
('Prague',    65.00,  98.00, 15.00, 7, 178.00, 3);
