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

namespace Webkul\S3amazon\Model\MediaStorage\File;

use Magento\MediaStorage\Model\File\Storage as FileStorage;

/**
 * @inheritdoc
 */
class Storage extends FileStorage
{
    const STORAGE_MEDIA_S3 = 2;
}
