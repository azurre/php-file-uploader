<?php
/**
 * @author Alex Milenin
 * @email  admin@azrr.info
 * @date   09.04.2019
 */

namespace Azurre\Component\Http\Uploader;

/**
 * Class File
 */
class File implements FileInterface
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * File constructor.
     *
     * @param $fileData
     */
    public function __construct(array $fileData = null)
    {
        $this->data = $fileData;
    }

    /**
     * @param array $fileData
     * @return $this
     */
    public static function create(array $fileData = null)
    {
        return new static($fileData);
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return $this->get(static::NAME);
    }

    /**
     * @inheritdoc
     */
    public function setName($name)
    {
        $this->data[static::NAME] = (string)$name;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getFullName()
    {
        return $this->get(static::FULL_NAME);
    }

    /**
     * @inheritdoc
     */
    public function setFullName($fullName)
    {
        $this->data[static::FULL_NAME] = (string)$fullName;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getNewName()
    {
        return $this->get(static::NEW_NAME);
    }

    /**
     * @inheritdoc
     */
    public function setNewName($newName)
    {
        $this->data[static::NEW_NAME] = (string)$newName;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getFullPath()
    {
        return $this->get(static::FULL_PATH);
    }

    /**
     * @inheritdoc
     */
    public function setFullPath($fullPath)
    {
        $this->data[static::FULL_PATH] = (string)$fullPath;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getExtension()
    {
        return $this->get(static::EXTENSION);
    }

    /**
     * @inheritdoc
     */
    public function setExtension($extension)
    {
        $this->data[static::EXTENSION] = (string)$extension;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getMimeType()
    {
        return $this->get(static::MIME_TYPE);
    }

    /**
     * @inheritdoc
     */
    public function setMimeType($mimeType)
    {
        $this->data[static::MIME_TYPE] = (string)$mimeType;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getTmpName()
    {
        return $this->get(static::TMP_NAME);
    }

    /**
     * @inheritdoc
     */
    public function setTmpName($tmpName)
    {
        $this->data[static::TMP_NAME] = (string)$tmpName;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getSize()
    {
        return (int)$this->get(static::SIZE);
    }

    /**
     * @inheritdoc
     */
    public function setSize($size)
    {
        $this->data[static::SIZE] = (int)$size;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getErrorCode()
    {
        return (int)$this->get(static::ERROR_CODE);
    }

    /**
     * @inheritdoc
     */
    public function setErrorCode($errorCode)
    {
        $this->data[static::ERROR_CODE] = (int)$errorCode;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isFileUploaded()
    {
        return $this->getStatus() === static::STATUS_COMPLETE && $this->getErrorCode() === static::ERROR_NO_ERROR;
    }

    /**
     * @inheritdoc
     */
    public function getStatus()
    {
        return (int)$this->get(static::STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setStatus($status)
    {
        $this->data[static::STATUS] = (int)$status;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function get($key, $default = null)
    {
        return $this->has($key) ? $this->data[$key] : $default;
    }

    /**
     * @param string $key
     * @return bool
     */
    protected function has($key)
    {
        return isset($this->data[$key]);
    }
}
