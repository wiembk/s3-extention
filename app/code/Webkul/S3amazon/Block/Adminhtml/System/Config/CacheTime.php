<?php declare(strict_types=1);
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_S3amazon
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
namespace Webkul\S3amazon\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Caching time
 */
class CacheTime extends AbstractFieldArray
{
    /**
     * Prepare rendering the new field by adding all the needed columns
     */
    protected function _prepareToRender()
    {
        $this->addColumn('file_type', [
            'label' => __('File Extension'),
            'class' => 'required-entry',
            'size' => 6
        ]);
        $this->addColumn('file_time', [
            'label' => __('max-age'),
            'class' => 'required-entry',
            'size' => 10
        ]);
        
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }
}
