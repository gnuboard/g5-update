-- gnuboard5.g5_billing_service definition

CREATE TABLE `g5_billing_service` (
  `service_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `summary` varchar(255) DEFAULT '',
  `explan` text DEFAULT NULL,
  `mobile_explan` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `order` int(11) NOT NULL DEFAULT 1,
  `is_use` tinyint(1) NOT NULL DEFAULT 1,
  `expiration` int(11) DEFAULT 0,
  `expiration_unit` varchar(1) DEFAULT 'm',
  `recurring` int(11) NOT NULL DEFAULT 0,
  `recurring_unit` varchar(1) NOT NULL DEFAULT 'm',
  `service_table` varchar(20) NOT NULL,
  `service_url` varchar(255) DEFAULT NULL,
  `service_hook_code` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`service_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- gnuboard5.g5_billing_service_price definition

CREATE TABLE `g5_billing_service_price` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_id` int(11) NOT NULL,
  `price` int(11) NOT NULL DEFAULT 0,
  `application_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `g5_billing_service_price_FK` (`service_id`),
  CONSTRAINT `g5_billing_service_price_FK` FOREIGN KEY (`service_id`) REFERENCES `g5_billing_service` (`service_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- gnuboard5.g5_billing_information definition

CREATE TABLE `g5_billing_information` (
  `od_id` bigint(20) unsigned NOT NULL,
  `service_id` int(11) NOT NULL,
  `mb_id` varchar(20) NOT NULL,
  `billing_key` varchar(255) NOT NULL,
  `start_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `end_date` datetime DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `next_payment_date` datetime DEFAULT NULL,
  PRIMARY KEY (`od_id`),
  KEY `g5_billing_information_FK` (`service_id`),
  CONSTRAINT `g5_billing_information_FK` FOREIGN KEY (`service_id`) REFERENCES `g5_billing_service` (`service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- gnuboard5.g5_billing_history definition

CREATE TABLE `g5_billing_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `od_id` bigint(20) unsigned NOT NULL,
  `mb_id` varchar(20) NOT NULL DEFAULT '',
  `billing_key` varchar(255) NOT NULL,
  `amount` int(11) NOT NULL DEFAULT 0,
  `result_code` varchar(4) NOT NULL,
  `result_message` varchar(100) NOT NULL,
  `result_data` text DEFAULT NULL,
  `card_name` varchar(20) DEFAULT NULL,
  `payment_count` int(11) NOT NULL DEFAULT 1,
  `payment_no` varchar(100) NOT NULL,
  `payment_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `expiration_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `g5_billing_history_FK` (`od_id`),
  CONSTRAINT `g5_billing_history_FK` FOREIGN KEY (`od_id`) REFERENCES `g5_billing_information` (`od_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- gnuboard5.g5_billing_key_history definition

CREATE TABLE `g5_billing_key_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pg_code` varchar(20) NOT NULL,
  `od_id` bigint(20) unsigned NOT NULL,
  `mb_id` varchar(20) NOT NULL,
  `result_code` varchar(4) NOT NULL DEFAULT '',
  `result_message` varchar(100) NOT NULL DEFAULT '',
  `card_code` varchar(4) DEFAULT NULL,
  `card_name` varchar(20) DEFAULT NULL,
  `card_no` varchar(100) DEFAULT NULL,
  `billing_key` varchar(255) DEFAULT NULL,
  `issue_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;