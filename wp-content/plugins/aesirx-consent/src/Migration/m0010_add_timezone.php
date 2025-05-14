<?php

global $wpdb;

$sql = [];

// Add timezone field to analytics_visitors table

$sql[] = "ALTER TABLE `{$wpdb->prefix}analytics_visitors` ADD `timezone` VARCHAR(255) NULL DEFAULT NULL;";