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
namespace Webkul\S3amazon\Model\Captcha;

use Magento\MediaStorage\Helper\File\Storage\Database;
use Webkul\S3amazon\Helper\Data;

/**
 * Plugin for DefaultModel.
 */
class DefaultModel
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Database
     */
    private $database;

    /**
     * @param Data $helper
     * @param Database $database
     */
    public function __construct(
        Data $helper,
        Database $database
    ) {
        $this->helper = $helper;
        $this->database = $database;
    }

    /**
     * @param \Magento\Captcha\Model\DefaultModel $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterGenerate(\Magento\Captcha\Model\DefaultModel $subject, $result)
    {
        if ($this->helper->checkMediaStorageIsS3()) {
            $imageFile = $subject->getImgDir() . $result . $subject->getSuffix();
            $relativeImageFile = $this->database->getMediaRelativePath($imageFile);
            $this->database->getStorageDatabaseModel()->saveFile($relativeImageFile);
        }

        return $result;
    }
}
