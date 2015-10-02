# utf8
Yet another UTF-8 library, which is able to distinguish Windows-1250/1251/1252.

# Usage
```
use Vensko\Utf8;

$encoding = Utf8::detectEncoding($anyString);
$encoding = Utf8::detectUTF8($anyString);
$encoding = Utf8::detectBOM($anyString);
$encoding = Utf8::detectUtf16WithoutBOM($anyString);
$encoding = Utf8::detectSingleByteEncoding($singleByteString);

$stringUtf8 = Utf8::convert($anyString);
$stringUtf8 = Utf8::convert($stringUtf16le, 'UTF-16LE');
$stringUtf8 = Utf8::convert($anyString, null, true); // force UTF-8 output
```

# Supported charsets so far

 - ASCII
 - UTF-8
 - UTF-16BE
 - UTF-16LE
 - UTF-32LE with BOM
 - UTF-32BE with BOM
 - UTF-7 with BOM
 - Windows-1250
 - Windows-1251
 - Windows-1252 / ISO-8859-1
