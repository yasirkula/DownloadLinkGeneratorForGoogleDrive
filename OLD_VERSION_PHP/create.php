<?php
require_once '../../../google-api-php-client-2.0.3/vendor/autoload.php';

session_start();

$valid = 1;

if( isset($_GET["state"]) && $_GET["state"] )
{
	$state = json_decode($_GET["state"]);
	
	if( $state->action != 'create' )
	{
		echo 'Invalid state';
		$valid = 0;
	}
	else
	{
		$_SESSION['downloadlinkgenerator_createid'] = $state->folderId;
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
		if( !isset($_SESSION['downloadlinkgenerator_createid']) || !isset($_SESSION['downloadlinkgenerator_createid']) )
		{
			echo 'Use the service with \'Create\' button in Drive UI';
		}
		else
		{
			$client->setAccessToken($_SESSION['downloadlinkgenerator_accesstoken']);
			$drive_service = new Google_Service_Drive($client);
			$result = array();
			
			GetFilesRecursively( $_SESSION['downloadlinkgenerator_createid'], '' );
			
			echo '<pre>';
			foreach( $result as $fileInfo )
			{
				echo $fileInfo['path'] . ' ' . $fileInfo['link'] . "\r\n";
			}
			echo '</pre>';
		}
	}
	else 
	{
		$_SESSION['downloadlinkgenerator_op'] = 'create';
		$redirect_uri = 'http://yasirkula.net/drive/downloadlinkgenerator/oauth2callback.php';
		header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
	}
}

function GetFilesRecursively( $folderId, $relativePath )
{
	global $result;
	global $drive_service;
	
	try
	{
		$pageToken = NULL;
		
		do
		{
			$parameters = array(
					'q' => "trashed=false and '" . $folderId . "' in parents and mimeType != 'application/vnd.google-apps.folder'",
					'pageSize' => '1000',
					'fields' => 'nextPageToken, files(name, webContentLink)' );
					
			if ($pageToken) 
			{
				$parameters['pageToken'] = $pageToken;
			}
			
			$files = $drive_service->files->listFiles($parameters);
			
			$fileMetas = $files->getFiles();
			foreach( $fileMetas as $meta )
			{
				if( $meta->webContentLink )
					array_push( $result, [ "path" => $relativePath . $meta->name, "link" => $meta->webContentLink ] );
			}
			
			$pageToken = $files->getNextPageToken();
		} while( $pageToken );
		
		$pageToken = NULL;
		
		do
		{
			$parameters = array(
					'q' => "trashed=false and '" . $folderId . "' in parents and mimeType = 'application/vnd.google-apps.folder'",
					'pageSize' => '1000',
					'fields' => 'nextPageToken, files(id, name)' );
					
			if ($pageToken) 
			{
				$parameters['pageToken'] = $pageToken;
			}
			
			$folders = $drive_service->files->listFiles($parameters);
			
			$folderMetas = $folders->getFiles();
			foreach( $folderMetas as $meta )
			{
				GetFilesRecursively( $meta->id, $relativePath . $meta -> name . '\\' );
			}
			
			$pageToken = $folders->getNextPageToken();
		} while( $pageToken );
	}
	catch (Google_Service_Exception $e) 
	{
		if ($e->getCode() == 401)
		{
			unset($_SESSION['downloadlinkgenerator_accesstoken']);
			$_SESSION['downloadlinkgenerator_op'] = 'create';
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
?>