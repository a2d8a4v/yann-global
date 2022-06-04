<?php
/**
 * Class: DELETE_FILES
 * Custom for global settings.
 */

class DELETE_FILES {

	private $errors;

	/**
	 * Set up the hooks and default values
	 */
	public function __construct() {
		$this->errors = False;
		$this->YANN_root_user_id = 1;
	}


	/**
	 * Remove all files and the folder
	 * 
	 * @param object $dir, dir absolute path
	 */
	private function DELETE_FILES_rmDirRecursive( $dir ) {
		$it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
		$files = new RecursiveIteratorIterator($it,
					 RecursiveIteratorIterator::CHILD_FIRST);
		foreach($files as $file) {
			if ($file->isDir()){
				rmdir($file->getRealPath());
			} else {
				unlink($file->getRealPath());
			}
		}
		rmdir($dir);
	}


	/**
	 * Remove uploaded files
	 * 
	 * @param object $post_id, post id of post
	 */
	private function DELETE_FILES_deleteUploadedFiles( $post_id ) {
		global $wpdb;
		$posttype  = $this->posttype;
		$joysticks = $this->joystick_types_arr;
		$labname   = $this->labname;

		foreach ($joysticks as $joystick) {

			// has file check
			$table_name = $wpdb->prefix . $this->table_for_uploaded_file;
			$DBsearch   = $wpdb->get_results ( "
			SELECT * 
			FROM  $table_name
				WHERE postid = '".$post_id."'
				AND CPTtype = '".$posttype."'
				AND joystick = '".$joystick."'
			" );

			// Maybe multiple files
			foreach($DBsearch as $fileinfo) {

				$Year      = $fileinfo->year;
				$micro     = $fileinfo->microtimestamp;
				$filename  = $fileinfo->filename;
				$timestamp = $fileinfo->timestamp;
				// Upload dir path
				if (!isset($this->upload_dir)) {
					$this->upload_dir = '/CPT/' . $posttype . "/" . $joystick . "/" . $Year;
				}
				$dir = wp_upload_dir()['basedir'] . $this->upload_dir;

				// Remove db record
				$data = array(
					'postid' => $post_id ,
					'Labname' => $labname ,
					'CPTtype' => $posttype ,
					'joystick' => $joystick ,
					'timestamp' => $timestamp ,
					'microtimestamp' => $micro ,
					'filename' => $filename ,
					'year' => $Year,
				);
				$data_format = array( '%s' , '%s' , '%s' , '%s' , '%s' , '%s' , '%s' , '%s' );
				$do_delete   = $wpdb->delete( $table_name , $data , $data_format );

				// Delete files and folder
				$this->DELETE_FILES_rmDirRecursive( $dir . "/" . $post_id );
				// If the Year level folder is also empty, just delete it
				if ( $this->is_dir_empty( $dir ) ) {
					rmdir($dir);
				}
				$this->upload_dir = "";
			}
		}
	}


	/**
	 * Check is empty folder
	 * 
	 * @param object $post_id, post id of post
	 */
	private function DELETE_FILES_is_dir_empty($dir) {
		$handle = opendir($dir);
		while (false !== ($entry = readdir($handle))) {
			if ($entry != "." && $entry != "..") {
				closedir($handle);
				return false;
			}
		}
		closedir($handle);
		return true;
	}

}
