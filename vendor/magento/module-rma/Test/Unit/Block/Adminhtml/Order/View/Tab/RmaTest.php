<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Rma\Test\Unit\Block\Adminhtml\Order\View\Tab;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Order RMA tab test
 */
class RmaTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \Magento\Rma\Block\Adminhtml\Order\View\Tab\Rma
     */
    protected $rmaTab;

    /**
     * @var \Magento\Rma\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $rmaHelperMock;

    protected function setUp()
    {
        $this->objectManager = new ObjectManager($this);

        $this->rmaHelperMock = $this->createMock(\Magento\Rma\Helper\Data::class);

        $this->rmaTab = $this->objectManager->getObject(
            \Magento\Rma\Block\Adminhtml\Order\View\Tab\Rma::class,
            [
                'rmaHelper' => $this->rmaHelperMock
            ]
        );
    }

    /**
     * @param bool $canCreateRma
     * @param bool $expectedResult
     * @dataProvider canShowTabDataProvider
     */
    public function testCanShowTab($canCreateRma, $expectedResult)
    {
        $this->rmaHelperMock->expects($this->any())
            ->method('canCreateRma')
            ->willReturn($canCreateRma);

        $this->assertEquals($expectedResult, $this->rmaTab->canShowTab());
    }

    /**
     * @return array
     */
    public function canShowTabDataProvider()
    {
        return [
            [true, true],
            [false, false]
        ];
    }
}
