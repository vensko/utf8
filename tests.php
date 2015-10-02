<?php

use Vensko\Utf8;

require_once __DIR__.'/Utf8.php';
require_once __DIR__.'/Alphabet.php';

foreach (glob(__DIR__.'/tests/*') as $dir) {
    $dirName = basename($dir);

    echo "### ".basename($dir)."\n\n";

    foreach (glob($dir.'/*.*') as $file) {
        $text = file_get_contents($file);
        echo Utf8::detectEncoding($text) ?: '[NOT DETECTED]';
        echo " <- ".basename($file);
        echo "\n";
    }

    echo "\n\n";
}
