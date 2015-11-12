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
		p.postid,
		p.threadid,
		t.boardid,
		p.userid,
		p.username,
		p.ipaddress,
		p.posttime,
		p.posttopic,
		p.message
	FROM
		" . DatabaseFactory::WBB_TABLE_REPFIX . "posts AS p
		LEFT JOIN " . DatabaseFactory::WBB_TABLE_REPFIX . "threads AS t ON p.threadid = t.threadid
	WHERE
		p.visible = TRUE;
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
	Document::getInstance()->addItem($row["postid"] . " - " . $row["subject"]);

	$insert->bindParam(":postId", $row["postid"]);
	$insert->bindParam(":topicId", $row["threadid"]);
	$insert->bindParam(":forumId", $row["boardid"]);
	$insert->bindParam(":username", $row["username"]);
	$insert->bindParam(":posterId", DatabaseFactory::modUserId($row["userid"]));
	$insert->bindParam(":posterIP", $row["ipaddress"]);
	$insert->bindParam(":time", $row["posttime"]);
	$insert->bindParam(":subject", $row["posttopic"]);
	$insert->bindParam(":text", $row["message"]);
	$insert->bindParam(":checksum", md5($row["message"]));
	$insert->execute();
	$insert->closeCursor();
}
$get->closeCursor();

Document::getInstance()->write();
?>
