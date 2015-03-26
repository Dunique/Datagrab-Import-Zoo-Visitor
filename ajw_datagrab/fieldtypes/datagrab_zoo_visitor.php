<?php
/**
 * DataGrab Fieldtype Class
 * 
 * Provides methods to interact with EE fieldtypes 
 *
 * @package   DataGrab
 * @author    Andrew Weaver <aweaver@brandnewbox.co.uk>
 * @copyright Copyright (c) Andrew Weaver
 **/

class Datagrab_zoo_visitor extends Datagrab_fieldtype {
			
	/**
	 * Fetch a list of configuration settings that this field type can use
	 *
	 * @param string $name the field name
	 * @return array of configuration setting names
	 * @author Andrew Weaver
	 */
	function register_setting( $field_name ) {
		return array( 
			$field_name . "_zoo_password", 
			$field_name . "_zoo_email",
			$field_name . "_zoo_username",
			$field_name . "_zoo_screen_name",
		);
	}
	
	/**
	 * Generate the form elements to configure this field
	 *
	 * @param string $field_name the field's name
	 * @param string $field_label the field's label
	 * @param string $field_type the field's type
	 * @param string $data array of data that can be used to select from
	 * @return array containing form's label and elements
	 * @author Andrew Weaver
	 */
	function display_configuration( $field_name, $field_label, $field_type, $data ) {
		$config = array();
		$hidden = form_hidden( $field_name, "1" );
		$password = "<p>Password: " . NBS . form_dropdown( $field_name.'_zoo_password', $data["data_fields"], 
			isset($data["default_settings"]["cf"][$field_name.'_zoo_password']) ? 
			$data["default_settings"]["cf"][$field_name.'_zoo_password'] : '' )."</p>";
	
		$email = "<p>Emailaddress: " . NBS . form_dropdown( $field_name.'_zoo_email', $data["data_fields"], 
			isset($data["default_settings"]["cf"][$field_name.'_zoo_email']) ? 
			$data["default_settings"]["cf"][$field_name.'_zoo_email'] : '' )."</p>";

		$username = "<p>Username: " . NBS . form_dropdown( $field_name.'_zoo_username', $data["data_fields"],
				isset($data["default_settings"]["cf"][$field_name.'_zoo_username']) ?
					$data["default_settings"]["cf"][$field_name.'_zoo_username'] : '' )."</p>";

		$screen_name = "<p>Screen name: " . NBS . form_dropdown( $field_name.'_zoo_screen_name', $data["data_fields"],
				isset($data["default_settings"]["cf"][$field_name.'_zoo_screen_name']) ?
					$data["default_settings"]["cf"][$field_name.'_zoo_screen_name'] : '' )."</p>";
	
		$config["label"] = form_label($field_label) . BR .
			'<a href="http://brandnewbox.co.uk/support/details/" class="help">Zoo Visitor notes</a>';
		$config["value"] = $screen_name . NBS . $username . NBS . $email . NBS . $password . NBS  . $hidden;
			
		return $config;
	}
	
	/**
	 * Prepare data for posting
	 *
	 * @param object $DG The DataGrab model object
	 * @param string $item The current row of data from the data source
	 * @param string $field_id The id of the field
	 * @param string $field The name of the field
	 * @param string $data The data array to insert into the channel
	 * @param string $update Update or insert?
	 */
	function prepare_post_data( $DG, $item, $field_id, $field, &$data, $update = FALSE ) {
		/*$field_data = $DG->datatype->get_item( $item, $DG->settings["cf"][ $field ] );

		if( $field_data == "" ) {
			$data[ "field_id_" . $field_id ] = "n";
		} else {
			$data[ "field_id_" . $field_id ] = explode( "|", $field_data );
		}*/
		//$data[ $field_id ] = $DG->datatype->get_item( $item, $DG->settings[ $field ] );
	}
	
	/**
	 * As prepare_post_data but set after the check for existing entries
	 *
	 * @param object $DG The DataGrab model object
	 * @param string $item The current row of data from the data source
	 * @param string $field_id The id of the field
	 * @param string $field The name of the field
	 * @param string $data The data array to insert into the channel
	 * @param string $update Update or insert?
	 */
	function final_post_data( $DG, $item, $field_id, $field, &$data, $update = FALSE ) {
	}
	
	/**
	 * As prepare_post_data but set after entry has been added
	 *
	 * @param object $DG The DataGrab model object
	 * @param string $item The current row of data from the data source
	 * @param string $field_id The id of the field
	 * @param string $field The name of the field
	 * @param string $data The data array to insert into the channel
	 * @param string $entry_id Update or insert?
	 */
	function post_process_entry( $DG, $item, $field_id, $field, &$data, $entry_id = FALSE ) {
					
		//load Zoo Visitor
		$this->EE->load->add_package_path(PATH_THIRD . 'zoo_visitor/');
		$this->EE->load->library('zoo_visitor_cp');

		//get the data
		$username = $DG->datatype->get_item( $item, $DG->settings["cf"][ $field."_zoo_username" ] );
		$password = $DG->datatype->get_item( $item, $DG->settings["cf"][ $field."_zoo_password" ] );
		$screen_name = $DG->datatype->get_item( $item, $DG->settings["cf"][ $field."_zoo_screen_name" ] );
		$email = $DG->datatype->get_item( $item, $DG->settings["cf"][ $field."_zoo_email" ] );
		$status = 'Members-id5';


		//get the member based on email/username
		$members = $this->EE->db->select('*')->from('members')->where('email', $email)->get();
		if($entry_id != false)
		{
			//exists member
			if($members->num_rows() == 0)
			{
				//delete empty members
				$this->EE->db->delete('members', array(
					'email' => '',
				));
				//delete empty entries
				$this->EE->db->delete('channel_titles', array(
					'url_title' => '',
				));
				
				$member_data = array();
				$member_data['username'] = $username;
				$member_data['screen_name'] = $screen_name;
				$member_data['password'] = $password;
				$member_data['email'] = $email;
				$member_data['group_id'] = 5; // STATIC, need dynamic
				$member_id = $this->register_member($member_data);
			}
			
			//or update the data with the given one
			else
			{
//				$member = $members->row();
//				$tmp_POST = $_POST;
//				$_POST = array();
//				$_POST['username'] = $member->email;
//				$_POST['email'] = $member->email;
//				$_POST['current_password'] = $member->password;
//
//				$member_id = $this->EE->zoo_visitor_cp->update_email();
//				$_POST = $tmp_POST;
			}

			//update the data with the new settings
			$this->EE->db->where('entry_id', $entry_id);
			$this->EE->db->update('channel_titles', array(
				'status' => $status,
				'author_id' => $member_id
			));
			//$DG->api_channel_entries->update_entry( $entry_id, $data);		
		}
		
	}
	
	/**
	 * Rebuild the POST data of from existing entry
	 *
	 * @param string $DG 
	 * @param string $field_id 
	 * @param string $data 
	 * @param string $existing_data 
	 * @return void
	 * @author Andrew Weaver
	 */
	function rebuild_post_data( $DG, $field_id, &$data, $existing_data ) {
			
			
		// print get_class( $this );
		//$data[ "field_id_".$field_id ] = 'rein_'.$existing_data[ "field_id_".$field_id ];
	}
	
	/**
	 * Register Member
	 *
	 * Create a member profile
	 *
	 * @access    public
	 * @return    mixed
	 */
	function register_member($member_data)
	{
		$this->EE->load->helper('security');

		$data = array();

		$data['group_id'] = $member_data['group_id'];

		// If the screen name field is empty, we'll assign is
		// from the username field.

		$data['screen_name'] = ($member_data['screen_name']) ? $member_data['screen_name'] : $member_data['username'];

		// Assign the query data

		$data['username']         = $member_data['username'];
		$data['password']         = (version_compare(APP_VER, '2.6.0', '<')) ?  do_hash($member_data['password']) : md5($member_data['password']);
		$data['email']            = $member_data['email'];
		$data['ip_address']       = $this->EE->input->ip_address();
		$data['unique_id']        = random_string('encrypt');
		$data['join_date']        = $this->EE->localize->now;
		$data['language']         = $this->EE->config->item('deft_lang');
		$data['timezone']         = ($this->EE->config->item('default_site_timezone') && $this->EE->config->item('default_site_timezone') != '') ? $this->EE->config->item('default_site_timezone') : $this->EE->config->item('server_timezone');
		//$data['daylight_savings'] = ($this->EE->config->item('default_site_dst') && $this->EE->config->item('default_site_dst') != '') ? $this->EE->config->item('default_site_dst') : $this->EE->config->item('daylight_savings');
		$data['time_format']      = ($this->EE->config->item('time_format') && $this->EE->config->item('time_format') != '') ? $this->EE->config->item('time_format') : 'us';

		// Was a member group ID submitted?

		$data['group_id'] = (!$member_data['group_id']) ? 2 : $member_data['group_id'];

		$member_id = $this->EE->member_model->create_member($data);

		// Write log file

		$message = $this->EE->lang->line('new_member_added');
		$this->EE->load->library('logger');
		$this->EE->logger->log_action($message . NBS . NBS . stripslashes($data['username']));

		// Update Stats
		$this->EE->stats->update_member_stats();

		return $member_id;
	}
	
}

?>