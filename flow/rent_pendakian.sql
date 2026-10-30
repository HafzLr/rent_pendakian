-- =====================================================================
-- Hiking Gear Rental - MySQL schema (v2)
-- Based on the Chen ERD: 7 entities, 7 one-to-many relationships
-- Requires MySQL 8.0.16+ (or MariaDB 10.2+) so CHECK constraints work
-- Schema only, no sample data
--
-- Changes from v1 (manual payment verification instead of Midtrans):
--   payments: removed midtrans_order_id
--   payments: added proof_image, rejection_reason, verified_by (FK -> users)
--   payments: payment_method is now ENUM('bank_transfer','qris','cash')
--   payments: paid_at = time the admin confirmed the payment
--   payments: added CHECKs for confirmation time and rejection reason
-- =====================================================================

CREATE DATABASE IF NOT EXISTS rent_pendakian
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE rent_pendakian;

-- ---------------------------------------------------------------------
-- 1. users (strong entity)
-- ---------------------------------------------------------------------
CREATE TABLE users (
  user_id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name                VARCHAR(100) NOT NULL,
  email               VARCHAR(100) NOT NULL,
  password            VARCHAR(255) NOT NULL COMMENT 'store a hash (password_hash), never plain text',
  user_number         VARCHAR(20)  NOT NULL COMMENT 'WhatsApp number',
  role                ENUM('user', 'admin') NOT NULL DEFAULT 'user',
  idcard_photo        VARCHAR(255) NULL COMMENT 'file path of KTP/SIM photo',
  verification_status ENUM('pending', 'verified', 'rejected') NOT NULL DEFAULT 'pending',
  PRIMARY KEY (user_id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. categories (strong entity)
-- ---------------------------------------------------------------------
CREATE TABLE categories (
  category_id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  category_name VARCHAR(50) NOT NULL,
  PRIMARY KEY (category_id),
  UNIQUE KEY uq_categories_name (category_name)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. products (strong entity)
-- ---------------------------------------------------------------------
CREATE TABLE products (
  product_id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  category_id   INT UNSIGNED NOT NULL,
  product_name  VARCHAR(100) NOT NULL,
  description   TEXT NULL,
  price_per_day DECIMAL(10,2) NOT NULL,
  deposit       DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'per unit',
  image         VARCHAR(255) NULL,
  PRIMARY KEY (product_id),
  CONSTRAINT fk_products_category
    FOREIGN KEY (category_id) REFERENCES categories (category_id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_products_price   CHECK (price_per_day >= 0),
  CONSTRAINT chk_products_deposit CHECK (deposit >= 0)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. product_variants (WEAK entity, owner: products)
--    Real identity = (product_id, variant_name)
-- ---------------------------------------------------------------------
CREATE TABLE product_variants (
  variant_id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  product_id   INT UNSIGNED NOT NULL,
  variant_name VARCHAR(50) NOT NULL COMMENT 'color or size',
  total_stock  INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'physical units owned',
  PRIMARY KEY (variant_id),
  UNIQUE KEY uq_variant_identity (product_id, variant_name),
  CONSTRAINT fk_variants_product
    FOREIGN KEY (product_id) REFERENCES products (product_id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5. rentals (strong entity)
--    In-store pickup only, so there is no pickup method column.
-- ---------------------------------------------------------------------
CREATE TABLE rentals (
  rental_id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id        INT UNSIGNED NOT NULL,
  start_date     DATE NOT NULL,
  end_date       DATE NOT NULL,
  return_date    DATE NULL COMMENT 'set when the items come back',
  payment_scheme ENUM('down_payment', 'full') NOT NULL,
  total_rental   DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'sum of rental_items.subtotal',
  total_deposit  DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  penalty        DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  penalty_notes  TEXT NULL,
  status         ENUM(
                   'pending_payment',
                   'pending_verification',
                   'approved',
                   'active',
                   'completed',
                   'rejected',
                   'cancelled',
                   'expired'
                 ) NOT NULL DEFAULT 'pending_payment',
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'basis for the payment deadline',
  PRIMARY KEY (rental_id),
  KEY idx_rentals_availability (status, start_date, end_date),
  CONSTRAINT fk_rentals_user
    FOREIGN KEY (user_id) REFERENCES users (user_id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_rentals_dates   CHECK (end_date > start_date),
  CONSTRAINT chk_rentals_amounts CHECK (total_rental >= 0 AND total_deposit >= 0 AND penalty >= 0)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 6. rental_items (WEAK entity, owner: rentals)
--    Real identity = (rental_id, variant_id)
-- ---------------------------------------------------------------------
CREATE TABLE rental_items (
  rental_item_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  rental_id      INT UNSIGNED NOT NULL,
  variant_id     INT UNSIGNED NOT NULL,
  quantity       INT UNSIGNED NOT NULL,
  price_per_day  DECIMAL(10,2) NOT NULL COMMENT 'copied from products at transaction time',
  subtotal       DECIMAL(12,2) NOT NULL COMMENT 'duration x price_per_day x quantity',
  PRIMARY KEY (rental_item_id),
  UNIQUE KEY uq_rental_item_identity (rental_id, variant_id),
  CONSTRAINT fk_items_rental
    FOREIGN KEY (rental_id) REFERENCES rentals (rental_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_items_variant
    FOREIGN KEY (variant_id) REFERENCES product_variants (variant_id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_items_quantity CHECK (quantity > 0),
  CONSTRAINT chk_items_amounts  CHECK (price_per_day >= 0 AND subtotal >= 0)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 7. payments (strong entity)
--    Every payment is verified manually by an admin.
--    payment_status: pending = waiting for admin, success = verified,
--                    failed = rejected, expired = deadline passed
--    verified_by: the admin who verified or rejected (set by the app)
-- ---------------------------------------------------------------------
CREATE TABLE payments (
  payment_id       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  rental_id        INT UNSIGNED NOT NULL,
  payment_type     ENUM('down_payment', 'settlement', 'penalty') NOT NULL,
  payment_amount   DECIMAL(12,2) NOT NULL,
  payment_method   ENUM('bank_transfer', 'qris', 'cash') NOT NULL,
  payment_status   ENUM('pending', 'success', 'failed', 'expired') NOT NULL DEFAULT 'pending',
  paid_at          DATETIME NULL COMMENT 'time the admin confirmed the payment',
  proof_image      VARCHAR(255) NULL COMMENT 'file path of transfer/QRIS proof; NULL for cash',
  rejection_reason TEXT NULL COMMENT 'required when payment_status = failed',
  verified_by      INT UNSIGNED NULL COMMENT 'admin who verified or rejected the payment',
  PRIMARY KEY (payment_id),
  KEY idx_payments_status (payment_status),
  CONSTRAINT fk_payments_rental
    FOREIGN KEY (rental_id) REFERENCES rentals (rental_id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_payments_verifier
    FOREIGN KEY (verified_by) REFERENCES users (user_id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_payments_amount    CHECK (payment_amount > 0),
  CONSTRAINT chk_payments_confirmed CHECK (payment_status <> 'success' OR paid_at IS NOT NULL),
  CONSTRAINT chk_payments_rejected  CHECK (payment_status <> 'failed' OR rejection_reason IS NOT NULL)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Reference query (not executed): available stock for one variant
-- Replace the :named values with your own parameters.
-- Statuses that hold stock: pending_payment, pending_verification,
-- approved, active
-- ---------------------------------------------------------------------
-- SELECT v.total_stock - COALESCE((
--          SELECT SUM(ri.quantity)
--          FROM rental_items ri
--          JOIN rentals r ON r.rental_id = ri.rental_id
--          WHERE ri.variant_id = v.variant_id
--            AND r.status IN ('pending_payment', 'pending_verification', 'approved', 'active')
--            AND r.start_date < :end_date
--            AND r.end_date   > :start_date
--        ), 0) AS available_stock
-- FROM product_variants v
-- WHERE v.variant_id = :variant_id;

-- ---------------------------------------------------------------------
-- Optional: store the reason when a renter's KTP/SIM is rejected
-- ---------------------------------------------------------------------
-- ALTER TABLE users ADD COLUMN verification_notes TEXT NULL AFTER verification_status;

-- ---------------------------------------------------------------------
-- Upgrade from v1 (only if you already created the v1 tables)
-- Run once, after taking a backup. Not needed for a fresh install.
-- ---------------------------------------------------------------------
-- ALTER TABLE payments
--   DROP INDEX uq_payments_midtrans_order,
--   DROP COLUMN midtrans_order_id,
--   MODIFY payment_method ENUM('bank_transfer', 'qris', 'cash') NOT NULL,
--   ADD COLUMN proof_image VARCHAR(255) NULL,
--   ADD COLUMN rejection_reason TEXT NULL,
--   ADD COLUMN verified_by INT UNSIGNED NULL,
--   ADD KEY idx_payments_status (payment_status),
--   ADD CONSTRAINT fk_payments_verifier
--     FOREIGN KEY (verified_by) REFERENCES users (user_id)
--     ON DELETE RESTRICT ON UPDATE CASCADE,
--   ADD CONSTRAINT chk_payments_confirmed CHECK (payment_status <> 'success' OR paid_at IS NOT NULL),
--   ADD CONSTRAINT chk_payments_rejected  CHECK (payment_status <> 'failed' OR rejection_reason IS NOT NULL);
