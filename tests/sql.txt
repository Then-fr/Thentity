CREATE TABLE `test_1` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO test_1 (title, description, active)
VALUES
  ('Test A', 'Description du test A', 1),
  ('Test B', NULL, 0),
  ('Test C', 'Lorem ipsum dolor sit amet', 1);

CREATE TABLE `test_2` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `test_1_id` INT UNSIGNED NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('pending', 'ok', 'failed') NOT NULL DEFAULT 'pending',
  `meta` JSON DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`test_1_id`) REFERENCES test_1(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO test_2 (test_1_id, amount, status, meta)
VALUES
  (1, 10.50, 'ok',   '{"note": "paiement validé", "tags": ["green"]}'),
  (1, 99.99, 'pending', NULL),
  (2, 5.00, 'failed', '{"error": "CB refusée"}');


CREATE TABLE `test_3` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `test_2_id` INT UNSIGNED DEFAULT NULL,
  `code` VARCHAR(32) NOT NULL,
  `label` VARCHAR(255) NOT NULL,
  `enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_code` (`code`),
  FOREIGN KEY (`test_2_id`) REFERENCES test_2(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


INSERT INTO test_3 (test_2_id, code, label, enabled)
VALUES
  (1, 'ABC123', 'Premier élément', 1),
  (NULL, 'XYZ999', 'Sans relation', 0),
  (2, 'HELLO', 'Élément lié à test_2 #2', 1);




