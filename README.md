# Simple file uploader [![Latest Version](https://img.shields.io/github/release/azurre/php-simple-file-uploader.svg?style=flat-square)](https://github.com/azurre/php-simple-file-uploader/releases)
Very small(single class) and comfortable file uploader with validation

# Installation

Install composer in your project:
```
curl -s https://getcomposer.org/installer | php
```

Require the package with composer:
```
composer require azurre/php-simple-file-uploader
```

# Usage

```php
$loader = require_once __DIR__ . '/vendor/autoload.php';

use Azurre\Component\Http\Uploader;

if (isset($_FILES['file'])) {
    $Uploader = new Uploader();

    $Uploader
        ->setStoragePath('./')
        ->setOverwrite(false) // Overwrite existing files?
        ->setNameFormat(Uploader::NAME_FORMAT_ORIGINAL) 
        ->setReplaceCyrillic(false) // Transliterate cyrillic names

        ->addValidator(Uploader::VALIDATOR_MIME, array('image/png', 'image/jpeg'))
        ->addValidator(Uploader::VALIDATOR_EXTENSION, array('png', 'jpg'))
        ->addValidator(Uploader::VALIDATOR_SIZE, '1M' );

    $Uploader->afterUpload(function($file){
        //do something
    });

    $customData = 'key';
    // Custom name formatter. If you use custom formatter setNameFormat() and setReplaceCyrillic() will be ignored.
    $Uploader->setNameFormatter(function($file, $Upl) use($customData){
        /** @var Uploader $Upl */
        $newName = str_replace(' ', '-', $file['name']);
        $newName = $Upl->transliterate($newName);
        $newName .= "_{$customData}_" .  rand(1000,99999) .'.'. $file['extension'];
        return  $newName;
    });


    try {
        $Uploader->upload('file');
        $files = $Uploader->getFiles();
        echo '<pre>'. print_r($files, true) . '</pre>';
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
    [0] => Array
        (
            [name] => login
            [fullName] => login.jpg
            [newName] => login_57f6bd1710245.jpg
            [fullPath] => /home/sites/test.local/uploader/login_57f6bd1710245.jpg
            [extension] => jpg
            [mime] => image/jpeg
            [tmpName] => /tmp/phpO5emvp
            [size] => 1636
            [error] => 0
        )
)

-----------

Array
(
    [0] => Array
        (
            [name] => login1
            [fullName] => login1.jpg
            [newName] => login1_57f6bd1710245.jpg
            [fullPath] => /home/sites/test.local/libs/uploader/login1_57f6bd1710245.jpg
            [extension] => jpg
            [mime] => image/jpeg
            [tmpName] => /tmp/php13emrc
            [size] => 1636
            [error] => 0
        )

    [1] => Array
        (
            [name] => 0340
            [fullName] => 0340.jpg
            [newName] => 0340_57f2b14d74b43.jpg
            [fullPath] => /home/sites/test.local/libs/uploader/0340_57f2b14d74b43.jpg
            [extension] => jpg
            [mime] => image/jpeg
            [tmpName] => /tmp/phpSBTzyC
            [size] => 3109
            [error] => 0
        )
)
```
