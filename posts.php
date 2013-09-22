<?php

error_reporting(E_ALL);
ini_set("display_errors", TRUE);

require_once __DIR__ . "/DatabaseFactory.php";
require_once __DIR__ . "/Document.php";

$wbb = DatabaseFactory::getWbbConnection();
$phpbb = DatabaseFactory::getPhpbbConnection();
Document::getInstance()->setTitle("Posts");

Document::getInstance()->addItem("Truncating table \"posts\"");
$phpbb->query("
	TRUNCATE TABLE
		" . DatabaseFactory::PHPBB_TABLE_PREFIX . "posts;
");

$get = $wbb->prepare("
	SELECT
		p.postID,
		p.threadID,
		t.boardID,
		p.userID,
		p.username,
		p.ipAddress,
		p.time,
		p.subject,
		p.message
	FROM
		" . DatabaseFactory::WBB_TABLE_REPFIX . "post AS p
		LEFT JOIN " . DatabaseFactory::WBB_TABLE_REPFIX . "thread AS t ON p.threadID = t.threadID
	WHERE
		p.isDeleted = FALSE;
");

$insert = $phpbb->prepare("
	INSERT INTO
		" . DatabaseFactory::PHPBB_TABLE_PREFIX . "posts
	SET
		post_id = :postId,
		topic_id = :topicId,
		forum_id = :forumId,
		poster_id = :posterId,
		poster_ip = :posterIP,
		post_time = :time,
		post_username = :username,
		post_subject = :subject,
		post_text = :text,
		post_checksum = :checksum;
");

$get->execute();
while ($row = $get->fetch(PDO::FETCH_ASSOC)) {
	Document::getInstance()->addItem($row["postID"] . " - " . $row["subject"]);

	$insert->bindParam(":postId", $row["postID"]);
	$insert->bindParam(":topicId", $row["threadID"]);
	$insert->bindParam(":forumId", $row["boardID"]);
	$insert->bindParam(":username", $row["username"]);
	$insert->bindParam(":posterId", DatabaseFactory::modUserId($row["userID"]));
	$insert->bindParam(":posterIP", $row["ipAddress"]);
	$insert->bindParam(":time", $row["time"]);
	$insert->bindParam(":subject", $row["subject"]);
	$insert->bindParam(":text", $row["message"]);
	$insert->bindParam(":checksum", md5($row["message"]));
	$insert->execute();
	$insert->closeCursor();
}
$get->closeCursor();

Document::getInstance()->write();
?>
