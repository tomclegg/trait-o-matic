<?php

function is_warehouse_symlink($path)
{
  return is_link ($path)
    && (ereg("^warehouse://", readlink($path)) ||
	(!file_exists ($path) && preg_match("/^[0-9a-f]{32}/", readlink($path))));
}

function warehouse_fetch ($path)
{
	if (is_warehouse_symlink ($path))
	{
		$pw = posix_getpwuid(posix_getuid());
		putenv("HOME=".$pw["dir"]);
		return shell_exec ("whget ''".escapeshellarg($path)." -");
	}
	elseif (file_exists ($path)) {
		return file_get_contents ($path);
	}
	else {
		return FALSE;
	}
}

function warehouse_readfile ($path)
{
	if (is_warehouse_symlink ($path)) {
		passthru ("whget ''".escapeshellarg($path)." -");
		return TRUE;
	}
	elseif (file_exists ($path)) {
		readfile ($path);
		return TRUE;
	}
	else {
		return FALSE;
	}
}

?>
