<?php


use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Get_Statement extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
     
        $optionsConsentModal = get_option('aesirx_consent_modal_plugin_options', []);
        $ageCheck = $optionsConsentModal['age_check'];
        $countryCheck = $optionsConsentModal['country_check'];
        $minimumAge = isset($optionsConsentModal['minimum_age']) ? (int)$optionsConsentModal['minimum_age'] : 0;
        $maximumAge = isset($optionsConsentModal['maximum_age']) ? (int)$optionsConsentModal['maximum_age'] : 150;
        $allowedCountries = $optionsConsentModal['allowed_countries'] ?? [];
        $disallowedCountries = $optionsConsentModal['disallowed_countries'] ?? [];
        $response = [];
        if ($countryCheck === "countryCheck") {
            if (!empty($allowedCountries)) {
                $countrySet = array_values($allowedCountries);
                $type = "AttributeInSet";
            } elseif (!empty($disallowedCountries)) {
                $countrySet = array_values($disallowedCountries);
                $type = "AttributeNotInSet";
            }
            if(!empty($countrySet)) {
                $response[] = [
                    "type" => $type,
                    "attributeTag" => "nationality",
                    "set" => $countrySet,
                ];
            }
        }
        
        if ($ageCheck === "ageCheck") {
            $today = new DateTime();
            if ($maximumAge > 0) {
                $lowerDate = $today->sub(new DateInterval("P{$maximumAge}Y"))->format('Ymd');
                $today->add(new DateInterval("P{$maximumAge}Y")); // reset
            } else {
                $lowerDate = '19000101'; // January 1st, 1900
            }
        
            if ($minimumAge > 0) {
                $upperDate = $today->sub(new DateInterval("P{$minimumAge}Y"))->format('Ymd');
            } else {
                $upperDate = $today->format('Ymd');
            }
            $response[] = [
                "type" => "AttributeInRange",
                "attributeTag" => "dob",
                "lower" => $lowerDate,
                "upper" => $upperDate,
            ];
        }
      
        return  $response;
    }
}
