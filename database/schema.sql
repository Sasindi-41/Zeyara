-- Zeyara database schema
-- Structure only. No application records are included.

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `brand`;
CREATE TABLE `brand` (
  `brand_id` int NOT NULL AUTO_INCREMENT,
  `brand_name` varchar(45) NOT NULL,
  PRIMARY KEY (`brand_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `category`;
CREATE TABLE `category` (
  `cat_id` int NOT NULL AUTO_INCREMENT,
  `cat_name` varchar(45) NOT NULL,
  PRIMARY KEY (`cat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `concentration`;
CREATE TABLE `concentration` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cname` varchar(45) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `scent_family`;
CREATE TABLE `scent_family` (
  `sf_id` int NOT NULL AUTO_INCREMENT,
  `sf_name` varchar(45) NOT NULL,
  PRIMARY KEY (`sf_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `size`;
CREATE TABLE `size` (
  `size_id` int NOT NULL AUTO_INCREMENT,
  `size_name` varchar(45) NOT NULL,
  PRIMARY KEY (`size_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `user_type`;
CREATE TABLE `user_type` (
  `utype_id` int NOT NULL AUTO_INCREMENT,
  `type` varchar(20) NOT NULL,
  PRIMARY KEY (`utype_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `product`;
CREATE TABLE `product` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL,
  `description` varchar(200) NOT NULL,
  `path` varchar(100) NOT NULL,
  `size_id` int NOT NULL,
  `cat_id` int NOT NULL,
  `sf_id` int NOT NULL,
  `brand_id` int NOT NULL,
  `concentration_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_product_size_idx` (`size_id`),
  KEY `fk_product_category1_idx` (`cat_id`),
  KEY `fk_product_brand1_idx` (`brand_id`),
  KEY `fk_product_scent_family1_idx` (`sf_id`),
  KEY `fk_product_concentration1_idx` (`concentration_id`),
  CONSTRAINT `fk_product_brand1` FOREIGN KEY (`brand_id`) REFERENCES `brand` (`brand_id`),
  CONSTRAINT `fk_product_category1` FOREIGN KEY (`cat_id`) REFERENCES `category` (`cat_id`),
  CONSTRAINT `fk_product_concentration1` FOREIGN KEY (`concentration_id`) REFERENCES `concentration` (`id`),
  CONSTRAINT `fk_product_scent_family1` FOREIGN KEY (`sf_id`) REFERENCES `scent_family` (`sf_id`),
  CONSTRAINT `fk_product_size` FOREIGN KEY (`size_id`) REFERENCES `size` (`size_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `stock`;
CREATE TABLE `stock` (
  `stock_id` int NOT NULL AUTO_INCREMENT,
  `price` double NOT NULL,
  `qty` int NOT NULL,
  `status` varchar(45) NOT NULL,
  `product_id` int NOT NULL,
  PRIMARY KEY (`stock_id`),
  KEY `fk_stock_product1_idx` (`product_id`),
  CONSTRAINT `fk_stock_product1` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fname` varchar(45) NOT NULL,
  `lname` varchar(45) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mobile` varchar(10) NOT NULL,
  `username` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` varchar(45) NOT NULL,
  `user_type_id` int NOT NULL,
  `no` varchar(10) DEFAULT NULL,
  `line_1` varchar(50) DEFAULT NULL,
  `line_2` varchar(50) DEFAULT NULL,
  `img_path` varchar(100) DEFAULT NULL,
  `v_code` text,
  `v_code_expires` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_user_user_type1_idx` (`user_type_id`),
  CONSTRAINT `fk_user_user_type1` FOREIGN KEY (`user_type_id`) REFERENCES `user_type` (`utype_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `cart`;
CREATE TABLE `cart` (
  `cart_id` int NOT NULL AUTO_INCREMENT,
  `cart_qty` int NOT NULL,
  `stock_stock_id` int NOT NULL,
  `user_id` int NOT NULL,
  PRIMARY KEY (`cart_id`),
  KEY `fk_cart_stock1_idx` (`stock_stock_id`),
  KEY `fk_cart_user1_idx` (`user_id`),
  CONSTRAINT `fk_cart_stock1` FOREIGN KEY (`stock_stock_id`) REFERENCES `stock` (`stock_id`),
  CONSTRAINT `fk_cart_user1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `order_history`;
CREATE TABLE `order_history` (
  `ohid` int NOT NULL AUTO_INCREMENT,
  `order_id` varchar(64) NOT NULL,
  `order_date` datetime NOT NULL,
  `amount` double NOT NULL,
  `user_id` int NOT NULL,
  `payment_status` varchar(20) NOT NULL DEFAULT 'LEGACY',
  `payment_id` varchar(100) DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `currency` char(3) NOT NULL DEFAULT 'LKR',
  PRIMARY KEY (`ohid`),
  UNIQUE KEY `uq_order_history_order_id` (`order_id`),
  UNIQUE KEY `uq_order_history_payment_id` (`payment_id`),
  KEY `fk_order_history_user1_idx` (`user_id`),
  CONSTRAINT `fk_order_history_user1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `order_item`;
CREATE TABLE `order_item` (
  `oid` int NOT NULL AUTO_INCREMENT,
  `oi_qty` int NOT NULL,
  `order_history_ohid` int NOT NULL,
  `stock_stock_id` int NOT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`oid`),
  KEY `fk_order_item_order_history1_idx` (`order_history_ohid`),
  KEY `fk_order_item_stock1_idx` (`stock_stock_id`),
  CONSTRAINT `fk_order_item_order_history1` FOREIGN KEY (`order_history_ohid`) REFERENCES `order_history` (`ohid`),
  CONSTRAINT `fk_order_item_stock1` FOREIGN KEY (`stock_stock_id`) REFERENCES `stock` (`stock_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `stock_reservations`;
CREATE TABLE `stock_reservations` (
  `reservation_id` int NOT NULL AUTO_INCREMENT,
  `order_history_ohid` int NOT NULL,
  `stock_stock_id` int NOT NULL,
  `quantity` int NOT NULL,
  `reservation_status` enum('ACTIVE','COMMITTED','RELEASED') NOT NULL DEFAULT 'ACTIVE',
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`reservation_id`),
  UNIQUE KEY `uq_reservation_order_stock` (`order_history_ohid`,`stock_stock_id`),
  KEY `idx_reservation_stock_status` (`stock_stock_id`,`reservation_status`,`expires_at`),
  KEY `idx_reservation_expiry` (`reservation_status`,`expires_at`),
  CONSTRAINT `fk_reservation_order` FOREIGN KEY (`order_history_ohid`) REFERENCES `order_history` (`ohid`),
  CONSTRAINT `fk_reservation_stock` FOREIGN KEY (`stock_stock_id`) REFERENCES `stock` (`stock_id`),
  CONSTRAINT `chk_reservation_quantity` CHECK ((`quantity` > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

SET FOREIGN_KEY_CHECKS=1;
