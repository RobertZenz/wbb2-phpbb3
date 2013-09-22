<?php
error_reporting(E_ALL);
ini_set("display_errors", TRUE);

require_once __DIR__ . "/DatabaseFactory.php";
require_once __DIR__ . "/Document.php";

function getFlags($type) {
	switch ($type) {
		case 0:
			return 48;
			break;

		case 1:
			return 32;
			break;

		default:
			return 32;
			break;
	}
}

/**
 *
 * @param int $parentId
 * @param PDO $wbb
 * @param PDO $phpbb 
 * @param int $sortId 
 */
function insertForum($parentId, $wbb, $phpbb, $sortId) {
	$get = $wbb->prepare("
		SELECT
			boardID
		FROM
			" . DatabaseFactory::WBB_TABLE_REPFIX . "board_structure
		WHERE
			parentID = :id
		ORDER BY
			position ASC;
	");

	$forumGet = $wbb->prepare("
		SELECT
			boardID,
			parentID,
			title,
			description,
			boardType
		FROM
			" . DatabaseFactory::WBB_TABLE_REPFIX . "board
		WHERE
			boardID = :id;
	");

	$insert = $phpbb->prepare("
		INSERT INTO
			" . DatabaseFactory::PHPBB_TABLE_PREFIX . "forums
		SET
			forum_id = :id,
			parent_id = :parentId,
			left_id = :lid,
			forum_name = :name,
			forum_desc = :description,
			forum_type = :type,
			forum_flags = :flags;
	");

	$rightUpdate = $phpbb->prepare("
		UPDATE
			" . DatabaseFactory::PHPBB_TABLE_PREFIX . "forums
		SET
			right_id = :rightId
		WHERE
			forum_id = :id;
	");
	
	$get->bindParam(":id", $parentId);
	$get->execute();
	
	while ($row = $get->fetch(PDO::FETCH_ASSOC)) {	
		$forumGet->bindParam(":id", $row["boardID"]);
		$forumGet->execute();
		$forum = $forumGet->fetch(PDO::FETCH_ASSOC);
		$forumGet->closeCursor();

		Document::getInstance()->addItem("Inserting " . $row["boardID"] . " - " . $forum["title"]);
		
		$insert->bindParam(":id", $forum["boardID"]);
		$insert->bindParam(":parentId", $forum["parentID"]);
		$insert->bindParam(":lid", $sortId);
		$insert->bindParam(":name", $forum["title"]);
		$insert->bindParam(":description", $forum["description"]);
		$insert->bindParam(":type", typeConvert($forum["boardType"]));
		$insert->bindParam(":flags", getFlags($forum["boardType"]));
		$insert->execute();
		$insert->closeCursor();
		
		$sortId = insertForum($row["boardID"], $wbb, $phpbb, $sortId + 1);
		
		$rightUpdate->bindParam(":rightId", $sortId);
		$rightUpdate->bindParam(":id", $row["boardID"]);
		$rightUpdate->execute();
		$rightUpdate->closeCursor();
		
		$sortId++;
	}

	$get->closeCursor();
	
	return $sortId;
}

function typeConvert($type) {
	switch ($type) {
		case 0:
			return 1;
			break;

		case 1:
			return 0;
			break;

		default:
			return 0;
			break;
	}
}

$wbb = DatabaseFactory::getWbbConnection();
$phpbb = DatabaseFactory::getPhpbbConnection();
Document::getInstance()->setTitle("Forums");

Document::getInstance()->addItem("Truncating table \"forums\"");
$phpbb->query("
	TRUNCATE TABLE
		" . DatabaseFactory::PHPBB_TABLE_PREFIX . "forums;
");

insertForum(0, $wbb, $phpbb, 1);

Document::getInstance()->write();

?>
