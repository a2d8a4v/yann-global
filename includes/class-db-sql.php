<?php
/**
 * Class: DB_SQL
 * Custom for global settings.
 */

class DB_SQL {

	private $errors;

	public $type;
	public $table_name;
	public $postid_userid;

	/**
	 * Set up the hooks and default values
	 */
	public function __construct($params=array()) {
		$this->errors = False;
		$this->YANN_root_user_id = 1;

		$this->kind_arr = array(
			'record' => 'DB_SQL_uploaded_file_record_table',
			'option' => 'DB_SQL_options_table',
		);

		// validation
		foreach( $params as $k => $v ) {
			if (! in_array(
					$k, array(
						'type',
						'table_name',
						'postid_userid',
					)
				)
			) {
				throw new Exception("Key {$k} not ready!");
			}
			if ($k === 'type') {
				if (! in_array(
					$v, array(
							'record',
							'option',
						)
					)
				) {
					throw new Exception("type should be record or option!");
				}
			}
		}

		// assign
		foreach( $params as $k => $v ) {
			$this->$k = $v;
		}
	}

	public function DB_SQL_which_kind_table() {
		if ( array_key_exists($this->type, $this->kind_arr) ) {
			$func_name = $this->kind_arr[$this->type];
			return $this->$func_name();
		}
	}

	/**
	 * Function for generating table to recording conferences' names
	 *
	 */
	private function DB_SQL_options_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . $this->table_name;
		if ( $this->DB_SQL_check_table_not_exists( $table_name ) === true ) {
			$charset_collate = $this->DB_SQL_get_charset_table() ;
			$sql             = "CREATE TABLE IF NOT EXISTS `$table_name` (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			name VARCHAR(999) NOT NULL,
			UNIQUE KEY id (id)
			) $charset_collate;" ;
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' ) ;
			dbDelta( $sql ) ;
		}
	}

	/**
	 * Function for generating table to recording uploaded files
	 *
	 */
	private function DB_SQL_uploaded_file_record_table() {
		global $wpdb;
		$postid_userid = $this->postid_userid;
		$table_name = $wpdb->prefix . $this->table_name;
		if ( $this->DB_SQL_check_table_not_exists( $table_name ) === true ) {
			$charset_collate = $this->DB_SQL_get_charset_table() ;
			$sql             = "CREATE TABLE IF NOT EXISTS `$table_name` (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			$postid_userid VARCHAR(999) NOT NULL,
			Labname VARCHAR(999) NOT NULL,
			CPTtype VARCHAR(999) NOT NULL,
			joystick VARCHAR(999) NOT NULL,
			timestamp VARCHAR(999) NOT NULL,
			microtimestamp VARCHAR(999) NOT NULL,
			filename VARCHAR(999) NOT NULL,
			year VARCHAR(999) NOT NULL,
			mimetype VARCHAR(999) NOT NULL,
			counts int(9) DEFAULT NULL,
			UNIQUE KEY id (id)
			) $charset_collate;" ;
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' ) ;
			dbDelta( $sql ) ;
		}
	}

	/**
	 * Function for DB_SQL_meeting_uploaded_file_record_table
	 *
	 */
	private function DB_SQL_get_charset_table() {
		global $wpdb ;
		$charset_collate = $wpdb->has_cap( 'collation' ) ? $wpdb->get_charset_collate() : '' ;
		return $charset_collate ;
	}

	/**
	 * Create Table for Record download
	 *
	 */
	private function DB_SQL_check_table_not_exists( $table_name ) {
		global $wpdb ;
		$data_base     = constant( 'DB_NAME' ) ;
		$column_exists = $wpdb->query( "select * from information_schema.columns where table_schema='$data_base' and table_name = '$table_name'" ) ;
		if ( $column_exists === 0 ) {
			return true ;
		}
		return false ;
	}

}
