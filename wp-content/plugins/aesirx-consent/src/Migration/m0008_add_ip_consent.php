<?php

global $wpdb;

$sql = [];

// Add tier field to analytics_visitor_consent table

$sql[] = "ALTER TABLE `{$wpdb->prefix}analytics_visitor_consent` ADD `ip_consent` VARCHAR(255) NULL DEFAULT NULL FIRST;";