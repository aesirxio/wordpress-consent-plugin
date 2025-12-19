<?php


use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Get_Total_Consent_Region extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        $where_clause = [];
        $where_clause_2 = [];
        $bind = [];

        parent::aesirx_analytics_add_region_consent_filters($params, $where_clause, $bind, $where_clause_2);
        $sql = 
            "SELECT
                visitor.timezone,
                visitor.lang AS language,
                COUNT(DISTINCT CONCAT(category_consent.visitor_uuid, '|', category_consent.datetime)) AS total_consent_region,
                COUNT(DISTINCT CASE WHEN category_consent.tier != '5' THEN CONCAT(category_consent.visitor_uuid, '|', category_consent.datetime) END) AS opt_in_consent_region,
                COUNT(DISTINCT CASE WHEN category_consent.tier = '5' THEN CONCAT(category_consent.visitor_uuid, '|', category_consent.datetime) END) AS opt_out_consent_region,
                (
                    SELECT COUNT(DISTINCT c.datetime)
                    FROM `#__analytics_visitor_consent` c
                    JOIN `#__analytics_visitors` v ON c.visitor_uuid = v.uuid
                    WHERE " . implode(" AND ", $where_clause) ."
                ) AS total_consent
            FROM
                `#__analytics_visitors` visitor
            JOIN
                `#__analytics_visitor_consent` category_consent ON visitor.uuid = category_consent.visitor_uuid
            WHERE " . implode(" AND ", $where_clause_2) ."
            GROUP BY
                visitor.timezone,
                visitor.lang
            ORDER BY
                visitor.timezone, visitor.lang";

        $total_sql = "
            SELECT COUNT(*) FROM (
                SELECT 1
                FROM `#__analytics_visitors` visitor
                JOIN `#__analytics_visitor_consent` category_consent
                    ON visitor.uuid = category_consent.visitor_uuid
                WHERE " . implode(" AND ", $where_clause_2) . "
                GROUP BY visitor.timezone, visitor.lang
            ) AS total_table
        ";

        // $sort = self::aesirx_analytics_add_sort($params, ["date", "total"], "date");

        if (!empty($sort)) {
            $sql .= " ORDER BY " . implode(", ", $sort);
        }

        return parent::aesirx_analytics_get_list($sql, $total_sql, $params, [], array_merge($bind, $bind));
    }
}
