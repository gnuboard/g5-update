-- Create Database migration Table

CREATE TABLE `g5_migrations` (
  `mi_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `mi_version` varchar(30) NOT NULL,
  `mi_sort` int(11) DEFAULT 0,
  `mi_script` varchar(255) NOT NULL,
  `mi_execution_date` datetime DEFAULT NULL,
  `mi_execution_time` int(11) DEFAULT NULL,
  `mi_result` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`mi_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
