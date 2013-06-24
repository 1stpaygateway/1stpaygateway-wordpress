<?php
$nzshpcrt_gateways[$num]['name'] = '1stPayGateway.Net';
$nzshpcrt_gateways[$num]['internalname'] = '1stpaygatewaygateway_payments';
$nzshpcrt_gateways[$num]['function'] = 'gateway_1stpaygateway';
$nzshpcrt_gateways[$num]['form'] = "form_1stpaygateway";
$nzshpcrt_gateways[$num]['submit_function'] = "submit_1stpaygateway";
$nzshpcrt_gateways[$num]['payment_type'] = "credit_card";

if(in_array('1stpaygatewaygateway_payments',(array)get_option('custom_gateway_options'))) {

	$gateway_checkout_form_fields[$nzshpcrt_gateways[$num]['internalname']] = "

<tr>
<td colspan='2' align='left'><h2>Credit Card Information</h2></td>
</tr>
	<tr>
<td> Credit Card Number * </td>
<td>
<input type='text' size='16' value='' maxlength='16' name='card_number' />
</td>
</tr>
<tr>
<td> Credit Card Expiration * </td>
<td>
<input type='text' size='2' value='' maxlength='2' name='exp_m' />/<input type='text' size='2'  maxlength='2' value='' name='exp_y' /> MM/YY
</td>
</tr> 
<td> CVV Code * </td>
<td>
<input type='text' size='4' value='' maxlength='4' name='cvv2' /></td>
</tr> 
";
}


function form_1stpaygateway(){
	$output ='<tr><td>Transaction Center ID</td><td><input name="1stpaygateway_tc_id" type="text" value="'.get_option('1stpaygateway_tc_id').'"/></td></tr>';
	$output.='<tr><td>Gateway ID</td><td><input name="1stpaygateway_gateway_id" type="text" value="'.get_option('1stpaygateway_gateway_id').'"/></td></tr>';
	$output.='<tr><td>Processor</td><td><input name="1stpaygateway_processor" type="text" value="'.get_option('1stpaygateway_processor').'"/></td></tr>';
	$output.='<tr><td>MID</td><td><input name="1stpaygateway_mid" type="text" value="'.get_option('1stpaygateway_mid').'"/></td></tr>';
	$output.='<tr><td>TID</td><td><input name="1stpaygateway_tid" type="text" value="'.get_option('1stpaygateway_tid').'"/></td></tr>';
	$output.='<tr><td>Transaction Type</td><td><input name="1stpaygateway_transaction_type" type="text" value="'.get_option('1stpaygateway_transaction_type').'"/><br/>(auth or sale)</td></tr>';

	return $output;
}

function submit_1stpaygateway(){
	update_option('1stpaygateway_tc_id',$_POST['1stpaygateway_tc_id']);
	update_option('1stpaygateway_gateway_id',$_POST['1stpaygateway_gateway_id']);
	update_option('1stpaygateway_processor',$_POST['1stpaygateway_processor']);
	update_option('1stpaygateway_mid',$_POST['1stpaygateway_mid']);
	update_option('1stpaygateway_tid',$_POST['1stpaygateway_tid']);
	update_option('1stpaygateway_transaction_type',$_POST['1stpaygateway_transaction_type']);
	return true;
}

function gateway_1stpaygateway($seperator, $sessionid){
	//$wpdb is the database handle,
	//$wpsc_cart is the shopping cart object
	global $wpdb, $wpsc_cart;

	//This grabs the purchase log id from the database
	//that refers to the $sessionid
	$purchase_log = $wpdb->get_row("SELECT * FROM `".WPSC_TABLE_PURCHASE_LOGS."` WHERE `sessionid`= ".$sessionid." LIMIT 1",ARRAY_A) ;

	//This grabs the users info using the $purchase_log
	// from the previous SQL query
	$usersql = "SELECT 
	`".WPSC_TABLE_SUBMITED_FORM_DATA."`.value, 
	`".WPSC_TABLE_CHECKOUT_FORMS."`.`name`, 
	`".WPSC_TABLE_CHECKOUT_FORMS."`.`unique_name` FROM 
	`".WPSC_TABLE_CHECKOUT_FORMS."` LEFT JOIN 
	`".WPSC_TABLE_SUBMITED_FORM_DATA."` ON 
	`".WPSC_TABLE_CHECKOUT_FORMS."`.id = 
	`".WPSC_TABLE_SUBMITED_FORM_DATA."`.`form_id` WHERE  
	`".WPSC_TABLE_SUBMITED_FORM_DATA."`.`log_id`=".$purchase_log['id']." ORDER BY `".WPSC_TABLE_CHECKOUT_FORMS."`.`checkout_order`";
		
	$userinfo = $wpdb->get_results($usersql, ARRAY_A);

	$data = array();

	$data['USER']	= get_option('1stpaygateway_tc_id');
	$data['PWD'] 	= get_option('1stpaygateway_gateway_id');
	$data['PROCESSOR'] 	= get_option('1stpaygateway_processor');
	$data['MID'] 	= get_option('1stpaygateway_mid');
	$data['TID'] 	= get_option('1stpaygateway_tid');
	$data['OPERATIONTYPE'] 	= get_option('1stpaygateway_transaction_type');
	$data['AMT']	= number_format($wpsc_cart->total_price,2);
	$data['ITEMAMT']= number_format($wpsc_cart->subtotal,2);
	$data['SHIPPINGAMT']= number_format($wpsc_cart->base_shipping,2);
	$data['TAXAMT']= number_format($wpsc_cart->total_tax);

	$data['OPERATIONTYPE'] = ($data['OPERATIONTYPE'] == "") ? "auth" : $data['OPERATIONTYPE'];
	
	foreach((array)$userinfo as $key => $value){
		if(($value['unique_name']=='billingfirstname') && $value['value'] != ''){
			$data['first_name']	= $value['value'];
		}

		if(($value['unique_name']=='billinglastname') && $value['value'] != ''){
			$data['last_name']	= $value['value'];
		}

		if(($value['unique_name']=='billingaddress') && $value['value'] != ''){
			$data['owner_street']	= $value['value'];
		}

		if(($value['unique_name']=='billingcity') && $value['value'] != ''){
			$data['owner_city']	= $value['value'];
		}
		
		if(($value['unique_name']=='billingstate') && $value['value'] != ''){
			$data['owner_state']	= $value['value'];
		}
		
		if(($value['unique_name']=='billingpostcode') && $value['value'] != ''){
			$data['owner_zip']	= $value['value'];
		}
		
		if(($value['unique_name']=='billingcountry') && $value['value'] != ''){
			$data['owner_country']	= $value['value'];
		}
		
		if(($value['unique_name']=='billingemail') && $value['value'] != ''){
			$data['owner_email']	= $value['value'];
		}

		if(($value['unique_name']=='billingphone') && $value['value'] != ''){
			$data['owner_phone']	= $value['value'];
		}
	}

	$data["owner_name"] = $data['first_name'] . ' ' . $data['last_name'];
	
	foreach($wpsc_cart->cart_items as $i => $Item) {
		$data['PROD_NAME'.$i] = $Item->product_name;
		$data['PROD_AMT'.$i] = number_format($Item->unit_price,2);
		$data['PROD_NUMBER'.$i]	= $i;
		$data['PROD_QTY'.$i] = $Item->quantity;
		$data['PROD_TAXAMT'.$i]	= number_format($Item->tax,2);
	}

	$customer_field_keys = array("owner_name", "owner_street", "owner_street2", "owner_city", "owner_state", "owner_zip", "owner_country", "owner_email", "owner_phone");

	$xml = new DOMDocument('1.0');
	$transaction = $xml->createElement('TRANSACTION');
	$xml->appendChild($transaction);

	$fields = $xml->createElement('FIELDS');
	$transaction->appendChild($fields);
	
	$field = $xml->createElement('FIELD');
	$field->setAttribute('KEY', 'merchant');
	$field->appendChild($xml->createTextNode($data['USER']));
	$fields->appendChild($field);
	
	$field = $xml->createElement('FIELD');
	$field->setAttribute('KEY', 'gateway_id');
	$field->appendChild($xml->createTextNode($data['PWD']));
	$fields->appendChild($field);
	
	$field = $xml->createElement('FIELD');
	$field->setAttribute('KEY', 'processor');
	$field->appendChild($xml->createTextNode($data['PROCESSOR']));
	$fields->appendChild($field);
	
	$field = $xml->createElement('FIELD');
	$field->setAttribute('KEY', 'mid');
	$field->appendChild($xml->createTextNode($data['MID']));
	$fields->appendChild($field);
	
	$field = $xml->createElement('FIELD');
	$field->setAttribute('KEY', 'tid');
	$field->appendChild($xml->createTextNode($data['TID']));
	$fields->appendChild($field);
	
	$field = $xml->createElement('FIELD');
	$field->setAttribute('KEY', 'operation_type');
	$field->appendChild($xml->createTextNode($data['OPERATIONTYPE']));
	$fields->appendChild($field);
	
	$field = $xml->createElement('FIELD');
	$field->setAttribute('KEY', 'order_id');
	$field->appendChild($xml->createTextNode($sessionid));
	$fields->appendChild($field);
	
	$field = $xml->createElement('FIELD');
	$field->setAttribute('KEY', 'total');
	$field->appendChild($xml->createTextNode($data["AMT"]));
	$fields->appendChild($field);
	
	$field = $xml->createElement('FIELD');
	$field->setAttribute('KEY', 'card_name');
	$field->appendChild($xml->createTextNode("yes"));
	$fields->appendChild($field);
	
	$field = $xml->createElement('FIELD');
	$field->setAttribute('KEY', 'card_number');
	$field->appendChild($xml->createTextNode($_POST["card_number"]));
	$fields->appendChild($field);
	
	$field = $xml->createElement('FIELD');
	$field->setAttribute('KEY', 'card_exp');
	$field->appendChild($xml->createTextNode($_POST["exp_m"].''.$_POST["exp_y"]));
	$fields->appendChild($field);
	
	$field = $xml->createElement('FIELD');
	$field->setAttribute('KEY', 'cvv2');
	$field->appendChild($xml->createTextNode($_POST["cvv2"]));
	$fields->appendChild($field);
	
	$field = $xml->createElement('FIELD');
	$field->setAttribute('KEY', 'remote_ip_address');
	$field->appendChild($xml->createTextNode($_SERVER["REMOTE_ADDR"]));
	$fields->appendChild($field);
	
	foreach($customer_field_keys as $key)
	{
		$field = $xml->createElement('FIELD');
		$field->setAttribute('KEY', $key);
		$field->appendChild($xml->createTextNode($data[$key]));
		$fields->appendChild($field);
	}
	
	$xml_string = $xml->saveXML();

	//Now we have the information we want to send to the gateway in a nicely formatted string we can setup the cURL
	$connection = curl_init();
	curl_setopt($connection,CURLOPT_URL,"https://secure.1stpaygateway.net/secure/gateway/xmlgateway.aspx");
	$useragent = 'WP e-Commerce plugin';
	curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($connection, CURLOPT_NOPROGRESS, 1);
	curl_setopt($connection, CURLOPT_VERBOSE, 1);
	curl_setopt($connection, CURLOPT_FOLLOWLOCATION,0);
	curl_setopt($connection, CURLOPT_POST, 1);
	curl_setopt($connection, CURLOPT_POSTFIELDS, $xml_string);
	curl_setopt($connection, CURLOPT_TIMEOUT, 30);
	curl_setopt($connection, CURLOPT_USERAGENT, $useragent);
	curl_setopt($connection, CURLOPT_REFERER, "https://".$_SERVER['SERVER_NAME']);
	curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
	$buffer = curl_exec($connection);
	curl_close($connection);
	
	$response = getResponse($buffer);

	if($response["status"] == "1"){
		//redirect to  transaction page and store in DB as a order with
		//accepted payment
		$sql = "UPDATE `".WPSC_TABLE_PURCHASE_LOGS."` SET `processed`= '2' WHERE `sessionid`=".$sessionid;
		$wpdb->query($sql);
		$transact_url = get_option('transact_url');
		unset($_SESSION['WpscGatewayErrorMessage']);
		print "<script>window.location = '$transact_url&sessionid=$sessionid';</script>";
	} else {

		//redirect back to checkout page with errors
		$sql = "UPDATE `".WPSC_TABLE_PURCHASE_LOGS."` SET `processed`= '5' WHERE `sessionid`=".$sessionid;
		$wpdb->query($sql);
		$transact_url = get_option('checkout_url');
		$_SESSION['WpscGatewayErrorMessage'] = __('Sorry your transaction did not go through successfully: ' . $response["auth_response"]);
		header("Location: ".$transact_url);
	}
}

function getResponse($data)
{
	$xml = new SimpleXMLElement($data);
	
	$response = array();
	$keys = array('status', 'auth_code', 'auth_response', 'avs_code', 'cvv2_code', 'order_id', 'reference_number', 'error');
	
	foreach($xml->FIELDS->FIELD as $field){
		$value = (string) $field;
		foreach($keys as $key_name){
			if($key_name == $field['KEY']){
				$response[$key_name] = $value;
			}
		}
	}
	return $response;
}
?>