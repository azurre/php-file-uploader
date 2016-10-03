# Simple file uploader
Very small(single class) and comfortable file uploader

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
<?php

$loader = require_once __DIR__ . '/vendor/autoload.php';

use Azurre\Component\Http\Uploader;

if (isset($_FILES['file'])) {
    $Uploader = new Uploader();

    $Uploader
        ->setOverwrite(false)
        ->setRandomName(true)
        ->setStoragePath('./')

        ->addValidator(Uploader::VALIDATOR_MIME, array('image/png', 'image/jpeg'))
        ->addValidator(Uploader::VALIDATOR_EXTENSION, array('png', 'jpg'))
        ->addValidator(Uploader::VALIDATOR_SIZE, '1M' );
   
    $Uploader->afterUpload(function($file){
        //do something
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
            [name] => var1.jpg
            [newName] => 57f2b0a64425a.jpg
            [fullPath] => /home/sites/test.local/files/57f2b0a64425a.jpg
            [extension] => jpg
            [mime] => image/jpeg
            [tmpName] => /tmp/phpUyMgdy
            [size] => 631951
            [error] => 0
        )
)

-----------

Array
(
    [0] => Array
        (
            [name] => uv.jpg
            [newName] => 57f2b14d748c4.jpg
            [fullPath] => /home/sites/test.local/files/57f2b14d748c4.jpg
            [extension] => jpg
            [mime] => image/jpeg
            [tmpName] => /tmp/phpSsbep5
            [size] => 35985
            [error] => 0
        )

    [1] => Array
        (
            [name] => 0340.jpg
            [newName] => 57f2b14d74b43.jpg
            [fullPath] => /home/sites/test.local/files/57f2b14d74b43.jpg
            [extension] => jpg
            [mime] => image/jpeg
            [tmpName] => /tmp/phpSBTzyC
            [size] => 3109
            [error] => 0
        )
)
```
