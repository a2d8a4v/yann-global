<?php
/**
 * Class: UTILS
 * Custom for global settings.
 */

class UTILS {

	private $errors;

	/**
	 * Set up the hooks and default values
	 */
	public function __construct() {
		$this->errors = False;
		$this->YANN_root_user_id = 1;

		$this->empty_array = array();
	}

	/**
	 * Function to get microtime from server
	 *
	 */
	public function UTILS_millitime() {
		$microtime = microtime();
		$comps = explode(' ', $microtime);
	  
		// Note: Using a string here to prevent loss of precision
		// in case of "overflow" (PHP converts it to a double)
		return sprintf('%d%03d', $comps[1], $comps[0] * 1000);
	}



	/**
	 * Remove BOM characters
	 * @See https://stackoverflow.com/questions/37823850/php-curl-setopt-invalid-characters-0-failing-on-curl-init
	 * 
	 */
	public function UTILS_removeBOMcharacters($var) {
		return preg_replace('/\\0/', "", $var);
	}


	/**
	 * Function to zip two array
	 *
	 */
	public function UTILS_array_zip($a1, $a2) {
		$out = array();
		for($i = 0; $i < min(count($a1), count($a2)); $i++) {
			$out[$a1[$i]] = $a2[$i];
		}
		return $out;
	}



	/**
	 * Function to convert title to title case
	 *
	 */
	public function UTILS_getTitleCase( $s ) {
		return Stringy::create($s)->toTitleCase();
	}

	/**
	 * Function to remove ascii characters
	 *
	 */
	public function UTILS_cleanAsciiCharacters($string) {
		$string = str_replace(array('-', '–'), '-', $string);
		$string = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $string);  
		return $string;
	}


	/**
	 * Calculate the similarity of two sequences
	 *
	 */
	public function UTILS_twoSeqSimilarity($str1, $str2) {
		$len1 = strlen($str1);
		$len2 = strlen($str2);
	   
		$max = max($len1, $len2);
		$similarity = $i = $j = 0;
	   
		while (($i < $len1) && isset($str2[$j])) {
			if ($str1[$i] == $str2[$j]) {
				$similarity++;
				$i++;
				$j++;
			} elseif ($len1 < $len2) {
				$len1++;
				$j++;
			} elseif ($len1 > $len2) {
				$i++;
				$len1--;
			} else {
				$i++;
				$j++;
			}
		}
	
		return round($similarity / $max, 2);
	}

	public function UTILS_empty_array() {
		return $this->empty_array;
	}

	public function UTILS_language_regex( $language ) {

		// validation
		$this->UTILS_variable_validation('empty', $language, '');
		$this->UTILS_variable_validation('string', $language, '');

		// return
		switch (strtolower($language)) {
			case ('chinese'):
				return "/[\x{4e00}-\x{9fff}]|[\x{3400}-\x{4DBF}]|[\x{f900}-\x{faff}]|[\x{3100}-\x{312f}]/u";
				break;
			case ('japanese'):
				return "/[\x{3000}-\x{303f}]|[\x{3040}-\x{309f}]|[\x{u30a0}-\x{30ff}]|[\x{ff00}-\x{ff9f}]|[\x{4e00}-\x{9faf}]|[\x{3400}-\x{4dbf}]/u";
				break;
			case ('korean'):
				return "/[\x{ac00}-\x{d7a3}]|[\x{1100}-\x{11ff}]|[\x{3131}-\x{318e}]|[\x{ffa1}-\x{ffdc}]/u";
				break;
			case ('english'):
				return "/[a-zA-Z]/";
				break;
			case ('english_and_numbers'):
				return "/[a-zA-Z0-9]/";
				break;
			case ('numbers'):
				return "/[0-9]/";
				break;
			case ('special_characters'):
				return "/[\'^£$%&*()}{@#~?><>,|=_+¬-]/";
				break;
			default:
				throw new exception('Language is no implemented yet.');
				break;
		}

	}


	public function UTILS_variable_validation( $type, $variable, $error_message ) {
		switch ($type) {
			case ('string'):
				if (! is_string($variable)) {
					throw new exception('Variable should be a string type!');
				}
				break;
			case ('array'):
				if (! is_array($variable)) {
					throw new exception('Variable should be an array type!');
				}
				break;
			case ('empty'):
				if (empty($variable)) {
					throw new exception('Variable is empty!');
				}
				break;
			default:
				throw new exception('You need to give a type!');
				break;
		}
		return True;
	}

	public function UTILS_get_post_id($_request) {

		$errors = new WP_Error();

		if ( isset( $_request['post_id'] ) ) {
			return $_request['post_id'];
		}

		global $post;
		if (! empty($post) ) {
			$post_id = $post->ID;
			return $post_id;
		}

		$errors->add(__FUNCTION__, __('post_id does not exist here!', 'YANN_NTNUSMIL'));
		return $errors;
	}

	public function UTILS_get_post_type($_request) {

		$errors = new WP_Error();

		if ( isset( $_request['post_id'] ) ) {
			$post = get_post($_request['post_id']);
			$post_type = $post->post_type;
			return $post_type;
		}

		global $post;
		if (! empty($post) && ! isset($post_type) ) {
			$post_type = $post->post_type;
			return $post_type;
		}

		$errors->add(__FUNCTION__, __('post_type does not exist here!', 'YANN_NTNUSMIL'));
		return $errors;
	}
}
