<?php
/**
 * @date    29.03.2019
 * @version 1.5
 * @author  Aleksandr Milenin admin@azrr.info
 */

namespace Azurre\Component\Http;

/**
 * Class Uploader
 */
class Uploader
{
    /**
     * @var int Maximum try count to find available name for uploaded file
     */
    const NAME_TRY_COUNT = 10;

    /**#@+
     * Error codes
     */
    const
        ERROR_NO_ERROR = UPLOAD_ERR_OK,
        ERROR_INI_SIZE = UPLOAD_ERR_INI_SIZE,
        ERROR_FORM_SIZE = UPLOAD_ERR_FORM_SIZE,
        ERROR_PARTIAL = UPLOAD_ERR_PARTIAL,
        ERROR_NO_FILE = UPLOAD_ERR_NO_FILE,
        ERROR_NO_TMP_DIR = UPLOAD_ERR_NO_TMP_DIR,
        ERROR_CANNOT_WRITE = UPLOAD_ERR_CANT_WRITE,
        ERROR_EXTENSION = UPLOAD_ERR_EXTENSION,
        ERROR_FILE_TOO_LARGE = 50,
        ERROR_INVALID_MIMETYPE = 51,
        ERROR_INVALID_EXTENSION = 52,
        ERROR_INVALID_FORMATTER = 53,
        ERROR_FILENAME_EXISTS = 54,
        ERROR_NO_AVAILABLE_NAME = 55,
        ERROR_CANNOT_GET_FILE = 56,
        ERROR_CANNOT_MOVE_FILE = 57;
    /**#@-*/

    /**#@+
     * Validator codes
     */
    const
        VALIDATOR_MIME = 0,
        VALIDATOR_SIZE = 1,
        VALIDATOR_EXTENSION = 2;
    /**#@-*/

    /**#@+
     * Name format codes
     */
    const
        NAME_FORMAT_ORIGINAL = 0,
        NAME_FORMAT_RANDOM = 1,
        NAME_FORMAT_COMBINED = 2;
    /**#@-*/

    /**
     * @var array Error messages
     */
    protected static $errorMessages = [
        self::ERROR_NO_ERROR => 'No errors',
        self::ERROR_INI_SIZE => 'File size exceeds the upload_max_filesize in php.ini',
        self::ERROR_FORM_SIZE => 'File size exceeds the html form MAX_FILE_SIZE directive',
        self::ERROR_PARTIAL => 'The uploaded file was only partially uploaded',
        self::ERROR_NO_FILE => 'No file was uploaded',
        self::ERROR_NO_TMP_DIR => 'Missing a temporary folder',
        self::ERROR_CANNOT_WRITE => 'Failed to write file to disk',
        self::ERROR_EXTENSION => 'A PHP extension stopped the file upload',
        self::ERROR_INVALID_EXTENSION => 'Validator: restricted extension',
        self::ERROR_INVALID_MIMETYPE => 'Validator: restricted mime-type',
        self::ERROR_FILE_TOO_LARGE => 'Validator: file too large',
        self::ERROR_INVALID_FORMATTER => 'Formatter function must return filename',
        self::ERROR_NO_AVAILABLE_NAME => 'Cannot find available name for uploaded file',
        self::ERROR_CANNOT_GET_FILE => 'Cannot get file content from URL',
        self::ERROR_CANNOT_MOVE_FILE => 'Cannot move uploaded file'
    ];

    /** @var int Type of filename generation */
    protected $nameFormat = self::NAME_FORMAT_COMBINED;

    /** @var bool Overwrite existing files? */
    protected $overwrite = false;

    /** @var bool Transliterate cyrillic names */
    protected $replaceCyrillic = true;

    /** @var string Path to storage */
    protected $storagePath = './';

    /** @var \Closure|null */
    protected $beforeValidateCallback;

    /** @var \Closure|null */
    protected $afterValidateCallback;

    /** @var \Closure|null */
    protected $beforeUploadCallback;

    /** @var \Closure|null */
    protected $afterUploadCallback;

    /** @var \Closure|null */
    protected $nameFormatter;

    /** @var bool */
    protected $isUrlUpload = false;

    /** @var int|null Make directories tree in destination */
    protected $splitTreeSize;

    /** @var \Azurre\Component\Http\Uploader\FileInterface[] */
    protected $files = [];

    /** @var array */
    protected $validators = [];

    /** @var int */
    protected $errorCode = self::ERROR_NO_ERROR;

    /** @var int File permissions */
    protected $chmod = 0644;

    /**
     * Uploader constructor.
     */
    public function __construct()
    {
        // fix http://stackoverflow.com/questions/4451664/make-php-pathinfo-return-the-correct-filename-if-the-filename-is-utf-8
        setlocale(LC_ALL, 'en_US.UTF-8');
    }

    /**
     * Transliterate cyrillic names. Strongly recommended!
     *
     * @param bool $replace
     * @return $this
     */
    public function setReplaceCyrillic($replace = true)
    {
        $this->replaceCyrillic = (bool)$replace;
        return $this;
    }

    /**
     * @return int
     */
    public function getNameFormat()
    {
        return $this->nameFormat;
    }

    /**
     * @param int $nameFormat
     * @return $this
     */
    public function setNameFormat($nameFormat)
    {
        $this->nameFormat = (int)$nameFormat;
        return $this;
    }

    /**
     * @return int
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * @param int $errorCode
     * @param bool $throwException
     * @return $this
     * @throws UploadException
     */
    public function setError($errorCode, $throwException = true)
    {
        $this->errorCode = (int)$errorCode;
        if ($throwException) {
            throw new UploadException($this->getErrorMessage(), $this->errorCode);
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        return static::$errorMessages[$this->errorCode];
    }

    /**
     * @param bool $overwrite
     * @return $this
     */
    public function setOverwrite($overwrite = true)
    {
        $this->overwrite = (bool)$overwrite;
        return $this;
    }

    /**
     * @return string
     */
    public function getDestination()
    {
        return $this->storagePath;
    }

    /**
     * @param string $path
     * @return $this
     */
    public function setDestination($path)
    {
        $path = rtrim($path, DIRECTORY_SEPARATOR);
        $this->storagePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path) . DIRECTORY_SEPARATOR;
        return $this;
    }

    /**
     * @return \Azurre\Component\Http\Uploader\FileInterface[]
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * @return \Azurre\Component\Http\Uploader\FileInterface|array
     */
    public function getFirstFile()
    {
        return reset($this->files);
    }

    /**
     * @param int $validatorType
     * @param mixed $data
     * @return $this
     * @throws UploadException
     */
    public function addValidator($validatorType, $data)
    {
        if (!\is_int($validatorType) || $validatorType < 0 || $validatorType > 2) {
            throw new UploadException('Invalid validator type');
        }
        $this->validators[] = ['type' => $validatorType, 'data' => $data];
        return $this;
    }

    /**
     * @param \Azurre\Component\Http\Uploader\FileInterface $file
     * @throws UploadException
     */
    public function applyValidators(\Azurre\Component\Http\Uploader\FileInterface $file)
    {
        foreach ($this->validators as $validator) {
            switch ($validator['type']) {
                case self::VALIDATOR_MIME:
                    $this->validateMimeType($file['mime'], $validator['data']);
                break;

                case self::VALIDATOR_SIZE:
                    $this->validateSize($file['size'], $validator['data']);
                break;

                case self::VALIDATOR_EXTENSION:
                    $this->validateExtension($file['extension'], $validator['data']);
                break;
            }
        }
    }

    /**
     * @param string $mimeType
     * @param array $allowedMimeTypes
     * @return void
     * @throws UploadException
     */
    public function validateMimeType($mimeType, $allowedMimeTypes)
    {
        $allowedMimeTypes = \is_array($allowedMimeTypes) ? $allowedMimeTypes : [$allowedMimeTypes];
        if (!\in_array($mimeType, $allowedMimeTypes, true)) {
            $this->setError(static::ERROR_INVALID_MIMETYPE);
        }
    }

    /**
     * @param int|string $size Support human readable size e.g. "200K", "1M"
     * @param int $maxSize
     * @throws UploadException
     */
    public function validateSize($size, $maxSize)
    {
        $maxSize = static::humanReadableToBytes($maxSize);
        if ($size > $maxSize) {
            $this->setError(static::ERROR_FILE_TOO_LARGE);
        }
    }

    /**
     * @param string $extension
     * @param array $allowedExtensions
     * @return void
     * @throws UploadException
     */
    public function validateExtension($extension, $allowedExtensions)
    {
        $extension = strtolower($extension);
        $allowedExtensions = \is_array($allowedExtensions) ? $allowedExtensions : [$allowedExtensions];
        if (!\in_array($extension, $allowedExtensions, true)) {
            throw new UploadException("Extension {$extension} not allowed");
        }
    }

    /**
     * @param \Azurre\Component\Http\Uploader\FileInterface $file
     * @return string
     * @throws UploadException
     */
    protected function getName(\Azurre\Component\Http\Uploader\FileInterface $file)
    {
        if (!empty($this->nameFormatter)) {
            $newName = $this->applyCallback($this->nameFormatter, $file);
            if (empty($newName) || !\is_string($newName)) {
                $this->setError(static::ERROR_INVALID_FORMATTER);
            }
            return $newName;
        }

        if ($this->getNameFormat() === static::NAME_FORMAT_ORIGINAL) {
            return $file->getFullName();
        }

        $tryCount = 0;
        $prefix = '';
        while ($tryCount < static::NAME_TRY_COUNT) {
            $tryCount++;
            if ($this->getNameFormat() === static::NAME_FORMAT_COMBINED) {
                $prefix = ($this->replaceCyrillic ? static::transliterate($file->getName()) : $file->getName()) . '_';
            }
            $newName = uniqid($prefix, true) . '.' . $file->getExtension();
            $path = $this->getDestination() . $newName;
            if (!file_exists($path)) {
                return $newName;
            }
        }
        $this->setError(static::ERROR_NO_AVAILABLE_NAME);
        return 'error'; // IDE fix :(
    }


    /**
     * @param string $key Key of $_FILE array
     * @throws UploadException
     */
    public function upload($key)
    {
        $this->isUrlUpload = false;
        $this->reset();
        if (!isset($_FILES[$key])) {
            throw new UploadException("Cannot find uploaded file(s) with key: {$key}");
        }
        if (\is_array($_FILES[$key]['tmp_name'])) {
            foreach ($_FILES[$key]['name'] as $idx => $name) {
                $this->files[$idx] = \Azurre\Component\Http\Uploader\File::create([
                    'name' => pathinfo($name, PATHINFO_FILENAME),
                    'full_name' => $name,
                    'new_name' => $name,
                    'full_path' => '',
                    'extension' => pathinfo($name, PATHINFO_EXTENSION),
                    'mime_type' => $_FILES[$key]['type'][$idx],
                    'tmp_name' => $_FILES[$key]['tmp_name'][$idx],
                    'size' => $_FILES[$key]['size'][$idx],
                    'error_code' => $_FILES[$key]['error'][$idx]
                ]);
            }
        } else {
            $this->files[0] =  \Azurre\Component\Http\Uploader\File::create([
                'name' => pathinfo($_FILES[$key]['name'], PATHINFO_FILENAME),
                'full_name' => $_FILES[$key]['name'],
                'new_name' => $_FILES[$key]['name'],
                'full_path' => '',
                'extension' => pathinfo($_FILES[$key]['name'], PATHINFO_EXTENSION),
                'mime_type' => $_FILES[$key]['type'],
                'tmp_name' => $_FILES[$key]['tmp_name'],
                'size' => $_FILES[$key]['size'],
                'error_code' => $_FILES[$key]['error']
            ]);
        }
        $this->process();
    }

    /**
     * @return void
     * @throws UploadException
     */
    protected function process()
    {
        foreach ($this->files as $file) {
            if ($file->getErrorCode() !== self::ERROR_NO_ERROR) {
                $this->errorCode = $file->getErrorCode();
                continue;
            }
            $this->applyCallback($this->beforeValidateCallback, $file);
            $this->applyValidators($file);
            $this->applyCallback($this->afterValidateCallback, $file);
            if ($file->getErrorCode() !== self::ERROR_NO_ERROR) {
                continue;
            }
            $file->setNewName($this->getName($file));
            $destination = $this->getDestination();
            if ($this->getSplitTreeSize() > 0) {
                $destination = "{$destination}{$this->getSplitTreePath($file->getNewName())}" . DIRECTORY_SEPARATOR;
            }
            $destinationFile = $destination . $file->getNewName();
            if (!$this->overwrite && file_exists($destinationFile)) {
                $file->setErrorCode(static::ERROR_FILENAME_EXISTS);
                $this->setError(static::ERROR_FILENAME_EXISTS);
            }
            $this->applyCallback($this->beforeUploadCallback, $file);
            $this->createDestination($destination);
            if ($this->isUrlUpload) {
                if (rename($file->getTmpName(), $destinationFile) === false) {
                    $file->setErrorCode(static::ERROR_CANNOT_MOVE_FILE);
                    $this->setError(static::ERROR_CANNOT_MOVE_FILE);
                }
                chmod($destinationFile, $this->chmod);
            } else {
                if (move_uploaded_file($file->getTmpName(), $destinationFile) === false) {
                    $file->setErrorCode(static::ERROR_CANNOT_MOVE_FILE);
                    $this->setError(static::ERROR_CANNOT_MOVE_FILE);
                }
            }
            $file->setFullPath(realpath($destinationFile));
            $this->applyCallback($this->afterUploadCallback, $file);
        }
    }

    /**
     * @param string $path
     * @return $this
     * @throws UploadException
     */
    protected function createDestination($path)
    {
        if (!is_dir($path)) {
            if (!mkdir($path, 0777, true) && !is_dir($path)) {
                throw new UploadException("Can't create directory \"{$path}\"");
            }
        }
        return $this;
    }

    /**
     * @param string $url File url
     * @return void
     * @throws \Exception
     */
    public function uploadByUrl($url)
    {
        $this->isUrlUpload = true;
        $this->reset();
        $urlInfo = pathinfo($url);
        $basename = empty($urlInfo['basename']) ? 'noname' : $urlInfo['basename'];
        $basename = preg_replace('/[^\w\.\-]/', '', $basename);
        $tempFile = tempnam(sys_get_temp_dir(), 'upload');
        $file = \Azurre\Component\Http\Uploader\File::create([
            'name' => isset($urlInfo['filename']) ? $urlInfo['filename'] : 'noname',
            'full_name' => $basename,
            'new_name' => isset($urlInfo['basename']) ? $urlInfo['basename'] : 'noname',
            'full_path' => '',
            'extension' => isset($urlInfo['extension']) ? $urlInfo['extension'] : '',
            'mime_type' => 'application/octet-stream',
            'tmp_name' => $tempFile,
            'size' => 0,
            'error_code' => $tempFile ? static::ERROR_NO_ERROR : static::ERROR_NO_TMP_DIR
        ]);
        //@todo maxFileSize
        if (!$content = file_get_contents($url)) {
            $file->setErrorCode(static::ERROR_CANNOT_GET_FILE);
            return;
        }
        $file->setSize(\strlen($content));
        if (!file_put_contents($tempFile, $content)) {
            $file->setErrorCode(static::ERROR_CANNOT_WRITE);
            return;
        }
        if (\function_exists('\mime_content_type')) {
            $file->setMimeType(\mime_content_type($tempFile));
        }
        $this->process();
    }

    /**
     * @param callable $callback
     * @return $this
     */
    public function beforeValidate($callback)
    {
        $this->beforeValidateCallback = $callback;
        return $this;
    }

    /**
     * @param callable $callback
     * @return $this
     */
    public function afterValidate($callback)
    {
        $this->afterValidateCallback = $callback;
        return $this;
    }

    /**
     * @param callable $callback
     * @return $this
     */
    public function beforeUpload($callback)
    {
        $this->beforeUploadCallback = $callback;

        return $this;
    }

    /**
     * @param callable $callback
     * @return $this
     */
    public function afterUpload($callback)
    {
        $this->afterUploadCallback = $callback;
        return $this;
    }

    /**
     * @return \Closure|null
     */
    public function getNameFormatter()
    {
        return $this->nameFormatter;
    }

    /**
     * Set new filename formatter function
     *
     * @param callable $nameFormatter
     * @return $this
     */
    public function setNameFormatter($nameFormatter)
    {
        $this->nameFormatter = $nameFormatter;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getSplitTreeSize()
    {
        return $this->splitTreeSize;
    }

    /**
     * @param int|null $treeSize
     * @return $this
     */
    public function setSplitTreeSize($treeSize)
    {
        $this->splitTreeSize = $treeSize;
        return $this;
    }

    /**
     * Apply callable
     *
     * @param  callable $callback
     * @param  \Azurre\Component\Http\Uploader\FileInterface $file
     * @return mixed
     */
    protected function applyCallback($callback, $file)
    {
        if (\is_callable($callback)) {
            return $callback($file, $this);
        }
        return false;
    }

    /**
     * @param string $fileName
     * @param bool $glue
     * @return string
     */
    public function getSplitTreePath($fileName, $glue = true)
    {
        $hash = md5($fileName);
        $tree = \array_slice(str_split($hash, 3), 0, $this->getSplitTreeSize());
        return $glue ? implode(DIRECTORY_SEPARATOR, $tree) : $tree;
    }

    public function reset()
    {
        $this->errorCode = static::ERROR_NO_ERROR;
        $this->files = [];
        return $this;
    }

    /**
     * Convert human readable size into bytes
     *
     * @param  string $input
     * @return int
     */
    public static function humanReadableToBytes($input)
    {
        if (is_numeric($input)) {
            return (int)$input;
        }
        $number = (int)$input;
        $units = [
            'b' => 1,
            'k' => 1024,
            'm' => 1048576,
            'g' => 1073741824
        ];
        $unit = strtolower(substr($input, -1));
        if (isset($units[$unit])) {
            $number *= $units[$unit];
        }

        return $number;
    }

    /**
     * @param string $string
     * @return string
     */
    public static function transliterate($string)
    {
        $roman    = ['Sch', 'sch', 'Yo', 'Zh', 'Kh', 'Ts', 'Ch', 'Sh', 'Yu', 'ya', 'yo', 'zh', 'kh', 'ts', 'ch', 'sh', 'yu', 'ya', 'A', 'B', 'V', 'G', 'D', 'E', 'Z', 'I', 'Y', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', '', 'Y', '', 'E', 'a', 'b', 'v', 'g', 'd', 'e', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', '', 'y', '', 'e'];
        $cyrillic = ['Щ', 'щ', 'Ё', 'Ж', 'Х', 'Ц', 'Ч', 'Ш', 'Ю', 'я', 'ё', 'ж', 'х', 'ц', 'ч', 'ш', 'ю', 'я', 'А', 'Б', 'В', 'Г', 'Д', 'Е', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Ь', 'Ы', 'Ъ', 'Э', 'а', 'б', 'в', 'г', 'д', 'е', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'ь', 'ы', 'ъ', 'э'];
        return str_replace($cyrillic, $roman, $string);
    }
}
