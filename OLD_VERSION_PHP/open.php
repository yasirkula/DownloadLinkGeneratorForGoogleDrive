<?php
require_once '../../../google-api-php-client-2.0.3/vendor/autoload.php';

session_start();

$valid = 1;

if( isset($_GET["state"]) && $_GET["state"] )
{
	$state = json_decode($_GET["state"]);
	
	if( $state->action != 'open' )
	{
		echo 'Invalid state';
		$valid = 0;
	}
	else
	{
		if( $state->ids )
			$_SESSION['downloadlinkgenerator_openid'] = $state->ids[0];
		else
		{
			echo 'A document created in Drive does not support direct download. You should first convert it to a downloadable format.';
			$valid = 0;
		}
	}
}

if( $valid == 1 )
{
	$client = new Google_Client();
	$client->setAuthConfig('../../../google-api-php-client-2.0.3/client_id.json');
	$client->addScope('https://www.googleapis.com/auth/drive.install');
	$client->addScope(Google_Service_Drive::DRIVE_METADATA_READONLY);
	
	if (isset($_SESSION['downloadlinkgenerator_accesstoken']) && $_SESSION['downloadlinkgenerator_accesstoken']) 
	{
		if( !isset($_SESSION['downloadlinkgenerator_openid']) || !isset($_SESSION['downloadlinkgenerator_openid']) )
		{
			echo 'Use the service with \'Open\' button in Drive UI';
		}
		else
		{
			$client->setAccessToken($_SESSION['downloadlinkgenerator_accesstoken']);
			$drive_service = new Google_Service_Drive($client);
			$result = array();
			
			try
			{
				$fileMeta = $drive_service->files->get($_SESSION['downloadlinkgenerator_openid'], array( 'fields' => 'mimeType,webContentLink' ));
				if( $fileMeta->mimeType == 'application/vnd.google-apps.folder' )
				{
					$_SESSION['downloadlinkgenerator_createid'] = $_SESSION['downloadlinkgenerator_openid'];
					$redirect_uri = 'http://yasirkula.net/drive/downloadlinkgenerator/create.php';
					header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
				}
				else
				{
					if( $fileMeta -> webContentLink )
						echo $fileMeta -> webContentLink;
					else
						echo 'File is not shared';
				}
			}
			catch (Google_Service_Exception $e) 
			{
				if ($e->getCode() == 401)
				{
					unset($_SESSION['downloadlinkgenerator_accesstoken']);
					$_SESSION['downloadlinkgenerator_op'] = 'open';
					$redirect_uri = 'http://yasirkula.net/drive/downloadlinkgenerator/oauth2callback.php';
					header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
				}
				else if ($e->getCode() == 403)
				{
					if( ($e->getErrors()[0]["reason"] == "rateLimitExceeded"
					|| $e->getErrors()[0]["reason"] == "userRateLimitExceeded")) 
					{
						echo 'Try again in a minute.';
					}
				}
				else 
				{
					echo 'ERROR: ' . $e->getMessage();
				}
			}
		}
	}
	else 
	{
		$_SESSION['downloadlinkgenerator_op'] = 'open';
		$redirect_uri = 'http://yasirkula.net/drive/downloadlinkgenerator/oauth2callback.php';
		header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
	}
}
?>