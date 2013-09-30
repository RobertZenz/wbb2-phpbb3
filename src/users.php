<?php
error_reporting(E_ALL);
ini_set("display_errors", TRUE);

require_once __DIR__ . "/DatabaseFactory.php";
require_once __DIR__ . "/Document.php";

$userTypeNormal = 0;
$userTypeInactive = 1;
$userTypeIgnore = 2;
$userTypeFounder = 3;

$defaultGroup = 2;

function getStatus($isSticky) {
	if($isSticky === 1) {
		return 2;
	}
	
	return 0;
}

$wbb = DatabaseFactory::getWbbConnection();
$phpbb = DatabaseFactory::getPhpbbConnection();
Document::getInstance()->setTitle("Users");

Document::getInstance()->addItem("Deleting every user above " . DatabaseFactory::MAGIC_USER_ID);
$phpbb->query("
	DELETE FROM
		" . DatabaseFactory::PHPBB_TABLE_PREFIX . "users
	WHERE
		user_id > " . DatabaseFactory::MAGIC_USER_ID . ";

	DELETE FROM
		" . DatabaseFactory::PHPBB_TABLE_PREFIX . "user_group
	WHERE
		user_id > " . DatabaseFactory::MAGIC_USER_ID . ";
");

$get = $wbb->prepare("
	SELECT
		userID,
		username,
		email,
		signature,
		registrationDate
	FROM
		" . DatabaseFactory::WCF_TABLE_REPFIX . "user
	WHERE
		userID > 1;
");
		
$insert = $phpbb->prepare("
	INSERT INTO
		" . DatabaseFactory::PHPBB_TABLE_PREFIX . "users
	SET
		user_id = :id,
		user_type = :type,
		group_id = :group,
		username = :username,
		username_clean = :usernameClean,
		user_email = :email,
		user_sig = IFNULL(:signature, ''),
		user_regdate = :registrationDate;
");

$insertGroup = $phpbb->prepare("
	INSERT INTO
		" . DatabaseFactory::PHPBB_TABLE_PREFIX . "user_group
	SET
		group_id = :groupId,
		user_id = :userId;
");

$get->execute();
while($row = $get->fetch(PDO::FETCH_ASSOC)) {
	Document::getInstance()->addItem($row["userID"] . " - " . $row["username"]);
	
	$insert->bindParam(":id", DatabaseFactory::modUserId($row["userID"]));
	$insert->bindParam(":type", $userTypeNormal);
	$insert->bindParam(":group", $defaultGroup);
	$insert->bindParam(":username", $row["username"]);
	$insert->bindParam(":usernameClean", $row["username"]);
	$insert->bindParam(":email", $row["email"]);
	$insert->bindParam(":signature", $row["signature"]);
	$insert->bindParam(":registrationDate", $row["registrationDate"]);
	$insert->execute();
	$insert->closeCursor();
	
	$insertGroup->bindParam(":groupId", $defaultGroup);
	$insertGroup->bindParam(":userId", DatabaseFactory::modUserId($row["userID"]));
	$insertGroup->execute();
	$insertGroup->closeCursor();
}
$get->closeCursor();

Document::getInstance()->write();

?>
