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

}
