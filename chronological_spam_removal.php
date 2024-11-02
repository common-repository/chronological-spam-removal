<?php
/*
Plugin Name: Chronological Spam Removal
Description: Removes comments from the database that match your blacklist, your max links setting, and optionally if the author url field is set (helpful for comment forms that have no "website" input), and also optionally if any non US-en characters are present.
Author: Robert Brian Gottier
Version: 1.0.4.0
Author URI: http://brianswebdesign.com
Text Domain: chron-spam-removal
*/
class chronological_spam_removal {

	private $options;

	public function __construct()
	{
		// Load the localization file to do the translation
		add_action('init', array($this,'plugin_init'));

		// Activate and deactivate
		register_activation_hook(__FILE__, array($this,'activate_plugin_event'));
		register_deactivation_hook(__FILE__, array($this,'deactivate_plugin_event'));

		// Get the plugin options from the database
		$this->options = get_option('chronological_spam_removal');

		// Add custom wp_cron intervals
		add_filter('cron_schedules', array($this,'add_custom_frequency'));

		// Perform the wp_cron at the specified interval
		add_action('chronological_spam_removal', array($this,'do_delete_spam'));

		// If this is an update check if options interval changed, and reset cron interval if necessary
		if($_GET['settings-updated'] == 'true')
		{
			if ( wp_get_schedule('chronological_spam_removal') != $this->options['frequency'])
			{
				wp_clear_scheduled_hook('chronological_spam_removal');
				wp_schedule_event(time(), $this->options['frequency'], 'chronological_spam_removal');
			}
		}

		// Add options page to admin area
		if( is_admin() )
		{
			add_action('admin_menu', array($this, 'plugin_options'));
		}
	}

	//Load plugin text domain
	public function plugin_init()
	{
		load_plugin_textdomain('chron-spam-removal', null, plugin_basename(dirname(__FILE) . '/localization'));
	}

	// Create admin page in Settings menu
	public function plugin_options()
	{
		add_action('admin_init', array($this, 'register_settings'));
		add_options_page('Chronological Spam Removal','Chronological Spam Removal','manage_options',__CLASS__,array($this, 'admin_page'));
	}

	// Designate/register the setting group
	public function register_settings()
	{
		register_setting('chronological_spam_removal_group', 'chronological_spam_removal');
	}

	// Add default option(s) to options table and setup wp_cron for this plugin
	public function activate_plugin_event()
	{
		$this->install_options();
		wp_schedule_event(time(), 'twicedaily', 'chronological_spam_removal');
	}

	// Set the actual option in the options table
	public function install_options()
	{
		$default_options = array(
			'frequency' => 'twicedaily',
		);

		add_option('chronological_spam_removal', $default_options);
	}

	// Add custom intervals to wp_schedules()
	public function add_custom_frequency($schedules)
	{
		$schedules['biminutely'] = array(
						'interval' => 120,
						'display' => __('Once every 2 minutes','chron-spam-removal')
					);
		$schedules['sixdaily'] = array(
						'interval' => 14400,
						'display' => __('Once every 4 hours','chron-spam-removal')
					);
		$schedules['fourdaily'] = array(
						'interval' => 21600,
						'display' => __('Once every 6 hours','chron-spam-removal')
					);
		$schedules['weekly'] = array(
						'interval' => 604800,
						'display' => __('Once every week','chron-spam-removal')
					);

		return $schedules;
	}

	// Remove the wp_cron and options from the options table
	function deactivate_plugin_event()
	{
		wp_clear_scheduled_hook('chronological_spam_removal');
		delete_option('chronological_spam_removal');
	}

	// Delete the spam from the comments table
	public function do_delete_spam()
	{

		global $wpdb;
		$comment_ids = $wpdb->get_col( "SELECT comment_ID FROM $wpdb->comments WHERE comment_approved = '0' OR comment_approved = 'spam'" );

		if( ! empty($comment_ids) )
		{
			$max_links = get_option('comment_max_links');

			foreach ($comment_ids as $comment_id)
			{
				$comment = get_comment($comment_id);

				// Remove spam if any blacklist match
				if( wp_blacklist_check(
							$comment->comment_author,
							$comment->comment_author_email,
							$comment->comment_author_url,
							$comment->comment_content,
							$comment->comment_author_IP,
							$comment->comment_agent
					)
				)
				{
					wp_delete_comment($comment_id);
				}

				// Remove spam if plugin setting is set for no author url and author url exists
				else if( isset($this->options['no_author_url']) && $this->options['no_author_url'] == 'TRUE' && ! empty($comment->comment_author_url) )
				{
					wp_delete_comment($comment_id);
				}

				// Remove spam if comment max links is equal or exceeded to that of comment content matches
				else if( substr_count( $comment->comment_content, 'http://' ) >= $max_links )
				{
					wp_delete_comment($comment_id);
				}

				// Remove any comment that contains non US keyboard characters in ANY field
				else if( isset($this->options['us-en_characters_only'])
							&& $this->options['us-en_characters_only'] == 'TRUE'
							&& preg_match('/[^\x20-\x7E\s]/',	$comment->comment_author .
																$comment->comment_author_email .
																$comment->comment_author_url .
																$comment->comment_content .
																$comment->comment_author_IP .
																$comment->comment_agent
						)
				)
				{
					wp_delete_comment($comment_id);
				}
			}
		}
	}

	// Show the admin view
	public function admin_page()
	{
		if( ! current_user_can('manage_options'))
		{
			// Without correct permissions, die!
			wp_die(__('You do not have sufficient permissions to access this page','chron-spam-removal'));
		}

		$schedules = wp_get_schedules();
		uasort($schedules, array($this, 'interval_sort'));
		echo '
			<div class="wrap">
				<h2>' . __('Chronological Spam Removal Options','chron-spam-removal') . '</h2>
				<form method="post" action="options.php">
					<table class="form-table">
						<tr valign="top">
							<th scope="row">' . __('Frequency','chron-spam-removal') . '</th>
							<td>
								';

		// The settings_fields() set important hidden form fields critical to updating options
		settings_fields('chronological_spam_removal_group');

		echo '<select name="chronological_spam_removal[frequency]">';
		foreach($schedules as $schedule => $attr)
		{
			echo '<option value="' . $schedule . '"';
			echo ($this->options['frequency'] == $schedule)? ' selected="selected"' : '';
			echo '>' . $attr['display'] . '</option>';
		}
		echo '</select>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">' . __('No URL Form Field','chron-spam-removal') . '</th>
							<td>
								<input type="checkbox" name="chronological_spam_removal[no_author_url]" value="TRUE" ';
		echo (isset($this->options['no_author_url']) && $this->options['no_author_url'] == 'TRUE')? 'checked="checked"' : '';
		echo ' />
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">' . __('US-en Characters Only','chron-spam-removal') . '</th>
							<td>
								<input type="checkbox" name="chronological_spam_removal[us-en_characters_only]" value="TRUE" ';
		echo (isset($this->options['us-en_characters_only']) && $this->options['us-en_characters_only'] == 'TRUE')? 'checked="checked"' : '';
		echo ' />
							</td>
						</tr>
					</table>
					<p class="submit">
						<input type="submit" class="button-primary" value="' . __('Save Changes','chron-spam-removal') . '" />
					</p>
				</form>
			</div>
		';
	}

	// Sort the wp cron schedules
	public function interval_sort( $a, $b )
	{
		return $a['interval'] > $b['interval'];
	}

}

$chronological_spam_removal = new chronological_spam_removal();
?>