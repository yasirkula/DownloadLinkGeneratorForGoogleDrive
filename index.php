<html>
<body>
	<?php
	$valid = 1;
	$fileId = '';
	
	if( isset($_GET["state"]) && $_GET["state"] )
	{
		$state = json_decode($_GET["state"]);
		
		if( $state && is_object( $state ) )
		{
			if( $state->action == 'open' )
			{
				if( isset( $state->ids ) && $state->ids )
					$fileId = $state->ids[0];
				else
				{
					echo '<b>A document created in Drive does not support direct download. You should first convert it to a downloadable format</b>';
					$valid = 0;
				}
			}
			else if( $state->action == 'create' )
			{
				if( isset( $state->folderId ) && $state->folderId )
					$fileId = $state->folderId;
				else
				{
					echo '<b>Use the service with \'Open with\' or \'New\' buttons in Drive UI</b>';
					$valid = 0;
				}
			}
			else
			{
				echo '<b>Invalid state, try again</b>';
				$valid = 0;
			}
		}
		else
		{
			echo '<b>Use the service with \'Open with\' or \'New\' buttons in Drive UI</b>';
			$valid = 0;
		}
	}
	else
	{
		echo '<b>Use the service with \'Open with\' or \'New\' buttons in Drive UI</b>';
		$valid = 0;
	}?>
	
	<input id="fileId" type="hidden" value="<?php echo $fileId; ?>" />
	
	<pre>Source code available at: <a href="https://github.com/yasirkula/DownloadLinkGeneratorForGoogleDrive">https://github.com/yasirkula/DownloadLinkGeneratorForGoogleDrive</a> (using <i>HTML</i>, <i>PHP</i> and <i>Javascript</i>)</br>
Note that the file(s) or the parent folder must be shared publicly for the download links to work everywhere.</pre>
	
	<div id="authorize-div" style="display: none">
		<b>You need to authorize access to Drive first:</b> <button id="authorize-button" onclick="handleAuthClick()">Authorize</button>
	</div>
	
	<?php if( $valid == 1 ) { ?>
	<pre id="status"><b>Status:</b> <span style="text-style=bold; color:blue;">generating download link(s), please wait...</span></pre>
	<pre id="result"></pre>
	<pre id="error" style="color: red; text-style: bold;"></pre>
	<?php } ?>
	
	<script type="text/javascript">
	var CLIENT_ID = 'YOUR_APP_CLIENT_ID';
	var SCOPES = 'https://www.googleapis.com/auth/drive.install https://www.googleapis.com/auth/drive.metadata.readonly';

	var statusText = document.getElementById('status');
	var resultText = document.getElementById('result');
	var errorText = document.getElementById('error');
	
	var waitingFoldersStack = [];
	
	function checkAuth() 
	{
		gapi.auth.authorize( {
			'client_id': CLIENT_ID,
			'scope': SCOPES,
			'immediate': true
		}, handleAuthResult );
	}

	function handleAuthResult( authResult ) 
	{
		var authorizeDiv = document.getElementById('authorize-div');
		if (authResult && !authResult.error) {
			authorizeDiv.style.display = 'none';
			if( isValidOp() )
				loadDriveApi();
		} else {
			authorizeDiv.style.display = 'inline';
			if( isValidOp() )
				statusText.innerHTML = "";
		}
	}

	function handleAuthClick() 
	{
		gapi.auth.authorize( {
			client_id: CLIENT_ID,
			scope: SCOPES,
			immediate: false
		}, handleAuthResult );
		
		return false;
	}

	function loadDriveApi() 
	{
		gapi.client.load('drive', 'v3', handleRequest);
	}

	function handleRequest() 
	{
		if( !isValidOp() )
			return;
			
		statusText.innerHTML = "<b>Status:</b> <span style=\"text-style=bold; color:blue;\">generating download link(s), please wait...</span>";
		
		var request = gapi.client.drive.files.get({
			'fileId': getFileId(),
			'fields': "mimeType, name, webContentLink"
		});
		
		request.execute( function(resp) {
			if( !resp.error )
			{
				if( resp.mimeType == 'application/vnd.google-apps.folder' )
				{
					getFilesRecursively( getFileId(), "" );
				}
				else
				{
					if( resp.webContentLink )
						resultText.innerHTML = resp.name + " <a href=\"" + resp.webContentLink + "\">" + resp.webContentLink + "</a>";
					else
						resultText.innerHTML = "File is not shared";
						
					statusText.innerHTML = "<b>Status:</b> <span style=\"text-style=bold; color:green;\">finished</span>";
				}
			}
			else
				handleError( resp.error );
		});
	}
	
	function getFilesRecursively( folderId, relativePath )
	{
		var getFiles = function(request) 
		{
			request.execute(function(resp) 
			{
				if( !resp.error )
				{
					var files = resp.files;
					if (files && files.length > 0) 
					{
						for (var i = 0; i < files.length; i++) 
						{
							var file = files[i];
							if( file.webContentLink )
								resultText.innerHTML += relativePath + file.name + " <a href=\"" + file.webContentLink + "\">" + file.webContentLink + "</a>\r\n";
						}
					}
					
					var nextPageToken = resp.nextPageToken;
					if (nextPageToken) 
					{
						request = gapi.client.drive.files.list({
							'q': "trashed=false and '" + folderId + "' in parents and mimeType != 'application/vnd.google-apps.folder'",
							'pageSize': 1000,
							'fields': "nextPageToken, files(name, webContentLink)",
							'pageToken': nextPageToken
						});
						
						getFiles(request);
					}
					else
					{
						if( waitingFoldersStack.length > 0 )
						{
							var folderToEnter = waitingFoldersStack.shift();
							console.log( "Entering folder: " + folderToEnter._relativePath );
							getFilesRecursively( folderToEnter._id, folderToEnter._relativePath );
						}
						else
						{
							statusText.innerHTML = "<b>Status:</b> <span style=\"text-style=bold; color:green;\">finished</span>";
						}
					}
				}
				else
					handleError( resp.error );
			});
		}
		
		var getFolders = function(request) 
		{
			request.execute(function(resp) 
			{
				if( !resp.error )
				{
					var folders = resp.files;
					if (folders && folders.length > 0) 
					{
						for (var i = 0; i < folders.length; i++) 
						{
							var folder = folders[i];
							waitingFoldersStack.push( { _id: folder.id, _relativePath: relativePath + folder.name + "\\" } )
						}
					}
					
					var nextPageToken = resp.nextPageToken;
					if (nextPageToken) 
					{
						request = gapi.client.drive.files.list({
							'q': "trashed=false and '" + folderId + "' in parents and mimeType = 'application/vnd.google-apps.folder'",
							'pageSize': 1000,
							'fields': "nextPageToken, files(id, name)",
							'pageToken': nextPageToken
						});
						
						getFolders(request);
					}
					else
					{
						request = gapi.client.drive.files.list({
							'q': "trashed=false and '" + folderId + "' in parents and mimeType != 'application/vnd.google-apps.folder'",
							'pageSize': 1000,
							'fields': "nextPageToken, files(name, webContentLink)"
						});
						
						getFiles(request);
					}
				}
				else
					handleError( resp.error );
			});
		}
		
		var request = gapi.client.drive.files.list({
			'q': "trashed=false and '" + folderId + "' in parents and mimeType = 'application/vnd.google-apps.folder'",
			'pageSize': 1000,
			'fields': "nextPageToken, files(id, name)"
		});
		
		getFolders( request );
	}
	
	function getFileId()
	{
		var val = document.getElementById('fileId').value;
		if( !val )
			return "";

		return val;
	}
	
	function isValidOp()
	{
		return getFileId().length > 0;
	}
	
	function handleError( err )
	{
		console.log( "ERROR: " + JSON.stringify( err ) );
		
		var reason = "";
		var msg = "";
		
		if( err.errors && err.errors.length > 0 )
		{
			reason = err.errors[0].reason;
			msg = err.errors[0].message;
		}
		else if( err.data && err.data.length > 0 )
		{
			reason = err.data[0].reason;
			msg = err.data[0].message;
		}
		
		if( err.code == 401 )
		{
			handleAuthClick();
		}
		else if( err.code == 403 )
		{
			if( reason == "rateLimitExceeded" || reason == "userRateLimitExceeded" )
				errorText.innerHTML = "Too many requests; try again in a minute.";
			else if( reason == "dailyLimitExceeded" )
				errorText.innerHTML = "App reached daily limit (just wow O_O ); service will be available tomorrow.";
			else
				errorText.innerHTML = err.code + ": " + err.message + "(" + reason + ": " + msg + ")\r\n";
		}
		else
		{
			errorText.innerHTML = err.code + ": " + err.message + " (" + reason + ": " + msg + ")\r\n";
		}
		
		statusText.innerHTML = "<b>Status:</b> <span style=\"text-style=bold; color:red;\">see error log below</span>";
	}
	</script>
	<script src="https://apis.google.com/js/client.js?onload=checkAuth"></script>
</body>
</html>
