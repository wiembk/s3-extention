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
namespace Webkul\S3amazon\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Title extends AbstractDb
{
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init('downloadable_link_title', 'title_id');
    }
}
