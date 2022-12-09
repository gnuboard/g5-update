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
  `base_price` int(11) DEFAULT 0,
  PRIMARY KEY (`service_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- gnuboard5.g5_billing_service_price definition

CREATE TABLE `g5_billing_service_price` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_id` int(11) NOT NULL,
  `price` int(11) NOT NULL DEFAULT 0,
  `application_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `application_end_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
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

-- gnuboard5.g5_billing_cancel definition

CREATE TABLE `g5_billing_cancel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `od_id` bigint(20) unsigned NOT NULL,
  `payment_no` varchar(100) NOT NULL,
  `type` varchar(10) NOT NULL DEFAULT 'all' COMMENT '(all : 전체취소, partial: 부분취소)',
  `result_code` varchar(4) NOT NULL,
  `result_message` varchar(100) NOT NULL,
  `cancel_no` varchar(100) DEFAULT NULL,
  `cancel_reason` varchar(255) DEFAULT NULL,
  `cancel_amount` int(11) NOT NULL DEFAULT 0,
  `refundable_amount` int(11) NOT NULL DEFAULT 0,
  `cancel_time` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- gnuboard5.g5_billing_scheduler_history definition

CREATE TABLE `g5_billing_scheduler_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `success_count` int(11) NOT NULL DEFAULT 0,
  `fail_count` int(11) NOT NULL DEFAULT 0,
  `state` int(1) NOT NULL DEFAULT 0 COMMENT '1: 성공, 0: 실패, -1:부분성공',
  `start_time` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- gnuboard5.g5_billing_config definition

CREATE TABLE `g5_billing_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bc_use_cancel_refund` tinyint(1) NOT NULL DEFAULT 1 COMMENT '구독취소 환불 (1:사용, 0:미사용)',
  `bc_use_pause` tinyint(1) NOT NULL DEFAULT 0 COMMENT '구독 일시정지 (1:사용, 0:미사용)',
  `bc_pg_code` varchar(10) DEFAULT '' COMMENT '정기결제 PG사 (kcp, toss)',
  `bc_kcp_site_cd` varchar(20) DEFAULT '' COMMENT 'kcp 사이트 코드',
  `bc_kcp_group_id` varchar(20) DEFAULT '' COMMENT 'kcp 그룹 ID',
  `bc_kcp_cert_path` varchar(255) DEFAULT '' COMMENT 'kcp 서비스 인증서 경로',
  `bc_kcp_prikey_path` varchar(255) DEFAULT '' COMMENT 'kcp 암호화 개인 키 경로',
  `bc_kcp_prikey_password` varchar(255) DEFAULT '' COMMENT 'kcp 암호화 개인 키 비밀번호',
  `bc_kcp_is_test` tinyint(1) DEFAULT 1 COMMENT 'kcp 테스트 환경여부 (1:테스트, 0:운영)',
  `bc_kcp_curruncy` varchar(10) DEFAULT '410' COMMENT '통화 단위 (410:원화)',
  `bc_notice_email` varchar(255) DEFAULT '' COMMENT '자동결제 스케쥴러 실행 결과 수신 이메일',
  `bc_update_ip` varchar(100) NOT NULL DEFAULT '',
  `bc_update_id` varchar(100) NOT NULL DEFAULT '',
  `bc_update_time` datetime NOT NULL,
  `bc_toss_is_test` tinyint(1) DEFAULT 1 COMMENT 'toss 테스트 환경여부 (1:테스트, 0:운영)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;