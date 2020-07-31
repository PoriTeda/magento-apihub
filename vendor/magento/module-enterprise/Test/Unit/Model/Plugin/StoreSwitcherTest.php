<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Enterprise\Test\Unit\Model\Plugin;

use Magento\Enterprise\Model\Plugin\StoreSwitcher as StoreSwitcherPlugin;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Backend\Block\Store\Switcher as StoreSwitcherBlock;

class StoreSwitcherTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var StoreSwitcherPlugin
     */
    private $storeSwitcherPlugin;

    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var StoreSwitcherBlock|\PHPUnit_Framework_MockObject_MockObject
     */
    private $subjectMock;

    protected function setUp()
    {
        $this->subjectMock = $this->getMockBuilder(StoreSwitcherBlock::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->storeSwitcherPlugin = $this->objectManagerHelper->getObject(StoreSwitcherPlugin::class);
    }

    public function testAfterGetHintUrl()
    {
        $this->assertEquals(
            StoreSwitcherPlugin::HINT_URL,
            $this->storeSwitcherPlugin->afterGetHintUrl($this->subjectMock)
        );
    }
}
