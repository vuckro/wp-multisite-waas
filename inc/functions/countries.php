<?php
/**
 * Country Functions
 *
 * @package WP_Ultimo\Functions
 * @since   2.0.0
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Returns the list of countries.
 *
 * @since 2.0.0
 * @return array
 */
function wu_get_countries() {

	return apply_filters('wu_get_countries', array(
		'AF' => __('Afghanistan', 'wp-ultimo-locations'),
		'AX' => __('&#197;land Islands', 'wp-ultimo-locations'),
		'AL' => __('Albania', 'wp-ultimo-locations'),
		'DZ' => __('Algeria', 'wp-ultimo-locations'),
		'AS' => __('American Samoa', 'wp-ultimo-locations'),
		'AD' => __('Andorra', 'wp-ultimo-locations'),
		'AO' => __('Angola', 'wp-ultimo-locations'),
		'AI' => __('Anguilla', 'wp-ultimo-locations'),
		'AQ' => __('Antarctica', 'wp-ultimo-locations'),
		'AG' => __('Antigua and Barbuda', 'wp-ultimo-locations'),
		'AR' => __('Argentina', 'wp-ultimo-locations'),
		'AM' => __('Armenia', 'wp-ultimo-locations'),
		'AW' => __('Aruba', 'wp-ultimo-locations'),
		'AU' => __('Australia', 'wp-ultimo-locations'),
		'AT' => __('Austria', 'wp-ultimo-locations'),
		'AZ' => __('Azerbaijan', 'wp-ultimo-locations'),
		'BS' => __('Bahamas', 'wp-ultimo-locations'),
		'BH' => __('Bahrain', 'wp-ultimo-locations'),
		'BD' => __('Bangladesh', 'wp-ultimo-locations'),
		'BB' => __('Barbados', 'wp-ultimo-locations'),
		'BY' => __('Belarus', 'wp-ultimo-locations'),
		'BE' => __('Belgium', 'wp-ultimo-locations'),
		'PW' => __('Belau', 'wp-ultimo-locations'),
		'BZ' => __('Belize', 'wp-ultimo-locations'),
		'BJ' => __('Benin', 'wp-ultimo-locations'),
		'BM' => __('Bermuda', 'wp-ultimo-locations'),
		'BT' => __('Bhutan', 'wp-ultimo-locations'),
		'BO' => __('Bolivia', 'wp-ultimo-locations'),
		'BQ' => __('Bonaire, Saint Eustatius and Saba', 'wp-ultimo-locations'),
		'BA' => __('Bosnia and Herzegovina', 'wp-ultimo-locations'),
		'BW' => __('Botswana', 'wp-ultimo-locations'),
		'BV' => __('Bouvet Island', 'wp-ultimo-locations'),
		'BR' => __('Brazil', 'wp-ultimo-locations'),
		'IO' => __('British Indian Ocean Territory', 'wp-ultimo-locations'),
		'VG' => __('British Virgin Islands', 'wp-ultimo-locations'),
		'BN' => __('Brunei', 'wp-ultimo-locations'),
		'BG' => __('Bulgaria', 'wp-ultimo-locations'),
		'BF' => __('Burkina Faso', 'wp-ultimo-locations'),
		'BI' => __('Burundi', 'wp-ultimo-locations'),
		'KH' => __('Cambodia', 'wp-ultimo-locations'),
		'CM' => __('Cameroon', 'wp-ultimo-locations'),
		'CA' => __('Canada', 'wp-ultimo-locations'),
		'CV' => __('Cape Verde', 'wp-ultimo-locations'),
		'KY' => __('Cayman Islands', 'wp-ultimo-locations'),
		'CF' => __('Central African Republic', 'wp-ultimo-locations'),
		'TD' => __('Chad', 'wp-ultimo-locations'),
		'CL' => __('Chile', 'wp-ultimo-locations'),
		'CN' => __('China', 'wp-ultimo-locations'),
		'CX' => __('Christmas Island', 'wp-ultimo-locations'),
		'CC' => __('Cocos (Keeling) Islands', 'wp-ultimo-locations'),
		'CO' => __('Colombia', 'wp-ultimo-locations'),
		'KM' => __('Comoros', 'wp-ultimo-locations'),
		'CG' => __('Congo (Brazzaville)', 'wp-ultimo-locations'),
		'CD' => __('Congo (Kinshasa)', 'wp-ultimo-locations'),
		'CK' => __('Cook Islands', 'wp-ultimo-locations'),
		'CR' => __('Costa Rica', 'wp-ultimo-locations'),
		'HR' => __('Croatia', 'wp-ultimo-locations'),
		'CU' => __('Cuba', 'wp-ultimo-locations'),
		'CW' => __('Cura&ccedil;ao', 'wp-ultimo-locations'),
		'CY' => __('Cyprus', 'wp-ultimo-locations'),
		'CZ' => __('Czech Republic', 'wp-ultimo-locations'),
		'DK' => __('Denmark', 'wp-ultimo-locations'),
		'DJ' => __('Djibouti', 'wp-ultimo-locations'),
		'DM' => __('Dominica', 'wp-ultimo-locations'),
		'DO' => __('Dominican Republic', 'wp-ultimo-locations'),
		'EC' => __('Ecuador', 'wp-ultimo-locations'),
		'EG' => __('Egypt', 'wp-ultimo-locations'),
		'SV' => __('El Salvador', 'wp-ultimo-locations'),
		'GQ' => __('Equatorial Guinea', 'wp-ultimo-locations'),
		'ER' => __('Eritrea', 'wp-ultimo-locations'),
		'EE' => __('Estonia', 'wp-ultimo-locations'),
		'ET' => __('Ethiopia', 'wp-ultimo-locations'),
		'FK' => __('Falkland Islands', 'wp-ultimo-locations'),
		'FO' => __('Faroe Islands', 'wp-ultimo-locations'),
		'FJ' => __('Fiji', 'wp-ultimo-locations'),
		'FI' => __('Finland', 'wp-ultimo-locations'),
		'FR' => __('France', 'wp-ultimo-locations'),
		'GF' => __('French Guiana', 'wp-ultimo-locations'),
		'PF' => __('French Polynesia', 'wp-ultimo-locations'),
		'TF' => __('French Southern Territories', 'wp-ultimo-locations'),
		'GA' => __('Gabon', 'wp-ultimo-locations'),
		'GM' => __('Gambia', 'wp-ultimo-locations'),
		'GE' => __('Georgia', 'wp-ultimo-locations'),
		'DE' => __('Germany', 'wp-ultimo-locations'),
		'GH' => __('Ghana', 'wp-ultimo-locations'),
		'GI' => __('Gibraltar', 'wp-ultimo-locations'),
		'GR' => __('Greece', 'wp-ultimo-locations'),
		'GL' => __('Greenland', 'wp-ultimo-locations'),
		'GD' => __('Grenada', 'wp-ultimo-locations'),
		'GP' => __('Guadeloupe', 'wp-ultimo-locations'),
		'GU' => __('Guam', 'wp-ultimo-locations'),
		'GT' => __('Guatemala', 'wp-ultimo-locations'),
		'GG' => __('Guernsey', 'wp-ultimo-locations'),
		'GN' => __('Guinea', 'wp-ultimo-locations'),
		'GW' => __('Guinea-Bissau', 'wp-ultimo-locations'),
		'GY' => __('Guyana', 'wp-ultimo-locations'),
		'HT' => __('Haiti', 'wp-ultimo-locations'),
		'HM' => __('Heard Island and McDonald Islands', 'wp-ultimo-locations'),
		'HN' => __('Honduras', 'wp-ultimo-locations'),
		'HK' => __('Hong Kong', 'wp-ultimo-locations'),
		'HU' => __('Hungary', 'wp-ultimo-locations'),
		'IS' => __('Iceland', 'wp-ultimo-locations'),
		'IN' => __('India', 'wp-ultimo-locations'),
		'ID' => __('Indonesia', 'wp-ultimo-locations'),
		'IR' => __('Iran', 'wp-ultimo-locations'),
		'IQ' => __('Iraq', 'wp-ultimo-locations'),
		'IE' => __('Ireland', 'wp-ultimo-locations'),
		'IM' => __('Isle of Man', 'wp-ultimo-locations'),
		'IL' => __('Israel', 'wp-ultimo-locations'),
		'IT' => __('Italy', 'wp-ultimo-locations'),
		'CI' => __('Ivory Coast', 'wp-ultimo-locations'),
		'JM' => __('Jamaica', 'wp-ultimo-locations'),
		'JP' => __('Japan', 'wp-ultimo-locations'),
		'JE' => __('Jersey', 'wp-ultimo-locations'),
		'JO' => __('Jordan', 'wp-ultimo-locations'),
		'KZ' => __('Kazakhstan', 'wp-ultimo-locations'),
		'KE' => __('Kenya', 'wp-ultimo-locations'),
		'KI' => __('Kiribati', 'wp-ultimo-locations'),
		'KW' => __('Kuwait', 'wp-ultimo-locations'),
		'KG' => __('Kyrgyzstan', 'wp-ultimo-locations'),
		'LA' => __('Laos', 'wp-ultimo-locations'),
		'LV' => __('Latvia', 'wp-ultimo-locations'),
		'LB' => __('Lebanon', 'wp-ultimo-locations'),
		'LS' => __('Lesotho', 'wp-ultimo-locations'),
		'LR' => __('Liberia', 'wp-ultimo-locations'),
		'LY' => __('Libya', 'wp-ultimo-locations'),
		'LI' => __('Liechtenstein', 'wp-ultimo-locations'),
		'LT' => __('Lithuania', 'wp-ultimo-locations'),
		'LU' => __('Luxembourg', 'wp-ultimo-locations'),
		'MO' => __('Macao S.A.R., China', 'wp-ultimo-locations'),
		'MK' => __('Macedonia', 'wp-ultimo-locations'),
		'MG' => __('Madagascar', 'wp-ultimo-locations'),
		'MW' => __('Malawi', 'wp-ultimo-locations'),
		'MY' => __('Malaysia', 'wp-ultimo-locations'),
		'MV' => __('Maldives', 'wp-ultimo-locations'),
		'ML' => __('Mali', 'wp-ultimo-locations'),
		'MT' => __('Malta', 'wp-ultimo-locations'),
		'MH' => __('Marshall Islands', 'wp-ultimo-locations'),
		'MQ' => __('Martinique', 'wp-ultimo-locations'),
		'MR' => __('Mauritania', 'wp-ultimo-locations'),
		'MU' => __('Mauritius', 'wp-ultimo-locations'),
		'YT' => __('Mayotte', 'wp-ultimo-locations'),
		'MX' => __('Mexico', 'wp-ultimo-locations'),
		'FM' => __('Micronesia', 'wp-ultimo-locations'),
		'MD' => __('Moldova', 'wp-ultimo-locations'),
		'MC' => __('Monaco', 'wp-ultimo-locations'),
		'MN' => __('Mongolia', 'wp-ultimo-locations'),
		'ME' => __('Montenegro', 'wp-ultimo-locations'),
		'MS' => __('Montserrat', 'wp-ultimo-locations'),
		'MA' => __('Morocco', 'wp-ultimo-locations'),
		'MZ' => __('Mozambique', 'wp-ultimo-locations'),
		'MM' => __('Myanmar', 'wp-ultimo-locations'),
		'NA' => __('Namibia', 'wp-ultimo-locations'),
		'NR' => __('Nauru', 'wp-ultimo-locations'),
		'NP' => __('Nepal', 'wp-ultimo-locations'),
		'NL' => __('Netherlands', 'wp-ultimo-locations'),
		'NC' => __('New Caledonia', 'wp-ultimo-locations'),
		'NZ' => __('New Zealand', 'wp-ultimo-locations'),
		'NI' => __('Nicaragua', 'wp-ultimo-locations'),
		'NE' => __('Niger', 'wp-ultimo-locations'),
		'NG' => __('Nigeria', 'wp-ultimo-locations'),
		'NU' => __('Niue', 'wp-ultimo-locations'),
		'NF' => __('Norfolk Island', 'wp-ultimo-locations'),
		'MP' => __('Northern Mariana Islands', 'wp-ultimo-locations'),
		'KP' => __('North Korea', 'wp-ultimo-locations'),
		'NO' => __('Norway', 'wp-ultimo-locations'),
		'OM' => __('Oman', 'wp-ultimo-locations'),
		'PK' => __('Pakistan', 'wp-ultimo-locations'),
		'PS' => __('Palestinian Territory', 'wp-ultimo-locations'),
		'PA' => __('Panama', 'wp-ultimo-locations'),
		'PG' => __('Papua New Guinea', 'wp-ultimo-locations'),
		'PY' => __('Paraguay', 'wp-ultimo-locations'),
		'PE' => __('Peru', 'wp-ultimo-locations'),
		'PH' => __('Philippines', 'wp-ultimo-locations'),
		'PN' => __('Pitcairn', 'wp-ultimo-locations'),
		'PL' => __('Poland', 'wp-ultimo-locations'),
		'PT' => __('Portugal', 'wp-ultimo-locations'),
		'PR' => __('Puerto Rico', 'wp-ultimo-locations'),
		'QA' => __('Qatar', 'wp-ultimo-locations'),
		'RE' => __('Reunion', 'wp-ultimo-locations'),
		'RO' => __('Romania', 'wp-ultimo-locations'),
		'RU' => __('Russia', 'wp-ultimo-locations'),
		'RW' => __('Rwanda', 'wp-ultimo-locations'),
		'BL' => __('Saint Barth&eacute;lemy', 'wp-ultimo-locations'),
		'SH' => __('Saint Helena', 'wp-ultimo-locations'),
		'KN' => __('Saint Kitts and Nevis', 'wp-ultimo-locations'),
		'LC' => __('Saint Lucia', 'wp-ultimo-locations'),
		'MF' => __('Saint Martin (French part)', 'wp-ultimo-locations'),
		'SX' => __('Saint Martin (Dutch part)', 'wp-ultimo-locations'),
		'PM' => __('Saint Pierre and Miquelon', 'wp-ultimo-locations'),
		'VC' => __('Saint Vincent and the Grenadines', 'wp-ultimo-locations'),
		'SM' => __('San Marino', 'wp-ultimo-locations'),
		'ST' => __('S&atilde;o Tom&eacute; and Pr&iacute;ncipe', 'wp-ultimo-locations'),
		'SA' => __('Saudi Arabia', 'wp-ultimo-locations'),
		'SN' => __('Senegal', 'wp-ultimo-locations'),
		'RS' => __('Serbia', 'wp-ultimo-locations'),
		'SC' => __('Seychelles', 'wp-ultimo-locations'),
		'SL' => __('Sierra Leone', 'wp-ultimo-locations'),
		'SG' => __('Singapore', 'wp-ultimo-locations'),
		'SK' => __('Slovakia', 'wp-ultimo-locations'),
		'SI' => __('Slovenia', 'wp-ultimo-locations'),
		'SB' => __('Solomon Islands', 'wp-ultimo-locations'),
		'SO' => __('Somalia', 'wp-ultimo-locations'),
		'ZA' => __('South Africa', 'wp-ultimo-locations'),
		'GS' => __('South Georgia/Sandwich Islands', 'wp-ultimo-locations'),
		'KR' => __('South Korea', 'wp-ultimo-locations'),
		'SS' => __('South Sudan', 'wp-ultimo-locations'),
		'ES' => __('Spain', 'wp-ultimo-locations'),
		'LK' => __('Sri Lanka', 'wp-ultimo-locations'),
		'SD' => __('Sudan', 'wp-ultimo-locations'),
		'SR' => __('Suriname', 'wp-ultimo-locations'),
		'SJ' => __('Svalbard and Jan Mayen', 'wp-ultimo-locations'),
		'SZ' => __('Swaziland', 'wp-ultimo-locations'),
		'SE' => __('Sweden', 'wp-ultimo-locations'),
		'CH' => __('Switzerland', 'wp-ultimo-locations'),
		'SY' => __('Syria', 'wp-ultimo-locations'),
		'TW' => __('Taiwan', 'wp-ultimo-locations'),
		'TJ' => __('Tajikistan', 'wp-ultimo-locations'),
		'TZ' => __('Tanzania', 'wp-ultimo-locations'),
		'TH' => __('Thailand', 'wp-ultimo-locations'),
		'TL' => __('Timor-Leste', 'wp-ultimo-locations'),
		'TG' => __('Togo', 'wp-ultimo-locations'),
		'TK' => __('Tokelau', 'wp-ultimo-locations'),
		'TO' => __('Tonga', 'wp-ultimo-locations'),
		'TT' => __('Trinidad and Tobago', 'wp-ultimo-locations'),
		'TN' => __('Tunisia', 'wp-ultimo-locations'),
		'TR' => __('Turkey', 'wp-ultimo-locations'),
		'TM' => __('Turkmenistan', 'wp-ultimo-locations'),
		'TC' => __('Turks and Caicos Islands', 'wp-ultimo-locations'),
		'TV' => __('Tuvalu', 'wp-ultimo-locations'),
		'UG' => __('Uganda', 'wp-ultimo-locations'),
		'UA' => __('Ukraine', 'wp-ultimo-locations'),
		'AE' => __('United Arab Emirates', 'wp-ultimo-locations'),
		'GB' => __('United Kingdom (UK)', 'wp-ultimo-locations'),
		'US' => __('United States (US)', 'wp-ultimo-locations'),
		'UM' => __('United States (US) Minor Outlying Islands', 'wp-ultimo-locations'),
		'VI' => __('United States (US) Virgin Islands', 'wp-ultimo-locations'),
		'UY' => __('Uruguay', 'wp-ultimo-locations'),
		'UZ' => __('Uzbekistan', 'wp-ultimo-locations'),
		'VU' => __('Vanuatu', 'wp-ultimo-locations'),
		'VA' => __('Vatican', 'wp-ultimo-locations'),
		'VE' => __('Venezuela', 'wp-ultimo-locations'),
		'VN' => __('Vietnam', 'wp-ultimo-locations'),
		'WF' => __('Wallis and Futuna', 'wp-ultimo-locations'),
		'EH' => __('Western Sahara', 'wp-ultimo-locations'),
		'WS' => __('Samoa', 'wp-ultimo-locations'),
		'YE' => __('Yemen', 'wp-ultimo-locations'),
		'ZM' => __('Zambia', 'wp-ultimo-locations'),
		'ZW' => __('Zimbabwe', 'wp-ultimo-locations'),
	));

} // end wu_get_countries;

/**
 * Returns the list of countries with an additional empty state option.
 *
 * @since 2.0.0
 * @return array
 */
function wu_get_countries_as_options() {

	return array_merge(array(
		'' => __('Select Country', 'wp-ultimo'),
	), wu_get_countries());

} // end wu_get_countries_as_options;

/**
 * Returns the country object.
 *
 * @since 2.0.11
 *
 * @param string      $country_code Two-letter country ISO code.
 * @param string|null $name The country name.
 * @param array       $fallback_attributes Fallback attributes if the country class is not present.
 * @return \WP_Ultimo\Country\Country
 */
function wu_get_country($country_code, $name = null, $fallback_attributes = array()) {

	$country_code = strtoupper($country_code);

	$country_class = "\\WP_Ultimo\\Country\\Country_{$country_code}";

	if (class_exists($country_class)) {

		return $country_class::get_instance();

	} // end if;

	return \WP_Ultimo\Country\Country_Default::build($country_code, $name, $fallback_attributes);

} // end wu_get_country;

/**
 * Get the state list for a country.
 *
 * @since 2.0.12
 *
 * @param string $country_code The country code.
 * @param string $key_name The name to use for the key entry.
 * @param string $value_name The name to use for the value entry.
 * @return array
 */
function wu_get_country_states($country_code, $key_name = 'id', $value_name = 'value') {

	static $state_options = array();

	$options = array();

	$cache = wu_get_isset($state_options, $country_code, false);

	if ($cache) {

		$options = $cache;

	} else {

		$country = wu_get_country($country_code);

		$state_options[$country_code] = $country->get_states_as_options(false);

		$options = $state_options[$country_code];

	} // end if;

	if (empty($key_name)) {

		return $options;

	} // end if;

	return wu_key_map_to_array($options, $key_name, $value_name);

} // end wu_get_country_states;

/**
 * Get cities for a collection of states of a country.
 *
 * @since 2.0.11
 *
 * @param string $country_code The country code.
 * @param array  $states The list of state codes to retrieve.
 * @param string $key_name The name to use for the key entry.
 * @param string $value_name The name to use for the value entry.
 * @return array
 */
function wu_get_country_cities($country_code, $states, $key_name = 'id', $value_name = 'value') {

	static $city_options = array();

	$states = (array) $states;

	$options = array();

	foreach ($states as $state_code) {

		$cache = wu_get_isset($city_options, $state_code, false);

		if ($cache) {

			$options = array_merge($options, $cache);

		} else {

			$country = wu_get_country($country_code);

			$city_options[$state_code] = $country->get_cities_as_options($state_code, false);

			$options = array_merge($options, $city_options[$state_code]);

		} // end if;

	} // end foreach;

	if (empty($key_name)) {

		return $options;

	} // end if;

	return wu_key_map_to_array($options, $key_name, $value_name);

} // end wu_get_country_cities;

/**
 * Returns the country name for a given country code.
 *
 * @since 2.0.0
 *
 * @param string $country_code Country code.
 * @return string
 */
function wu_get_country_name($country_code) {

	$country_name = wu_get_isset(wu_get_countries(), $country_code, __('Not found', 'wp-ultimo'));

	return apply_filters('wu_get_country_name', $country_name, $country_code);

} // end wu_get_country_name;

/**
 * Get the list of countries and counts based on customers.
 *
 * @since 2.0.0
 * @param integer        $count The number of results to return.
 * @param boolean|string $start_date The start date.
 * @param boolean|string $end_date The end date.
 * @return array
 */
function wu_get_countries_of_customers($count = 10, $start_date = false, $end_date = false) {

	global $wpdb;

	$table_name          = "{$wpdb->base_prefix}wu_customermeta";
	$customer_table_name = "{$wpdb->base_prefix}wu_customers";

	$date_query = '';

	if ($start_date || $end_date) {

		$date_query = 'AND c.date_registered >= %s AND c.date_registered <= %s';

		$date_query = $wpdb->prepare($date_query, $start_date . ' 00:00:00', $end_date . " 23:59:59"); // phpcs:ignore

	} // end if;

	$query = "
		SELECT m.meta_value as country, COUNT(distinct c.id) as count
		FROM `{$table_name}` as m
		INNER JOIN `{$customer_table_name}` as c ON m.wu_customer_id = c.id
		WHERE m.meta_key = 'ip_country' AND m.meta_value != ''
		$date_query
		GROUP BY m.meta_value
		ORDER BY count DESC
		LIMIT %d
	";

	$query = $wpdb->prepare($query, $count); // phpcs:ignore

	$results = $wpdb->get_results($query); // phpcs:ignore

	$countries = array();

	foreach ($results as $result) {

		$countries[$result->country] = $result->count;

	} // end foreach;

	return $countries;

} // end wu_get_countries_of_customers;

/**
 * Get the list of countries and counts based on customers.
 *
 * @since 2.0.0
 * @param string         $country_code The country code.
 * @param integer        $count The number of results to return.
 * @param boolean|string $start_date The start date.
 * @param boolean|string $end_date The end date.
 * @return array
 */
function wu_get_states_of_customers($country_code, $count = 100, $start_date = false, $end_date = false) {

	global $wpdb;

	static $states = array();

	$table_name          = "{$wpdb->base_prefix}wu_customermeta";
	$customer_table_name = "{$wpdb->base_prefix}wu_customers";

	$date_query = '';

	if ($start_date || $end_date) {

		$date_query = 'AND c.date_registered >= %s AND c.date_registered <= %s';

		$date_query = $wpdb->prepare($date_query, $start_date . ' 00:00:00', $end_date . " 23:59:59"); // phpcs:ignore

	} // end if;

	$states = wu_get_country_states('BR', false);

	if (empty($states)) {

		return array();

	} // end if;

	$states_in = implode("','", array_keys($states));

	$query = "
		SELECT m.meta_value as state, COUNT(distinct c.id) as count
		FROM `{$table_name}` as m
		INNER JOIN `{$customer_table_name}` as c ON m.wu_customer_id = c.id
		WHERE m.meta_key = 'ip_state' AND m.meta_value IN ('$states_in')
		$date_query
		GROUP BY m.meta_value
		ORDER BY count DESC
		LIMIT %d
	";

	$query = $wpdb->prepare($query, $count); // phpcs:ignore

	$results = $wpdb->get_results($query); // phpcs:ignore

	if (empty($results)) {

		return array();

	} // end if;

	$_states = array();

	foreach ($results as $result) {

		$final_label = sprintf('%s (%s)', $states[$result->state], $result->state);

		$_states[$final_label] = absint($result->count);

	} // end foreach;

	return $_states;

} // end wu_get_states_of_customers;
