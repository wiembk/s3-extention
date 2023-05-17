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
namespace Webkul\S3amazon\Model\MediaStorage\File\Storage;

use Webkul\S3amazon\Helper\Data;
use Webkul\S3amazon\Model\MediaStorage\File\Storage\S3storage;

/**
 * Plugin for File Storage Databse.
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
     * @param S3Storage $storageModel
     */
    public function __construct(
        Data $helper,
        S3Storage $storageModel
    ) {
        $this->helper = $helper;
        $this->s3StorageModel = $storageModel;
    }
    
    /**
     * @param Magento\MediaStorage\Model\File\Storage\Database $subject
     * @param \Closure $proceed
     * @param string $directory
     * @return mixed
     */
    public function aroundGetDirectoryFiles($subject, $proceed, $directory)
    {
        if ($this->helper->checkMediaStorageIsS3()) {
            return $this->s3StorageModel->getDirectoryFiles($directory);
        }
        
        return $proceed($directory);
    }
}
