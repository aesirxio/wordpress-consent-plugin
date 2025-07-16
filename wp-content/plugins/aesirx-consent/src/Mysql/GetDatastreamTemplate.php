<?php


use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Get_Datastream_Template extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        $options = get_option('aesirx_analytics_plugin_options', []);
        $optionsConsentModal = get_option('aesirx_consent_modal_plugin_options', []);
        $optionsGPC = get_option('aesirx_consent_gpc_plugin_options', []);
        $disabled_block_domains = get_option('aesirx_analytics_plugin_options_disabled_block_domains', '');
        return [
            'domain' => $options['datastream_domain'],
            'template' => empty($optionsConsentModal['datastream_template']) ? 'simple-consent-mode' : $optionsConsentModal['datastream_template'],
            'gtag_id' => $options['datastream_gtag_id'],
            'gtm_id' => $options['datastream_gtm_id'],
            "consent_text" =>  $optionsConsentModal['datastream_consent'],
            "cookie_text" =>  $optionsConsentModal['datastream_cookie'],
            "detail_text" =>  $optionsConsentModal['datastream_detail'],
            "reject_text" =>  $optionsConsentModal['datastream_reject'],
            "gpc_consent" =>  $optionsGPC['gpc_consent'],
            'disabled_block_domains' => $disabled_block_domains
        ];
    }
}
