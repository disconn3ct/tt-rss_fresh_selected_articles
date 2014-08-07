tt-rss_fresh_selected_articles
==============================

Tiny Tiny RSS virtual feed: show all fresh articles but some exceptions

Installation
------------

Clone or extract this repo to the path `<your-ttrss-installation>/plugins/fresh_selected_articles/`. Be aware that the plugin folder is exactly named `fresh_selected_articles`, otherwise you won't find the plugin in your preferences.

Configuration
-------------

You can configure the IDs of the feeds you do not want to see in this virtual feed in the preference backend. Just list the feed IDs comma separated. Nothing more and nothing less. The content of that text area is directly used in the DB query. 

For finding out the feed ID look it up in the database or go to the frontend, select the desired feed and have a look for `f` parameter in the URL. That's the ID.

TODO
----

* select feeds from combo box

