<?php
class Auth_phpBB extends Plugin implements IAuthModule {

	private $link;
	private $host;
	private $base;

	function about() {
		return array(1.0,
			"Authenticates against phpBB3",
			"ArmyOfPirates",
			true);
	}

	function init($host) {
		$this->link = $host->get_link();
		$this->host = $host;
		$this->base = new Auth_Base($this->link);

		$host->add_hook($host::HOOK_AUTH_USER, $this);
	}
	
	function update_user($user, $uid){
		$email = db_escape_string($this->link, $user->data['user_email']);
		db_query($this->link, "UPDATE ttrss_users SET email = '$email' WHERE id = ".$uid);
	}

	function authenticate($login, $password) {
		global $phpbb_root_path, $phpEx, $auth, $user, $db, $config, $cache, $template;
		define('IN_PHPBB', true);
		
		// Modify this, relative from tt-rss root directory
		$phpbb_root_path = '../phpBB3/';
		
		$plugin_path = dirname(__FILE__);
		$phpEx = substr(strrchr(__FILE__, '.'), 1);
		include_once($plugin_path . '/common.php');

		// Start session management
		$user->session_begin();
		$user->setup();
		
		if($user->data['is_registered'] && !$user->data['is_bot']){
			$_SESSION["hide_logout"] = true;
			$password = make_password(12);
			$uid = $this->base->auto_create_user($user->data['username']);
			$this->update_user($user, $uid);
			return $uid;
		}else{
			if ($login && $password) {
				define('IN_LOGIN', true);
				$result = $auth->login($login, $password, true);
				if($user->data['is_registered'] && !$user->data['is_bot']){
					$_SESSION["hide_logout"] = true;
					$password = make_password(12);
					$uid= $this->base->auto_create_user($user->data['username']);
					$this->update_user($user, $uid);
					return $uid;
				}
			}
		}

		return false;
	}
}
?>
