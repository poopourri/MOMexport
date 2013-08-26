<?php

//Access the FoxyCart API
function foxyshop_get_foxycart_data($foxyData, $foxycart_domain, $foxycart_api_key) {
	global $foxycart_domain, $foxycart_api_key;

	$foxyData = array_merge(array("api_token" => $foxycart_api_key), $foxyData);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://" . $foxycart_domain . "/api");
	curl_setopt($ch, CURLOPT_POSTFIELDS, $foxyData);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	$response = trim(curl_exec($ch));
	if (!$response) {
		//$response = "<?xml version='1.0' encoding='UTF-8'><foxydata><result>ERROR</result><messages><message>Connection Error: " . curl_error($ch) . "</message></messages></foxydata>";
		die("Connection Error: " . curl_error($ch));
	}
	curl_close($ch);
	return $response;
}


function get_country_code($code) {
	$countries = getCountries();
	$full_country_names = full_country_names();

	$full_country_name = "";
	foreach ($full_country_names as $key => $val) {
		if ($key == $code) {
			$full_country_name = $val;
		}
	}
	if (!$full_country_name) {
		return "000";
	}

	foreach ($countries as $key => $val) {
		if ($val == $full_country_name) {
			return $key;
		}
	}
	return "000";

}
