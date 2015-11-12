<?php
error_reporting(E_ALL);
ini_set("display_errors", TRUE);

require_once __DIR__ . "/DatabaseFactory.php";
require_once __DIR__ . "/Document.php";

function getStatus($isSticky) {
	if($isSticky === 1) {
		return 2;
	}
	
	return 0;
}

$wbb = DatabaseFactory::getWbbConnection();
$phpbb = DatabaseFactory::getPhpbbConnection();
Document::getInstance()->setTitle("Topics");

Document::getInstance()->addItem("Truncating table \"topics\"");
$phpbb->query("
	TRUNCATE TABLE
		" . DatabaseFactory::PHPBB_TABLE_PREFIX . "topics;
");

$get = $wbb->prepare("
	SELECT
		t.threadid,
		t.boardid,
		t.topic,
		t.starterid,
		t.starttime,
		t.views,
		t.replycount,
		t.important,
		t.closed,
		MIN(p.postid) AS 'firstpostid',
		t.starter,
		MAX(p.postID) AS 'lastpostid',
		t.lastposterid,
		t.lastposter,
		t.lastposttime
	FROM
		" . DatabaseFactory::WBB_TABLE_REPFIX . "threads AS t
		LEFT JOIN " . DatabaseFactory::WBB_TABLE_REPFIX . "posts AS p ON t.threadid = p.threadid AND p.visible = TRUE
	WHERE
		t.visible = TRUE
	GROUP BY
		t.threadid;
");
		
$insert = $phpbb->prepare("
	INSERT INTO
		" . DatabaseFactory::PHPBB_TABLE_PREFIX . "topics
	SET
		topic_id = :topicId,
		forum_id = :forumId,
		topic_title = :title,
		topic_poster = :posterId,
		topic_time = :time,
		topic_views = :views,
		topic_replies = :replies,
		topic_replies_real = :repliesReal,
		topic_status = :status,
		topic_type = :type,
		topic_first_post_id = :firstPostId,
		topic_first_poster_name = :firstPostName,
		topic_last_post_id = :lastPostId,
		topic_last_poster_id = :lastPosterId,
		topic_last_poster_name = :lastPosterName,
		topic_last_post_subject = :lastSubject,
		topic_last_post_time = :lastPostTime,
		topic_last_view_time = :lastViewTime;
");

$get->execute();
while($row = $get->fetch(PDO::FETCH_ASSOC)) {
	Document::getInstance()->addItem($row["threadid"] . " - " . $row["topic"]);
	
	$insert->bindParam(":topicId", $row["threadid"]);
	$insert->bindParam(":forumId", $row["boardid"]);
	$insert->bindParam(":title", $row["topic"]);
	$insert->bindParam(":posterId", DatabaseFactory::modUserId($row["startid"]));
	$insert->bindParam(":time", $row["starttime"]);
	$insert->bindParam(":views", $row["views"]);
	$insert->bindParam(":replies", $row["replycount"]);
	$insert->bindParam(":repliesReal", $row["replycount"]);
	$insert->bindParam(":status", $row["closed"]);
	$insert->bindParam(":type", getStatus($row["important"]));
	$insert->bindParam(":firstPostId", $row["firstpostid"]);
	$insert->bindParam(":firstPostName", $row["firstposter"]);
	$insert->bindParam(":lastPostId", $row["lastpostid"]);
	$insert->bindParam(":lastPosterId", DatabaseFactory::modUserId($row["lastposterid"]));
	$insert->bindParam(":lastPosterName", $row["lastposter"]);
	$insert->bindParam(":lastSubject", $row["topic"]);
	$insert->bindParam(":lastPostTime", $row["lastposttime"]);
	$insert->bindParam(":lastViewTime", $row["lastposttime"]);
	$insert->execute();
	$insert->closeCursor();
}
$get->closeCursor();

Document::getInstance()->write();

?>
