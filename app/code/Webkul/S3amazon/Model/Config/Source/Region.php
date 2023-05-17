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
namespace Webkul\S3amazon\Model\Config\Source;

class Region implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Return list of available Amazon S3 regions
     *
     * @return array
     */
    public function toOptionArray()
    {
        $regions = [
            [
                'value' => 'us-east-2',
                'label' => 'US East (Ohio)'
            ],
            [
                'value' => 'us-east-1',
                'label' => 'US East (N. Virginia)'
            ],
            [
                'value' => 'us-west-1',
                'label' => 'US West (N. California)'
            ],
            [
                'value' => 'us-west-2',
                'label' => 'US West (Oregon)'
            ],
            [
                'value' => 'ap-east-1',
                'label' => 'Asia Pacific (Hong Kong)'
            ],
            [
                'value' => 'ap-south-1',
                'label' => 'Asia Pacific (Mumbai)'
            ],
            [
                'value' => 'ap-northeast-3',
                'label' => 'Asia Pacific (Osaka-Local)'
            ],
            [
                'value' => 'ap-northeast-2',
                'label' => 'Asia Pacific (Seoul)'
            ],
            [
                'value' => 'ap-southeast-1',
                'label' => 'Asia Pacific (Singapore)'
            ],
            [
                'value' => 'ap-southeast-2',
                'label' => 'Asia Pacific (Sydney)'
            ],
            [
                'value' => 'ap-northeast-1',
                'label' => 'Asia Pacific (Tokyo)'
            ],
            [
                'value' => 'ca-central-1',
                'label' => 'Canada (Central)'
            ],
            [
                'value' => 'cn-north-1',
                'label' => 'China (Beijing)'
            ],
            [
                'value' => 'cn-northwest-1',
                'label' => 'China (Ningxia)'
            ],
            [
                'value' => 'eu-central-1',
                'label' => 'EU (Frankfurt)'
            ],
            [
                'value' => 'eu-west-1',
                'label' => 'EU (Ireland)'
            ],
            [
                'value' => 'eu-west-2',
                'label' => 'EU (London)'
            ],
            [
                'value' => 'eu-west-3',
                'label' => 'EU (Paris)'
            ],
            [
                'value' => 'eu-north-1',
                'label' => 'EU (Stockholm)'
            ],
            [
                'value' => 'me-south-1',
                'label' => 'Middle East (Bahrain)'
            ],
            [
                'value' => 'sa-east-1',
                'label' => 'South America (Sao Paulo)'
            ],
            [
                'value' => 'us-gov-east-1',
                'label' => 'AWS GovCloud (US-East)'
            ],
            [
                'value' => 'us-gov-west-1',
                'label' => 'AWS GovCloud (US-West)'
            ],
        ];

        return $regions;
    }
}
