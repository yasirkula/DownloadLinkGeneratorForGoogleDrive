# Download Link Generator For Google Drive™

This is the source code of *Download Link Generator For Drive™* extension: https://gsuite.google.com/marketplace/app/download_link_generator_for_drive/631283629814

It lets users generate a list of their files with their download links in a Google Drive™ folder. Feel free to use this repository as a reference if you are creating your own Google Drive™ extension with similar functionality.

**[Support the Developer ☕](https://yasirkula.itch.io/unity3d)**

## How does the extension work?

Using the [Google Drive API](https://developers.google.com/drive/api/v3/about-sdk), after user authenticates the extension, a number of queries are made as follows:

- If user opened a file with this extension, download link of that file is returned
- If user opened a folder with this extension, download links for all the files in that folder and any folders underneath it (recursive) are returned

## Why would I want to use this extension?

Say you have a large number of files on Google Drive™ and you want to get a download link for each of these files. The thing is, you don't want to spend so much time sharing each file one by one and copying their download links manually.

Instead, you can open the Drive™ folder that contains your files with this extension and the extension will generate a list of the download links in the following format (one file per line): `{File relative path} {File's download url}`

For these download links to work everywhere, it is sufficient to make the folder that contains your files public (to do this, you can right click the folder, select "*Get link*" and change visibility from "*Restricted*" to "*Anyone with the link*").

Be aware that downloading big files using a direct download link will prompt a Drive™ dialog stating that "*The file exceeds the maximum size that Google can scan.*" and the user must click the "*Download anyway*" button to proceed. In C#, you can skip this step using the following WebClient implementation: https://gist.github.com/yasirkula/d0ec0c07b138748e5feaecbd93b6223c
