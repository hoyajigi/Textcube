<?
function encodeURL($url) {
	global $service;
	if (@$service['useEncodedURL'])
		return str_replace('%2F', '/', rawurlencode($url));
	else
		return str_replace(array('%', ' ', '"', '#', '&', '\'', '<', '>', '?'), array('%25', '%20', '%22', '%23', '%26', '%27', '%3C', '%3E', '%3F'), $url);
}

class User {
	/*@static@*/
	function getName($userid = null) {
		global $database, $owner;
		if (!isset($userid))
			$userid = $owner;
		return DBQuery::queryCell("SELECT name FROM {$database['prefix']}Users WHERE userid = $userid");
	}
	
	/*@static@*/
	function getEmail($userid = null) {
		global $database, $owner;
		if (!isset($userid))
			$userid = $owner;
		return DBQuery::queryCell("SELECT loginid FROM {$database['prefix']}Users WHERE userid = $userid");
	}
	
	/*@static@*/
	function confirmPassword($password) {
		global $database, $owner;
		$password = md5($password);
		return DBQuery::queryExistence("SELECT userid FROM {$database['prefix']}Users WHERE userid = $owner AND password = '$password'");
	}
}

/// Backward-Compatibility
function getTimestamp3($time) {
	return Timestamp::format3($time);
}
?>