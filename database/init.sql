-- VoyageVista Database Schema
CREATE DATABASE IF NOT EXISTS voyagevista CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE voyagevista;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'prestataire', 'etudiant') DEFAULT 'etudiant',
    avatar_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE destinations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    country VARCHAR(100) NOT NULL,
    description TEXT,
    image_url VARCHAR(255),
    latitude DECIMAL(9,6),
    longitude DECIMAL(9,6),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE transports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('flight', 'train', 'bus', 'car', 'ferry') NOT NULL,
    company VARCHAR(100),
    departure_location VARCHAR(150) NOT NULL,
    arrival_location VARCHAR(150) NOT NULL,
    departure_time DATETIME NOT NULL,
    arrival_time DATETIME NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    seats_available INT DEFAULT 0,
    destination_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE SET NULL
);

CREATE TABLE accommodations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    type ENUM('hotel', 'hostel', 'apartment', 'villa', 'resort') NOT NULL,
    destination_id INT NOT NULL,
    address VARCHAR(255),
    price_per_night DECIMAL(10,2) NOT NULL,
    stars TINYINT CHECK (stars BETWEEN 1 AND 5),
    capacity INT DEFAULT 1,
    description TEXT,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE
);

CREATE TABLE activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    destination_id INT NOT NULL,
    duration_hours DECIMAL(4,1),
    price DECIMAL(10,2) DEFAULT 0.00,
    category VARCHAR(50),
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE
);

CREATE TABLE itineraries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('draft', 'confirmed', 'completed', 'cancelled') DEFAULT 'draft',
    total_budget DECIMAL(12,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    itinerary_id INT,
    type ENUM('transport', 'accommodation', 'activity') NOT NULL,
    reference_id INT NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    check_in DATE,
    check_out DATE,
    quantity INT DEFAULT 1,
    total_price DECIMAL(12,2) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (itinerary_id) REFERENCES itineraries(id) ON DELETE SET NULL
);

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Annuaire des universités partenaires Erasmus
CREATE TABLE universities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    city VARCHAR(100) NOT NULL,
    country VARCHAR(100) NOT NULL,
    website VARCHAR(255),
    erasmus_code VARCHAR(50),
    langue VARCHAR(100),                           -- Langue(s) d'enseignement principale(s)
    email_contact VARCHAR(150),                    -- Email du bureau des relations internationales
    description TEXT,                              -- Présentation générale de l'université
    nb_etudiants_etrangers INT UNSIGNED DEFAULT 0, -- Nombre d'étudiants internationaux accueillis
    destination_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE SET NULL
);

-- Messages de contact envoyés aux bureaux des relations internationales
CREATE TABLE university_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    university_id INT NOT NULL,
    user_id INT,                                    -- NULL si expéditeur non connecté
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    sender_name VARCHAR(100) NOT NULL,
    sender_email VARCHAR(150) NOT NULL,
    status ENUM('unread', 'read', 'replied') DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Logements spécifiques étudiants (résidences, colocations, studios, auberges)
CREATE TABLE student_housing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    type ENUM('residence', 'colocation', 'studio', 'auberge', 'famille_hote') NOT NULL,
    ville VARCHAR(100) NOT NULL,                   -- Ville du logement (facilite le filtrage)
    destination_id INT NOT NULL,
    university_id INT,
    address VARCHAR(255),
    prix_nuit DECIMAL(10,2),                       -- Prix à la nuit (auberges et courts séjours)
    price_per_month DECIMAL(10,2),                 -- Prix mensuel (résidences, colocations, studios)
    distance_campus_km DECIMAL(5,2),               -- Distance au campus principal en km
    available_rooms INT DEFAULT 0,
    description TEXT,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE,
    FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE SET NULL
);

-- Coûts moyens de référence par destination (table statistique, 1 ligne par ville)
CREATE TABLE destination_costs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    destination_id INT NOT NULL UNIQUE,
    monthly_rent_avg DECIMAL(10,2) NOT NULL,
    monthly_food_avg DECIMAL(10,2) NOT NULL,
    monthly_transport_avg DECIMAL(10,2) NOT NULL,
    monthly_leisure_avg DECIMAL(10,2) NOT NULL,
    monthly_total_avg DECIMAL(10,2) GENERATED ALWAYS AS (
        monthly_rent_avg + monthly_food_avg + monthly_transport_avg + monthly_leisure_avg
    ) STORED,
    currency CHAR(3) DEFAULT 'EUR',
    notes TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE
);

-- Estimations budgétaires personnalisées sauvegardées par chaque étudiant (calculateur)
CREATE TABLE budget_estimations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    destination VARCHAR(100) NOT NULL,                       -- Nom libre de la destination visée
    transport DECIMAL(10,2) DEFAULT 0.00,                   -- Coût aller-retour transport
    logement DECIMAL(10,2) DEFAULT 0.00,                    -- Coût total logement sur la durée
    activites DECIMAL(10,2) DEFAULT 0.00,                   -- Budget activités et loisirs
    vie_quotidienne_par_jour DECIMAL(10,2) DEFAULT 0.00,    -- Dépenses jour : repas, transports locaux
    nb_jours INT NOT NULL DEFAULT 30,                       -- Durée totale du séjour en jours
    -- Colonne calculée automatiquement par MySQL
    total_calcule DECIMAL(12,2) GENERATED ALWAYS AS (
        transport + logement + activites + (vie_quotidienne_par_jour * nb_jours)
    ) STORED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Informations visa et démarches administratives par destination et zone de nationalité
CREATE TABLE visa_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    destination_id INT NOT NULL,
    nationality_zone ENUM('EU', 'non-EU', 'tous') NOT NULL DEFAULT 'tous',
    nationalite VARCHAR(100),                   -- Description textuelle (ex : "Ressortissants UE/EEE")
    visa_required TINYINT(1) DEFAULT 0,
    visa_type VARCHAR(100),                     -- Intitulé officiel du visa requis
    duree_max_jours INT,                        -- Durée maximale de séjour autorisée
    delai_traitement_jours INT,                 -- Délai de traitement de la demande en jours
    cost_eur DECIMAL(8,2) DEFAULT 0.00,
    lien_officiel VARCHAR(500),                 -- URL officielle pour la demande de visa
    requirements TEXT,                          -- Documents requis
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE
);
