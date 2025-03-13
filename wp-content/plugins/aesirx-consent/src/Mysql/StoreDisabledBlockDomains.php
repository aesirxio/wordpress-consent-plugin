<?php


use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Store_Disabled_Block_Domains extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        $response = [];
        if($params[1]) {
            $jsonBlockDomain = json_encode($params[1], JSON_PRETTY_PRINT);
            update_option('aesirx_analytics_plugin_options_disabled_block_domains', $jsonBlockDomain);
        } else {
            update_option('aesirx_analytics_plugin_options_disabled_block_domains', '');
        }
        $response['disabled_block_domains'] = sanitize_text_field($jsonBlockDomain);
        return $response;
    }
}
