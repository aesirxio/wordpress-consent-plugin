<?php

global $wpdb;

$sql = [];

// Add override_language field to analytics_visitor_consent table

$sql[] = "ALTER TABLE `{$wpdb->prefix}analytics_visitor_consent` ADD `override_language` VARCHAR(255) NULL DEFAULT NULL;";