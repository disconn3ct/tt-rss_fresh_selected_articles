<?php
class fresh_selected_articles extends Plugin {
  private $host;
  private $dbh;
  private $dummy_id;
  private $from_part;
  private $unwanted_feeds;
  private $where_part;

  function about() {
    return array(0.1,
      "vfeed for fresh articles except some specific feeds",
      "strubbl",
      false);
  }

  function init($host) {
    $this->dbh = $host->get_dbh();
    $this->host = $host;
    $this->dummy_id = $host->add_feed(-1, __("Fresh selected articles"), 'images/score_high.png', $this);
    $this->from_part = "
      FROM
        ttrss_entries,ttrss_user_entries,ttrss_feeds ";

    if (DB_TYPE == "pgsql") {
	    $time_part = "date_entered > NOW() - INTERVAL '".get_pref("FRESH_ARTICLE_MAX_AGE")." hour' ";
    } else {
	    $time_part = "date_entered > DATE_SUB(NOW(), INTERVAL ".get_pref("FRESH_ARTICLE_MAX_AGE")." HOUR) ";
    }
    $this->where_part = "
      WHERE
        ttrss_user_entries.feed_id NOT IN (%UNWANTED_FEEDS%) AND
        ttrss_user_entries.feed_id = ttrss_feeds.id AND
        ttrss_user_entries.ref_id = ttrss_entries.id AND
        ttrss_user_entries.owner_uid = '".$_SESSION["uid"]."' AND
        unread = true AND ".$time_part;

    $host->add_hook($host::HOOK_PREFS_TAB, $this);
  }

  function get_unwanted() {
    /* this function is used as dirty workaround to the restriction that host->get() is not possible in init() */
    $query_unwanted_feeds = "SELECT content as freshselected FROM ttrss_plugin_storage WHERE name='fresh_selected_articles' AND owner_uid=".$_SESSION['uid'];
    $result1 = db_query($query_unwanted_feeds);
    if ($result1) {
      $json_serialized = db_fetch_result($result1, 0, "freshselected");
      if($json_serialized) {
        $uf = unserialize($json_serialized)['unwanted_feeds'];
      }
      else {
        $uf = 0;
      }
    }
    else {
      $uf = 0;
    }
    return $uf;
  }

  function get_unread($feed_id) {
    $this->unwanted_feeds = $this->get_unwanted();
    $this->where_part = str_replace("%UNWANTED_FEEDS%", $this->unwanted_feeds, $this->where_part);

    $query1yearcount = "SELECT DISTINCT count(date_entered) as freshselected ".$this->from_part.$this->where_part;
    $result = db_query($query1yearcount);
    if ($result) {
      $counter = db_fetch_result($result, 0, "freshselected");
      if (!$counter) return 0;
      else return $counter;
    }
    else {
      return 0;
    }
  }

  function get_headlines($feed_id, $options) {
    $this->unwanted_feeds = $this->get_unwanted();
    $this->where_part = str_replace("%UNWANTED_FEEDS%", $this->unwanted_feeds, $this->where_part);
#    print "<script type=\"text/javascript\">console.log('unwanted: ".$this->unwanted_feeds." where: ".$this->where_part."');</script>";
    $query1year = "SELECT DISTINCT
        date_entered,
        guid,
        ttrss_entries.id,ttrss_entries.title,
        updated,
        label_cache,
        tag_cache,
        always_display_enclosures,
        site_url,
        note,
        num_comments,
        comments,
        int_id,
        uuid,
        lang,
        hide_images,
        unread,feed_id,marked,published,link,last_read,orig_feed_id,
        last_marked, last_published,
        ttrss_feeds.title AS feed_title,favicon_avg_color,
        content,
        author,
        score
      ".$this->from_part.$this->where_part."
      ORDER BY
        ttrss_feeds.title,
        score DESC,
        date_entered,
        updated
      LIMIT ".$options['limit']."
      OFFSET ".$options['offset'];
    $result = db_query($query1year);
    return array($result);
  }

  function hook_prefs_tab($args) {
    if ($args != "prefPrefs") {
      return;
    }
    print "<div dojoType=\"dijit.layout.AccordionPane\" title=\"".__('Fresh selected articles')."\">";
    print "<p>" . __("You can set predefined feed IDs here (comma-separated list):") . "</p>";
    print "<form dojoType=\"dijit.form.Form\">";
    print "<script type=\"dojo/method\" event=\"onSubmit\" args=\"evt\">
            evt.preventDefault();
            if (this.validate()) {
              console.log(dojo.objectToQuery(this.getValues()));
              new Ajax.Request('backend.php', {
                parameters: dojo.objectToQuery(this.getValues()),
                onComplete: function(transport) {
                  notify_info(transport.responseText);
                }
              });
              //this.reset();
            }
            </script>";
    print "<input dojoType=\"dijit.form.TextBox\" style=\"display : none\" name=\"op\" value=\"pluginhandler\">";
    print "<input dojoType=\"dijit.form.TextBox\" style=\"display : none\" name=\"method\" value=\"save\">";
    print "<input dojoType=\"dijit.form.TextBox\" style=\"display : none\" name=\"plugin\" value=\"fresh_selected_articles\">";

    $unwanted_feeds = $this->host->get($this, "unwanted_feeds");

    print "<textarea dojoType=\"dijit.form.SimpleTextarea\" style='font-size : 12px; width : 50%' rows=\"3\"
            name='unwanted_feeds'>$unwanted_feeds</textarea>";
    print "<p><button dojoType=\"dijit.form.Button\" type=\"submit\">".
            __("Save")."</button>";
    print "</form>";
    print "</div>";
  }

  function save() {
    $unwanted_feeds = db_escape_string($_POST["unwanted_feeds"]);
    $this->host->set($this, "unwanted_feeds", $unwanted_feeds);
    echo __("Unwanted fresh feeds saved.");
  }

  function api_version() {
    return 2;
  }

}
?>
