<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Block\Adminhtml\Report\Create;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class CreateButtonTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Support\Block\Adminhtml\Report\Create\CreateButton
     */
    protected $createButton;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManagerHelper;

    protected function setUp()
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->createButton = $this->objectManagerHelper->getObject(
            \Magento\Support\Block\Adminhtml\Report\Create\CreateButton::class
        );
    }

    public function testGetButtonData()
    {
        $buttonData = [
            'label' => __('Create'),
            'class' => 'primary'
        ];

        $this->assertEquals($buttonData, $this->createButton->getButtonData());
    }
}
