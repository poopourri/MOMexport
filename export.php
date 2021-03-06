<?php
//Set Timezone and errors
date_default_timezone_set("America/Chicago");

//Set Config Details
$foxycart_domain = "poopourri.foxycart.com";
$foxycart_api_key = "spfxd22f6593863ada65d41ad99494dfe354f8ba8f6875fba646f1db0d35fbaa419b";
$entries_per_page = 300;

//Email Settings
//$to_email = "david@sparkweb.net"; //testing
//$to_email = "nealsharmon@gmail.com"; //testing
$to_email = "janette@poopourri.net";
$cc_email = "todd@poopourri.net";


//Setup Pagination
$pagination_start = 1;
if (isset($_GET['pagination_start'])) {
	$pagination_start = (int)$_GET['pagination_start'];
}

//Just Yesterday
$start_date = date("Y-m-d", strtotime("-1 day"));
$end_date = date("Y-m-d", strtotime("-1 day"));

//Today **testing
//$start_date = date("Y-m-d");
//$end_date = date("Y-m-d");

//Lots of Days **testing
//$start_date = date("Y-m-d", strtotime("-30 days"));
//$end_date = date("Y-m-d", strtotime("now"));


//Includes
require "class.phpmailer.php";
require "countries.php";
require "fields.php";
require "functions.php";

//Setup Args
$foxydata = array(
	"api_action" => "transaction_list",
	"is_test_filter" => 0,
	"transaction_date_filter_begin" => $start_date,
	"transaction_date_filter_end" => $end_date,
	"pagination_start" => $pagination_start,
	"entries_per_page" => $entries_per_page,
);

//Get Orders
$foxy_response = foxyshop_get_foxycart_data($foxydata, $foxycart_domain, $foxycart_api_key);
$xml = simplexml_load_string($foxy_response, NULL, LIBXML_NOCDATA);

//Stop On Error
if ((string)$xml->result == "ERROR") {
	foreach ($xml->messages->message as $message) {
		echo $message . "\n";
	}
	die;
}

//Setup Rows
$rows = array();
$rows[] = array_keys(getFieldTitles());

//Setup Statistics
$pagination_end = (int)$xml->statistics->pagination_end;
$filtered_total = (int)$xml->statistics->filtered_total;

//For Each Order
foreach ($xml->transactions->transaction as $transaction) {
	$cols = getFieldTitles();
	$extra_rows = array();

	//Basics
	$cols['source_key'] = "harmon";
	$cols['sales_id'] = "har";
	$cols['shipvia'] = "PM";
	$cols['continued'] = "";
	$cols['paymethod'] = "cc";
	$cols['useshipamt'] = "Y";
	$cols['internet'] = "T";

	//Billing Address
	$cols['firstname'] = (string)$transaction->customer_first_name;
	$cols['lastname'] = (string)$transaction->customer_last_name;
	$cols['company'] = (string)$transaction->customer_company;
	$cols['address1'] = (string)$transaction->customer_address1;
	$cols['address2'] = (string)$transaction->customer_address2;
	$cols['city'] = (string)$transaction->customer_city;
	$cols['state'] = (string)$transaction->customer_state;
	$cols['zipcode'] = (string)$transaction->customer_postal_code;
	if ((string)$transaction->customer_country != "US") {
		$cols['cforeign'] = "Y";
	}
	$cols['country'] = get_country_code((string)$transaction->customer_country);
	$cols['phone'] = substr((string)$transaction->customer_phone, 0, 18);

	//Order Details
	$cols['paid'] = (double)$transaction->order_total;
	$cols['order_date'] = date("Ymd", strtotime((string)$transaction->transaction_date));
	$cols['odr_num'] = (string)$transaction->id;
	$cols['shipping'] = (double)$transaction->shipping_total;
	$cols['email'] = substr((string)$transaction->customer_email, 0, 50);
	$processor_response = (string)$transaction->processor_response;
	if (strpos($processor_response, "Authorize.net") !== false) {
		$cols['reference'] = trim(substr($processor_response, strpos($processor_response, ":") + 1));
	}

	//Shipping Address
	$cols['sfirstname'] = (string)$transaction->shipping_first_name;
	$cols['slastname'] = (string)$transaction->shipping_last_name;
	$cols['scompany'] = (string)$transaction->shipping_company;
	$cols['saddress1'] = (string)$transaction->shipping_address1;
	$cols['saddress2'] = (string)$transaction->shipping_address2;
	$cols['scity'] = (string)$transaction->shipping_city;
	$cols['sstate'] = (string)$transaction->shipping_state;
	$cols['szipcode'] = (string)$transaction->shipping_postal_code;
	$cols['scountry'] = get_country_code((string)$transaction->shipping_country);
	$cols['sphone'] = substr((string)$transaction->shipping_phone, 0, 18);
	$cols['semail'] = substr((string)$transaction->customer_email, 0, 50);

	//Products
	$arr_products = array();
	foreach ($transaction->transaction_details->transaction_detail as $transaction_detail) {
		$arr_products[] = array(
			"code" => (string)$transaction_detail->product_code,
			"quantity" => (int)$transaction_detail->product_quantity,
			"price" => (int)$transaction_detail->product_price,
		);
	}

	//Assign Products
	$product_count = 0;
	foreach ($arr_products as $key => $val) {
		$product_count++;
		if ($product_count > 5) continue;
		$cols["product0" . $product_count] = $val['code'];
		$cols["quantity0" . $product_count] = $val['quantity'];
		$cols["price0" . $product_count] = $val['price'];
	}


	//Extra Products
	if (count($arr_products) > 5) {
		$new_col = getFieldTitles();

		$product_count = 0;
		for ($i = 5; $i < count($arr_products); $i++) {
			$product_count++;
			$new_col['continued'] = "Y";
			$new_col["product0" . $product_count] = $val['code'];
			$new_col["quantity0" . $product_count] = $val['quantity'];
			$new_col["price0" . $product_count] = $val['price'];
			if ($product_count == 5) {
				$product_count = 0;
				$extra_rows[] = $new_col;
				$new_col = $cols;
			}
		}
		if ($product_count > 0) {
			$extra_rows[] = $new_col;
		}
	}

	$rows[] = $cols;
	if (count($extra_rows) > 0) {
		foreach ($extra_rows as $new_row) {
			$rows[] = $new_row;
		}
	}

}


$write = "";
foreach ($rows as $row) {
	foreach ($row as $key => $val) {
		if ($key != "0" && $key != "custnum") {
			$write .= ",";
		}
		$write .= '"' . str_replace('"', '""', $val) . '"';
	}
	$write .= "\n";
}

//Debug
if (isset($_GET['neal-debug'])) {
	echo "<pre>" . $write . "</pre>";
	//echo "<pre>" . print_r($rows, 1) . "</pre>";
	//die;
}


//Setup File
$localpath = dirname(__FILE__) . "/tempfiles/";
$file = Date("Y-m-d_H-i-s") . ".csv";
$fp = fopen($localpath . $file, 'w');
//fwrite($fp, pack("CCC",0xef,0xbb,0xbf)); //Do the UTF-8 Encoding
//fwrite($fp, "\xEF\xBB\xBF");
fwrite($fp, $write);
fclose($fp);


//Prepare and send email (only if debug mode is false)
if (!isset($_GET['neal-debug'])) {
	$email_body = "The sales import file for " . date("m/d/Y", strtotime("-1 day")) . " is attached to this email.\n<br>";
	$email_attachment = $localpath . $file;

	$mail = new PHPMailer();

	$subject = "Sales Data For " . date("m/d/Y", strtotime("-1 day")) . " (" . $pagination_start . "-" . $pagination_end . ")";

	//Attachment
	if (isset($email_attachment)) {
		if (!is_array($email_attachment)) $email_attachment = array($email_attachment);
		foreach ($email_attachment as $current_email_attachment) {
			$mail->AddAttachment($current_email_attachment);
		}
	}

	$mail->isHTML(true);
	$mail->CharSet = "UTF-8";
	$mail->SetFrom($to_email, "Order Management");
	$mail->AddAddress($to_email);
	if (isset($cc_email)) {
		$mail->AddCC($cc_email);
	}
	$mail->Subject = $subject;
	$mail->Body = $email_body;
	if (!$mail->Send()) {
		echo "Email Not Sent\n";
	} else {
		echo "Email Sent (" . $pagination_start . "-" . $pagination_end . ")\n";
	}
}

//Delete Temp File
unlink($localpath . $file);

//Keep Going if Paging Isn't Done
if ($pagination_end < $filtered_total) {
	$url = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'] . "?pagination_start=" . ($pagination_end + 1);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	echo curl_exec($ch);
	$info = curl_getinfo($ch);

	//Show Diagnostic Info
	//echo "<pre>" . print_r($info, 1) . "</pre>";
	//echo "<pre>" . print_r($xml->statistics, 1) . "</pre>";

}


//All Done
die;
