-- =====================================================================
-- ADD LIVE CHAT to a database that was imported BEFORE the chat feature.
-- Safe to run more than once. (New installs: schema.sql already has this.)
-- =====================================================================
-- (select your database in phpMyAdmin before importing this file)

-- ---------------------------------------------------------------------
-- LIVE CHAT: visitors <-> admin, inside the website (no WhatsApp)
-- A visitor is recognised by a random token kept in a cookie, so guests
-- can chat without an account and come back to the same conversation.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS chat_conversations (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  token           CHAR(32)     NOT NULL UNIQUE,      -- secret in the visitor's cookie
  user_id         INT UNSIGNED NULL,                 -- set when a logged-in customer starts the chat
  visitor_name    VARCHAR(100) NOT NULL,
  visitor_contact VARCHAR(150) NULL,                 -- optional phone / email
  status          ENUM('open','closed') NOT NULL DEFAULT 'open',
  last_message_at DATETIME     NOT NULL,
  created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_chat_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_chat_last (last_message_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS chat_messages (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  conversation_id INT UNSIGNED NOT NULL,
  sender          ENUM('visitor','admin') NOT NULL,
  admin_id        INT UNSIGNED NULL,                 -- which admin replied
  body            TEXT         NOT NULL,
  read_at         DATETIME     NULL,                 -- NULL = the other side has not seen it yet
  created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_chatmsg_conv  FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE,
  CONSTRAINT fk_chatmsg_admin FOREIGN KEY (admin_id)        REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_chatmsg_conv (conversation_id, id)
) ENGINE=InnoDB;

INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
  ('chat_enabled', '1'),
  ('chat_welcome', 'Halo! Ada yang bisa kami bantu? Tanyakan produk, harga, atau kebutuhan layout gym Anda.');
