-- ============================================================
--  VoyageVista — FICHIER 1 : Structure de la base de données
-- ============================================================
--  SAFE : Ce fichier peut être exécuté plusieurs fois sans erreur.
--  - Les tables déjà existantes ne sont pas modifiées (IF NOT EXISTS).
--  - Le rôle 'prestataire' est ajouté s'il n'existe pas encore.
--
--  Instructions :
--  1. Ouvrir phpMyAdmin → onglet "SQL" ou "Importer"
--  2. Importer/coller ce fichier en PREMIER
--  3. Ensuite importer 2_donnees.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS voyagevista_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE voyagevista_db;

-- ------------------------------------------------------------
--  UTILISATEURS
--  Le rôle 'prestataire' est inclus dès la création.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS UTILISATEURS (
    id               INT          NOT NULL AUTO_INCREMENT,
    nom              VARCHAR(100) NOT NULL,
    email            VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe     VARCHAR(255) NOT NULL,
    role             ENUM('admin','user','etudiant','prestataire') NOT NULL DEFAULT 'user',
    date_inscription DATETIME     DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT PK_UTILISATEURS PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  DESTINATIONS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS DESTINATIONS (
    id          INT          NOT NULL AUTO_INCREMENT,
    nom         VARCHAR(150) NOT NULL,
    pays        VARCHAR(100) NOT NULL,
    description TEXT,
    categorie   ENUM('plage','montagne','ville','nature','aventure','culture') NOT NULL,
    latitude    DECIMAL(9,6),
    longitude   DECIMAL(9,6),
    image_url   VARCHAR(255),
    CONSTRAINT PK_DESTINATIONS PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  TRANSPORTS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS TRANSPORTS (
    id             INT           NOT NULL AUTO_INCREMENT,
    type           ENUM('avion','train','bus','ferry','voiture') NOT NULL,
    compagnie      VARCHAR(100)  NOT NULL,
    lieu_depart    VARCHAR(150)  NOT NULL,
    lieu_arrivee   VARCHAR(150)  NOT NULL,
    prix           DECIMAL(10,2) NOT NULL,
    destination_id INT           DEFAULT NULL,
    CONSTRAINT PK_TRANSPORTS PRIMARY KEY (id),
    CONSTRAINT FK_TRANS_DEST FOREIGN KEY (destination_id)
        REFERENCES DESTINATIONS(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  HEBERGEMENTS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS HEBERGEMENTS (
    id             INT           NOT NULL AUTO_INCREMENT,
    nom            VARCHAR(150)  NOT NULL,
    type           ENUM('hotel','auberge','appartement','villa','camping','residence') NOT NULL,
    prix_par_nuit  DECIMAL(10,2) NOT NULL,
    etoiles        TINYINT       DEFAULT NULL,
    description    TEXT,
    image_url      VARCHAR(255)  DEFAULT NULL,
    destination_id INT           NOT NULL,
    CONSTRAINT PK_HEBERGEMENTS PRIMARY KEY (id),
    CONSTRAINT FK_HEBERG_DEST FOREIGN KEY (destination_id)
        REFERENCES DESTINATIONS(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  ACTIVITES
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ACTIVITES (
    id             INT           NOT NULL AUTO_INCREMENT,
    nom            VARCHAR(150)  NOT NULL,
    prix           DECIMAL(10,2) NOT NULL,
    categorie      VARCHAR(100)  NOT NULL,
    duree_heures   DECIMAL(5,2)  NOT NULL,
    description    TEXT,
    image_url      VARCHAR(255)  DEFAULT NULL,
    destination_id INT           NOT NULL,
    CONSTRAINT PK_ACTIVITES PRIMARY KEY (id),
    CONSTRAINT FK_ACTIV_DEST FOREIGN KEY (destination_id)
        REFERENCES DESTINATIONS(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  UNIVERSITES
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS UNIVERSITES (
    id             INT          NOT NULL AUTO_INCREMENT,
    nom            VARCHAR(200) NOT NULL,
    ville          VARCHAR(100) NOT NULL,
    pays           VARCHAR(100) NOT NULL,
    code_erasmus   VARCHAR(50)  DEFAULT NULL,
    langue         VARCHAR(50)  NOT NULL,
    destination_id INT          DEFAULT NULL,
    CONSTRAINT PK_UNIVERSITES PRIMARY KEY (id),
    CONSTRAINT FK_UNIV_DEST FOREIGN KEY (destination_id)
        REFERENCES DESTINATIONS(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  LOGEMENTS_ETUDIANTS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS LOGEMENTS_ETUDIANTS (
    id                  INT           NOT NULL AUTO_INCREMENT,
    nom                 VARCHAR(150)  NOT NULL,
    type                ENUM('residence','colocation','studio','famille') NOT NULL,
    prix_par_mois       DECIMAL(10,2) NOT NULL,
    distance_campus_km  DECIMAL(5,2)  NOT NULL,
    destination_id      INT           NOT NULL,
    universite_id       INT           DEFAULT NULL,
    CONSTRAINT PK_LOGEMENTS_ETU PRIMARY KEY (id),
    CONSTRAINT FK_LOGETUD_DEST FOREIGN KEY (destination_id)
        REFERENCES DESTINATIONS(id),
    CONSTRAINT FK_LOGETUD_UNIV FOREIGN KEY (universite_id)
        REFERENCES UNIVERSITES(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  INFOS_VISA
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS INFOS_VISA (
    id               INT           NOT NULL AUTO_INCREMENT,
    zone_nationalite ENUM('UE','hors_UE','monde') NOT NULL,
    visa_requis      TINYINT(1)    NOT NULL DEFAULT 0,
    type_visa        VARCHAR(100)  DEFAULT NULL,
    duree_max_jours  INT           DEFAULT NULL,
    cout_eur         DECIMAL(8,2)  DEFAULT NULL,
    destination_id   INT           NOT NULL,
    CONSTRAINT PK_INFOS_VISA PRIMARY KEY (id),
    CONSTRAINT FK_VISA_DEST FOREIGN KEY (destination_id)
        REFERENCES DESTINATIONS(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  ITINERAIRES
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ITINERAIRES (
    id             INT           NOT NULL AUTO_INCREMENT,
    titre          VARCHAR(200)  NOT NULL,
    date_debut     DATE          NOT NULL,
    date_fin       DATE          NOT NULL,
    statut         ENUM('brouillon','confirme','termine','annule') NOT NULL DEFAULT 'brouillon',
    budget_total   DECIMAL(12,2) DEFAULT 0.00,
    utilisateur_id INT           NOT NULL,
    CONSTRAINT PK_ITINERAIRES PRIMARY KEY (id),
    CONSTRAINT FK_ITIN_USER FOREIGN KEY (utilisateur_id)
        REFERENCES UTILISATEURS(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  ELEMENTS_ITINERAIRE
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ELEMENTS_ITINERAIRE (
    id             INT NOT NULL AUTO_INCREMENT,
    type_element   ENUM('transport','hebergement','activite') NOT NULL,
    reference_id   INT NOT NULL,
    numero_jour    INT NOT NULL,
    position_ordre INT NOT NULL DEFAULT 1,
    itineraire_id  INT NOT NULL,
    CONSTRAINT PK_ELEMENTS_ITIN PRIMARY KEY (id),
    CONSTRAINT FK_ELEM_ITIN FOREIGN KEY (itineraire_id)
        REFERENCES ITINERAIRES(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  RESERVATIONS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS RESERVATIONS (
    id               INT           NOT NULL AUTO_INCREMENT,
    type             ENUM('transport','hebergement','activite') NOT NULL,
    reference_id     INT           NOT NULL,
    statut           ENUM('en_attente','confirme','annule','rembourse') NOT NULL DEFAULT 'en_attente',
    prix_total       DECIMAL(10,2) NOT NULL,
    utilisateur_id   INT           NOT NULL,
    itineraire_id    INT           DEFAULT NULL,
    date_reservation DATETIME      DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT PK_RESERVATIONS PRIMARY KEY (id),
    CONSTRAINT FK_RESA_USER FOREIGN KEY (utilisateur_id)
        REFERENCES UTILISATEURS(id),
    CONSTRAINT FK_RESA_ITIN FOREIGN KEY (itineraire_id)
        REFERENCES ITINERAIRES(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  NOTIFICATIONS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS NOTIFICATIONS (
    id             INT          NOT NULL AUTO_INCREMENT,
    titre          VARCHAR(200) NOT NULL,
    message        TEXT,
    type           ENUM('info','alerte','confirmation','rappel') NOT NULL,
    est_lu         TINYINT(1)   NOT NULL DEFAULT 0,
    date_creation  DATETIME     DEFAULT CURRENT_TIMESTAMP,
    utilisateur_id INT          NOT NULL,
    CONSTRAINT PK_NOTIFICATIONS PRIMARY KEY (id),
    CONSTRAINT FK_NOTIF_USER FOREIGN KEY (utilisateur_id)
        REFERENCES UTILISATEURS(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  ESTIMATIONS_BUDGET
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ESTIMATIONS_BUDGET (
    id               INT           NOT NULL AUTO_INCREMENT,
    nom_destination  VARCHAR(150)  NOT NULL,
    transport        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    logement         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    activites        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    nb_jours         INT           NOT NULL,
    total_calcule    DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    utilisateur_id   INT           NOT NULL,
    CONSTRAINT PK_ESTIM_BUDGET PRIMARY KEY (id),
    CONSTRAINT FK_ESTIM_USER FOREIGN KEY (utilisateur_id)
        REFERENCES UTILISATEURS(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  MIGRATIONS pour bases existantes
--  (Sans effet si les tables viennent d'être créées ci-dessus)
-- ============================================================

-- Ajout du rôle 'prestataire'
ALTER TABLE UTILISATEURS
    MODIFY COLUMN role ENUM('admin','user','etudiant','prestataire') NOT NULL DEFAULT 'user';

-- Ajout de image_url sur HEBERGEMENTS si la colonne n'existe pas encore
SET @col_heberg = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'HEBERGEMENTS'
      AND COLUMN_NAME  = 'image_url'
);
SET @sql_heberg = IF(@col_heberg = 0,
    'ALTER TABLE HEBERGEMENTS ADD COLUMN image_url VARCHAR(255) DEFAULT NULL',
    'SELECT 1'
);
PREPARE _stmt FROM @sql_heberg;
EXECUTE _stmt;
DEALLOCATE PREPARE _stmt;

-- Ajout de image_url sur ACTIVITES si la colonne n'existe pas encore
SET @col_activ = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'ACTIVITES'
      AND COLUMN_NAME  = 'image_url'
);
SET @sql_activ = IF(@col_activ = 0,
    'ALTER TABLE ACTIVITES ADD COLUMN image_url VARCHAR(255) DEFAULT NULL',
    'SELECT 1'
);
PREPARE _stmt FROM @sql_activ;
EXECUTE _stmt;
DEALLOCATE PREPARE _stmt;
