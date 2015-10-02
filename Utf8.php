<?php

namespace Vensko;

Utf8::$asciiRange = array_fill_keys(range(0, 127), true);

class Utf8
{
    const UTF16BE_BOM = "\xFE\xFF";
    const UTF16LE_BOM = "\xFF\xFE";
    const UTF8_BOM = "\xEF\xBB\xBF";
    const UTF7_BOM = "\x2B\x2F\x76";
    const UTF32BE_BOM = "\x00\x00\xFE\xFF";
    const UTF32LE_BOM = "\xFF\xFE\x00\x00";

    const ASCII = 'ASCII';

    const UTF8 = 'UTF-8';
    const UTF7 = 'UTF-7';
    const UTF16BE = 'UTF-16BE';
    const UTF16LE = 'UTF-16LE';
    const UTF32BE = 'UTF-32BE';
    const UTF32LE = 'UTF-32LE';

    const CP1250 = 'Windows-1250';
    const CP1251 = 'Windows-1251';
    const CP1252 = 'Windows-1252';

    public static $asciiRange = [];

    /**
     * Ensures that a string is UTF-8 encoded
     *
     * @param string|$str
     * @param string|null $fromEncoding
     * @param bool $force
     * @return string
     */
    public static function convert($str, $fromEncoding = null, $force = false)
    {
        if ($fromEncoding === null) {
            $fromEncoding = static::detectEncoding($str);
        }

        if ($fromEncoding !== null) {
            if ($fromEncoding === static::UTF8 || $fromEncoding === static::ASCII) {
                return $str;
            }

            return mb_convert_encoding($str, static::UTF8, $fromEncoding);
        }

        return $force ? mb_convert_encoding($str, static::UTF8) : $str;
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

        $enc = static::detectBOM($str)
        OR $enc = static::detectUtf16WithoutBOM($str)
        OR $enc = static::detectUTF8($str)
        OR $enc = static::detectSingleByteEncoding($str);

        return $enc;
    }

    /**
     * @param string $str
     * @param array $chars
     * @return null|string
     */
    public static function detectASCII($str, array $chars = [])
    {
        if ($str === '') {
            return static::ASCII;
        }

        if (!$chars) {
            $chars = count_chars($str, 1);
        } else {
            reset($chars);
        }

        // Check for null character
        if (!key($chars)) {
            return null;
        }

        end($chars);

        // Check for 8-bit character
        if (key($chars) > 127) {
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
            } else {
                if (strpos($str, "\x0020") !== false) {
                    return static::UTF16BE;
                } else {
                    return $nullPos % 2 === 0 ? static::UTF16BE : static::UTF16LE;
                }
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
                    return static::UTF32LE;
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
                    return static::UTF7;
                }

                if ("\x00\x00" === $bom && isset($str[3]) && static::UTF32BE_BOM === $bom.$str[2].$str[3]) {
                    return static::UTF32BE;
                }
            }
        }

        return null;
    }

    /**
     * Detects Windows 1250, 1251, 1252
     *
     * @param string $str
     * @return string
     */
    public static function detectSingleByteEncoding($str)
    {
        if (strpos($str, '<') !== false) {
            $str = strip_tags($str);
        }

        $regex = '/\b[\xC0-\xD9\xDD-\xDF\xA1\xA8\xE0-\xFF\xA2\xB3\xB8\xBA\xBF\xB5][\xE0-\xFF\xA2\xB3\xB8\xBA\xBF\xB5]+\b/';
        $nonLatinWords = preg_match_all($regex, $str);

        if ($nonLatinWords) {
            $regex = '/\b(?:[a-z]+[\xC0-\xFF\x8A\x9A\x8C\x9C\x8E\x9E]|[\xC0-\xFF\x8A\x9A\x8C\x9C\x8E\x9E]+[a-z])[a-z]*\b/';
            $latinWords = preg_match_all($regex, $str);

            if ($nonLatinWords > $latinWords) {
                return static::CP1251;
            }
        }

        $chars = count_chars($str, 1);
        $chars = array_diff_key($chars, static::$asciiRange);

        $matched = [
            static::CP1250 => 0,
            static::CP1252 => 0,
        ];

        foreach (Alphabet::$chars as $lang => $codes) {
            if (!array_diff_key($chars, $codes)) {
                $enc = Alphabet::$langCharsets[$lang];

                if (!$matched[$enc]) {
                    $matched[$enc] = 1;

                    if (min($matched)) {
                        break;
                    }
                }
            }
        }

        if ($matched[static::CP1250] !== $matched[static::CP1252]) {
            return $matched[static::CP1250] > $matched[static::CP1252] ? static::CP1250 : static::CP1252;
        }

        arsort($chars);

        $langs = [];
        foreach ($chars as $char => $count) {
            foreach (array_column(Alphabet::$chars, $char) as $lang) {
                if (!isset($langs[$lang])) {
                    $langs[$lang] = 0;
                }

                $langs[$lang] += 10 * $count;
            }
        }

        $langs = array_keys($langs, max($langs));

        if (!isset($langs[1])) {
            return Alphabet::$langCharsets[$langs[0]];
        }

        $enc = [];
        foreach ($langs as $lang) {
            $enc[Alphabet::$langCharsets[$lang]] = true;
        }

        if (count($enc) === 1) {
            return key($enc);
        }

        // At this point we've got a relatively short string which fits into multiple alphabets in both encodings.

        if (isset($chars[141]) || isset($chars[143]) || isset($chars[157]) || preg_match('/[\xA3\xB3\xAA\xAF\xB9\xA5\xBA\xBC\xBE][a-z\xC0-\xFF]/', $str)) {
            return static::CP1250;
        }

        return static::CP1252;
    }
}
