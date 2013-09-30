<?php

class DatabaseFactory {
	const PHPBB_TABLE_PREFIX = "phpbb_";
	const WBB_TABLE_REPFIX = "wbb1_1_";
	const WCF_TABLE_REPFIX = "wcf1_";
	
	const MAGIC_USER_ID = 53;
	
	/**
	 *
	 * @return PDO 
	 */
	static function getWbbConnection() {
		return new PDO("mysql:host=localhost;port=3306;dbname=YOURDB;charset=utf8;", "USERNAME", "PASSWORD", array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
	}

	/**
	 *
	 * @return PDO 
	 */
	static function getPhpbbConnection() {
		return new PDO("mysql:host=localhost;port=3306;dbname=YOURDB;charset=utf8;", "USERNAME", "PASSWORD", array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
	}

	/**
	 *
	 * @param int $userId 
	 */
	static function modUserId($userId) {
		if($userId == 0) {
			return 1; // Anonymous
		} elseif ($userId == 1) {
			return 2; // Admin
		}
		
		return $userId + self::MAGIC_USER_ID; // Skip all the bots and stuff.
	}
}

?>
