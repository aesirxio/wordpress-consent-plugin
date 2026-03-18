<?php

use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

include_once plugin_dir_path(__FILE__) . 'ConsentWebhook.php';

Class AesirX_Analytics_Reject_Consent extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        // Validate and sanitize each parameter in the $params array
        $validated_params = [];
        foreach ($params as $key => $value) {
            $validated_params[$key] = sanitize_text_field($value);
        }

        global $wpdb;

        $expiration   = gmdate('Y-m-d H:i:s');
        $visitor_uuid = $validated_params['visitor_uuid'];

        // Read stored categories so we can record per-purpose denials in the webhook.
        $categories  = [];
        $stored_json = get_option('aesirx_analytics_plugin_options_disabled_block_domains', '');
        if (!empty($stored_json)) {
            $stored_items = json_decode($stored_json, true);
            if (is_array($stored_items)) {
                foreach ($stored_items as $item) {
                    if (!empty($item['category'])) {
                        $categories[] = sanitize_text_field($item['category']);
                    }
                }
                $categories = array_unique($categories);
            }
        }

        // Forward the Reject All event to GRC Suite via webhook
        if (AesirX_ComplianceOne_Webhook::is_enabled()) {
            $webhook = new AesirX_ComplianceOne_Webhook();
            $webhook->send(AesirX_ComplianceOne_Webhook::buildRejectAllPayload(
                $visitor_uuid,
                $validated_params,
                $categories
            ));
        }

        return true;
    }
}
