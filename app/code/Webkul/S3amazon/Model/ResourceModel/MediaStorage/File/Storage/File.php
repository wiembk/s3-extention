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
namespace Webkul\S3amazon\Model\ResourceModel\MediaStorage\File\Storage;

use Magento\MediaStorage\Helper\File\Storage\Database;

class File
{
    /**
     * @var Database
     */
    protected $fileStorageDb;

    /**
     * @param Database $fileStorageDb
     */
    public function __construct(
        Database $fileStorageDb
    ) {
        $this->fileStorageDb = $fileStorageDb;
    }

    /**
     * during save file uploading the file to S3 server.
     *
     * @param \Magento\MediaStorage\Model\ResourceModel\File\Storage\File $subject
     * @param bool $result
     * @param string $filePath
     * @param string $content
     * @param bool $overwrite
     * @return bool
     */
    public function afterSaveFile(
        \Magento\MediaStorage\Model\ResourceModel\File\Storage\File $subject,
        $result,
        $filePath,
        $content,
        $overwrite = false
    ) {
        if ($result && $filePath) {
            $this->fileStorageDb->saveFile($filePath);
        }

        return $result;
    }
}
