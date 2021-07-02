<html>
<body style="font-family: Helvetica, sans-serif; margin-bottom: 60px;">
	<?php
	$fileId = '';
	$preCheckError = '';
	
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
					$preCheckError = 'Download links can\'t be generated for documents created in Drive. You should first convert the document to a downloadable format (e.g. .doc, .docx, .ppt, .xls).';
			}
			else if( $state->action == 'create' )
			{
				if( isset( $state->folderId ) && $state->folderId )
					$fileId = $state->folderId;
				else
					$preCheckError = 'You need to right click a file/folder in Drive and select \'Open with-&gt;Download Link Generator\'.';
			}
			else
				$preCheckError = 'Invalid state, try again.';
		}
		else
			$preCheckError = 'You need to right click a file/folder in Drive and select \'Open with-&gt;Download Link Generator\'.';
	}
	else
		$preCheckError = 'You need to right click a file/folder in Drive and select \'Open with-&gt;Download Link Generator\'.';
	?>
	
	<input id="fileId" type="hidden" value="<?php echo $fileId; ?>" />
	
	<div style="max-width:680px; margin:0 auto; padding-top: 30px; line-height: 150%;">
	
	<h3 style="text-align:center;">Download Link Generator for Drive™</h3>
	
	<p style="text-align:center;"><a href="https://github.com/yasirkula/DownloadLinkGeneratorForGoogleDrive">Source Code</a> | <a href="https://gsuite.google.com/marketplace/app/download_link_generator_for_drive/631283629814">Marketplace</a></p>
	
	<p>This Drive™ extension/add-on lets you generate direct download links for the files in your Drive™ storage. Simply right click the file/folder in your Drive™ and select <i>Open with-&gt;Download Link Generator</i>. When a folder is selected, download links for all the files in that folder are generated. If <i>Download Link Generator</i> button isn't present, then you may first need to authorize this extension by clicking the <i>Authorize</i> button below.</p>
	
	<p>For the generated download links to work everywhere, you need to make the file/folder public. To do this, you can right click the file/folder, select <i>Get link</i> and change visibility from <i>Restricted</i> to <i>Anyone with the link</i>.</p>
	
	<p><b>Privacy:</b> To generate download links, this extension accesses the metadata of the selected file/folder and reads its unique ID. It is necessary because the download link is generated from that ID. All communications with the Drive™ servers is handled via the official <i>Drive™ Javascript API</i> and your Drive™ data is not stored in any way in our databases. This extension is hosted at <i>yasirkula.net</i> website and is subject to its <a href="https://yasirkula.net/privacy-policy/">Privacy Policy</a>.</p>
	
	<div id="authorize-div" style="display: none">
		<b>You need to authorize access to Drive first:</b> <button id="authorize-button" onclick="handleAuthClick()">Authorize</button>
	</div>
	
	</div>
	
	<?php if( $preCheckError == '' ) { ?>
	<pre id="status" style="max-width:680px; margin:0 auto;"><b>Status:</b> <span style="text-style=bold; color:blue;">generating download link(s), please wait...</span></pre>
	</br><pre id="result" style="display:table; margin:0 auto;"></pre>
	</br><pre id="error" style="color: red; text-style: bold; max-width:680px; margin:0 auto;"></pre>
	<?php } else echo '</br><p id="pre-check-error" style="max-width:680px; margin:0 auto; color: red; display: none;"><b>' . $preCheckError . '</b></p>'; ?>
	
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
		var authorizeDiv = document.getElementById( 'authorize-div' );
		var preCheckErrorDiv = document.getElementById( 'pre-check-error' );
		
		if( authResult && !authResult.error )
		{
			authorizeDiv.style.display = 'none';
			if( isValidOp() )
				loadDriveApi();
			
			if( preCheckErrorDiv )
				preCheckErrorDiv.style.display = 'block';
		}
		else
		{
			authorizeDiv.style.display = 'block';
			if( isValidOp() )
				statusText.innerHTML = "";
			
			if( preCheckErrorDiv )
				preCheckErrorDiv.style.display = 'none';
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
