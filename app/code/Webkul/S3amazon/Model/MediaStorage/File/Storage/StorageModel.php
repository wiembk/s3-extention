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

use Magento\MediaStorage\Helper\File\Storage as StorageHelper;
use Magento\MediaStorage\Model\File\Storage;
use Webkul\S3amazon\Model\MediaStorage\File\Storage\S3storageFactory as StorageFactory;

/**
 * Plugin to set S3 model as Storage Model.
 */
class StorageModel
{
    /**
     * @var StorageHelper
     */
    private $coreFileStorage;

    /**
     * @var StorageFactory
     */
    private $s3StorageFactory;

    /**
     * @param StorageHelper $coreFileStorage
     * @param StorageFactory $s3StorageFactory
     */
    public function __construct(
        StorageHelper $coreFileStorage,
        StorageFactory $s3StorageFactory
    ) {
        $this->coreFileStorage = $coreFileStorage;
        $this->s3StorageFactory = $s3StorageFactory;
    }

    /**
     * @param Storage $subject
     * @param \Closure $proceed
     * @param string $storage
     * @param array $params
     * @return mixed
     */
    public function aroundGetStorageModel($subject, $proceed, $storage = null, array $params = [])
    {
        // called only sync time
        $storageModel = $proceed($storage, $params);
        if ($storageModel === false) {
            if ($storage === null) {
                $storage = $this->coreFileStorage->getCurrentStorageCode();
            }
            if ($storage == \Webkul\S3amazon\Model\MediaStorage\File\Storage::STORAGE_MEDIA_S3) {
                $storageModel = $this->s3StorageFactory->create();
            } else {
                return false;
            }
            
            if (!empty($params['init'])) {
                $storageModel->init();
            }
        }
        
        return $storageModel;
    }
}
