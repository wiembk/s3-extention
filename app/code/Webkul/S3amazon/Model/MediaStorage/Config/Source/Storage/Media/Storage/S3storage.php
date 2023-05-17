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

namespace Webkul\S3amazon\Model\MediaStorage\Config\Source\Storage\Media\Storage;

use Magento\MediaStorage\Model\Config\Source\Storage\Media\Storage;

/**
 * Plugin for Storage.
 *
 * @see Storage
 */
class S3storage
{
    /**
     * Provide Amazon S3 option
     *
     * @param Storage $subject
     * @param array $result
     * @return array
     */
    public function afterToOptionArray($subject, $result)
    {
        $result[] = [
            'value' => \Webkul\S3amazon\Model\MediaStorage\File\Storage::STORAGE_MEDIA_S3,
            'label' => __('Amazon S3'),
        ];
        
        return $result;
    }
}
