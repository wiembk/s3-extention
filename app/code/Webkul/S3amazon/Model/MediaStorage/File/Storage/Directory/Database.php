<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_S3amazon
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
namespace Webkul\S3amazon\Model\MediaStorage\File\Storage\Directory;

use Webkul\S3amazon\Helper\Data;
use Webkul\S3amazon\Model\MediaStorage\File\Storage\S3storage;

/**
 * Plugin for Directoty Database.
 */
class Database
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var S3Storage
     */
    private $s3StorageModel;

    /**
     * @param Data $helper
     * @param S3storage $s3Storage
     */
    public function __construct(
        Data $helper,
        S3storage $s3Storage
    ) {
        $this->helper = $helper;
        $this->s3StorageModel = $s3Storage;
    }

    /**
     * @param Magento\MediaStorage\Model\File\Storage\Directory\Database $subject
     * @param \Closure $proceed
     * @param string $path
     * @return mixed
     */
    public function aroundCreateRecursive($subject, $proceed, $path)
    {
        if ($this->helper->checkMediaStorageIsS3()) {
            return $this;
        }

        return $proceed($path);
    }

    /**
     * @param Magento\MediaStorage\Model\File\Storage\Directory\Database $subject
     * @param \Closure $proceed
     * @param string $directory
     * @return array
     */
    public function aroundGetSubdirectories($subject, $proceed, $directory)
    {
        if ($this->helper->checkMediaStorageIsS3()) {
            return $this->s3StorageModel->getSubdirectories($directory);
        }

        return $proceed($directory);
    }

    /**
     * @param Magento\MediaStorage\Model\File\Storage\Directory\Database $subject
     * @param \Closure $proceed
     * @param string $path
     * @return mixed
     */
    public function aroundDeleteDirectory($subject, $proceed, $path)
    {
        if ($this->helper->checkMediaStorageIsS3()) {
            return $this->s3StorageModel->deleteDirectory($path);
        }

        return $proceed($path);
    }
}
