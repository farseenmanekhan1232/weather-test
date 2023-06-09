<?php
require_once $_SERVER['DOCUMENT_ROOT']."/google-api-php-client/vendor/autoload.php"; // Google Client API v2.0
require_once "account_verification_token.php"; // Get the verification code.

// Get the variables from the weather station:
// Wind Direction (wd), Average Wind Speed (a_ws), Max Wind Speed (m_ws), 1hr Rainfall (1_rf), 24hr Rainfall (24_rf), Temperature (tem), Humidity (hum), Barometric Pressure (b_pr).
$variables_from_module;
if(isset($_GET['wd']) && isset($_GET['a_ws']) && isset($_GET['m_ws']) && isset($_GET['1_rf']) && isset($_GET['24_rf']) && isset($_GET['tem']) && isset($_GET['hum']) && isset($_GET['b_pr'])){
	$variables_from_module = [
	  "wd" => (int)$_GET['wd'],
	  "a_ws" => (float)$_GET['a_ws'],
	  "m_ws" => (float)$_GET['m_ws'],
	  "1_rf" => (float)$_GET['1_rf'],
	  "24_rf" => (float)$_GET['24_rf'],
	  "tem" => (float)$_GET['tem'],
	  "hum" => (int)$_GET['hum'],
	  "b_pr" => (float)$_GET['b_pr']
    ];
}else{
    $variables_from_module = [
	  "wd" => "err",
	  "a_ws" => "err",
	  "m_ws" => "err",
	  "1_rf" => "err",
	  "24_rf" => "err",
	  "tem" => "err",
	  "hum" => "err",
	  "b_pr" => "err"
    ];
}


/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient()
{
    $client = new Google_Client();
    $client->setApplicationName('Remote Weather Station'); // Enter your application name.
    $client->setScopes('https://www.googleapis.com/auth/spreadsheets');
    $client->setAuthConfig('credentials.json');
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');

    // Load previously authorized token from a file, if it exists.
    // The file token.json stores the user's access and refresh tokens, and is
    // created automatically when the authorization flow completes for the first
    // time.
    $tokenPath = 'token.json';
    if (file_exists($tokenPath)) {
        $accessToken = json_decode(file_get_contents($tokenPath), true);
        $client->setAccessToken($accessToken);
		print("Token Found!");
    }

    // If there is no previous token or it's expired.
    if ($client->isAccessTokenExpired()) {
        // Refresh the token if possible, else fetch a new one.
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        } else {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();
			// Do not forget to refresh the page after getting the verification code and entering it to the account_verification_token.php.
            printf("Open the following link in your browser:<br><br>%s<br><br>", $authUrl); // <= Comment
            print 'Do not forget to refresh the page after getting the verification code and entering it to the account_verification_token.php.<br><br>Set the verification code in the account_verification_token.php file.'; // <= Comment
            // Set the verification code to create the token.json.
			$authCode = trim($GLOBALS['account_verification_token']);

            // Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            $client->setAccessToken($accessToken);

            // Check to see if there was an error and the account_verification_token is entered.
            if (array_key_exists('error', $accessToken)) {
                throw new Exception(join(', ', $accessToken));
            }else{
				print("Successful! Refresh the page.");
			}
        }
        // Save the token to a file.
        if (!file_exists(dirname($tokenPath))) {
            mkdir(dirname($tokenPath), 0700, true);
        }
        file_put_contents($tokenPath, json_encode($client->getAccessToken()));
    }
    return $client;
}


// Get the API client and construct the service object.
$client = getClient();
$service = new Google_Service_Sheets($client);

// Enter your spreadsheetId:
$spreadsheetId = '18aYYrzyU1fBeNGGjrvEzfBn9l6DrpEjG6VCRr7dor00';
// Enter the range (the first row) under which new values will be appended (8 rows):
$range = 'A1:H1';
// Append recent findings from the weather station to the spreadsheet.
$values = [
    [$variables_from_module["wd"], $variables_from_module["a_ws"], $variables_from_module["m_ws"], $variables_from_module["1_rf"], $variables_from_module["24_rf"], $variables_from_module["tem"], $variables_from_module["hum"], $variables_from_module["b_pr"]]
];
$body = new Google_Service_Sheets_ValueRange([
    'values' => $values
]);
$params = [
    'valueInputOption' => "RAW"
];
// Append if only requested!
if(isset($_GET['wd']) && isset($_GET['a_ws']) && isset($_GET['m_ws']) && isset($_GET['1_rf']) && isset($_GET['24_rf']) && isset($_GET['tem']) && isset($_GET['hum']) && isset($_GET['b_pr'])){
    $result = $service->spreadsheets_values->append($spreadsheetId, $range, $body, $params);
    printf("<br><br>%d cells appended.", $result->getUpdates()->getUpdatedCells());
}else{
	print ("<br>Missing Data!");
}
