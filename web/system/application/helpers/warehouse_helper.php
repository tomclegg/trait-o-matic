<?php

function warehouse_fetch ($path)
{
	if (is_link ($path) && ereg("^warehouse://", readlink($path))) {
		return shell_exec ("whget ''".escapeshellarg($path)." -");
	}
	else {
		return file_get_contents ($path);
	}
}

function warehouse_readfile ($path)
{
	if (is_link ($path) && ereg("^warehouse://", readlink($path))) {
		passthru ("whget ''".escapeshellarg($path)." -");
	}
	else {
		readfile ($path);
	}
}

?>
