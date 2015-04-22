# podjob
Automatically downloading audio podcasts and cleaning up the ID3V2 tags in the mp3 files based on the podcast XML.

Subscribe to podcasts on your server and download the files as well as the raw XML for each item and the channel. Podjob downloads the files and then changes the ID3 tags to match the information from the podcast XML. Each podcast has separate option settings.

Requires id3v2 : http://sourceforge.net/projects/id3v2/

Run the podjob script in your cronjob. I have it set like this (checking every hour for new files):

01 * * * * /home/micz/podjob/podjob

TODO (22 April 2015):

* automatic renaming files after download
* downloading deleted files if available
* podcast creation script for downloaded and processed mp3 files
