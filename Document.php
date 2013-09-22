<?php

class Document {

	/**
	 *
	 * @var Document
	 */
	private static $instance;

	/**
	 *
	 * @return Document
	 */
	static function getInstance() {
		if (self::$instance === NULL) {
			self::$instance = new Document();
		}

		return self::$instance;
	}

	/**
	 *
	 * @var string[] 
	 */
	private $items = array();

	/**
	 *
	 * @var string
	 */
	private $title = "";

	function addItem($item) {
		array_push($this->items, $item);
	}

	public function getTitle() {
		return $this->title;
	}

	public function setTitle($title) {
		$this->title = $title;
	}

	function write() {
		if (php_sapi_name() === "cli") {
			foreach ($this->items as $item) {
				echo $item;
			}
		} else {
			?>
			<html>
				<head>
					<title>WBB2-phpbb3 - <?php echo $this->title; ?></title>
					<style type="text/css">
						body {
							font-family: monospace;
						}
					</style>
				</head>
				<body>
					<ul>
						<?php foreach ($this->items as $item) { ?>
							<li><?php echo $item; ?></li>
						<?php } ?>
					</ul>
				</body>
			</html>
			<?php
		}
	}

}
?>
