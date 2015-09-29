# utf8
Yet another UTF-8 library. Initially written to detect encoding of media tags and convert them to UTF-8, it's optimized to work with small chunks of text. Original idea was to collect detection results and choose encoding, which was detected in most cases. For large texts, I'd recommend [chardet](https://github.com/chardet/chardet).

# Usage
```
use Vensko\Utf8;

$encoding = Utf8::detectEncoding($anyString);
$encoding = Utf8::detectUTF8($anyString);
$encoding = Utf8::detectBOM($anyString);
$encoding = Utf8::detectUtf16WithoutBOM($anyString);
$encoding = Utf8::detectSingleByteEncoding($singleByteString);

$stringUtf8 = Utf8::convert($anyString);
$stringUtf8 = Utf8::convert($stringUtf16, 'UTF-16LE');
$stringUtf8 = Utf8::convert($stringUtf16, null, true); // force UTF-8 output
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
 - ISO-8859-1
