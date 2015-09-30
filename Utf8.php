<?php

namespace Vensko;

Utf8::$asciiVowels = array_fill_keys(Utf8::$asciiVowels, true);
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
    const UTF8 = 'UTF-8';
    const UTF7 = 'UTF-7';
    const UTF16BE = 'UTF-16BE';
    const UTF16LE = 'UTF-16LE';
    const UTF32BE = 'UTF-32BE';
    const UTF32LE = 'UTF-32LE';
    const CP1250 = 'Windows-1250';
    const CP1251 = 'Windows-1251';
    const CP1252 = 'Windows-1252';
    const CP1256 = 'Windows-1256';

    public static $singleByteDetectors = [
        self::CP1252 => [__CLASS__, 'detectWin1252'],
        self::CP1250 => [__CLASS__, 'detectWin1250'],
        self::CP1251 => [__CLASS__, 'detectWin1251'],
    ];

    public static $asciiVowels = [65, 69, 73, 79, 85, 89, 97, 101, 105, 111, 117, 121];

    protected static $win1251Positives = [
        255 => "\xFF", // ˙ [dot]      | я (Cyrillic ya)  | ÿ (old Dutch, rare French names)
        //223 => "\xDF", // ß (eszett) | Я (Cyrillic YA)  | ß (eszett)
        254 => "\xFE", // ţ (Gagauz)   | ю (Cyrillic yu)  | þ (Icelandic, Old English)
        222 => "\xDE", // Ţ (Gagauz)   | Ю (Cyrillic YU)  | Þ (Icelandic, Old English)
        215 => "\xD7", // × [times]    | Ч (Cyrillic CH)  | × [times]
        247 => "\xF7", // ÷            | ч (Cyrillic ch)  | ÷
        184 => "\xB8", // ¸ [cedilla]  | ё (Cyrillic yo)  | ¸ [cedilla]
        178 => "\xB2", // ˛ [ogonek]   | І (Belarusian I) | ² [square]
        162 => "\xA2", // ˇ [caron]    | ў (Belarusian u) | ¢ [cent]
        136 => "\x88", // [empty]      | € [euro]         | ˆ [circumflex]
    ];

    protected static $win1251StopList = [
        142 => "\x8E", // Ž | Ћ (Serbian Tshe)    | Ž
        158 => "\x9E", // ž | ћ (Serbian Tshe)    | ž
        143 => "\x8F", // Ź | Џ (Macedonian Dzhe) | [empty]
        159 => "\x9F", // ź | џ (Macedonian Dzhe) | Ÿ (old Dutch, rare French names)
        140 => "\x8C", // Ś | Њ (Macedonian Nje)  | Œ (French)
        156 => "\x9C", // ś | њ (Macedonian Nje)  | œ (French)
        138 => "\x8A", // Š | Љ (Macedonian Lje)  | Š
        154 => "\x9A", // š | љ (Macedonian Lje)  | š
    ];

    protected static $invalid1251Seq = [];

    protected static $win1250Positives = [
        163 => "\xA3", // Ł
        141 => "\x8D", // Ť
        143 => "\x8F", // Ź
        159 => "\x9F", // ź
    ];

    protected static $diacritics1250 = [
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
            if ($fromEncoding === static::UTF8 || $fromEncoding === static::ASCII) {
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
     * Detects ISO-8859-1 and Windows 1250, 1251
     *
     * @param string $str
     * @return string
     */
    public static function detectSingleByteEncoding($str)
    {
        $chars = count_chars($str, 1);

        // Arabic
        if (isset($chars[144])) {
            return static::CP1256;
        }

        $candidates = [];

        foreach (static::$singleByteDetectors as $enc => $callback) {
            $candidates[$enc] = $callback($chars, $str);

            if ($candidates[$enc] === true) {
                return $enc;
            }

            if ($candidates[$enc] === false) {
                unset($candidates[$enc]);
            }
        }

        asort($candidates);
        end($candidates);

        return $candidates ? key($candidates) : static::CP1251;
    }

    /**
     * @param array $chars
     * @param string $str
     * @return null|string
     */
    protected static function detectWin1250(array $chars, $str)
    {
        // €
        if (isset($chars[136])) {
            return false;
        }

        // ą
        if (isset($chars[185])) {
            $pos = strpos($str, "\xB9");

            if ($pos !== 0) {
                if (!isset($str[$pos + 1])) {
                    return true;
                }

                $prev = ord($str[$pos - 1]);

                if ($prev > 64 && $prev < 123) {
                    return true;
                }

                $next = ord($str[$pos + 1]);

                if (($next > 96 && $next < 123) || $next === 179) {
                    return true;
                }
            }
        }

        $weight = 0;

        if ($positives = array_intersect_key(static::$win1250Positives, $chars)) {
            $weight += count($positives) * 25;
        }

        $regex = '/[\xC0-\xFE\x8A-\x8F\x9A-\x9F\xA3\xB3\xA5\xAA\xAF\xB9\xBA\xBC\xBE\xBF][a-z]|[a-z][\xC0-\xFE\x8A-\x8F\x9A-\x9F\xA3\xB3\xA5\xAA\xAF\xB9\xBA\xBC\xBE\xBF]/';
        $weight += 2 * preg_match_all($regex, $str);

        return $weight;
    }

    /**
     * @param array $chars
     * @param string $str
     * @return null|string
     */
    protected static function detectWin1252(array $chars, $str)
    {
        $regex = '/[\xC0-\xFF\x8A\x9A\x8C\x9C\x8E\x9E][a-z]|[a-z][\xC0-\xFF\x8A\x9A\x8C\x9C\x8E\x9E]/';
        $weight = 5 * preg_match_all($regex, $str);

        return $weight;
    }

    /**
     * @param array $chars
     * @param string $str
     * @return null|string
     */
    protected static function detectWin1251(array $chars, $str)
    {
        if (array_intersect_key(static::$win1251StopList, $chars)) {
            return false;
        }

        $weight = 0;

        foreach (array_intersect_key(static::$win1251Positives, $chars) as $code => $hex) {
            $weight += 5 * $chars[$code];
        }

        // Either Cyrillic capital YA or German eszett
        if (isset($chars[223])) {
            $pos = strpos($str, "\xDF");

            if ($pos === 0) {
                $weight += 25;
            } else {
                $prev = ord($str[$pos - 1]); // previous letter

                if ($prev !== 228 && $prev !== 246 && $prev !== 252 // German umlauts
                    && !isset(static::$asciiVowels[$prev])
                ) {
                    $weight += 10 * $chars[223];
                }
            }
        }

        // number sign or a with ogonek
        if (isset($chars[185])) {
            $pos = strpos($str, "\xB9");

            if ($pos === 0) {
                $weight += 25;
            }

            $next = ord($str[$pos + 1]);

            if ($next > 47 && $next < 58) {
                return true;
            }
        }

        $regex = '/([\xC0-\xD9\xDD-\xDF\xA1\xA8][\xE0-\xFF\xA2\xB3\xB8\xBA\xBF\xB5]{2,}|[\xE0-\xFF\xA2\xB3\xB8\xBA\xBF\xB5]{3,})/';
        $weight += 2 * preg_match_all($regex, $str);

        $i = 0;
        while (isset($str[$i + 1])) {
            if (isset(static::$invalid1251Seq[$str[$i]][$str[$i + 1]])) {
                $weight -= 10;
            }
            $i++;
        }

        return $weight;
    }

    /**
     * Generates a table of given charsets, useful for developers
     *
     * @param string|array $charsets
     * @param bool $render
     * @return array|string
     */
    public static function getCharTable($charsets, $render = false)
    {
        $table = [];
        $nums = range(128, 255);
        $chars = array_map('chr', $nums);
        $chars = implode("\n", $chars);

        foreach ((array)$charsets as $charset) {
            $column = iconv($charset, "utf-8//IGNORE", $chars);
            $column = explode("\n", $column);
            $table[$charset] = array_combine($nums, $column);
        }

        if (!$render) {
            return $table;
        }

        $output = '';

        for ($i = 128; $i < 256; $i++) {
            $output .= $i.' | '.strtoupper(dechex($i));
            foreach ($table as $charset => $codes) {
                $output .= ' | '.($codes[$i] === '' ? ' ' : $codes[$i]);
            }
            $output .= "\n";
        }

        return $output;
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
            static::$invalid1251Seq[strtoupper($first)] = static::$invalid1251Seq[$first] = array_fill_keys(explode(',',
                $second), true);
        }
    }
}
