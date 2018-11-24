# Download Link Generator For Google Drive™
Create list of files and their download links in a Google Drive™ folder. Available at: http://yasirkula.net/drive/downloadlinkgenerator/ (it is integrated with the Drive UI, see https://gsuite.google.com/marketplace/app/download_link_generator_for_drive/631283629814)

## How does it work?

Using the Drive API, after user authenticates the app, a number of queries are made as following:

- If user opened a file with this app, download link to that file is returned
- If user opened a folder or used the New (create) button with this app, download links for any shared files in that folder and any folders under it (recursive) are returned

## Why would I want to use this app?

Say you have a large number of files and you want to get a download link for each of these files. You decide to host your files on Google Drive™. The thing is, you don't want to spend so much time sharing each file separately and copying their download links manually. 

Instead, you can open the folder that contains your files with this app and get a list of download links in the following format (one file per line): `{File relative path} {Download url}`

It is sufficient to just share the folder that contains your files publicly for those download links to work everywhere. Now you can write a simple script to fetch the download links from that list and use it however you want.

Be aware that downloading big files using a direct download link will prompt a Drive dialog stating that "The file exceeds the maximum size that Google can scan." and the user must click "Download anyway" button to proceed. You can skip this step in C# using this example code snippet (uses WebClient): https://gist.github.com/yasirkula/d0ec0c07b138748e5feaecbd93b6223c
