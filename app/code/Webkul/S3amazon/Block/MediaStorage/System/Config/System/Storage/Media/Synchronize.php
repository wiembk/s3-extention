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
namespace Webkul\S3amazon\Block\MediaStorage\System\Config\System\Storage\Media;

class Synchronize
{
    /**
     * set the custom template
     * @return string
     */
    public function aroundGetTemplate()
    {
        return 'Webkul_S3amazon::system/config/system/storage/media/synchronise.phtml';
    }
}
