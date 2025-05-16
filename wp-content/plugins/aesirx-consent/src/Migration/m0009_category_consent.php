<?php

global $wpdb;

$sql = [];

// Create analytics_category_consent table
$sql[] = "
    CREATE TABLE `{$wpdb->prefix}analytics_category_consent` (
        `id` char(36) NOT NULL,
        `uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
        `datetime` datetime DEFAULT NULL,
        `expiration` datetime DEFAULT NULL,
        `allow` varchar(255) DEFAULT NULL,
        `reject` varchar(255) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `analytics_category_consent_1` (`uuid`),
        CONSTRAINT `analytics_category_consent_1` FOREIGN KEY (`uuid`) REFERENCES `{$wpdb->prefix}analytics_visitors` (`uuid`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB;";