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
		t.threadID,
		t.boardID,
		t.topic,
		t.time,
		t.views,
		t.replies,
		t.isSticky,
		t.isClosed,
		t.firstPostID,
		t.username,
		MAX(p.postID) AS 'lastPostID',
		t.lastPosterID,
		t.lastPoster,
		t.lastPostTime
	FROM
		" . DatabaseFactory::WBB_TABLE_REPFIX . "thread AS t
		LEFT JOIN " . DatabaseFactory::WBB_TABLE_REPFIX . "post AS p ON t.threadID = p.threadID AND p.isDeleted = FALSE
	WHERE
		t.isDeleted = FALSE
	GROUP BY
		t.threadID;
");
		
$insert = $phpbb->prepare("
	INSERT INTO
		" . DatabaseFactory::PHPBB_TABLE_PREFIX . "topics
	SET
		topic_id = :topicId,
		forum_id = :forumId,
		topic_title = :title,
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
	Document::getInstance()->addItem($row["threadID"] . " - " . $row["topic"]);
	
	$insert->bindParam(":topicId", $row["threadID"]);
	$insert->bindParam(":forumId", $row["boardID"]);
	$insert->bindParam(":title", $row["topic"]);
	$insert->bindParam(":time", $row["time"]);
	$insert->bindParam(":views", $row["views"]);
	$insert->bindParam(":replies", $row["replies"]);
	$insert->bindParam(":repliesReal", $row["replies"]);
	$insert->bindParam(":status", $row["isClosed"]);
	$insert->bindParam(":type", getStatus($row["isSticky"]));
	$insert->bindParam(":firstPostId", $row["firstPostID"]);
	$insert->bindParam(":firstPostName", $row["username"]);
	$insert->bindParam(":lastPostId", $row["lastPostID"]);
	$insert->bindParam(":lastPosterId", $row["lastPosterID"]);
	$insert->bindParam(":lastPosterName", $row["lastPoster"]);
	$insert->bindParam(":lastSubject", $row["topic"]);
	$insert->bindParam(":lastPostTime", $row["lastPostTime"]);
	$insert->bindParam(":lastViewTime", $row["lastPostTime"]);
	$insert->execute();
	$insert->closeCursor();
}
$get->closeCursor();

Document::getInstance()->write();

?>
