# Simple file uploader [![Latest Version](https://img.shields.io/github/release/azurre/php-simple-file-uploader.svg?style=flat-square)](https://github.com/azurre/php-simple-file-uploader/releases)
Small, comfortable and powerful file uploader

## Features
* No dependencies
* Easy to use
* Easy to validate
* Easy to extend/customize
* Upload by URL
* Unified upload result
* Cyrillic transliteration 

## Installation

Install composer in your project:
```
curl -s https://getcomposer.org/installer | php
```

Require the package with composer:
```
composer require azurre/php-simple-file-uploader
```

## Usage

### Simple example

```php
$loader = require_once __DIR__ . '/vendor/autoload.php';

use Azurre\Component\Http\Uploader;

if (isset($_FILES['file'])) {
    try {
        $uploader = Uploader::create()->upload('file');
    } catch (\Exception $e) {
        exit("Error: {$e->getMessage()}");
    }
    echo $uploader->getFirstFile()->getFullPath();
}

?>
<form method="POST" enctype="multipart/form-data">
    <input type="file" name="file" />
    <input type="submit" value="Upload File" />
</form>
```

Output
```
/var/www/Test_5cd31dbb246530.38121881.xlsx
```

### Example

```php
if (isset($_FILES['file'])) {
    try {
        $uploader = Uploader::create()
            ->setDestination('./')
            ->setOverwrite(false)// Overwrite existing files?
            ->setNameFormat(Uploader::NAME_FORMAT_ORIGINAL)
            ->setReplaceCyrillic(false)// Transliterate cyrillic names
            ->addValidator(Uploader::VALIDATOR_MIME, ['image/png', 'image/jpeg'])
            ->addValidator(Uploader::VALIDATOR_EXTENSION, ['png', 'jpg'])
            ->addValidator(Uploader::VALIDATOR_SIZE, '1M');

        // After upload callback
        $uploader->afterUpload(function ($file) {
            //do something
        });

        $customData = 'KEY';
        // Custom name formatter. If you will use custom formatter setNameFormat() setReplaceCyrillic() will be ignored.
        $uploader->setNameFormatter(function ($file, $upl) use ($customData) {
            /** @var Uploader\File $file */
            /** @var Uploader $upl */
            $newName = str_replace(' ', '-', $file->getName());
            $newName = Uploader::transliterate($newName);
            $newName .= uniqid("_{$customData}_", true) . ".{$file->getExtension()}";
            return $newName;
        });

        $uploader->upload('file');
        echo '<pre>' . print_r($uploader->getFiles(), true) . '</pre>';
    } catch (\Exception $e) {
        echo 'Error:' . $e->getMessage();
    }
}

?>
<form method="POST" enctype="multipart/form-data">
    <input type="file" name="file" value="" />
    <input type="submit" value="Upload File" />
</form>
```

Output
```
Array
(
    [0] => Azurre\Component\Http\Uploader\File Object
        (
            [data:protected] => Array
                (
                    [name] => Новая Картинка
                    [full_name] => Новая Картинка.jpg
                    [new_name] => Novaya-Kartinka_KEY_5cd32798d81c15.93269375.jpg
                    [full_path] => /var/www/pricer.local/public/Novaya-Kartinka_KEY_5cd32798d81c15.93269375.jpg
                    [extension] => jpg
                    [mime_type] => image/jpeg
                    [tmp_name] => /tmp/phpd2DBTm
                    [size] => 280012
                    [error_code] => 0
                )
        )
    [1] => Azurre\Component\Http\Uploader\File Object
        (
            [data:protected] => Array
                (
                    [name] => web-server-certificate
                    [full_name] => web-server-certificate.png
                    [new_name] => web-server-certificate_KEY_5cd32798d82f45.89296123.png
                    [full_path] => /var/www/pricer.local/public/web-server-certificate_KEY_5cd32798d82f45.89296123.png
                    [extension] => png
                    [mime_type] => image/png
                    [tmp_name] => /tmp/php93mYNo
                    [size] => 70652
                    [error_code] => 0
                )
        )
)
```

### Example upload by URL
```php
$url = 'https://img.shields.io/github/release/azurre/php-simple-file-uploader.svg?style=flat-square';
try {
    $uploader = Uploader::create()->uploadByUrl($url);
    echo '<pre>' . print_r($uploader->getFirstFile(), true) . '</pre>';
} catch (\Exception $e) {
    echo 'Error:' . $e->getMessage();
}
```
Output
```
Azurre\Component\Http\Uploader\File Object
(
    [data:protected] => Array
        (
            [name] => php-simple-file-uploader
            [full_name] => php-simple-file-uploader.svg
            [new_name] => php-simple-file-uploader_5cd32cc8d0b301.55846637.svg
            [full_path] => /var/www/pricer.local/public/php-simple-file-uploader_5cd32cc8d0b301.55846637.svg
            [extension] => svg
            [mime_type] => image/svg
            [tmp_name] => /tmp/upload9wl2BK
            [size] => 952
            [error_code] => 0
        )

)
```

## License
[MIT](https://choosealicense.com/licenses/mit/)