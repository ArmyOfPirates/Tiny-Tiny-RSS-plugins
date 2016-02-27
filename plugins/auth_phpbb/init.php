<?php
class Auth_phpBB extends Plugin implements IAuthModule {

	private $host;
	private $base;

	function about() {
		return array(1.1,
			"Authenticates against phpBB3",
			"ArmyOfPirates",
			true);
	}

	function init($host) {
		$this->host = $host;
		$this->base = new Auth_Base();

		$host->add_hook($host::HOOK_AUTH_USER, $this);
	}
	
	function update_user($user, $uid){
		$email = db_escape_string($user->data['user_email']);
		db_query("UPDATE ttrss_users SET email = '$email' WHERE id = ".$uid);
	}

	function authenticate($login, $password) {
		//if (defined('TTRSS_SESSION_NAME') && TTRSS_SESSION_NAME=='ttrss_api_sid') {
		//	return false;
		//}
		
		// prevent too many logins
		if (defined('PHPBB_LOGIN_ATTEMPT')) {
			return false;
		}
		define('PHPBB_LOGIN_ATTEMPT', true);
		
		$_SESSION["hide_logout"] = true;
		global $phpbb_root_path, $phpEx, $auth, $user, $db, $config, $cache, $template;
		define('IN_PHPBB', true);
		
		// Modify this, relative from tt-rss root directory
		$phpbb_root_path = '../../../httpdocs/phpBB3/';
		
		$plugin_path = dirname(__FILE__);
		$phpEx = substr(strrchr(__FILE__, '.'), 1);
		include_once($plugin_path . '/common.php');

		// Start session management
		$user->session_begin();
		$user->setup();
		
		if($user->data['is_registered'] && !$user->data['is_bot']){
			$password = make_password(12);
			$uid = $this->base->auto_create_user($user->data['username']);
			$_SESSION["fake_login"] = $user->data['username'];
			$_SESSION["fake_password"] = "******";
			$_SESSION["hide_hello"] = true;
			$_SESSION["hide_logout"] = true;
			$this->update_user($user, $uid);
			return $uid;
		}else{
			if ($login && $password) {
				define('IN_LOGIN', true);
				$result = $auth->login($login, $password, true);
				if($user->data['is_registered'] && !$user->data['is_bot']){
					$password = make_password(12);
					$uid= $this->base->auto_create_user($user->data['username']);
					$_SESSION["fake_login"] = $user->data['username'];
					$_SESSION["fake_password"] = "******";
					$_SESSION["hide_hello"] = true;
					$_SESSION["hide_logout"] = true;
					$this->update_user($user, $uid);
					return $uid;
				}
			}
		}

		return false;
	}

	function api_version() {
		return 2;
	}
}
?>
