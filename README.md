# podjob
Automatically downloading audio podcasts and cleaning up the ID3V2 tags in the mp3 files based on the podcast XML.

Requires id3v2 : http://sourceforge.net/projects/id3v2/

Run the podjob script in your cronjob. I have it set like this:
01 * * * * /home/micz/podjob/podjob
(checking every hour for new files)
