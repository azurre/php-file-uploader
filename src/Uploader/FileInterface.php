<?php
/**
 * @author Alex Milenin
 * @email  admin@azrr.info
 * @copyright Copyright (c)Alex Milenin (https://azrr.info/)
 */

namespace Azurre\Component\Http\Uploader;

/**
 * Uploaded file interface
 */
interface FileInterface
{
    const NAME = 'name';
    const FULL_NAME = 'full_name';
    const NEW_NAME = 'new_name';
    const FULL_PATH = 'full_path';
    const EXTENSION = 'extension';
    const MIME_TYPE = 'mime_type';
    const TMP_NAME = 'tmp_name';
    const SIZE = 'size';
    const ERROR_CODE = 'error_code';
    const STATUS = 'status';

    const ERROR_NO_ERROR = \Azurre\Component\Http\Uploader::ERROR_NO_ERROR;

    const STATUS_NEW = 0;
    const STATUS_IN_PROGRESS = 1;
    const STATUS_COMPLETE = 2;

    /**
     * @return string|null
     */
    public function getName();

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name);

    /**
     * @return string|null
     */
    public function getFullName();

    /**
     * @param string $fullName
     * @return $this
     */
    public function setFullName($fullName);

    /**
     * @return string|null
     */
    public function getNewName();

    /**
     * @param string $newName
     * @return $this
     */
    public function setNewName($newName);

    /**
     * @return string|null
     */
    public function getFullPath();

    /**
     * @param string $fullPath
     * @return $this
     */
    public function setFullPath($fullPath);

    /**
     * @return string|null
     */
    public function getExtension();

    /**
     * @param string $extension
     * @return $this
     */
    public function setExtension($extension);

    /**
     * @return string|null
     */
    public function getMimeType();

    /**
     * @param string $mimeType
     * @return $this
     */
    public function setMimeType($mimeType);

    /**
     * @return string|null
     */
    public function getTmpName();

    /**
     * @param string $tmpName
     * @return $this
     */
    public function setTmpName($tmpName);

    /**
     * @return int|null
     */
    public function getSize();

    /**
     * @param int $size
     * @return $this
     */
    public function setSize($size);

    /**
     * @param int $errorCode
     * @return $this
     */
    public function setErrorCode($errorCode);

    /**
     * @return int
     */
    public function getErrorCode();

    /**
     * @return bool
     */
    public function isFileUploaded();

    /**
     * @return int
     */
    public function getStatus();

    /**
     * @param int $statusCode
     * @return $this
     */
    public function setStatus($statusCode);

    /**
     * @return array
     */
    public function toArray();
}
