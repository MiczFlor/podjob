# podjob
Automatically downloading audio podcasts and cleaning up the ID3V2 tags in the mp3 files based on the podcast XML.

Subscribe to podcasts on your server and download the files as well as the raw XML for each item and the channel. Podjob downloads the files and then changes the ID3 tags to match the information from the podcast XML. Each podcast has separate option settings.

Requires id3v2 : http://sourceforge.net/projects/id3v2/

# Install

~~~
git clone git@github.com:MiczFlor/podjob.git
~~~

Or download the code as a ZIP file through the github web page.

Open your terminal and go to the `podjob` directory. Now make local copies of the config files in the code base by typing:

~~~
cp options.ini.sample options.ini
cp config.ini.sample config.ini
cp serverlist.ini.sample serverlist.ini
~~~

# Configure

The configuration files all end with `.ini` and can be found in the podjob folder. (Below you learn where to find the config files for each podcast.)

## Global configuration

### config.ini

Adjust the folder locations to your system. The rest should be fine.

* `baseloc` the location of the podjob repo
* `wwwbaseloc` inside this folder will be the podcasts, each with a folder
* `nonwwwbaseloc` location for everything outside www reach (like config, metadata)
* `tempfolder` tempfolder to temporarily store downloads like podcasts

### podjob

This file requires you to make one change. Because it will be called by cron, it needs to know the abolute path to the file `config.ini`.

# Adding podcasts

Inside `serverlist.ini` you can add your podcasts. You need the RSS url to add a podcast. Your added podcasts need to look like this:

~~~
[Serial]
podcasturl = "http://feeds.serialpodcast.org/serialpodcast"
folder = "Serial"

[Pumuckl BR]
podcasturl = "http://feeds.br.de/pumuckl/feed.xml"
folder = "Pumuckl"
~~~

## Configuration for each podcast

When you start `podjob` and a new podcast was added, you can find a copy of the `options.ini` file inside the `data` folder. Look for:

* `/data/name-of-podcast-folder/options.ini`

Inside this file you can for example set this particular podcast to download ALL episodes.

IMPORTANT: if the rename option is set globally, you need to overwrite it in the podcasts ini file, if you want it switched off. To keep the original file name, write:

~~~
rename = "%filename%"
~~~

# Running `podjob` 

## Setting file permissions

You can run `podjob` from the command line. Make sure that the file permissions are set to allow `execute`:

* open your terminal and go to the folder with the file `podjob` 
* change the settings to allow running this from command line:
    * `sudo chmod +x podjob`
    
## Running from the command line

You need to tell `podjob` the absolute path to your config file. Therefore you need to call the file like this (replacing the path with your permisions):

~~~
./podjob --config=/home/micz/Documnts/github/podjob/config.ini
~~~

## Automatically running `podjob` 

You can run the podjob script in your cronjob. I have it set like this (checking every hour for new files):

~~~
01 * * * * /home/micz/podjob/podjob --config=/home/micz/Documnts/github/podjob/config.ini
~~~

Learn [more about cron in this post](https://help.ubuntu.com/community/CronHowto).

TODO (1. Aug 2020):

* podcast creation script for downloaded and processed mp3 files
* skip download if podjob can not create local folders and files
