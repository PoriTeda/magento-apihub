<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VersionsCms\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class LoggingTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\VersionsCms\Model\Logging
     */
    protected $logging;

    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestInterface;

    /**
     * @var \Magento\Logging\Model\Event|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventModel;

    protected function setUp()
    {
        $this->requestInterface = $this->createMock(\Magento\Framework\App\RequestInterface::class);
        $this->eventModel = $this->getMockBuilder(\Magento\Logging\Model\Event::class)
            ->setMethods(['setInfo', '__wakeup', '__sleep'])
            ->disableOriginalConstructor()->getMock();
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->logging = $this->objectManagerHelper->getObject(
            \Magento\VersionsCms\Model\Logging::class,
            [
                'request' => $this->requestInterface
            ]
        );
    }

    public function testPostDispatchCmsHierachyView()
    {
        $this->eventModel->expects($this->once())->method('setInfo')->with('Tree Viewed')->will($this->returnSelf());
        $this->logging->postDispatchCmsHierachyView([], $this->eventModel);
    }
}
