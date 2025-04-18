<?php


use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Get_Total_Consent_Tier extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        $where_clause = [];
        $bind = [];

        parent::aesirx_analytics_add_consent_filters($params, $where_clause, $bind);

        $sql =
        "SELECT 
            tier,
            ROUND(COUNT(*) / 2) AS total
        FROM (
            SELECT
            CASE 
                WHEN visitor_consent.tier = '5' THEN 5
                WHEN visitor_consent.tier = '6' THEN 6
                WHEN consent.web3id IS NOT NULL AND consent.wallet_uuid IS NOT NULL THEN 4 
                WHEN consent.web3id IS NULL AND consent.wallet_uuid IS NOT NULL THEN 3 
                WHEN consent.web3id IS NOT NULL AND consent.wallet_uuid IS NULL THEN 2 
                ELSE 1 
            END AS tier
            FROM `#__analytics_visitor_consent` AS visitor_consent 
            LEFT JOIN `#__analytics_visitors` AS visitors ON visitors.uuid = visitor_consent.visitor_uuid 
            LEFT JOIN `#__analytics_consent` AS consent ON consent.uuid = visitor_consent.consent_uuid
            WHERE " . implode(' AND ', $where_clause) ."
        )
        AS tier_data 
        GROUP BY tier";

        $total_sql =
            "SELECT 
            COUNT(DISTINCT CASE 
            WHEN visitor_consent.tier = '5' THEN 5
            WHEN visitor_consent.tier = '6' THEN 6
            WHEN visitor_consent.consent_uuid IS NULL THEN 1 
            WHEN consent.web3id IS NOT NULL AND consent.wallet_uuid IS NOT NULL THEN 4 
            WHEN consent.web3id IS NULL AND consent.wallet_uuid IS NOT NULL THEN 3 
            WHEN consent.web3id IS NOT NULL AND consent.wallet_uuid IS NULL THEN 2 
            ELSE 1 END) AS total 
            FROM `#__analytics_visitor_consent` AS visitor_consent 
            LEFT JOIN `#__analytics_visitors` AS visitors ON visitors.uuid = visitor_consent.visitor_uuid 
            LEFT JOIN `#__analytics_consent` AS consent ON consent.uuid = visitor_consent.consent_uuid 
            WHERE " . implode(" AND ", $where_clause);

        $sort = self::aesirx_analytics_add_sort($params, ["tier", "total"], "tier");

        if (!empty($sort)) {
            $sql .= " ORDER BY " . implode(", ", $sort);
        }

        return parent::aesirx_analytics_get_list($sql, $total_sql, $params, [], $bind);
    }
}
