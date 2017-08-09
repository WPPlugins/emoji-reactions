=== Emoji Reactions ===
Contributors: stuartquin
Donate link: http://stuartquin.com/
Tags: reactions, emoji, smiley
Requires at least: 4.3
Tested up to: 4.3
Stable tag: 0.1.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Add emoji reactions to posts, pages and comments. Inspired by Slack

== Description ==

Emoji Reactions allows visitors to a WordPress site to rate and react to
content using Emojis.

* Full set of emoji from [EmojiOne](http://emojione.com)
* Support for range of content - posts, pages, comments.
* Track user votes - only one of each reaction per user per content item.
* Shortcode support - add reactions anywhere in content

== Installation ==

1. Upload 'emoji-reactions' to '/wp-content/plugins'
1. Activate through the plugins menu in WordPress

**Include in template:**

`
// By default reactions will be associated with the current context 
// do_action("emojiemo_render", CONTENT_ID=get_the_ID(), CONTENT_TYPE=get_post_type());
do_action("emojiemo_render");
`

**Use shortcodes:**

Add to a post/page:
`
[emojiemo]
`

Use type to add custom reactions throughout content
`
[emojiemo id=1 type=my_reaction]
`


== Frequently Asked Questions ==

= Please ask some questions using support forum =

Answers in forum

== Screenshots ==

1. Emoji selector widget
1. Click Emoji to share reaction

== Changelog ==

= 0.1.2 =
* Usage instructions in installation

= 0.1.1 =
* New banner image
* Fixed scaling with font size


= 0.1.0 =
* Initial release

== Upgrade Notice ==

= 0.1.0 =
Initial release
