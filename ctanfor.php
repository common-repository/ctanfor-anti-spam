<?php
/*
Plugin Name: Ctanfor Anti-Spam
Plugin URI: https://www.ctanfor.com
Description: Ctanfor Anti-spam is a small and lightweight anti-spam plugin. It is free for personal and commercial use. The vast majority of filtering operations are located in the cloud, so it will not affect the speed of access to your site. You just need to activate it and forget about it.
Version: 1.0.0
Author: Ctanfor
Author URI: https://profiles.wordpress.org/jackcb/
License: GPLv2 or later

*/
	class ctanfor_anti_spam
	{
		private $api_url_checkspam = 'http://api.ctanfor.com/';
		private $api_url_status = 'http://api.ctanfor.com/status/';
		
		function __construct()
		{
			$this->InitHooks();
		}
		
		function InitHooks()
		{
			register_activation_hook( __FILE__, array( $this, 'active_ctanfor' ) );
			register_deactivation_hook( __FILE__, array( $this, 'deactive_ctanfor' ) );
			
			add_filter( 'pre_comment_approved', array( $this, 'check_comment_spam' ), 10, 2 );
			add_filter( 'wp_mail', array ($this,'check_contact_spam'));
		}
		
		function active_ctanfor()
		{
			$active_info['website'] = site_url();
			$active_info['status']  = "active";
			
			$this->http_post_changestatus($active_info);
		}
		
		function deactive_ctanfor()
		{
			$deactive_info['website'] = site_url();
			$deactive_info['status']  = "deactive";
			
			$this->http_post_changestatus($deactive_info);
		}
		
		function check_comment_spam($approved , $commentdata)
		{	
			global $current_user;
			if(in_array( 'administrator', $current_user->roles ))
			{
				return $approved;
			}
			
			$comment['user_ip']      = $this->get_ip_address();
			$comment['user_agent']   = $this->get_user_agent();
			$comment['referrer']     = $this->get_referer();
			$comment['content']      = $commentdata['comment_content'];

			$comment['blog']         = get_option( 'home' );
			$comment['blog_lang']    = get_locale();
			$comment['blog_charset'] = get_option('blog_charset');
			$comment['permalink']    = get_permalink( $comment['comment_post_ID'] );
			
			$result = $this->http_post_checkspam($comment);
			
			if ( "Spam" == $result )
			{
				//$commentdata['comment_approved'] = 'spam';
				return 'spam';
			}
			
			return $approved;
			
		}
		
		function check_contact_spam($args)
		{
			global $current_user;
			if(in_array( 'administrator', $current_user->roles ))
			{
				return $args;
			}
			
			$contact['user_ip']      = $this->get_ip_address();
			$contact['user_agent']   = $this->get_user_agent();
			$contact['referrer']     = $this->get_referer();
			
			$contact['subject']      = $args['subject'];
			$contact['content']      = $args['message'];
			
			$result = $this->http_post_checkspam($contact);
			if ( "Spam" == $result )
			{
				$args['to']="";
			}
			
			return $args;
		}
		
		function get_ip_address()
		{
			return isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : null;
		}
		
		function get_user_agent() 
		{
			return isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : null;
		}
		
		function get_referer()
		{
			return isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : null;
		}
		
		function http_post_changestatus($request) 
		{	
			foreach($request as $k=>$v)
			{ 
				$request[$k]=base64_encode($v);
			} 
			$body = "sdata=" . base64_encode(json_encode($request));
			
			$r_http = new WP_Http;
			$r_http->request( $this->api_url_status, array( 'method' => 'POST', 'body' => $body) );
			
			return 0;
		}
		
		function http_post_checkspam($request) 
		{	
			foreach($request as $k=>$v)
			{ 
				$request[$k]=base64_encode($v);
			} 
			$body = "sdata=" . base64_encode(json_encode($request));
			
			
			$r_http = new WP_Http;
			$r_response = $r_http->request( $this->api_url_checkspam, array( 'method' => 'POST', 'body' => $body) );
			$r_json= $r_response['body'];
			$r_data = json_decode($r_json,TRUE);
			
			return $r_data['TYPE'];
		}
		
		function __destruct()
		{
			
		}
	}
	
	class ctanfor_anti_spam_admin
	{
		function __construct()
		{
			$this->InitHooks();
		}
		
		function InitHooks()
		{
			add_filter( 'plugin_action_links_' . plugin_basename( plugin_dir_path( __FILE__ ) . 'ctanfor.php'), array($this, 'ctanfor_reademe_links') );
			add_action('admin_menu', array($this, 'add_ctanfor_anti_spam_menu'));
		}
		
		function ctanfor_reademe_links($links)
		{
			$settings_link = '<a href="' . admin_url('admin.php?page=ctanfor/ctanfor.php') . '">Readme</a>';
			array_unshift( $links, $settings_link ); 
			return $links;
		}
		
		function add_ctanfor_anti_spam_menu()
		{
			add_menu_page( 'Ctanfor Anti-Spam', 'Ctanfor Anti-Spam', 'activate_plugins', __FILE__, array($this, 'mainmenu_ctanfor'));
		}
		
		function mainmenu_ctanfor()
		{
			echo '<div class="wrap">';
			echo '<h1>Ctanfor Anti-Spam Plugin</h1>';
			echo '<h2>Introductions:</h2>';
			echo '<p>Ctanfor Anti-spam is a small and lightweight anti-spam plugin. It is free for personal and commercial use. The vast majority of filtering operations are located in the cloud, so it will not affect the speed of access to your site. You just need to activate it and forget about it. It filters junk reviews for you all day. If spam coments are detected, ctanfor places it in spams folder; if spam in contact is detected, ctanfor discards it directly. Ctanfor-anti-spam will continue to learn to improve its detection accuracy.</p>';
			echo '<h2>Do the test:</h2>';
			echo '<p>1. Sign out (because we do not filter comments of administrators)</p>';
			echo '<p>2. Fill the following text in comment box:</p>';
			echo '<p>100% free iphone, don not hesitate to click here to get it!</p>';
			echo '<p>3. Fill in the username and mailbox </p>';
			echo '<p>4. submit the comment.</p>';
			echo '<p>You will find the comment in spam folder</p>';
			echo '</div>';
		}
		
		
		
		function __destruct()
		{
			
		}
	}
	
	$ctanfor = new ctanfor_anti_spam();
	$ctanfor_admin = new ctanfor_anti_spam_admin();
	
