<?php

/**
 * AesirX ComplianceOne — Consent Webhook Integration
 *
 * Sends consent grant and revoke events from the AesirX Analytics WordPress
 * plugin to the GRC Suite CMP webhook endpoint.
 *
 * Endpoint:  POST {datastream_compliance_one_endpoint}/api/v1/consent/cmp-webhook
 * Auth:      HMAC-SHA256 signature over the raw JSON body, sent in the
 *            "x-aesirx-signature" request header.
 *
 * consent_action values:
 *   accept_all  — user clicked Accept All
 *   reject_all  — user clicked Reject All
 *   grant       — user saved custom preferences (customize flow)
 *   update      — user updated existing consent choices
 *   revoke      — user withdrew consent from account settings
 *
 * banner_interaction values:
 *   accept_all  — Accept All button
 *   reject_all  — Reject All button
 *   customize   — opened preferences/customize panel
 *   close       — dismissed banner without choosing
 *   ignore      — banner shown, no interaction
 *
 * Usage — drop these calls into your existing handlers:
 *
 *   // AddConsentLevel1 (no-categories case only — StoreDisabledBlockDomains not called)
 *   $webhook->send(AesirX_ComplianceOne_Webhook::buildConsentPayload(
 *       $params['uuid'], (int) $params['consent'], $params
 *   ));
 *
 *   // RejectConsentLevel1 after DB updates
 *   $webhook->send(AesirX_ComplianceOne_Webhook::buildRejectAllPayload(
 *       $validated_params['visitor_uuid'], $validated_params
 *   ));
 *
 *   // RevokeConsentLevel1/5 after DB updates
 *   $webhook->send(AesirX_ComplianceOne_Webhook::buildRevokePayload(
 *       $validated_params['visitor_uuid'], $validated_params, $categories
 *   ));
 *
 *   // StoreDisabledBlockDomains after DB inserts
 *   $webhook->send(AesirX_ComplianceOne_Webhook::buildCustomizePayload(
 *       $params[3], $params[2], $params[1] ?? [], $params
 *   ));
 */
class AesirX_ComplianceOne_Webhook
{
    private string $endpoint;
    private string $secret;

    public function __construct()
    {
        $options        = get_option('aesirx_analytics_plugin_options', []);
        $this->endpoint = rtrim((string) ($options['datastream_compliance_one_endpoint'] ?? ''), '/');
        $this->secret   = (string) ($options['datastream_compliance_one_secret'] ?? '');
    }

    /**
     * Returns true when both endpoint and secret are configured (instance method).
     */
    public function is_configured(): bool
    {
        return $this->endpoint !== '' && $this->secret !== '';
    }

    /**
     * Static check — returns true when both options exist and are non-empty.
     * Use this to bail out early before instantiating the class.
     */
    public static function is_enabled(): bool
    {
        $options = get_option('aesirx_analytics_plugin_options', []);
        return !empty($options['datastream_compliance_one_endpoint'])
            && !empty($options['datastream_compliance_one_secret']);
    }

    /**
     * Sign and POST a consent payload to the GRC Suite webhook endpoint.
     *
     * @param  array            $payload  Consent event payload.
     * @return array|WP_Error   Decoded JSON response body, or WP_Error on failure.
     */
    public function send(array $payload)
    {
        if (!$this->is_configured()) {
            return new WP_Error(
                'not_configured',
                esc_html__('ComplianceOne webhook endpoint or secret is not configured.', 'aesirx-consent')
            );
        }

        $body      = wp_json_encode($payload);
        $signature = hash_hmac('sha256', $body, $this->secret);

        // Build the full webhook URL. If the stored endpoint already contains the
        // webhook path (e.g. was copied from the GRC Suite config page), strip it
        // first so the path is never doubled.
        $webhook_path = '/api/v1/consent/cmp-webhook';
        $base         = rtrim(str_replace($webhook_path, '', $this->endpoint), '/');
        $url          = $base . $webhook_path;

        $response = wp_remote_post(
            $url,
            [
                'headers'     => [
                    'Content-Type'       => 'application/json',
                    'x-aesirx-signature' => $signature,
                ],
                'body'        => $body,
                'data_format' => 'body',
                'timeout'     => 10,
            ]
        );
        if (is_wp_error($response)) {
            return $response;
        }

        $status        = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);

        // 202 Accepted — new record stored
        // 409 Conflict  — duplicate event, already recorded (safe to ignore)
        if (!in_array($status, [202, 409], true)) {
            return new WP_Error(
                'webhook_failed',
                sprintf(
                    /* translators: %d: HTTP status code */
                    esc_html__('ComplianceOne webhook returned HTTP %d.', 'aesirx-consent'),
                    $status
                ),
                ['response' => $response_body]
            );
        }

        return $response_body ?? [];
    }

    // ── Payload Builders ─────────────────────────────────────────────────────

    /**
     * Build an Accept All payload from AddConsentLevel1 parameters.
     *
     * Used only when blocking_cookies_category is empty and StoreDisabledBlockDomains
     * is not called. The consent level integer is used as the cmp_purpose_id.
     *
     * consent_action: "accept_all" | banner_interaction: "accept_all"
     *
     * @param  string $visitor_uuid  Visitor UUID  ($params['uuid']).
     * @param  int    $consent_level Consent level ($params['consent']).
     * @param  array  $params        Full params array (may contain 'domain', 'session_id').
     * @return array
     */
    public static function buildConsentPayload(
        string $visitor_uuid,
        int $consent_level,
        array $params = []
    ): array {
        return [
            'visitor_id'         => $visitor_uuid,
            'domain'             => self::resolveDomain($params),
            'session_id'         => sanitize_text_field($params['session_id'] ?? ''),
            'consent_action'     => 'accept_all',
            'banner_interaction' => 'accept_all',
            'consent_version'           => self::getVersion('consent_version'),
            'policy_version'            => self::getVersion('policy_version'),
            'cookie_declaration_version' => self::getVersion('cookie_declaration_version'),
            'user'               => self::getCurrentWpUser(),
            'timestamp'          => gmdate('c'),
            'purposes'           => [
                [
                    'cmp_purpose_id' => (string) $consent_level,
                    'status'         => 'granted',
                    'legal_basis'    => 'consent',
                ],
            ],
        ];
    }

    /**
     * Build a Reject All payload from RejectConsentLevel1 parameters.
     *
     * Used when the visitor clicks the Reject All button on the consent banner.
     * Pass $categories to record per-purpose denials in analytics.
     *
     * consent_action: "reject_all" | banner_interaction: "reject_all"
     *
     * @param  string   $visitor_uuid  Visitor UUID ($params['visitor_uuid']).
     * @param  array    $params        Full params array (may contain 'domain', 'session_id').
     * @param  string[] $categories    Optional list of category names to mark as denied.
     * @return array
     */
    public static function buildRejectAllPayload(
        string $visitor_uuid,
        array $params = [],
        array $categories = []
    ): array {
        $purposes = [];
        foreach ($categories as $category) {
            if ($category === 'essential') {
                continue;
            }
            $purposes[] = [
                'cmp_purpose_id' => sanitize_text_field($category),
                'status'         => 'denied',
                'legal_basis'    => 'consent',
            ];
        }

        return [
            'visitor_id'         => $visitor_uuid,
            'domain'             => self::resolveDomain($params),
            'session_id'         => sanitize_text_field($params['session_id'] ?? ''),
            'consent_action'     => 'reject_all',
            'banner_interaction' => 'reject_all',
            'consent_version'           => self::getVersion('consent_version'),
            'policy_version'            => self::getVersion('policy_version'),
            'cookie_declaration_version' => self::getVersion('cookie_declaration_version'),
            'user'               => self::getCurrentWpUser(),
            'timestamp'          => gmdate('c'),
            'purposes'           => $purposes,
        ];
    }

    /**
     * Build a consent-revoke payload from RevokeConsentLevel1/5 parameters.
     *
     * Used when the visitor withdraws previously granted consent from account
     * settings (not a banner Reject All click — use buildRejectAllPayload for that).
     * Pass $categories to record per-purpose denials (recommended).
     *
     * consent_action: "revoke" | banner_interaction: "reject_all"
     *
     * @param  string   $visitor_uuid  Visitor UUID ($params['visitor_uuid']).
     * @param  array    $params        Full params array (may contain 'domain', 'session_id').
     * @param  string[] $categories    Optional list of category names to mark as denied.
     * @return array
     */
    public static function buildRevokePayload(
        string $visitor_uuid,
        array $params = [],
        array $categories = []
    ): array {
        $purposes = [];
        foreach ($categories as $category) {
            if ($category === 'essential') {
                continue;
            }
            $purposes[] = [
                'cmp_purpose_id' => sanitize_text_field($category),
                'status'         => 'denied',
                'legal_basis'    => 'consent',
            ];
        }

        return [
            'visitor_id'         => $visitor_uuid,
            'domain'             => self::resolveDomain($params),
            'session_id'         => sanitize_text_field($params['session_id'] ?? ''),
            'consent_action'     => 'revoke',
            'banner_interaction' => 'reject_all',
            'consent_version'           => self::getVersion('consent_version'),
            'policy_version'            => self::getVersion('policy_version'),
            'cookie_declaration_version' => self::getVersion('cookie_declaration_version'),
            'user'               => self::getCurrentWpUser(),
            'timestamp'          => gmdate('c'),
            'purposes'           => $purposes,
        ];
    }

    /**
     * Build a consent payload from StoreDisabledBlockDomains parameters.
     *
     * Called for both full consent (Accept All) and customize flows:
     *   - Full consent:  $disabled_block_domains is empty  → consent_action "accept_all", all purposes "granted"
     *   - Customize:     $disabled_block_domains has items → consent_action "grant", mixed granted/denied
     *
     * The cmp_purpose_id is the category name (e.g. "analytics"), matching the
     * value AesirX CMP sends in the POST to /disabled-block-domains.
     *
     * @param  string $visitor_uuid            Visitor UUID ($params[3]).
     * @param  array  $list_category           All consent categories ($params[2]).
     * @param  array  $disabled_block_domains  Blocked domain items ($params[1]).
     * @param  array  $params                  Full params array (for domain/session).
     * @return array
     */
    public static function buildCustomizePayload(
        string $visitor_uuid,
        array $list_category,
        array $disabled_block_domains = [],
        array $params = []
    ): array {
        // Index denied domains by category for O(1) lookup
        $denied_by_category = [];
        foreach ($disabled_block_domains as $item) {
            if (empty($item['category']) || empty($item['domain'])) {
                continue;
            }
            $denied_by_category[$item['category']][$item['domain']] = [
                'domain' => sanitize_text_field($item['domain']),
                'name'   => sanitize_text_field($item['name'] ?? ''),
            ];
        }

        // Full consent when no domains are blocked
        $is_full_consent = empty($denied_by_category);

        // Full list of configured blocking domains, grouped by category
        $all_by_category = [];
        foreach (self::getAllBlockingDomains() as $entry) {
            if (empty($entry['category']) || empty($entry['domain'])) {
                continue;
            }
            $all_by_category[$entry['category']][] = [
                'domain' => sanitize_text_field($entry['domain']),
                'name'   => sanitize_text_field($entry['name'] ?? ''),
            ];
        }

        $purposes = [];
        foreach ($list_category as $category) {
            if ($category === 'essential') {
                continue; // essential is always granted, no need to report
            }

            $denied_map   = $denied_by_category[$category] ?? [];
            $all_domains  = $all_by_category[$category] ?? [];
            $granted_list = [];
            $denied_list  = array_values($denied_map);

            foreach ($all_domains as $entry) {
                if (!isset($denied_map[$entry['domain']])) {
                    $granted_list[] = $entry;
                }
            }

            $purposes[] = [
                'cmp_purpose_id'  => sanitize_text_field($category),
                'status'          => !empty($denied_list) ? 'denied' : 'granted',
                'legal_basis'     => 'consent',
                'granted_domains' => $granted_list,
                'denied_domains'  => $denied_list,
            ];
        }

        return [
            'visitor_id'         => $visitor_uuid,
            'domain'             => self::resolveDomain($params),
            'session_id'         => sanitize_text_field($params['session_id'] ?? ''),
            'consent_action'     => $is_full_consent ? 'accept_all' : 'grant',
            'banner_interaction' => $is_full_consent ? 'accept_all' : 'customize',
            'consent_version'           => self::getVersion('consent_version'),
            'policy_version'            => self::getVersion('policy_version'),
            'cookie_declaration_version' => self::getVersion('cookie_declaration_version'),
            'user'               => self::getCurrentWpUser(),
            'timestamp'          => gmdate('c'),
            'purposes'           => $purposes,
        ];
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Resolve the domain from params or fall back to the WordPress home URL host.
     */
    private static function resolveDomain(array $params): string
    {
        if (!empty($params['domain'])) {
            return sanitize_text_field($params['domain']);
        }
        return (string) wp_parse_url(home_url(), PHP_URL_HOST);
    }

    /**
     * Read a version field from the consent modal plugin settings.
     *
     * Supported keys: consent_version, policy_version, cookie_declaration_version.
     */
    private static function getVersion(string $key): string
    {
        $options = get_option('aesirx_consent_modal_plugin_options', []);
        return (string) ($options[$key] ?? '1.0.0');
    }

    /**
     * Return the logged-in WordPress user's display name, email and phone, or
     * null when the request is anonymous.
     *
     * Phone is read from user meta (first non-empty of: phone, billing_phone).
     *
     * @return array{display_name:string, email:string, phone:string}|null
     */
    private static function getCurrentWpUser(): ?array
    {
        if (!function_exists('wp_get_current_user') || !is_user_logged_in()) {
            return null;
        }
        $user = wp_get_current_user();
        if (!$user || empty($user->ID)) {
            return null;
        }

        $phone = '';
        foreach (['phone', 'billing_phone'] as $meta_key) {
            $value = get_user_meta($user->ID, $meta_key, true);
            if (is_string($value) && $value !== '') {
                $phone = $value;
                break;
            }
        }

        $display = trim((string) get_user_meta($user->ID, 'nickname', true));
        if ($display === '') {
            $first = trim((string) get_user_meta($user->ID, 'first_name', true));
            $last  = trim((string) get_user_meta($user->ID, 'last_name', true));
            $display = trim($first . ' ' . $last);
        }
        if ($display === '') {
            $display = (string) $user->display_name;
        }

        return [
            'display_name' => sanitize_text_field($display),
            'email'        => sanitize_email((string) $user->user_email),
            'phone'        => sanitize_text_field($phone),
        ];
    }

    /**
     * Rebuild the full `aesirxBlockJSDomains` list from plugin options.
     *
     * Mirrors the construction in aesirx-consent.php so the webhook can split
     * each category's domains into granted vs denied without relying on
     * client-side state.
     *
     * @return array<int, array{domain:string, category:string, name:string}>
     */
    private static function getAllBlockingDomains(): array
    {
        $options = get_option('aesirx_analytics_plugin_options', []);

        $paths = isset($options['blocking_cookies']) && is_array($options['blocking_cookies'])
            ? array_filter($options['blocking_cookies'], fn($v) => trim((string) $v) !== '')
            : [];
        $categories = (isset($options['blocking_cookies_category']) && is_array($options['blocking_cookies_category']))
            ? $options['blocking_cookies_category']
            : [];
        $plugins_raw = (isset($options['blocking_cookies_plugins']) && is_array($options['blocking_cookies_plugins']))
            ? $options['blocking_cookies_plugins']
            : [];
        $plugins_cat_raw = (isset($options['blocking_cookies_plugins_category']) && is_array($options['blocking_cookies_plugins_category']))
            ? $options['blocking_cookies_plugins_category']
            : [];

        $prefix = 'wp-content/plugins/';
        $plugins = !empty($plugins_raw)
            ? array_map(
                fn($v) => $prefix . $v,
                array_filter($plugins_raw, fn($v) => trim((string) $v) !== '')
            )
            : [];

        $plugins_category = [];
        $plugins_name     = [];
        foreach ($plugins_cat_raw as $slug => $pluginData) {
            if (!is_array($pluginData)) {
                continue;
            }
            foreach ($pluginData as $pluginName => $category) {
                $plugins_category[$prefix . $slug] = $category;
                $plugins_name[$prefix . $slug]     = $pluginName;
            }
        }

        $merged = array_values(array_unique(array_merge($paths, $plugins), SORT_REGULAR));

        $result = [];
        foreach ($merged as $key => $domain) {
            $result[] = [
                'domain'   => (string) $domain,
                'category' => (string) ($categories[$key] ?? ($plugins_category[$domain] ?? 'custom')),
                'name'     => (string) ($plugins_name[$domain] ?? ''),
            ];
        }
        return $result;
    }
}
