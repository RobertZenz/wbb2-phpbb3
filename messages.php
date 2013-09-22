<?php

error_reporting(E_ALL);
ini_set("display_errors", TRUE);

require_once __DIR__ . "/DatabaseFactory.php";
require_once __DIR__ . "/Document.php";

$folderInbox = 0;
$folderSent = -1;

$wbb = DatabaseFactory::getWbbConnection();
$phpbb = DatabaseFactory::getPhpbbConnection();
Document::getInstance()->setTitle("Private Messages");

Document::getInstance()->addItem("Truncating table \"privmsgs\"");
$phpbb->query("
	TRUNCATE TABLE
		" . DatabaseFactory::PHPBB_TABLE_PREFIX . "privmsgs;
");

Document::getInstance()->addItem("Truncating table \"privmsgs_to\"");
$phpbb->query("
	TRUNCATE TABLE
		" . DatabaseFactory::PHPBB_TABLE_PREFIX . "privmsgs_to;
");

// The positive ID checking was a hack I made to allow deletion of messages.
// Since the message was for both inboxed, I needed an easy way to figure out
// who deleted it. If an ID is negative, that user deleted the message.
// In retrospect I should have used more columns for that.
// Good news for you is, is that this is 100% compatible with your default
// WBB2 installation. Turns out you're lucky!
// Actually, I'm lucky, otherwise I'd need to rewrite this later.
$get = $wbb->prepare("
	SELECT
		imID,
		senderID,
		recipientID,
		subject,
		message,
		sendtime
	FROM
		" . DatabaseFactory::WCF_TABLE_REPFIX . "im
	WHERE
		senderID > 0 OR
		recipientID > 0;
");

$insert = $phpbb->prepare("
	INSERT INTO
		" . DatabaseFactory::PHPBB_TABLE_PREFIX . "privmsgs
	SET
		msg_id  = :id,
		author_id = :authorId,
		message_time = :time,
		message_subject = :subject,
		message_text = :text,
		to_address = :recipient;
");

$insertTo = $phpbb->prepare("
	INSERT INTO
		" . DatabaseFactory::PHPBB_TABLE_PREFIX . "privmsgs_to
	SET
		msg_id = :id,
		user_id = :toId,
		author_id = :fromId,
		folder_id = :folderId,
		pm_new = FALSE,
		pm_unread = FALSE;
");

$get->execute();
while ($row = $get->fetch(PDO::FETCH_ASSOC)) {
	Document::getInstance()->addItem($row["imID"] . " - " . $row["subject"]);

	$recipient = "u_" + DatabaseFactory::modUserId(abs($row["recipientID"]));
	$subject = $row["subject"];
	if(strlen($subject) === 0) {
		$subject = "No Subject";
	}
	
	$insert->bindParam(":id", $row["imID"]);
	$insert->bindParam(":authorId", DatabaseFactory::modUserId(abs($row["senderID"])));
	$insert->bindParam(":time", $row["sendtime"]);
	$insert->bindParam(":subject", $subject);
	$insert->bindParam(":text", $row["message"]);
	$insert->bindParam(":recipient", $recipient);

	$insert->execute();
	$insert->closeCursor();

	$insertTo->bindParam(":id", $row["imID"]);
	$insertTo->bindParam(":toId", DatabaseFactory::modUserId(abs($row["recipientID"])));
	$insertTo->bindParam(":fromId", DatabaseFactory::modUserId(abs($row["senderID"])));

	if ($row["recipientID"] > 0) {
		$insertTo->bindParam(":folderId", $folderInbox);
		$insertTo->execute();
		$insertTo->closeCursor();
	}

	if ($row["senderID"] > 0) {
		$insertTo->bindParam(":folderId", $folderSent);
		$insertTo->execute();
		$insertTo->closeCursor();
	}
}
$get->closeCursor();

Document::getInstance()->write();
?>
