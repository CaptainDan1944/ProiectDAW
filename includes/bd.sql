CREATE TABLE players (
    player_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL, -- Hashed password for security
    magic_class ENUM('fire', 'water', 'ice', 'nature', 'dark', 'light') NOT NULL,
    level ENUM('apprentice', 'adept', 'mage', 'archmage', 'admin') NOT NULL DEFAULT 'apprentice',
    gold_coins INT DEFAULT 100, -- Default starting gold
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    item_type ENUM('potion', 'scroll', 'book', 'tome', 'artifact', 'weapon', 'staff') NOT NULL,
    name VARCHAR(100) NOT NULL,
    price INT NOT NULL CHECK (price >= 0), -- Price in gold coins
    required_level ENUM('none', 'apprentice', 'adept', 'mage', 'archmage') DEFAULT 'apprentice', -- Restriction
    description TEXT,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE borrowed_items (
    borrow_id INT AUTO_INCREMENT PRIMARY KEY,
    player_id INT,
    item_id INT,
    borrow_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    return_date TIMESTAMP NULL,
    status ENUM('pending', 'accepted', 'rejected','borrowed', 'returned', 'cancelled') DEFAULT 'pending',
    FOREIGN KEY (player_id) REFERENCES players(player_id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(item_id) ON DELETE CASCADE
);

CREATE TABLE training_sessions (
    training_id INT AUTO_INCREMENT PRIMARY KEY,
    trainer_id INT, -- FK to a Mage or Archmage
    start_time DATETIME NOT NULL,
    duration INT NOT NULL, -- Duration in minutes
    level ENUM('apprentice', 'adept', 'mage', 'archmage') NOT NULL, -- Restriction
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trainer_id) REFERENCES players(player_id) ON DELETE CASCADE
);

CREATE TABLE training_participants (
    participation_id INT AUTO_INCREMENT PRIMARY KEY,
    training_id INT,
    player_id INT,
    FOREIGN KEY (training_id) REFERENCES training_sessions(training_id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES players(player_id) ON DELETE CASCADE
);

CREATE TABLE promotions (
    promotion_id INT AUTO_INCREMENT PRIMARY KEY,
    promoted_by INT, -- Archmage who gave the promotion
    promoted_player INT, -- The player who got promoted
    old_level ENUM('apprentice', 'adept', 'mage') NOT NULL, -- Previous level
    new_level ENUM('adept', 'mage', 'archmage') NOT NULL, -- New level
    promoted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (promoted_by) REFERENCES players(player_id) ON DELETE CASCADE,
    FOREIGN KEY (promoted_player) REFERENCES players(player_id) ON DELETE CASCADE
);

CREATE TABLE user_inventory (
    inventory_id INT AUTO_INCREMENT PRIMARY KEY,
    player_id INT,
    item_id INT,
    purchase_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (player_id) REFERENCES players(player_id),
    FOREIGN KEY (item_id) REFERENCES items(item_id)
);

ALTER TABLE players ADD COLUMN email VARCHAR(255) NOT NULL;

ALTER TABLE players ADD COLUMN is_admin TINYINT(1) DEFAULT 0;

ALTER TABLE items ADD COLUMN rarity VARCHAR(50);

ALTER TABLE training_sessions
ADD COLUMN title VARCHAR(100) NOT NULL AFTER trainer_id,
ADD COLUMN description TEXT AFTER level;

ALTER TABLE promotions ADD COLUMN reviewed TINYINT DEFAULT 0;

ALTER TABLE promotions ADD COLUMN comment TEXT;

ALTER TABLE borrowed_items ADD COLUMN actual_return_date TIMESTAMP NULL;

ALTER TABLE items ADD COLUMN is_borrowable BOOLEAN DEFAULT FALSE;

CREATE TABLE IF NOT EXISTS site_visits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    visit_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);