<?php
/*
Plugin Name: Emoji Reactions
Plugin URI:  http://stuartquin.com
Description: Widget to gather reactions and votes using Emojis. Emoji provided free by http://emojione.com
Version:     0.1
Author:      Stuart Quin
Author URI:  http://stuartquin.com
 */

require_once(__DIR__ . "/emojione/autoload.php");
require_once(__DIR__ . "/includes/emojis.php");
$emojiemoClient = new Emojione\Client(new Emojione\Ruleset());
$emojiemoClient->imageType = 'png';
$emojiemoClient->sprites = true;

function emojiemo_setup_database() {
  global $wpdb;
  $tableName = $wpdb->prefix . "emojiemo_emojis";

  $charsetCollate = $wpdb->get_charset_collate();

  $sql = "CREATE TABLE $tableName (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    resource_id mediumint(9),
    resource_type varchar(20),
    emotion varchar(32) NOT NULL,
    user_id varchar(32),
    created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
    KEY idx_emojiemo_resource_id (resource_id),
    KEY idx_emojiemo_user_id (user_id),
    KEY idx_emojiemo_resource_type (resource_type),
    UNIQUE KEY id (id)
  ) $charsetCollate;";

  require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
  dbDelta( $sql );
}

function emojiemo_activate() {
  emojiemo_setup_database();
}

function emojiemo_set_newuser_cookie() {
  if (!isset($_COOKIE['wp_emojiemo_emojis'])) {
    setcookie('wp_emojiemo_emojis', md5(uniqid(rand(), true)), time()+60*60*24*30);
  }
}

function emojiemo_get_emoji($emoji) {
  global $emojiemoClient;

  $tpl = "<span class='emojiemo-emotion' data-emoji='{$emoji}'>";
  $tpl .= "{$emojiemoClient->shortnameToImage(":".$emoji.":")}</span>";
  return $tpl;
}

function emojiemo_get_header_emoji($emoji, $group) {
  global $emojiemoClient;
  $tpl = "<span class='emojiemo-selector-header' data-group='{$group}' data-emoji='{$emoji}'>";
  $tpl .= "{$emojiemoClient->toImage(":".$emoji.":")}</span>";
  return $tpl;
}

function emojiemo_get_emotions($resourceId, $resourceType, $limit=10) {
  global $wpdb;
  $qry = "SELECT COUNT(*) AS total, emotion
    FROM wp_emojiemo_emojis
    WHERE resource_id=%d AND resource_type=%s
    GROUP BY emotion ORDER BY total DESC LIMIT %d;";

  return $wpdb->get_results($wpdb->prepare($qry, $resourceId, $resourceType, $limit));
}

function emojiemo_get_user_emotions($resourceId, $resourceType, $userId, $emoji) {
  global $wpdb;
  $qry = "SELECT COUNT(*) AS total
    FROM wp_emojiemo_emojis
    WHERE resource_id=%d AND resource_type=%s AND user_id=%s AND emotion=%s";

  return $wpdb->get_row($wpdb->prepare($qry, $resourceId, $resourceType, $userId, $emoji));
}


function emojiemo_existing($resourceId, $resourceType) {
  $selected = emojiemo_get_emotions($resourceId);
  $tpl = '';
  foreach($selected as $emotion) {
    $tpl .= emojiemo_get_emoji($emotion->emotion);
    $tpl .= "<span class='emojiemo-existing-count'>{$emotion->total}</span>";
  }
  return $tpl;
}

function emojiemo_init() {
  remove_action( 'admin_print_styles', 'print_emoji_styles' );
  remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
  remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
  remove_action( 'wp_print_styles', 'print_emoji_styles' );
  remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
  remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
  remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
  remove_filter( 'wpemoji', 'wp_staticize_emoji' );
  remove_filter( 'twoemoji', 'wp_staticize_emoji' );
}

function emojiemo_action_init() {
  emojiemo_init();
}

function emojiemo_render($resourceId, $resourceType) {
  $saveUrl = admin_url('admin-ajax.php');
  $tpl = "<div class='emojiemo-widget' data-url='{$saveUrl}' data-resource-id='{$resourceId}' data-resource-type='{$resourceType}'>";
  $tpl .= '<div class="emojiemo-existing">';
  $tpl .= '</div>';
  $tpl .= '<div class="emojiemo-add" title="Add Reaction">';
  $tpl .= '<img class="emojione" />';
  $tpl .= '</div>';
  $tpl .= "<div class='emojiemo-selector'>";
  $tpl .= '</div>';
  $tpl .= '</div>';
  return $tpl;
}

function emojiemo_shortcode_render($atts) {
  if (isset($atts["id"])) {
    $resourceId = $atts["id"];
  } else {
    $resourceId = get_the_ID();
  }
  if (isset($atts["type"])) {
    $resourceType = $atts["type"];
  } else {
    $resourceType = get_post_type();
  }
  return emojiemo_render($resourceId, $resourceType);
}

function emojiemo_action_render($resourceId=null, $resourceType=null) {
  if (!$resourceId && $resourceId !== 0) {
    $resourceId = get_the_ID();
  }
  if ($resourceType === null) {
    $resourceType = get_post_type();
  }
  echo emojiemo_render($resourceId, $resourceType);
}

function emojiemo_action_add_emotion() {
  global $wpdb;
  $emoji = $_REQUEST['emoji'];
  $resourceId = $_REQUEST['resource_id'];
  $resourceType = $_REQUEST['resource_type'];
  $userId = $_COOKIE['wp_emojiemo_emojis'];
  // @TODO filter these to only allow sensible
  $tableName = $wpdb->prefix . "emojiemo_emojis";

  $userEmotions = emojiemo_get_user_emotions($resourceId, $resourceType, $userId, $emoji);

  if (intval($userEmotions->total) > 0) {
    $wpdb->delete($tableName, array(
      "resource_id" => $resourceId,
      "resource_type" => $resourceType,
      "user_id" => $userId,
      "emotion" => $emoji
    ));
  } else {
    $wpdb->insert($tableName, array(
      "resource_id" => $resourceId,
      "resource_type" => $resourceType,
      "user_id" => $userId,
      "emotion" => $emoji
    ), array("%d", "%s", "%s", "%s"));
  }
  emojiemo_action_get_emotions();
}

function emojiemo_action_all_emotions() {
  header('Content-Type: application/json');
  $allEmojis = emojiemo_get_all_emojis();
  $headerEmojis = emojiemo_get_header_emojis();

  $all = array(
    "headers" => array(),
    "emojis" => array()
  );

  foreach($allEmojis as $group => $emojis) {
    $all["emojis"][$group] = array();
    $all["headers"][$group] = emojiemo_get_header_emoji($headerEmojis[$group], $group);
    foreach($emojis as $emotion) {
      $all["emojis"][$group][] = emojiemo_get_emoji($emotion);
    }
  }

  print(json_encode($all));
  wp_die();
}

function emojiemo_action_get_emotions() {
  emojiemo_set_newuser_cookie();
  $resourceId = $_REQUEST['resource_id'];
  $resourceType = $_REQUEST['resource_type'];
  header('Content-Type: application/json');

  $results = array();
  $selected = emojiemo_get_emotions($resourceId, $resourceType);
  foreach($selected as $emotion) {
    $results[$emotion->emotion] = array(
      "html" => emojiemo_get_emoji($emotion->emotion),
      "total" => $emotion->total
    );
  }
  print(json_encode($results));
  wp_die();
}

register_activation_hook( __FILE__, 'emojiemo_activate' );

add_shortcode('emojiemo', emojiemo_shortcode_render);

add_action("wp_ajax_emojiemo_add", "emojiemo_action_add_emotion");
add_action("wp_ajax_nopriv_emojiemo_add", "emojiemo_action_add_emotion");

add_action("wp_ajax_emojiemo_get", "emojiemo_action_get_emotions");
add_action("wp_ajax_nopriv_emojiemo_get", "emojiemo_action_get_emotions");

add_action("wp_ajax_emojiemo_all", "emojiemo_action_all_emotions");
add_action("wp_ajax_nopriv_emojiemo_all", "emojiemo_action_all_emotions");

add_action("init", emojiemo_action_init);
add_action("emojiemo_render", emojiemo_action_render, 10, 3);

wp_enqueue_script("emojiemo",
  plugins_url( "/static/emojiemotions.js", __FILE__ ),
  array("jquery"));

wp_enqueue_style("emojiemo_styles",
  plugins_url( "/static/styles.css", __FILE__ ));

wp_enqueue_style("emojione_styles",
  plugins_url( "/static/emojione.sprites.css", __FILE__ ));
