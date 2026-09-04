-- Timekeeper schema (MySQL 8+ / MariaDB 10.5+)
SET NAMES utf8mb4;
SET time_zone = '-03:00';

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  avatar VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_preferences (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL UNIQUE,
  default_view ENUM('vertical','horizontal') NOT NULL DEFAULT 'vertical',
  density ENUM('compact','comfortable') NOT NULL DEFAULT 'compact',
  date_format VARCHAR(32) NOT NULL DEFAULT 'd/m/Y',
  theme VARCHAR(32) NOT NULL DEFAULT 'archivist',
  interface_sounds TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_prefs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_resets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_pr_token (token_hash),
  KEY idx_pr_user (user_id),
  CONSTRAINT fk_pr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS collections (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(160) NOT NULL,
  description TEXT NULL,
  icon VARCHAR(64) NOT NULL DEFAULT 'scroll',
  color VARCHAR(16) NOT NULL DEFAULT '#91A7C4',
  cover_image VARCHAR(255) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_col_user (user_id, sort_order),
  CONSTRAINT fk_col_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS timelines (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  collection_id BIGINT UNSIGNED NULL,
  name VARCHAR(190) NOT NULL,
  slug VARCHAR(190) NOT NULL,
  description TEXT NULL,
  icon VARCHAR(64) NOT NULL DEFAULT 'hourglass',
  color VARCHAR(16) NOT NULL DEFAULT '#C9B58A',
  cover_image VARCHAR(255) NULL,
  default_view ENUM('vertical','horizontal') NOT NULL DEFAULT 'vertical',
  date_format VARCHAR(32) NOT NULL DEFAULT 'd/m/Y',
  start_date DATE NULL,
  end_date DATE NULL,
  status ENUM('active','archived','draft') NOT NULL DEFAULT 'active',
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  UNIQUE KEY uq_tl_user_slug (user_id, slug),
  KEY idx_tl_user (user_id, status, sort_order),
  KEY idx_tl_col (collection_id),
  KEY idx_tl_deleted (deleted_at),
  CONSTRAINT fk_tl_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_tl_col FOREIGN KEY (collection_id) REFERENCES collections(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  timeline_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  color VARCHAR(16) NOT NULL DEFAULT '#91A7C4',
  icon VARCHAR(64) NOT NULL DEFAULT 'diamond',
  sort_order INT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_cat_tl_name (timeline_id, name),
  CONSTRAINT fk_cat_tl FOREIGN KEY (timeline_id) REFERENCES timelines(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  timeline_id BIGINT UNSIGNED NOT NULL,
  category_id BIGINT UNSIGNED NULL,
  title VARCHAR(255) NOT NULL,
  summary VARCHAR(500) NULL,
  description TEXT NULL,
  start_date DATE NULL,
  end_date DATE NULL,
  date_precision ENUM('day','month','year','decade','century','unknown') NOT NULL DEFAULT 'day',
  end_date_precision ENUM('day','month','year','decade','century','unknown') NULL,
  start_time TIME NULL,
  end_time TIME NULL,
  is_ongoing TINYINT(1) NOT NULL DEFAULT 0,
  is_milestone TINYINT(1) NOT NULL DEFAULT 0,
  is_favorite TINYINT(1) NOT NULL DEFAULT 0,
  location VARCHAR(255) NULL,
  people VARCHAR(255) NULL,
  url VARCHAR(500) NULL,
  video_url VARCHAR(500) NULL,
  icon VARCHAR(64) NULL,
  color VARCHAR(16) NULL,
  image VARCHAR(255) NULL,
  notes TEXT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  KEY idx_ev_tl (timeline_id, deleted_at, start_date),
  KEY idx_ev_cat (category_id),
  KEY idx_ev_fav (is_favorite),
  CONSTRAINT fk_ev_tl FOREIGN KEY (timeline_id) REFERENCES timelines(id) ON DELETE CASCADE,
  CONSTRAINT fk_ev_cat FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tags (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(80) NOT NULL,
  UNIQUE KEY uq_tag_user_name (user_id, name),
  CONSTRAINT fk_tag_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS event_tags (
  event_id BIGINT UNSIGNED NOT NULL,
  tag_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (event_id, tag_id),
  CONSTRAINT fk_et_ev FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  CONSTRAINT fk_et_tag FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS event_relations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  source_event_id BIGINT UNSIGNED NOT NULL,
  target_event_id BIGINT UNSIGNED NOT NULL,
  relation_type ENUM('related','cause','consequence','reference','continues','precedes') NOT NULL DEFAULT 'related',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_rel (source_event_id, target_event_id, relation_type),
  CONSTRAINT fk_rel_src FOREIGN KEY (source_event_id) REFERENCES events(id) ON DELETE CASCADE,
  CONSTRAINT fk_rel_tgt FOREIGN KEY (target_event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS event_timeline_links (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id BIGINT UNSIGNED NOT NULL,
  timeline_id BIGINT UNSIGNED NOT NULL,
  UNIQUE KEY uq_etl (event_id, timeline_id),
  CONSTRAINT fk_etl_ev FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  CONSTRAINT fk_etl_tl FOREIGN KEY (timeline_id) REFERENCES timelines(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS attachments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id BIGINT UNSIGNED NOT NULL,
  file_name VARCHAR(255) NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  file_type VARCHAR(80) NOT NULL,
  file_size INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_att_ev FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
