-- Création de la base de données
CREATE DATABASE IF NOT EXISTS ecole_bulletins CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ecole_bulletins;

-- Table des années scolaires
CREATE TABLE IF NOT EXISTS school_years (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(20) NOT NULL UNIQUE,
    is_active BOOLEAN DEFAULT FALSE,
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL
);



-- Table des utilisateurs (Directeur et Professeurs)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nom VARCHAR(100) NOT NULL,
    role ENUM('admin', 'prof') NOT NULL DEFAULT 'prof',
    is_active BOOLEAN DEFAULT TRUE,
    code_invitation VARCHAR(20) DEFAULT NULL, -- Utilisé par le directeur pour inviter, stocke le code utilisé par le prof
    grade VARCHAR(100) DEFAULT NULL,
    statut VARCHAR(100) DEFAULT NULL,
    corps VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des classes (ex: 6ème A, CM2)
CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des élèves
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    matricule VARCHAR(50) DEFAULT NULL UNIQUE,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    sexe ENUM('M', 'F') DEFAULT 'M',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table inscription élève par année (Historique des classes)
CREATE TABLE IF NOT EXISTS student_enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    class_id INT NOT NULL,
    school_year_id INT NOT NULL,
    statut ENUM('Nouveau', 'Redoublant') DEFAULT 'Nouveau',
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (school_year_id) REFERENCES school_years(id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (student_id, school_year_id)
);

-- Table des matières (ex: Maths, Français)
CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL UNIQUE,
    coefficient INT DEFAULT 1
);

-- Table d'attribution (Qui enseigne Quoi à Quelle Classe)
CREATE TABLE IF NOT EXISTS assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prof_id INT NOT NULL,
    subject_id INT NOT NULL,
    class_id INT NOT NULL,
    school_year_id INT NOT NULL,
    coefficient INT DEFAULT 1, -- Le coefficient dépend de la classe
    FOREIGN KEY (prof_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (school_year_id) REFERENCES school_years(id) ON DELETE CASCADE,
    UNIQUE KEY unique_subject_class_year (subject_id, class_id, school_year_id)
);


-- Table des invitations générées par le directeur
CREATE TABLE IF NOT EXISTS invitations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    is_used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des évaluations (Devoirs, Interros, etc.)
CREATE TABLE IF NOT EXISTS evaluations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    type ENUM('interrogation', 'devoir') NOT NULL,
    nom VARCHAR(100) NOT NULL, -- Ex: 'Interro 1', 'Devoir Trimestre 1'
    periode ENUM('Semestre 1', 'Semestre 2') NOT NULL DEFAULT 'Semestre 1',
    date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE
);

-- Table des notes
CREATE TABLE IF NOT EXISTS grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    evaluation_id INT NOT NULL, -- Lie la note à une évaluation précise
    valeur DECIMAL(4, 2) NOT NULL, -- Note sur 20 (ex: 15.50)
    appreciation TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (evaluation_id) REFERENCES evaluations(id) ON DELETE CASCADE
);
