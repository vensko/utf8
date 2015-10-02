<?php

namespace Vensko;

foreach (Alphabet::$chars as $lang => $codes) {
    Alphabet::$chars[$lang] = array_fill_keys(array_merge($codes, Alphabet::$nonAlpha[Alphabet::$langCharsets[$lang]]),
        $lang);

    foreach (Alphabet::$chars[$lang] as $code => $v) {
        Alphabet::$langIndex[$code][Alphabet::$langCharsets[$lang]][$lang] = true;
    }
}

class Alphabet
{
    /*
     * 1250
     */
    const HUNGARIAN = 0;
    const ALBANIAN = 1;
    const SERBIAN = 2;
    const POLISH = 3;
    const SLOVENE = 4;
    const ROMANIAN = 5;
    const CZECH = 6;
    const SLOVAK = 7;
    const SORBIAN = 8;
    const TURKMEN = 9;

    /*
     * 1252
     */
    const GERMAN = 10;
    const FRENCH = 11;
    const SPANISH = 12;
    const PORTUGUESE = 13;
    const ITALIAN = 14;
    const DANISH = 15;
    const FINNISH = 16;
    const NORWEGIAN = 17;
    const SWEDISH = 18;
    const ICELANDIC = 19;

    public static $langCharsets = [
        // Windows-1250
        self::HUNGARIAN => Utf8::CP1250,
        self::SERBIAN => Utf8::CP1250,
        self::POLISH => Utf8::CP1250,
        self::SLOVENE => Utf8::CP1250,
        self::ROMANIAN => Utf8::CP1250,
        self::CZECH => Utf8::CP1250,
        self::SLOVAK => Utf8::CP1250,
        self::SORBIAN => Utf8::CP1250,
        self::TURKMEN => Utf8::CP1250,

        // Windows-1252
        self::FRENCH => Utf8::CP1252,
        self::SPANISH => Utf8::CP1252,
        self::PORTUGUESE => Utf8::CP1252,
        self::ITALIAN => Utf8::CP1252,
        self::DANISH => Utf8::CP1252,
        self::FINNISH => Utf8::CP1252,
        self::NORWEGIAN => Utf8::CP1252,
        self::SWEDISH => Utf8::CP1252,
        self::ICELANDIC => Utf8::CP1252,

        // fit both
        self::GERMAN => Utf8::CP1252,
        self::ALBANIAN => Utf8::CP1252,
    ];

    public static $langIndex = [];

    public static $chars = [
        self::GERMAN => [ // Same as CP1250
            196, // Ä
            228, // ä
            214, // Ö
            246, // ö
            220, // Ü
            252, // ü
            223, // ß
        ],
        self::FRENCH => [
            201, // É
            233, // é
            192, // À
            224, // à
            200, // È
            232, // è
            217, // Ù
            249, // ù
            194, // Â
            226, // â
            202, // Ê
            234, // ê
            206, // Î
            238, // î
            212, // Ô
            244, // ô
            219, // Û
            251, // û
            203, // Ë
            235, // ë
            207, // Ï
            239, // ï
            220, // Ü
            252, // ü
            159, // Ÿ
            255, // ÿ
            199, // Ç
            231, // ç
            209, // Ñ
            241, // ñ
            140, // Œ
            156, // œ
            198, // Æ
            230, // æ
        ],
        self::SPANISH => [
            209, // Ñ
            241, // ñ
            161, // ¡
            191, // ¿
        ],
        self::PORTUGUESE => [
            193, // Á
            225, // á
            194, // Â
            226, // â
            195, // Ã
            227, // ã
            192, // À
            224, // à
            199, // Ç
            231, // ç
            201, // É
            233, // é
            202, // Ê
            234, // ê
            205, // Í
            237, // í
            211, // Ó
            243, // ó
            212, // Ô
            244, // ô
            213, // Õ
            245, // õ
            218, // Ú
            250, // ú
        ],
        self::ITALIAN => [
            192, // À
            224, // à
            201, // É
            233, // é
            200, // È
            232, // è
            211, // Ó
            243, // ó
            210, // Ò
            242, // ò
            204, // Ì
            236, // ì
            238, // î
            217, // Ù
            249, // ù
        ],
        self::DANISH => [
            198, // Æ
            230, // æ
            216, // Ø
            248, // ø
            197, // Å
            229, // å
        ],
        self::SWEDISH => [
            197, // Å
            229, // å
            196, // Ä
            228, // ä
            214, // Ö
            246, // ö
        ],
        self::NORWEGIAN => [
            198, // Æ
            230, // æ
            216, // Ø
            248, // ø
            197, // Å
            229, // å
            201, // É
            233, // é
            200, // È
            232, // è
            202, // Ê
            234, // ê
            211, // Ó
            243, // ó
            210, // Ò
            242, // ò
            194, // Â
            226, // â
            212, // Ô
            244, // ô
        ],
        self::FINNISH => [
            197, // Å
            229, // å
            196, // Ä
            228, // ä
            214, // Ö
            246, // ö
            138, // Š
            154, // š
            142, // Ž
            158, // ž
        ],
        self::ICELANDIC => [
            193, // Á
            225, // á
            208, // Ð
            240, // ð
            201, // É
            233, // é
            211, // Ó
            243, // ó
            218, // Ú
            250, // ú
            221, // Ý
            253, // ý
            222, // Þ
            254, // þ
            198, // Æ
            230, // æ
        ],

        /*
         * 1250
         */

        self::HUNGARIAN => [
            193, // Á
            225, // á
            203, // Ë
            235, // ë
            201, // É
            233, // é
            205, // Í
            237, // í
            211, // Ó
            243, // ó
            214, // Ö
            246, // ö
            213, // Ő
            245, // ő
            218, // Ú
            250, // ú
            219, // Ű
            251, // ű
            220, // Ü
            252, // ü
        ],
        self::SERBIAN => [ // Bosnian, Croatian, Serbian, and Montenegrin
            200, // Č
            232, // č
            198, // Ć
            230, // ć
            142, // Ž
            158, // ž
            208, // Đ
            240, // đ
            138, // Š
            154, // š
        ],
        self::POLISH => [
            165, // Ą
            185, // ą
            198, // Ć
            230, // ć
            202, // Ę
            234, // ę
            163, // Ł
            179, // ł
            209, // Ń
            241, // ń
            211, // Ó
            243, // ó
            140, // Ś
            156, // ś
            143, // Ź
            159, // ź
            175, // Ż
            191, // ż
        ],
        self::SLOVENE => [
            200, // Č
            232, // č
            138, // Š
            154, // š
            142, // Ž
            158, // ž
        ],
        self::ROMANIAN => [
            195, // Ă
            227, // ă
            194, // Â
            226, // â
            206, // Î
            238, // î
            170, // Ş
            186, // ş
            222, // Ţ
            254, // ţ
        ],
        self::CZECH => [
            193, // Á
            225, // á
            200, // Č
            232, // č
            207, // Ď
            239, // ď
            201, // É
            233, // é
            204, // Ě
            236, // ě
            205, // Í
            237, // í
            210, // Ň
            242, // ň
            211, // Ó
            243, // ó
            216, // Ř
            248, // ř
            138, // Š
            154, // š
            141, // Ť
            157, // ť
            218, // Ú
            250, // ú
            217, // Ů
            249, // ů
            221, // Ý
            253, // ý
            142, // Ž
            158, // ž
        ],
        self::SLOVAK => [
            193, // Á
            225, // á
            196, // Ä
            228, // ä
            200, // Č
            232, // č
            207, // Ď
            239, // ď
            201, // É
            233, // é
            188, // Ľ
            190, // ľ
            197, // Ĺ
            229, // ĺ
            210, // Ň
            242, // ň
            211, // Ó
            243, // ó
            212, // Ô
            244, // ô
            192, // Ŕ
            224, // ŕ
            138, // Š
            154, // š
            141, // Ť
            157, // ť
            218, // Ú
            250, // ú
            221, // Ý
            253, // ý
            142, // Ž
            158, // ž
        ],
        /*
        self::ALBANIAN => [ // Same as CP1252
            203, // Ë
            235, // ë
            199, // Ç
            231, // ç
        ],
        self::SORBIAN => [
            200, // Č
            232, // č
            198, // Ć
            230, // ć
            204, // Ě
            236, // ě
            211, // Ó
            243, // ó
            216, // Ř
            248, // ř
            192, // Ŕ
            224, // ŕ
            138, // Š
            154, // š
            140, // Ś
            156, // ś
            142, // Ž
            158, // ž
            143, // Ź
            159, // ź
        ],
        self::TURKMEN => [
            199, // Ç
            231, // ç
            196, // Ä
            228, // ä
            142, // Ž
            158, // ž
            210, // Ň
            242, // ň
            214, // Ö
            246, // ö
            170, // Ş
            186, // ş
            220, // Ü
            252, // ü
            221, // Ý
            253, // ý
        ],
        */
    ];

    public static $nonAlpha = [
        Utf8::CP1250 => [
            148,
            149,
            150,
            151,
            152,
            153,
            155,
            160,
            161,
            162,
            164,
            166,
            167,
            168,
            169,
            171,
            172,
            173,
            174,
            176,
            177,
            178,
            180,
            181,
            182,
            183,
            184,
            187,
            189,
            215,
            247,
            255,
        ],
        Utf8::CP1252 => [
            128,
            129,
            130,
            131,
            132,
            133,
            134,
            135,
            136,
            137,
            139,
            141,
            143,
            144,
            145,
            146,
            147,
            148,
            149,
            150,
            151,
            152,
            153,
            155,
            157,
            160,
            162,
            163,
            164,
            165,
            166,
            167,
            168,
            169,
            170,
            171,
            172,
            173,
            174,
            175,
            176,
            177,
            178,
            179,
            180,
            181,
            182,
            183,
            184,
            185,
            186,
            187,
            188,
            189,
            190,
            215,
            247,
        ]
    ];

}
