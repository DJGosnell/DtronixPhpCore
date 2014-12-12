<?php

namespace Core;

class FileHelper {

	/**
	 * Scans a directory recursively or not for all the files and directories.
	 * 
	 * @param string $directory Directory to initially scan.
	 * @param bool $recursive True if search is intended to be recursive.
	 * @param int $modified_time Minimum modified time to return files with.  False to disable.
	 * 
	 * @return mixed On success array of associative arrays with keys of "name" and "modified"; otherwise false..
	 */
	public static function directoryScan($directory, $recursive = false, $modified_time = false) {
		$directory = rtrim($directory, "/");
		$file_info = array();
		
		// If we did not open anything, then there is nothing to do.
		if(($handle = opendir($directory)) == false) {
			closedir($handle);
			return false;
		}
		
		while(($file = readdir($handle)) !== false) {
			
			// Ignore current and parent directory.
			if($file == "." || $file == "..") {
				continue;
			}
			
			// If we have anotehr directory, add it to the search list.
			if($recursive == true && is_dir($directory . "/" . $file)) {
				$file_info = array_merge($file_info, self::directoryScan($directory . "/" . $file, $recursive, $modified_time));
				continue;
			}

			$name = $directory . "/" . $file;
			$modified = filemtime($name);
	
			// If the supplied time is greater than the file modified time, skip it.
			if($modified_time !== false && $modified < $modified_time){
				continue;
			}

			$file_info[] = array(
				"name" => $name,
				"modified" => $modified
			);
		}
		closedir($handle);

		return $file_info;
	}

}
