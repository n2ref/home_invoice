CREATE DATABASE `home_invoices` DEFAULT CHARACTER SET utf8;

CREATE TABLE `invoices` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `date_invoice` timestamp NOT NULL,
    `date_invoice_created` varchar(255) DEFAULT NULL,
    `address` varchar(255) DEFAULT NULL,
    `payer_name` varchar(255) DEFAULT NULL,
    `personal_account` varchar(255) DEFAULT NULL,
    `total_accrued` decimal(8,2) DEFAULT NULL,
    `total_price` decimal(8,2) DEFAULT NULL,
    `cold_water_count` decimal(8,2) DEFAULT NULL,
    `cold_water_diff` decimal(8,2) DEFAULT NULL,
    `hot_water_count` decimal(8,2) DEFAULT NULL,
    `hot_water_diff` decimal(8,2) DEFAULT NULL,
    `house_square` decimal(8,2) DEFAULT NULL,
    `house_sub_square` decimal(8,2) DEFAULT NULL,
    `house_people` decimal(8,2) DEFAULT NULL,
    `house_people_energy` decimal(8,2) DEFAULT NULL,
    `house_people_other` decimal(8,2) DEFAULT NULL,
    `house_hot_water_count` decimal(8,2) DEFAULT NULL,
    `house_hot_water_cal` decimal(8,2) DEFAULT NULL,
    `house_cold_water_count` decimal(8,2) DEFAULT NULL,
    `house_energy` decimal(8,2) DEFAULT NULL,
    `house_energy_lift` decimal(8,2) DEFAULT NULL,
    `invoice_data` json DEFAULT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `invoices_services` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `invoice_id` int unsigned NOT NULL,
    `title` varchar(255) DEFAULT NULL,
    `volume` decimal(8,2) DEFAULT NULL,
    `rate` decimal(8,2) DEFAULT NULL,
    `accrued` decimal(8,2) DEFAULT NULL,
    `privileges` decimal(8,2) DEFAULT NULL,
    `recalculation` decimal(8,2) DEFAULT NULL,
    `total` decimal(8,2) DEFAULT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `invoice_id` (`invoice_id`),
    CONSTRAINT `fk1_invoices_services` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `invoices_services_extra` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `invoice_id` int unsigned NOT NULL,
    `title` varchar(255) DEFAULT NULL,
    `value` decimal(8,2) DEFAULT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `invoice_id` (`invoice_id`),
    CONSTRAINT `fk1_invoices_services_extra` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
