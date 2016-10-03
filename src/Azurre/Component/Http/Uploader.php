<?php
/**
 * @date    04.03.2015
 * @version 1.0
 * @author  Aleksandr Milenin admin@azrr.info
 */

namespace Azurre\Component\Http;

class Uploader {

    //! Maximum try count to find random name for uploaded file
    const NAME_TRY_COUNT = 10;


    const
        ERROR_NO_ERROR = 0,
        ERROR_INI_SIZE = 1,
        ERROR_FORM_SIZE = 2,
        ERROR_PARTIAL = 3,
        ERROR_NO_FILE = 4,
        ERROR_NO_TMP_DIR = 6,
        ERROR_CANT_WRITE = 7,
        ERROR_EXTENSION = 8,
        ERROR_FILE_TOO_LARGE = 20,
        ERROR_INVALID_MIMETYPE = 21,
        ERROR_INVALID_EXTENSION = 22,

        VALIDATOR_MIME = 0,
        VALIDATOR_SIZE = 1,
        VALIDATOR_EXTENSION = 2;

    protected $errorMessages = array(
        self::ERROR_NO_ERROR       => 'No errors',
        self::ERROR_INI_SIZE       => 'File size exceeds the upload_max_filesize in php.ini',
        self::ERROR_FORM_SIZE      => 'File size exceeds the html form MAX_FILE_SIZE directive',
        self::ERROR_PARTIAL        => 'The uploaded file was only partially uploaded',
        self::ERROR_NO_FILE        => 'No file was uploaded',
        self::ERROR_NO_TMP_DIR     => 'Missing a temporary folder',
        self::ERROR_CANT_WRITE     => 'Failed to write file to disk',
        self::ERROR_EXTENSION      => 'A PHP extension stopped the file upload',
        self::ERROR_FILE_TOO_LARGE => 'Validator: file too large'
    );


    /**
     * Set random name for each uploaded file
     *
     * @var bool
     */
    protected $randomName = false;

    /**
     * Overwrite existing files?
     *
     * @var bool
     */
    protected $overwrite = false;

    /**
     * Path to storage
     *
     * @var string
     */
    protected $storagePath = './';

    protected
        $beforeValidateCallback,
        $afterValidateCallback,
        $beforeUploadCallback,
        $afterUploadCallback,
        $files = array(),
        $validators = array(),
        $errorCode = self::ERROR_NO_ERROR;


    /**
     * @return int
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }


    /**
     * @param int  $errorCode
     * @param bool $throwException
     *
     * @return $this
     * @throws \Exception
     */
    public function setError($errorCode, $throwException = true)
    {
        $this->errorCode = $errorCode;

        if ($throwException) {
            throw new \Exception($this->getErrorMessage(), $this->errorCode);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function clearErrorCode()
    {
        $this->errorCode = static::ERROR_NO_ERROR;

        return $this;

    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessages[ $this->errorCode ];
    }


    /**
     * @param bool $overwrite
     *
     * @return $this
     */
    public function setOverwrite($overwrite = true)
    {
        $this->overwrite = $overwrite;

        return $this;
    }


    /**
     * @param string $storagePath
     *
     * @return $this
     */
    public function setStoragePath($storagePath)
    {
        $this->storagePath = $storagePath;

        return $this;
    }

    /**
     * @param bool $generateRandomName
     *
     * @return $this
     */
    public function setRandomName($generateRandomName = false)
    {
        $this->randomName = $generateRandomName;

        return $this;
    }


    /**
     * @return array
     */
    public function getFiles()
    {
        return $this->files;
    }


    /**
     * @param int   $validatorType
     * @param mixed $data
     *
     * @return $this
     * @throws \Exception
     */
    public function addValidator($validatorType, $data)
    {
        if (!is_int($validatorType) || $validatorType < 0 || $validatorType > 2) {
            throw new \Exception('Invalid validator type');
        }

        $this->validators[] = array('type' => $validatorType, 'data' => $data);

        return $this;
    }

    /**
     * @param array $file
     *
     * @throws \Exception
     */
    public function applyValidators($file)
    {
        foreach ($this->validators as $validator) {
            switch ($validator['type']) {
                case self::VALIDATOR_MIME:
                    $this->validateMimetype($file['mime'], $validator['data']);
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
     * @param string $mimetype
     * @param array  $allowedMimetypes
     *
     * @throws \Exception
     */
    public function validateMimetype($mimetype, $allowedMimetypes)
    {
        $allowedMimetypes = is_array($allowedMimetypes) ? $allowedMimetypes : array($allowedMimetypes);

        if (!in_array($mimetype, $allowedMimetypes)) {
            $this->setError("Mimetype {$mimetype} not allowed");
        }
    }

    /**
     * @param int|string  $size Support human readable size e.g. "200K", "1M"
     * @param int $maxSize
     *
     * @throws \Exception
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
     * @param array  $allowedExtensions
     *
     * @throws \Exception
     */
    public function validateExtension($extension, $allowedExtensions)
    {
        $extension         = strtolower($extension);
        $allowedExtensions = is_array($allowedExtensions) ? $allowedExtensions : array($allowedExtensions);

        if (!in_array($extension, $allowedExtensions)) {
            throw new \Exception("Extension {$extension} not allowed");
        }
    }

    /**
     * @return string
     */
    public function getDestination()
    {
        return $this->storagePath;
    }

    /**
     * @param string $extension
     *
     * @return string
     * @throws \Exception
     */
    public function getRandomName($extension)
    {
        $tryCount = 0;
        while ($tryCount < static::NAME_TRY_COUNT) {
            $tryCount++;
            $newName = uniqid() . '.' . $extension;
            $path    = $this->getDestination() . $newName;
            if (!file_exists($path)) {
                return $newName;
            }
        }

        throw new \Exception('Cannot find free random name for file');
    }


    /**
     * @param string $key Key of $_FILE array
     *
     * @throws \Exception
     */
    public function upload($key)
    {
        $this->clearErrorCode();

        if (!isset($_FILES[ $key ])) {
            throw new \Exception("Cannot find uploaded file(s) with key: {$key}");
        }

        if (is_array($_FILES[ $key ]['tmp_name'])) {
            foreach ($_FILES[ $key ]['name'] as $idx => $name) {
                $this->files[ $idx ] = array(
                    'name'      => $name,
                    'newName'   => $name,
                    'fullPath'  => '',
                    'extension' => pathinfo($name, PATHINFO_EXTENSION),
                    'mime'      => $_FILES[ $key ]['type'][ $idx ],
                    'tmpName'   => $_FILES[ $key ]['tmp_name'][ $idx ],
                    'size'      => $_FILES[ $key ]['size'][ $idx ],
                    'error'     => $_FILES[ $key ]['error'][ $idx ]
                );
            }
        } else {
            $this->files[0] = array(
                'name'      => $_FILES[ $key ]['name'],
                'newName'   => $_FILES[ $key ]['name'],
                'fullPath'  => '',
                'extension' => pathinfo($_FILES[ $key ]['name'], PATHINFO_EXTENSION),
                'mime'      => $_FILES[ $key ]['type'],
                'tmpName'   => $_FILES[ $key ]['tmp_name'],
                'size'      => $_FILES[ $key ]['size'],
                'error'     => $_FILES[ $key ]['error']
            );
        }

        foreach ($this->files as $key => &$file) {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $this->errorCode = $file['error'];
                continue;
            }

            $this->applyCallback($this->beforeValidateCallback, $file);
            $this->applyValidators($file);
            $this->applyCallback($this->afterValidateCallback, $file);

            if ($this->randomName) {
                $file['newName'] = $this->getRandomName($file['extension']);
            }

            $destinationFile = $this->getDestination() . $file['newName'];

            if (!$this->overwrite && file_exists($destinationFile)) {
                throw new \Exception("File {$file['name']} already exists");
            }

            $this->applyCallback($this->beforeUploadCallback, $file);
            if (move_uploaded_file($file['tmpName'], $destinationFile) === false) {
                throw new \Exception("Cannot move file {$file['name']} to destination folder");
            }
            $file['fullPath'] = $destinationFile;
            $this->applyCallback($this->afterUploadCallback, $file);
        }
    }


    /**
     * @param callable $callback
     *
     * @return $this
     */
    public function beforeValidate($callback)
    {
        $this->beforeValidateCallback = $callback;

        return $this;
    }

    /**
     * @param callable $callback
     *
     * @return $this
     */
    public function afterValidate($callback)
    {
        $this->afterValidateCallback = $callback;

        return $this;
    }

    /**
     * @param callable $callback
     *
     * @return $this
     */
    public function beforeUpload($callback)
    {
        $this->beforeUploadCallback = $callback;

        return $this;
    }

    /**
     * @param callable $callback
     *
     * @return $this
     */
    public function afterUpload($callback)
    {
        $this->afterUploadCallback = $callback;

        return $this;
    }

    /**
     * Apply callable
     *
     * @param  callable $callback
     * @param  array    $file
     */
    protected function applyCallback($callback, $file)
    {
        if (is_callable($callback)) {
            call_user_func_array($callback, array($file, $this));
        }
    }

    /**
     * Convert human readable size into bytes
     *
     * @param  string $input
     *
     * @return int
     */
    public static function humanReadableToBytes($input)
    {
        if (is_numeric($input)) {
            return (int)$input;
        }

        $number = (int)$input;
        $units  = array(
            'b' => 1,
            'k' => 1024,
            'm' => 1048576,
            'g' => 1073741824
        );
        $unit = strtolower(substr($input, -1));
        if (isset($units[ $unit ])) {
            $number = $number * $units[ $unit ];
        }

        return $number;
    }

}
