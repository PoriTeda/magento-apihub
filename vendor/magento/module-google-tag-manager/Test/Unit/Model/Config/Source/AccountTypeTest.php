<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GoogleTagManager\Test\Unit\Model\Config\Source;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use \Magento\GoogleTagManager\Helper\Data as Helper;

class AccountTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\GoogleTagManager\Model\Config\Source\AccountType */
    protected $accountType;

    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    protected function setUp()
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->accountType = $this->objectManagerHelper->getObject(
            \Magento\GoogleTagManager\Model\Config\Source\AccountType::class
        );
    }

    public function testToOptionArray()
    {
        $options =  [
            [
                'value' => Helper::TYPE_UNIVERSAL,
                'label' => __('Universal Analytics')
            ],
            [
                'value' => Helper::TYPE_TAG_MANAGER,
                'label' => __('Google Tag Manager')
            ],
        ];
        $this->assertEquals($options, $this->accountType->toOptionArray());
    }
}
