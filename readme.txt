=== Chronological Spam Removal ===
Contributors: skunkbad
Tags: spam, comments, database, removal, automatic
Tested up to: 3.3.1
Stable tag: 1.0.4.0

Plugin removes comments from the comments table that match blacklisted items, have too many links, or contain a author url (not default), or have non US-en characters (not default).

== Description ==

PHP V5+ only! This plugin deletes spam from the comments table of the database. It does so by checking it for matches against the characters or words you have blacklisted in Settings->Discussion. Also on the Settings->Discussion page is a setting for the maximum allowed links that a comment can contain. This plugin will delete comments that have too many links. Spam can also be deleted if it has a url in the author url field. This is handy if you don't have a author url form field in your comment form, and bots are submitting without using your form. Finally, spam can be deleted if there are any non US-en keyboard characters in any comment row. I don't expect any foreign language characters on my blog, and while I know this setting may be a little harsh, it's a spammy world out there, and sometimes ya gotta do what ya gotta do.

This plugin adds a menu item in the Settings section of the admin area. Currently only three options are available:

1) The frequency to run the automated process of removing spam. Default is twice a day.

2) Whether or not to remove spam that has been submitted with the website field. Default is NO (unchecked).

3) Whether or not to remove spam that has non US-en keyboard characters. Default is NO (unchecked).

== Installation ==

1. Use the auto-installer or upload entire plugin to `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in Wordpress.
3. Change settings in new Settings->Chronological Spam Removal page.

== Frequently Asked Questions ==

= Who is this plugin aimed at? =
I am a Defensio user, and I like how Defensio works, but I'd rather not have the spam cluttering up my comments table. If you'd like to keep your comments table size smaller by reducing the amount of spam comments, then this plugin will do it for you.

= Why are there no Questions here? =
Because no-one has asked me.. Ask me a question by going to the [Brian's Web Design Contact Page](http://brianswebdesign.com/contact)

== Changelog ==

= 1.0.4.0 =
 * cron entry in wp_options table now updated when plugin settings are updated. $_GET variable from options.php redirect was changed from "updated" to "settings-updated".

= 1.0.3.0 =
 * Plugin now has an option to remove any comment that has non US-en keyboard characters.

= 1.0.2.0 =
 * Plugin now ready for internationalization (i18n). POT file located in /localization

= 1.0.1.0 =
 * Comments marked as pending or spam are now checked against the comments_max_links option in Settings->Discussion. If there are greater than or equal to that many instances of "http://" in the comment content, the comment is deleted.

= 1.0.0.0 =
 * Initial Commit to SVN