-- VoyageVista – Données de test
-- Mot de passe de tous les comptes : Test1234!
-- Hash bcrypt (cost 10) : password_hash('Test1234!', PASSWORD_BCRYPT)
USE voyagevista;

-- ─────────────────────────────────────────────
-- USERS (2 admins · 3 prestataires · 5 étudiants)
-- ─────────────────────────────────────────────
INSERT INTO users (name, email, password, role) VALUES
('Admin Principal',   'admin@voyagevista.fr',       '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'admin'),
('Admin Support',     'support@voyagevista.fr',      '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'admin'),
('EuroTravel Agency', 'contact@eurotravel.fr',       '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'prestataire'),
('StudyAbroad Pro',   'booking@studyabroad.fr',      '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'prestataire'),
('CampusHost Europe', 'info@campushost.eu',          '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'prestataire'),
('Alice Martin',      'alice.martin@ece-paris.fr',   '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'etudiant'),
('Thomas Bernard',    'thomas.bernard@ece-paris.fr', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'etudiant'),
('Camille Dubois',    'camille.dubois@ece-paris.fr', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'etudiant'),
('Leo Petit',         'leo.petit@ece-paris.fr',      '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'etudiant'),
('Sofia Garcia',      'sofia.garcia@ece-paris.fr',   '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'etudiant');

-- ─────────────────────────────────────────────
-- DESTINATIONS (10 villes Erasmus populaires)
-- ─────────────────────────────────────────────
INSERT INTO destinations (name, country, description, latitude, longitude) VALUES
('Barcelone',  'Espagne',   'Capitale catalane : architecture moderniste, plages urbaines et vie nocturne animee. Destination Erasmus n°1 en Europe.', 41.385064,  2.173403),
('Berlin',     'Allemagne', 'Metropole culturelle et artistique. Loyers abordables, scene musicale unique, vie etudiante cosmopolite.',                52.520008, 13.404954),
('Amsterdam',  'Pays-Bas',  'Ville de canaux et de velos. Tolerance, musees de classe mondiale, universites a rayonnement international.',             52.367600,  4.904139),
('Rome',       'Italie',    'Capitale eternelle. 2 500 ans d\'histoire en plein air et une gastronomie incomparable pour les etudiants.',              41.902782, 12.496366),
('Lisbonne',   'Portugal',  'Destination emergente et abordable : climat, tramways historiques et scene start-up dynamique.',                          38.722252, -9.139337),
('Prague',     'Tcheque',   'Joyau d\'Europe centrale. Centre medieval preserve et cout de vie parmi les plus bas du continent.',                       50.075538, 14.437800),
('Vienne',     'Autriche',  'Ville imperiale et musicale. Qualite de vie exceptionnelle, universites parmi les meilleures d\'Europe centrale.',        48.208176, 16.373819),
('Budapest',   'Hongrie',   'La "Paris de l\'Est". Bains thermaux, architecture baroque et prix ultra-competitifs pour les etudiants.',                47.497913, 19.040236),
('Dublin',     'Irlande',   'Capitale anglophone de l\'Europe. Ideal pour une experience internationale dans un environnement anglophone.',             53.349805, -6.260310),
('Copenhague', 'Danemark',  'Modele mondial de durabilite et bien-etre. Enseignement superieur d\'excellence dans un cadre de vie ideal.',             55.676098, 12.568337);

-- ─────────────────────────────────────────────
-- UNIVERSITIES (5 partenaires Erasmus avec détails)
-- ─────────────────────────────────────────────
INSERT INTO universities (name, city, country, website, erasmus_code, langue, email_contact, description, nb_etudiants_etrangers, destination_id) VALUES
(
    'Universitat de Barcelona', 'Barcelone', 'Espagne',
    'https://www.ub.edu', 'E BARCELO03',
    'Catalan, Castillan',
    'international@ub.edu',
    'Fondee en 1450, l\'UB est la principale universite de Barcelone avec 63 000 etudiants. 3e universite europeenne en nombre d\'Erasmus accueillis. Forte en droit, medecine et sciences sociales.',
    6500, 1
),
(
    'Freie Universitaet Berlin', 'Berlin', 'Allemagne',
    'https://www.fu-berlin.de', 'D BERLIN02',
    'Allemand, Anglais',
    'erasmus@fu-berlin.de',
    'Universite d\'excellence fondee en 1948, specialisee en sciences humaines, droit et sciences naturelles. Membre de l\'Alliance Europaea, 35 000 etudiants dont 25 % internationaux.',
    8200, 2
),
(
    'Universiteit van Amsterdam', 'Amsterdam', 'Pays-Bas',
    'https://www.uva.nl', 'NL AMSTERD01',
    'Neerlandais, Anglais',
    'studyabroad@uva.nl',
    'Fondee en 1632, l\'UvA accueille 42 000 etudiants et propose pres de 150 programmes en anglais. Classee top 60 mondial. Ideale pour economie, droit et sciences sociales.',
    11000, 3
),
(
    'Sapienza Universita di Roma', 'Rome', 'Italie',
    'https://www.uniroma1.it', 'I ROMA01',
    'Italien',
    'erasmus@uniroma1.it',
    'Fondee en 1303, la Sapienza est la plus grande universite d\'Europe avec 113 000 etudiants. Partenaire Erasmus+ depuis la creation du programme. Excellence en droit, architecture et ingenierie.',
    14000, 4
),
(
    'Universidade de Lisboa', 'Lisbonne', 'Portugal',
    'https://www.ulisboa.pt', 'P LISBOA01',
    'Portugais, Anglais',
    'international@ulisboa.pt',
    'Issue de la fusion des universites de Lisbonne et Technique (2013), l\'ULisboa regroupe 48 000 etudiants et 18 facultes. 300 accords Erasmus+ dans 30 pays. Reconnue pour ingenierie et gestion.',
    7500, 5
);

-- ─────────────────────────────────────────────
-- TRANSPORTS (vols + trains Paris vers chaque ville)
-- ─────────────────────────────────────────────
INSERT INTO transports (type, company, departure_location, arrival_location, departure_time, arrival_time, price, seats_available, destination_id) VALUES
('flight', 'Vueling',    'Paris CDG',        'Barcelone BCN',       '2026-09-01 06:30:00', '2026-09-01 08:45:00',  89.99, 180, 1),
('flight', 'EasyJet',   'Paris CDG',        'Barcelone BCN',       '2026-09-01 14:20:00', '2026-09-01 16:40:00',  74.99, 156, 1),
('train',  'Renfe AVE', 'Paris Gare de Lyon','Barcelone Sants',     '2026-09-01 09:40:00', '2026-09-01 15:50:00',  59.00, 300, 1),
('flight', 'Air France', 'Paris CDG',        'Berlin BER',          '2026-09-01 07:15:00', '2026-09-01 09:10:00', 109.99, 160, 2),
('flight', 'EasyJet',   'Paris CDG',        'Berlin BER',          '2026-09-01 18:00:00', '2026-09-01 19:55:00',  84.99, 156, 2),
('flight', 'Transavia', 'Paris ORY',        'Amsterdam AMS',       '2026-09-01 08:00:00', '2026-09-01 09:25:00',  69.99, 140, 3),
('flight', 'KLM',       'Paris CDG',        'Amsterdam AMS',       '2026-09-01 11:30:00', '2026-09-01 12:55:00', 119.99, 200, 3),
('train',  'Thalys',    'Paris Nord',       'Amsterdam Centraal',  '2026-09-01 10:25:00', '2026-09-01 13:53:00',  49.00, 400, 3),
('flight', 'ITA Airways','Paris CDG',       'Rome FCO',            '2026-09-02 07:40:00', '2026-09-02 09:55:00',  99.99, 170, 4),
('flight', 'Ryanair',   'Paris BVA',        'Rome CIA',            '2026-09-02 06:30:00', '2026-09-02 08:45:00',  39.99, 189, 4),
('flight', 'TAP Air',   'Paris CDG',        'Lisbonne LIS',        '2026-09-02 08:50:00', '2026-09-02 11:20:00',  94.99, 150, 5),
('flight', 'EasyJet',   'Paris CDG',        'Lisbonne LIS',        '2026-09-02 14:15:00', '2026-09-02 16:45:00',  79.99, 156, 5),
('flight', 'Ryanair',   'Paris BVA',        'Prague PRG',          '2026-09-03 06:00:00', '2026-09-03 08:25:00',  44.99, 189, 6),
('flight', 'Wizz Air',  'Paris ORY',        'Prague PRG',          '2026-09-03 12:10:00', '2026-09-03 14:35:00',  54.99, 180, 6),
('flight', 'Austrian',  'Paris CDG',        'Vienne VIE',          '2026-09-03 09:20:00', '2026-09-03 11:15:00', 114.99, 160, 7),
('flight', 'Lauda',     'Paris ORY',        'Vienne VIE',          '2026-09-03 16:30:00', '2026-09-03 18:25:00',  69.99, 180, 7),
('flight', 'Ryanair',   'Paris BVA',        'Budapest BUD',        '2026-09-04 07:15:00', '2026-09-04 09:55:00',  49.99, 189, 8),
('flight', 'Wizz Air',  'Paris ORY',        'Budapest BUD',        '2026-09-04 13:00:00', '2026-09-04 15:40:00',  59.99, 180, 8),
('flight', 'Aer Lingus','Paris CDG',        'Dublin DUB',          '2026-09-05 07:00:00', '2026-09-05 08:30:00', 104.99, 150, 9),
('flight', 'Ryanair',   'Paris BVA',        'Dublin DUB',          '2026-09-05 12:45:00', '2026-09-05 14:20:00',  59.99, 189, 9),
('flight', 'SAS',       'Paris CDG',        'Copenhague CPH',      '2026-09-05 08:30:00', '2026-09-05 11:15:00', 129.99, 150, 10),
('flight', 'EasyJet',   'Paris CDG',        'Copenhague CPH',      '2026-09-05 15:00:00', '2026-09-05 17:45:00',  99.99, 156, 10);

-- ─────────────────────────────────────────────
-- ACCOMMODATIONS (hôtels et auberges grand public)
-- ─────────────────────────────────────────────
INSERT INTO accommodations (name, type, destination_id, address, price_per_night, stars, capacity, description) VALUES
('Hostel One Paralelo',           'hostel',    1, 'Carrer de Blai 55, Barcelone',             22.00, 2,  8, 'Auberge animee dans Poble Sec, 10 min du Barrio Gothic.'),
('Aparthotel Bcn Rambla',         'apartment', 1, 'La Rambla 122, Barcelone',                 75.00, 3,  2, 'Studio climatise sur les Ramblas, ideal 1-4 semaines.'),
('Generator Berlin',              'hostel',    2, 'Storkower Str. 160, Berlin',               19.00, 2, 10, 'Design hostel a Prenzlauer Berg, piscine sur le toit.'),
('Aparthaus Mitte',               'apartment', 2, 'Linienstr. 40, Berlin-Mitte',              68.00, 3,  2, 'Appartement moderne au coeur de Berlin-Mitte.'),
('Stayokay Amsterdam Vondelpark', 'hostel',    3, 'Zandpad 5, Amsterdam',                     28.00, 2,  8, 'Auberge officielle au bord du Vondelpark, velos disponibles.'),
('The Student Hotel Amsterdam',   'hotel',     3, 'Wibautstraat 129, Amsterdam',              95.00, 4,  2, 'Hotel hybride pour longs sejours etudiants, coworking inclus.'),
('The Beehive Rome',              'hostel',    4, 'Via Marghera 8, Rome',                     26.00, 2,  6, 'Petite auberge familiale a 5 min de la Gare Termini.'),
('Aparthotel Trastevere',         'apartment', 4, 'Via della Lungara 10, Rome',               80.00, 3,  2, 'Studio renove dans le quartier Trastevere.'),
('Lisbon Lounge Hostel',          'hostel',    5, 'Rua de Sao Nicolau 41, Lisbonne',          20.00, 2,  8, 'Auberge primee en plein Baixa, terrasse vue sur le Tage.'),
('LX Factory Studios',            'apartment', 5, 'Rua Rodrigues de Faria 103, Lisbonne',     70.00, 3,  2, 'Studios design dans le complexe creatif LX Factory.'),
('Czech Inn Prague',              'hostel',    6, 'Francouzska 76, Prague',                   16.00, 2, 10, 'Auberge primee a Vinohrady, quartier prise des etudiants.'),
('Prague Old Town Apartment',     'apartment', 6, 'Celetna 10, Prague',                       60.00, 3,  2, 'Appartement en plein coeur de la Vieille Ville de Prague.'),
('Wombats Vienna',                'hostel',    7, 'Mariahilfer Str. 137, Vienne',             22.00, 2,  8, 'Hostel moderne pres de la rue commercante, bar rooftop.'),
('Das Tigra Wien',                'hotel',     7, 'Herrengasse 12, Vienne',                   89.00, 3,  2, 'Boutique-hotel en Innenstadt, 5 min de la Hofburg.'),
('Maverick City Lodge Budapest',  'hostel',    8, 'Pest-Buda u. 3, Budapest',                 14.00, 2,  8, 'Auberge centrale a 10 min du Parlement, bar et toit-terrasse.'),
('Budapest Rooms Keleti',         'apartment', 8, 'Keleti palyaudvar ter 1, Budapest',        50.00, 3,  2, 'Appartements pres de la gare Keleti.'),
('Generator Dublin',              'hostel',    9, 'Smithfield Square, Dublin',                29.00, 2, 10, 'Hostel design a Smithfield, proche du Temple Bar.'),
('Maldron Hotel Smithfield',      'hotel',     9, 'Smithfield Village, Dublin',              110.00, 3,  2, 'Hotel 3 etoiles bien situe, petit-dejeuner irlandais inclus.'),
('Steel House Copenhagen',        'hostel',   10, 'Herholdtsgade 6, Copenhague',              27.00, 2,  8, 'Hostel prime en plein centre de Copenhague, velos disponibles.'),
('CPH Living Apartments',         'apartment',10, 'Langebrogade 1C, Copenhague',              95.00, 3,  2, 'Appartements flottants sur le canal de Copenhague.');

-- ─────────────────────────────────────────────
-- STUDENT HOUSING (résidences, colocations, studios, auberges étudiantes)
-- prix_nuit = price_per_month / 30 pour faciliter la comparaison court/long séjour
-- ─────────────────────────────────────────────
INSERT INTO student_housing (name, type, ville, destination_id, university_id, address, prix_nuit, price_per_month, distance_campus_km, available_rooms, description) VALUES
-- Barcelone
('Residencia Penyafort-Montserrat', 'residence',  'Barcelone', 1, 1,    'Carrer de Mozart 2, Barcelone',          17.33, 520.00,  0.5, 12, 'Residence UB mixte, tout inclus. Cafeteria, salle de sport, wifi haut debit.'),
('Piso Compartido Gracia',          'colocation', 'Barcelone', 1, NULL, 'Carrer Verdi 45, Barcelone',             12.67, 380.00,  1.2,  3, 'Colocation 4 personnes dans le quartier Gracia, 10 min metro de la fac.'),
('Studio Poble Nou',                'studio',     'Barcelone', 1, NULL, 'Carrer Pallars 123, Barcelone',          21.67, 650.00,  2.0,  2, 'Studio meuble a Poble Nou, proche des universites polytechniques UPC.'),
('Auberge Be Sound BCN',            'auberge',    'Barcelone', 1, NULL, 'Carrer Nou de la Rambla 91, Barcelone',  18.00, NULL,    1.8,  6, 'Auberge etudiante avec dortoirs 6-8 personnes, casiers fermes, cuisine.'),
-- Berlin
('Studentenwerk Berlin Mitte',      'residence',  'Berlin',    2, 2,    'Luisenstr. 56, Berlin',                  10.33, 310.00,  0.2, 20, 'Residence officielle Studentenwerk Berlin, chambre simple, wifi inclus.'),
('WG-Zimmer Prenzlauer Berg',       'colocation', 'Berlin',    2, NULL, 'Kastanienallee 12, Berlin',              14.00, 420.00,  1.5,  2, 'Chambre en WG dans un appartement de 3 etudiants a Prenzlberg.'),
('Jugendherberge Berlin Mitte',     'auberge',    'Berlin',    2, NULL, 'Klosterstr. 68, Berlin',                 22.00, NULL,    0.9,  8, 'Auberge de jeunesse DJH labelisee, dortoirs 4-8 places, petit-dej inclus.'),
-- Amsterdam
('DUWO Uilenstede Amsterdam',       'residence',  'Amsterdam', 3, 3,    'Uilenstede 103, Amstelveen',             18.67, 560.00,  1.0, 35, 'Plus grande residence etudiante des Pays-Bas, navette campus UvA incluse.'),
('Kamer Jordaan Amsterdam',         'colocation', 'Amsterdam', 3, NULL, 'Egelantiersgracht 10, Amsterdam',        18.33, 550.00,  3.0,  1, 'Chambre dans magnifique appartement du Jordaan avec vue sur canal.'),
('Shelter City Amsterdam',          'auberge',    'Amsterdam', 3, NULL, 'Barndesteeg 21, Amsterdam',              27.00, NULL,    2.5,  4, 'Auberge chretienne non-confessionnelle, ambiance internationale, tres securisee.'),
-- Rome
('Casa dello Studente Sapienza',    'residence',  'Rome',      4, 4,    'Viale Regina Elena 295, Rome',            9.33, 280.00,  0.3, 18, 'Residence officielle Sapienza, tarif conventionne pour Erasmus.'),
('Stanza Condivisa Trastevere',     'colocation', 'Rome',      4, NULL, 'Via Trastevere 78, Rome',                11.67, 350.00,  1.8,  2, 'Chambre dans appartement romain typique, 4 etudiants, cuisine equipee.'),
('Yes! Hostel Roma',                'auberge',    'Rome',      4, NULL, 'Via Magenta 15, Rome',                   23.00, NULL,    0.6,  5, 'Auberge etudiante pres de Termini, dortoirs climatises, bar rooftop.'),
-- Lisbonne
('Residencia Universitaria Lisboa', 'residence',  'Lisbonne',  5, 5,    'Alameda da Universidade, Lisbonne',      10.67, 320.00,  0.1, 25, 'Residence partenaire ULisboa, petit-dejeuner et wifi inclus.'),
('Quarto em Alfama',                'colocation', 'Lisbonne',  5, NULL, 'Rua dos Remedios 50, Lisbonne',          12.67, 380.00,  2.5,  2, 'Chambre dans appartement historique avec vue sur le Tage.'),
-- Prague
('Kolej Vetrnik Prague',            'residence',  'Prague',    6, NULL, 'Thakurova 1, Prague',                     6.00, 180.00,  3.0, 40, 'Residence CVUT, la moins chere de Prague, ideale pour non-EU.'),
('Pokoj v Zizkove Prague',          'colocation', 'Prague',    6, NULL, 'Seifertova 22, Prague',                   8.67, 260.00,  2.0,  2, 'Chambre en colocation dans le quartier boheme de Zizkov.'),
-- Vienne
('OeH Wohnheim TU Wien',           'residence',  'Vienne',    7, NULL, 'Wiedner Hauptstr. 8, Vienne',            13.00, 390.00,  0.8, 15, 'Residence etudiante proche TU Wien et WU Wien, transport inclus.'),
('WG Neubau Vienne',                'colocation', 'Vienne',    7, NULL, 'Neubaugasse 34, Vienne',                 15.00, 450.00,  1.5,  3, 'Colocation 5 personnes dans le 7e arrondissement branche de Vienne.'),
-- Budapest
('Kollegium Eotvos Budapest',       'residence',  'Budapest',  8, NULL, 'Menesi ut 11-13, Budapest',               5.00, 150.00,  1.2, 30, 'Prestigieux college-residence lie a l\'ELTE, prix ultra-competitifs.'),
('Szoba Belvaros Budapest',         'colocation', 'Budapest',  8, NULL, 'Vaci utca 3, Budapest',                   6.67, 200.00,  0.5,  4, 'Chambre en colocation en plein centre de Budapest, idealement situe.'),
-- Dublin
('Griffith Hall Dublin',            'residence',  'Dublin',    9, NULL, 'Griffith Avenue, Dublin',                21.67, 650.00,  4.0, 20, 'Residence privee populaire Erasmus, navette UCD, petit-dej inclus.'),
('Houseshare Ranelagh Dublin',      'colocation', 'Dublin',    9, NULL, 'Charleston Road, Dublin',                23.33, 700.00,  3.0,  2, 'Chambre dans maison victorienne, 30 min en bus du Trinity College.'),
-- Copenhague
('KKIK Kollegium Copenhague',       'residence',  'Copenhague',10, NULL, 'Tagensvej 70, Copenhague',              16.00, 480.00,  1.5, 18, 'Residence etudiante mixte, proche du metro M1, velos disponibles.'),
('Lejlighed Norrebro Copenhague',   'colocation', 'Copenhague',10, NULL, 'Norrebrogade 45, Copenhague',           20.00, 600.00,  2.0,  2, 'Chambre dans appartement typique a Norrebro, quartier vivant et abordable.');

-- ─────────────────────────────────────────────
-- ACTIVITIES
-- ─────────────────────────────────────────────
INSERT INTO activities (name, description, destination_id, duration_hours, price, category) VALUES
('Visite guidee Barrio Gothic',       'Decouverte a pied du quartier gothique medieval.',                        1, 2.0,   0.00, 'culture'),
('Sagrada Familia + guide',           'Visite de l\'oeuvre de Gaudi avec guide francophone.',                    1, 2.5,  26.00, 'culture'),
('Surf a la Barceloneta',             'Initiation surf sur la plage de Barcelone, materiel inclus.',             1, 3.0,  40.00, 'sport'),
('Tour street art Berlin',            'Parcours street art dans Friedrichshain et Kreuzberg.',                   2, 2.0,   0.00, 'culture'),
('Escape Room Guerre Froide Berlin',  'Escape room theme Guerre Froide, 2 a 6 joueurs.',                        2, 1.5,  25.00, 'loisir'),
('Velo Canal tour Amsterdam',         'Location velo et itineraire des canaux patrimoniaux.',                    3, 3.0,  15.00, 'sport'),
('Musee Van Gogh Amsterdam',          'Billet coupe-file pour le plus grand musee Van Gogh.',                    3, 2.0,  20.00, 'culture'),
('Colisee et Forum Romain',           'Billet combine Colisee, Palatin et Forum Romain.',                        4, 3.5,  16.00, 'culture'),
('Cours de cuisine italienne Roma',   'Atelier pates fraiches et tiramisu avec chef romain.',                    4, 3.0,  65.00, 'gastronomie'),
('Tramway 28 et Alfama a pied',       'Parcours en tramway vintage suivi de la visite d\'Alfama.',               5, 2.5,   3.00, 'culture'),
('Degustation pasteis de nata',       'Atelier autour des patisseries typiques portugaises.',                    5, 1.5,  18.00, 'gastronomie'),
('Croisiere sur la Vltava Prague',    'Croisiere commentee au coucher de soleil.',                               6, 2.0,  19.00, 'loisir'),
('Concert de musique classique',      'Concert dans un palais baroque viennois.',                                7, 2.0,  39.00, 'culture'),
('Bains Szechenyi Budapest',          'Journee dans les thermes historiques de Budapest.',                       8, 4.0,  21.00, 'bien-etre'),
('Pub Crawl Temple Bar Dublin',       'Soiree guidee dans les meilleurs pubs de Dublin.',                        9, 4.0,  15.00, 'loisir'),
('Chateaux de Copenhague',            'Visite du Palais de Christiansborg et de Rosenborg.',                    10, 3.0,  18.00, 'culture');

-- ─────────────────────────────────────────────
-- DESTINATION COSTS (coûts de référence moyens par ville, anciennement budget_estimations)
-- ─────────────────────────────────────────────
INSERT INTO destination_costs (destination_id, monthly_rent_avg, monthly_food_avg, monthly_transport_avg, monthly_leisure_avg, currency, notes) VALUES
( 1, 700.00, 350.00,  55.00, 200.00, 'EUR', 'Loyer residence UB. Carte T-Jove mensuelle 40 EUR. Ticket restaurant UB ~4 EUR.'),
( 2, 560.00, 300.00,  86.00, 170.00, 'EUR', 'BVG Semesterticket ~29 EUR/mois. Loyer colocation centre ~600 EUR.'),
( 3, 850.00, 380.00,  98.00, 220.00, 'EUR', 'Marche tendu. OV-chipkaart mensuel ~100 EUR. Logement DUWO indispensable a reserver tot.'),
( 4, 600.00, 320.00,  35.00, 160.00, 'EUR', 'Residence Sapienza ~280 EUR. Mensa universitaire < 4 EUR. BIT mensuel 35 EUR.'),
( 5, 500.00, 270.00,  40.00, 140.00, 'EUR', 'La moins chere d\'Europe de l\'Ouest. Viva Viagem mensuel 40 EUR. Marches abondants.'),
( 6, 350.00, 220.00,  25.00, 110.00, 'EUR', 'Prague parmi les plus accessibles. PID mensuel 25 EUR. Mensa a 2-3 EUR le repas.'),
( 7, 680.00, 340.00,  48.00, 190.00, 'EUR', 'Wien Netzkarte mensuel 48 EUR. Mensa ~5-6 EUR. Musees gratuits le 1er dimanche.'),
( 8, 380.00, 220.00,  23.00, 110.00, 'EUR', 'Budapest ultra-accessible. BKK mensuel 23 EUR. Bains thermaux 15 EUR. Mensa 1-2 EUR.'),
( 9, 980.00, 450.00, 140.00, 260.00, 'EUR', 'Dublin la plus chere. Leap Card mensuel ~140 EUR. Loyers en constante hausse.'),
(10, 870.00, 430.00,  95.00, 240.00, 'EUR', 'Copenhague chere mais qualite de vie maximale. Rejsekort mensuel ~95 EUR.');

-- ─────────────────────────────────────────────
-- BUDGET ESTIMATIONS (calculs personnalisés par étudiant)
-- total_calcule est généré automatiquement par MySQL : transport + logement + activites + (vie_quotidienne_par_jour * nb_jours)
-- ─────────────────────────────────────────────
INSERT INTO budget_estimations (user_id, destination, transport, logement, activites, vie_quotidienne_par_jour, nb_jours) VALUES
-- Alice : Erasmus Barcelone 5 mois (150 jours)
(6, 'Barcelone', 90.00, 2600.00, 200.00, 15.00, 150),
-- Thomas : Stage Berlin 6 mois (180 jours)
(7, 'Berlin', 110.00, 1860.00, 150.00, 12.00, 180),
-- Sofia : Double diplôme Lisbonne 9 mois (270 jours)
(10, 'Lisbonne', 95.00, 2880.00, 180.00, 10.00, 270);

-- ─────────────────────────────────────────────
-- VISA INFO (réglementation France → destinations Erasmus)
-- nationality_zone EU = ressortissants UE/EEE · non-EU = hors espace Schengen
-- ─────────────────────────────────────────────
INSERT INTO visa_info (destination_id, nationality_zone, nationalite, visa_required, visa_type, duree_max_jours, delai_traitement_jours, cost_eur, lien_officiel, requirements, notes) VALUES
-- Barcelone
( 1, 'EU',     'Ressortissants UE/EEE', 0, NULL,                          NULL, NULL, 0.00,   NULL,
  NULL,
  'Carte d\'identite ou passeport valide. Libre circulation. Pas de visa pour les ressortissants UE.'),
( 1, 'non-EU', 'Hors UE/EEE',           1, 'Visa national etudiant D',     365,   90, 80.00,  'https://www.exteriores.gob.es/es/ServiciosalCiudadano/Paginas/Visados.aspx',
  'Lettre d\'admission, justificatif ressources >= 600 EUR/mois, assurance maladie, casier judiciaire.',
  'Deposer au Consulat d\'Espagne du pays de residence.'),
-- Berlin
( 2, 'EU',     'Ressortissants UE/EEE', 0, NULL,                          NULL, NULL, 0.00,   NULL,
  NULL,
  'Carte d\'identite ou passeport valide. Inscription Einwohnermeldeamt obligatoire sous 14 jours.'),
( 2, 'non-EU', 'Hors UE/EEE',           1, 'Nationales Visum etudiant',    365,   60, 75.00,  'https://www.auswaertiges-amt.de/de/visa-einreise-aufenthalt/visabestimmungen-node',
  'Lettre d\'admission, justificatif financier >= 720 EUR/mois ou Sperrkonto 10 332 EUR, assurance.',
  'Compte bloque Sperrkonto recommande : Deutsche Bank ou Fintiba.'),
-- Amsterdam
( 3, 'EU',     'Ressortissants UE/EEE', 0, NULL,                          NULL, NULL, 0.00,   NULL,
  NULL,
  'Carte d\'identite UE valide. Inscription au BRP (registre municipal) obligatoire a l\'arrivee.'),
( 3, 'non-EU', 'Hors UE/EEE',           1, 'MVV + Permis de sejour',       365,   60, 192.00, 'https://ind.nl/en/residence-permits/study',
  'Lettre d\'acceptation IND, justificatif financier >= 860 EUR/mois, assurance maladie neerlandaise.',
  'Dossier depose par l\'universite partenaire aupres de l\'IND.'),
-- Rome
( 4, 'EU',     'Ressortissants UE/EEE', 0, NULL,                          NULL, NULL, 0.00,   NULL,
  NULL,
  'Carte d\'identite ou passeport UE. Codice Fiscale a obtenir a l\'Agenzia delle Entrate.'),
( 4, 'non-EU', 'Hors UE/EEE',           1, 'Visto per studio D',           365,   90, 50.00,  'https://vistoperitalia.esteri.it',
  'Lettre d\'admission, justificatif ressources >= 400 EUR/mois, logement justifie, assurance.',
  'Permesso di soggiorno a demander a la Questura sous 8 jours d\'arrivee.'),
-- Lisbonne
( 5, 'EU',     'Ressortissants UE/EEE', 0, NULL,                          NULL, NULL, 0.00,   NULL,
  NULL,
  'Carte d\'identite ou passeport UE. NIF (numero fiscal) recommande pour ouvrir un compte bancaire.'),
( 5, 'non-EU', 'Hors UE/EEE',           1, 'Visto de estudo',              365,   60, 75.00,  'https://vistos.mne.gov.pt/en',
  'Lettre d\'admission, preuve financiere >= 760 EUR/mois, assurance maladie, extrait casier judiciaire.',
  'Deposer au Consulat du Portugal ou via VFS Global.'),
-- Prague
( 6, 'EU',     'Ressortissants UE/EEE', 0, NULL,                          NULL, NULL, 0.00,   NULL,
  NULL,
  'Carte d\'identite ou passeport UE. Enregistrement police etrangere sous 30 jours.'),
( 6, 'non-EU', 'Hors UE/EEE',           1, 'Long-stay visa etudiant',      365,   60, 100.00, 'https://www.mzv.cz/jnp/en/information_for_aliens/index.html',
  'Lettre d\'admission, preuves de logement et ressources, assurance maladie.',
  'Certains pays peuvent obtenir un permis de sejour directement sur place.'),
-- Vienne
( 7, 'EU',     'Ressortissants UE/EEE', 0, NULL,                          NULL, NULL, 0.00,   NULL,
  NULL,
  'Carte d\'identite ou passeport UE. Declaration Meldezettel obligatoire sous 3 jours.'),
( 7, 'non-EU', 'Hors UE/EEE',           1, 'Studentenvisum D',             365,   90, 100.00, 'https://www.bmi.gv.at/301/Fremdenpolizei_und_Grenzkontrolle',
  'Lettre d\'admission, Meldezettel, justificatif >= 930 EUR/mois, assurance maladie.',
  'Visa longue duree via le Consulat d\'Autriche competent.'),
-- Budapest
( 8, 'EU',     'Ressortissants UE/EEE', 0, NULL,                          NULL, NULL, 0.00,   NULL,
  NULL,
  'Carte d\'identite ou passeport UE. Enregistrement de residence conseille.'),
( 8, 'non-EU', 'Hors UE/EEE',           1, 'Visa etudiant D',              365,   60, 60.00,  'https://konzuliszolgalat.kormany.hu/en/visa-information',
  'Lettre d\'admission, preuve financiere, logement justifie, assurance maladie.',
  'Deposer a l\'ambassade de Hongrie ou Consulat competent.'),
-- Dublin
( 9, 'EU',     'Ressortissants UE/EEE', 0, NULL,                          NULL, NULL, 0.00,   NULL,
  NULL,
  'Passeport ou carte d\'identite UE. L\'Irlande n\'est pas dans Schengen mais est dans l\'UE.'),
( 9, 'non-EU', 'Hors UE/EEE',           1, 'Study Visa',                   365,   30, 60.00,  'https://www.irishimmigration.ie/coming-to-study-in-ireland',
  'Lettre d\'admission, preuve ressources >= 7 000 EUR pour 1 an, billet retour, hebergement confirme.',
  'Enregistrement GNIB / IRP obligatoire a l\'arrivee en Irlande.'),
-- Copenhague
(10, 'EU',     'Ressortissants UE/EEE', 0, NULL,                          NULL, NULL, 0.00,   NULL,
  NULL,
  'Carte d\'identite ou passeport UE. CPR number a demander a la mairie sous 5 jours.'),
(10, 'non-EU', 'Hors UE/EEE',           1, 'Residence permit etudiant',    365,   60, 205.00, 'https://www.nyidanmark.dk/en-GB',
  'Lettre d\'admission, preuve >= 6 243 DKK/mois, logement confirme, assurance maladie.',
  'Dossier en ligne via le portail SIRI (nyidanmark.dk).');

-- ─────────────────────────────────────────────
-- ITINERARIES (exemples étudiants)
-- ─────────────────────────────────────────────
INSERT INTO itineraries (user_id, title, description, start_date, end_date, status, total_budget) VALUES
(6,  'Erasmus Barcelone – Semestre 1',    'Semestre Erasmus a l\'Universitat de Barcelona, fac de droit.',          '2026-09-01', '2027-01-31', 'confirmed', 5800.00),
(7,  'Stage Berlin – 6 mois',             'Stage en entreprise a Berlin, quartier Mitte, en ingenierie logicielle.', '2026-09-15', '2027-03-15', 'confirmed', 5200.00),
(8,  'Erasmus Amsterdam – Semestre 1',    'Semestre d\'echange a l\'UvA, faculte d\'economie.',                      '2026-09-01', '2027-01-31', 'draft',     6500.00),
(9,  'Erasmus Prague – Semestre 2',       'Semestre Erasmus a l\'Universite Charles de Prague.',                     '2027-02-01', '2027-06-30', 'draft',     3800.00),
(10, 'Double diplome Lisbonne – 1 an',   'Annee complete a l\'Universidade de Lisboa en management.',                '2026-09-01', '2027-06-30', 'confirmed', 7500.00);

-- ─────────────────────────────────────────────
-- RESERVATIONS
-- ─────────────────────────────────────────────
INSERT INTO reservations (user_id, itinerary_id, type, reference_id, status, check_in, check_out, quantity, total_price, notes) VALUES
(6, 1, 'transport',     1, 'confirmed', NULL,         NULL,         1,   89.99, 'Vol Vueling CDG-BCN, siege fenetre demande.'),
(6, 1, 'accommodation', 1, 'confirmed', '2026-09-01', '2026-09-05', 4,   88.00, 'Hostel One, 4 nuits avant entree en residence.'),
(6, 1, 'accommodation', 1, 'confirmed', '2026-09-05', '2027-01-31', 1, 2600.00, 'Residencia Penyafort-Montserrat, 5 mois Erasmus.'),
(7, 2, 'transport',     4, 'confirmed', NULL,         NULL,         1,  109.99, 'Vol Air France CDG-BER, enregistrement en ligne.'),
(7, 2, 'accommodation', 4, 'confirmed', '2026-09-15', '2027-03-15', 1, 1860.00, 'Studentenwerk Berlin, 6 mois de stage.'),
(8, 3, 'transport',     6, 'pending',   NULL,         NULL,         1,   69.99, 'Vol Transavia ORY-AMS, attente confirmation.'),
(8, 3, 'accommodation', 6, 'pending',   '2026-09-01', '2027-01-31', 1, 2800.00, 'DUWO Uilenstede, candidature Erasmus soumise.'),
(10, 5, 'transport',   11, 'confirmed', NULL,         NULL,         1,   94.99, 'Vol TAP CDG-LIS, bagage cabine inclus.'),
(10, 5, 'accommodation',10,'confirmed', '2026-09-01', '2027-06-30', 1, 3200.00, 'Residencia Universitaria Lisboa, annee complete.');

-- ─────────────────────────────────────────────
-- NOTIFICATIONS
-- ─────────────────────────────────────────────
INSERT INTO notifications (user_id, title, message, type, is_read) VALUES
(6,  'Reservation confirmee',          'Votre vol Vueling Paris-Barcelone du 1er septembre est confirme. Bon voyage !', 'success', 1),
(6,  'Bienvenue sur VoyageVista',       'Decouvrez nos guides et conseils pour preparer votre semestre Erasmus.',        'info',    1),
(7,  'Reservation confirmee',          'Votre vol Air France Paris-Berlin du 15 septembre est confirme.',               'success', 1),
(7,  'Rappel documents',               'Pensez a verifier la validite de votre passeport (> 6 mois requis).',           'warning', 0),
(8,  'Reservation en attente',         'Votre reservation DUWO Amsterdam est en attente de confirmation (4-6 semaines).','info',   0),
(8,  'Document manquant',              'Votre lettre d\'acceptation UvA n\'a pas encore ete uploadee dans votre dossier.','warning', 0),
(9,  'Nouvelle destination disponible','Budapest vient d\'etre ajoutee avec 3 logements etudiants disponibles !',       'info',    0),
(10, 'Reservation confirmee',          'Votre reservation annuelle a la Residencia Universitaria Lisboa est confirmee.', 'success', 1),
(10, 'Info visa',                      'En tant que ressortissant UE, aucun visa n\'est requis pour le Portugal.',      'info',    1),
(1,  'Nouvel utilisateur inscrit',     'Leo Petit vient de creer un compte etudiant. Verifier son dossier Erasmus.',    'info',    0);
