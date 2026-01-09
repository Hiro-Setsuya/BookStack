<?php
define('PAYPAL_CLIENT_ID', 'AUbaETfmUXfZ0Hv2rTaqsbVFCe88WMLkZg_8GreLpb7ECUvTclpLAps55aiGc4Hc62JP1vBZLVTKRcg7');
define('PAYPAL_SECRET', 'EFUZ5tFs9nev6Xr-i7cSL-xoXNvVJH8F95xXEqnqzznrF_TXRZvahz0DlDCRiYuJL1vz4VwGZTlw4m0C');

function generateAccessToken($clientId, $secret)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://api-m.sandbox.paypal.com/v1/oauth2/token");
	curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept: application/json"]);
	curl_setopt($ch, CURLOPT_USERPWD, $clientId . ":" . $secret);
	curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($ch);

	if (!$response) {
		http_response_code(500);
		echo json_encode(["error" => "CURL TOKEN ERROR: " . curl_error($ch)]);
		curl_close($ch);
		exit;
	}

	curl_close($ch);
	$json = json_decode($response, true);
	return $json["access_token"] ?? null;
}
