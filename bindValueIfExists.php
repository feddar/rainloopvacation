<?php

function bindValueIfExists($st, $q, $name, $value, $type = PDO::PARAM_STR) {
	if (strpos($q, ":" . $name) !== FALSE)
		$st->bindValue(":" . $name, $value, $type);
}

?>
