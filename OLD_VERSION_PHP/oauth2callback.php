<?php
require_once '../../../google-api-php-client-2.0.3/vendor/autoload.php';

session_start();

$client = new Google_Client();
$client->setAuthConfig('../../../google-api-php-client-2.0.3/client_id.json');
$client->setRedirectUri('http://yasirkula.net/drive/downloadlinkgenerator/oauth2callback.php');
$client->addScope('https://www.googleapis.com/auth/drive.install');
$client->addScope(Google_Service_Drive::DRIVE_METADATA_READONLY);

if (! isset($_GET['code'])) {
  $auth_url = $client->createAuthUrl();
  header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
} else {
  $client->authenticate($_GET['code']);
  $_SESSION['downloadlinkgenerator_accesstoken'] = $client->getAccessToken();
  if (isset($_SESSION['downloadlinkgenerator_op']) && $_SESSION['downloadlinkgenerator_op'] == 'open') 
	$redirect_uri = 'http://yasirkula.net/drive/downloadlinkgenerator/open.php';
  else
	$redirect_uri = 'http://yasirkula.net/drive/downloadlinkgenerator/create.php';
  header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
}
?>