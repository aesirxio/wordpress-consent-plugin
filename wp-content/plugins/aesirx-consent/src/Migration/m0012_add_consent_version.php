<?php

global $wpdb;

$sql = [];

// Add consent_version field to analytics_visitor_consent table

$sql[] = "ALTER TABLE `{$wpdb->prefix}analytics_visitor_consent` ADD `consent_version` VARCHAR(255) NULL DEFAULT NULL;";