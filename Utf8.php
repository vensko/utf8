<?php

namespace Vensko;

Utf8::$asciiVowels = array_flip(Utf8::$asciiVowels);
Utf8::prepareNonCyrillicSequences();

class Utf8
{
	const UTF16BE_BOM = "\xFE\xFF";
	const UTF16LE_BOM = "\xFF\xFE";
	const UTF8_BOM = "\xEF\xBB\xBF"; // chr(0xEF).chr(0xBB).chr(0xBF)
	const UTF7_BOM = "\x2B\x2F\x76";
	const UTF32BE_BOM = "\x00\x00\xFE\xFF";
	const UTF32LE_BOM = "\xFF\xFE\x00\x00";

	const ASCII = 'ASCII';
	const ISO88591 = 'ISO-8859-1';
	const UTF8 = 'UTF-8';
	const UTF7 = 'UTF-7';
	const UTF16BE = 'UTF-16BE';
	const UTF16LE = 'UTF-16LE';
	const CP1250 = 'Windows-1250';
	const CP1251 = 'Windows-1251';
	const CP1256 = 'Windows-1256';

	const CYR_CAPITAL = '\\xC0-\\xD9\\xDD-\\xDF\\xA1\\xA8';
	const CYR_SMALL = '\\xE0-\\xFF\\xA2\\xB3\\xB8\\xBA\\xBF\\xB5';

	public static $asciiVowels = [65, 69, 73, 79, 85, 89, 97, 101, 105, 111, 117, 121];

	public static $invalid1251Seq = [];

	public static $nonCyrillic1251 = [
		142 => "\x8E", // Z with caron
		158 => "\x9E", // z with caron
		143 => "\x8F", // Z with acute accent
		159 => "\x9F", // z with acute accent
		140 => "\x8C", // non-cyrillic
		156 => "\x9C", // non-cyrillic
		138 => "\x8A", // non-cyrillic
		154 => "\x9A", // non-cyrillic
	];

	public static $diacritics1250 = [
		163 => "\xA3", // L with stroke
		179 => "\xB3", // l with stroke
		143 => "\x8F", // Z with acute accent
		159 => "\x9F", // z with acute accent
		175 => "\xAF", // Z with dot
		191 => "\xBF", // z with dot
		165 => "\xA5", // A with ogonek
		185 => "\xB9", // a with ogonek
		170 => "\xAA", // S with ogonek
		186 => "\xBA", // s with ogonek
		188 => "\xBC", // L with acute accent
		190 => "\xBE", // l with acute accent
	];

	protected static $charCount = [];

	/**
	 * Ensures that a string is UTF-8 encoded
	 *
	 * @param string|$str
	 * @param string|null $fromEncoding
	 * @param bool $forceUTF8
	 * @return string
	 */
	public static function convert($str, $fromEncoding = null, $forceUTF8 = false)
	{
		if ($fromEncoding === null) {
			$fromEncoding = static::detectEncoding($str);
		}

		if ($fromEncoding !== null) {
			if ($fromEncoding === 'ASCII' || $fromEncoding === static::UTF8) {
				return $str;
			}

			return mb_convert_encoding($str, static::UTF8, $fromEncoding);
		}

		return $forceUTF8 ? mb_convert_encoding($str, static::UTF8) : $str;
	}

	/**
	 * Detects string encoding
	 *
	 * Supported incoming charsets:
	 * - ASCII
	 * - UTF-8
	 * - UTF-16LE
	 * - UTF-16BE
	 * - UTF-32LE with BOM
	 * - UTF-32BE with BOM
	 * - UTF-7 with BOM
	 * - CP1250
	 * - CP1251
	 * - ISO-8859-1
	 *
	 * @param string $str
	 * @return string
	 */
	public static function detectEncoding($str)
	{
		if ($str === '') {
			return static::ASCII;
		}

		static::$charCount = [];

		$enc = static::detectBOM($str)
			OR $enc = static::detectUtf16WithoutBOM($str)
			OR $enc = static::detectASCII($str)
			OR $enc = static::detectUTF8($str)
			OR $enc = static::detectSingleByteEncoding($str, static::$charCount);

		return $enc;
	}

	/**
	 * @param string $str
	 * @return null|string
	 */
	public static function detectASCII($str)
	{
		if ($str === '') {
			return static::ASCII;
		}

		// count_chars returns the characters in ascending octet order
		static::$charCount = count_chars($str, 1);

		// Check for null character
		if (!key(static::$charCount)) {
			return null;
		}

		end(static::$charCount);

		// Check for 8-bit character
		if (key(static::$charCount) > 127) {
			return null;
		}

		return static::ASCII;
	}

	/**
	 * @param string $str
	 * @return null|string
	 */
	public static function detectUtf16WithoutBOM($str)
	{
		$nullPos = strpos($str, "\x00");

		if ($nullPos !== false) {
			if (strpos($str, "\x2000") !== false) {
				return static::UTF16LE;
			} else if (strpos($str, "\x0020") !== false) {
				return static::UTF16BE;
			} else {
				return $nullPos % 2 === 0 ? static::UTF16BE : static::UTF16LE;
			}
		}

		return null;
	}

	/**
	 * @param string $str
	 * @return null|string
	 */
	public static function detectUTF8($str)
	{
		return mb_check_encoding($str, 'UTF-8') ? static::UTF8 : null;
	}

	/**
	 * @param string $str
	 * @return null|string
	 */
	public static function detectBOM($str)
	{
		if (isset($str[1])) {
			$bom = $str[0].$str[1];

			if (static::UTF16BE_BOM === $bom) {
				return static::UTF16BE;
			}

			if (static::UTF16LE_BOM === $bom) {
				if (isset($str[3]) && static::UTF32LE_BOM === $bom.$str[2].$str[3]) {
					return 'UTF-32LE';
				} else {
					return static::UTF16LE;
				}
			}

			if (isset($str[2])) {
				$bom .= $str[2];

				if (static::UTF8_BOM === $bom) {
					return static::UTF8;
				}

				if (static::UTF7_BOM === $bom) {
					return 'UTF-7';
				}

				if ("\x00\x00" === $bom && isset($str[3]) && static::UTF32BE_BOM === $bom.$str[2].$str[3]) {
					return 'UTF-32BE';
				}
			}
		}

		return null;
	}

	/**
	 * Detects ISO-8859-1 and Windows 1250, 1251
	 *
	 * @param string $str
	 * @param array $chars
	 * @return string
	 */
	public static function detectSingleByteEncoding($str, $chars = [])
	{
		$enc = static::detectWinByUniqueCode($chars)
		OR $enc = static::detectWin1251($chars, $str)
		OR $enc = static::detectWin1250($chars, $str)
		OR $enc = static::detectISO88591($str);

		if (!$enc) {
			$enc = static::mayBeWin1251($chars, $str) ? static::CP1251 : static::ISO88591;
		}

		return $enc;
	}

	/**
	 * @param array $chars
	 * @param string $str
	 * @return null|string
	 */
	public static function detectWin1250(array $chars, $str)
	{
		// euro
		if (isset($chars[136])) {
			return null;
		}

		// a with ogonek
		if (isset($chars[185])) {
			$pos = strpos($str, "\xB9");

			if ($pos !== 0) {
				if (!isset($str[$pos + 1])) {
					return static::CP1250;
				}

				$prev = ord($str[$pos - 1]);

				if ($prev > 64 && $prev < 123) {
					return static::CP1250;
				}

				$next = ord($str[$pos + 1]);

				if (($next > 96 && $next < 123) || $next === 179) {
					return static::CP1250;
				}
			}
		}

		foreach (static::$diacritics1250 as $index => $i) {
			if (isset($chars[$index])) {
				$pos = strpos($str, $i);

				// next or previous is ASCII letter
				if ($pos > 0) {
					$prev = ord($str[$pos - 1]);
					if (($prev > 64 && $prev < 123) || $prev === 163) {
						return static::CP1250;
					}
				}
				if (isset($str[$pos + 1])) {
					$next = ord($str[$pos + 1]);
					if (($next > 64 && $next < 123) || $next === 163) {
						return static::CP1250;
					}
				}
			}
		}

		return null;
	}

	/**
	 * @param string $str
	 * @return null|string
	 */
	public static function detectISO88591($str)
	{
		if (preg_match('/[\x41-\x7A]{2,}[\xC0-\xFE]|[\xC0-\xFE][\x41-\x7A]{2,}/', $str)) {
			return static::ISO88591;
		}

		return null;
	}

	/**
	 * @param array $chars
	 * @return null|string
	 */
	public static function detectWinByUniqueCode(array $chars)
	{
		// Cyrillic small ya
		if (isset($chars[255])) {
			return static::CP1251;
		}

		// Cyrillic yo
		if (isset($chars[184])) {
			return static::CP1251;
		}

		// Cyryllic Belarusian I
		if (isset($chars[178])) {
			return static::CP1251;
		}

		// Cyrillic u short
		if (isset($chars[162])) {
			return static::CP1251;
		}

		// Euro
		if (isset($chars[136])) {
			return static::CP1251;
		}

		// L with stroke
		if (isset($chars[163])) {
			return static::CP1250;
		}

		// T with caron
		if (isset($chars[141])) {
			return static::CP1250;
		}

		// Z with acute accent
		if (isset($chars[143])) {
			return static::CP1250;
		}

		// z with acute accent
		if (isset($chars[159])) {
			return static::CP1250;
		}

		// Arabic
		if (isset($chars[144])) {
			return static::CP1256;
		}

		return null;
	}

	/**
	 * @param array $chars
	 * @param string $str
	 * @return null|string
	 */
	public static function detectWin1251(array $chars, $str)
	{
		// Cyrillic capital and small ch
		if (isset($chars[247]) || isset($chars[215])) {
			$pos = strpos($str, isset($chars[247]) ? "\xF7" : "\xD7");
			if ($pos === 0 || !ctype_digit($str[$pos - 1])) {
				return static::CP1251; // also 1253, 1255
			}
		}

		// Either Cyrillic capital YA or German eszett
		if (isset($chars[223])) {
			$pos = strpos($str, "\xDF");

			if ($pos === 0) {
				return static::CP1251;
			}

			$prev = ord($str[$pos - 1]); // previous letter

			if ($prev === 228 || $prev === 246 || $prev === 252 // German umlauts
				|| isset(static::$asciiVowels[$prev])
			) {
				// Looks like German
				return static::ISO88591;
			} else {
				return static::CP1251;
			}
		}

		// number sign or a with ogonek
		if (isset($chars[185])) {
			$pos = strpos($str, "\xB9");

			if ($pos === 0) {
				return static::CP1251;
			}

			$next = ord($str[$pos + 1]);

			if ($next > 47 && $next < 58) {
				return static::CP1251;
			}
		}

		if (preg_match('/(['.static::CYR_CAPITAL.']['.static::CYR_SMALL.']{2,}|['.static::CYR_SMALL.']{3,})/', $str)) {
			return static::CP1251;
		}

		return null;
	}

	/**
	 * @param array $chars
	 * @param string $str
	 * @return bool
	 */
	public static function mayBeWin1251(array $chars, $str)
	{
		if (array_intersect_key($chars, static::$nonCyrillic1251)) {
			return false;
		}

		$i = 0;
		while (isset($str[$i + 1])) {
			if (isset(static::$invalid1251Seq[$str[$i]]) && isset(static::$invalid1251Seq[$str[$i]][$str[$i + 1]])) {
				return false;
			}
			$i++;
		}

		return true;
	}

	public static function prepareNonCyrillicSequences()
	{
		$c = [
			'а:ъ,ы,ь',
			'б:й',
			'в:й,э',
			'г:й,ф,х,ь,ъ',
			'д:й',
			'е:э,ъ,ы,ь',
			'ж:й,ф,х,ш,щ',
			'з:й,п,щ',
			'и:ъ,ы,ь',
			'і:ъ,ы,ь',
			'й:а,ё,ж,й,э,ъ,ы,ь',
			'к:й,щ,ь',
			'л:й,э,ъ',
			'м:й,ъ',
			'н:й',
			'о:ъ,ы,ь',
			'п:в,г,ж,з,й,ъ',
			'р:й,ъ',
			'с:й',
			'т:й',
			'у:ъ,ы,ь',
			'ў:ъ,ы,ь',
			'ф:б,ж,з,й,п,х,ц,ъ,э',
			'х:ё,ж,й,щ,ы,ь,ю,я',
			'ц:б,ж,й,ф,х,ч,щ,ъ',
			'ч:б,г,з,й,п,ф,щ,ъ,ю,я',
			'ш:д,ж,з,й,щ,ъ',
			'щ:б,г,д,ж,з,й,л,п,т,ф,х,ц,ч,ш,щ,ъ,ы,э,ю,я',
			'ъ:ц,у,к,н,г,ш,щ,з,й,х,ъ,ф,ы,в,а,п,р,о,л,д,ж,э,ч,с,м,и,т,ь,б',
			'ы:а,ё,о,ф,э',
			'ь:а,й,л,у',
			'э:а,е,ё,ц,ч,э,ю,ъ,ы,ь',
			'ю:у,ъ,ы,ь',
			'я:а,ё,о,э,ъ,ы,ь',
		];

		$c = implode("\n", $c);
		$c = mb_convert_encoding($c, 'Windows-1251', 'UTF-8');
		$c = explode("\n", $c);

		static::$invalid1251Seq = [];

		foreach ($c as $line) {
			list($first, $second) = explode(':', $line);
			static::$invalid1251Seq[$first] = array_fill_keys(explode(',', $second), true);
		}
	}
}
