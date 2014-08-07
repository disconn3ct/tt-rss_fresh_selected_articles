<?php
class fresh_selected_articles extends Plugin {
  private $host;
  private $dbh;
  private $dummy_id;
  private $from_part;
  private $where_part;

  function about() {
    return array(1.0,
      "vfeed for fresh articles except some specific feeds",
      "strubbl",
      false);
  }

  function init($host) {
    $this->host = $host;
    $this->dbh = $host->get_dbh();
    $this->dummy_id = $host->add_feed(-1, __("Fresh selected articles"), 'images/score_high.png', $this);
    $this->from_part = "
      FROM
        ttrss_entries,ttrss_user_entries,ttrss_feeds ";
    $this->where_part = "
      WHERE
        ttrss_user_entries.feed_id = ttrss_feeds.id AND
        ttrss_user_entries.ref_id = ttrss_entries.id AND
        ttrss_user_entries.owner_uid = '".$_SESSION["uid"]."' AND
        unread = true AND
        date_entered > SUBDATE(NOW(), 3)";
  }

  function get_unread($feed_id) {
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

  function api_version() {
    return 2;
  }

}
?>
