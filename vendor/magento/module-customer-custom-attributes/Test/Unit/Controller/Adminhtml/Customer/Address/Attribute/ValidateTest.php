<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomerCustomAttributes\Test\Unit\Controller\Adminhtml\Customer\Address\Attribute;

use Magento\CustomerCustomAttributes\Controller\Adminhtml\Customer\Address\Attribute\Validate;
use Magento\Store\Model\WebsiteFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ValidateTest extends \PHPUnit\Framework\TestCase
{
    /** @var  Validate|\PHPUnit_Framework_MockObject_MockObject */
    protected $controller;

    /** @var \Magento\Backend\App\Action\Context|\PHPUnit_Framework_MockObject_MockObject */
    protected $context;

    /** @var \Magento\Framework\Registry|\PHPUnit_Framework_MockObject_MockObject */
    protected $coreRegistry;

    /** @var \Magento\Eav\Model\Config|\PHPUnit_Framework_MockObject_MockObject */
    protected $eavConfig;

    /** @var \Magento\Customer\Model\AttributeFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $attrFactory;

    /** @var \Magento\Eav\Model\Entity\Attribute\SetFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $attrSetFactory;

    /** @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $request;

    /** @var \Magento\Framework\App\ResponseInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $response;

    /** @var  WebsiteFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $websiteFactory;

    /** @var \Magento\Framework\App\ViewInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $view;

    protected function setUp()
    {
        $this->coreRegistry = $this->createMock(\Magento\Framework\Registry::class);
        $this->eavConfig = $this->createMock(\Magento\Eav\Model\Config::class);
        $this->attrFactory = $this->createPartialMock(\Magento\Customer\Model\AttributeFactory::class, ['create']);
        $this->attrSetFactory = $this->createPartialMock(
            \Magento\Eav\Model\Entity\Attribute\SetFactory::class,
            ['create']
        );
        $this->request = $this->getMockForAbstractClass(
            \Magento\Framework\App\RequestInterface::class,
            [],
            '',
            false
        );
        $this->response = $this->getMockForAbstractClass(
            \Magento\Framework\App\ResponseInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['setBody']
        );

        $this->websiteFactory = $this->getMockBuilder(\Magento\Store\Model\WebsiteFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->view = $this->getMockForAbstractClass(\Magento\Framework\App\ViewInterface::class, [], '', false);

        $objectHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->context = $objectHelper->getObject(
            \Magento\Backend\App\Action\Context::class,
            [
                'request' => $this->request,
                'response' => $this->response,
                'view' => $this->view
            ]
        );

        $this->controller = new Validate(
            $this->context,
            $this->coreRegistry,
            $this->eavConfig,
            $this->attrFactory,
            $this->attrSetFactory,
            $this->websiteFactory
        );
    }

    public function testExecute()
    {
        $this->request->expects($this->at(0))
            ->method('getParam')
            ->with('attribute_id')
            ->willReturn(false);
        $this->request->expects($this->at(1))
            ->method('getParam')
            ->with('attribute_code')
            ->willReturn('firstname');
        $this->request->expects($this->at(2))
            ->method('getParam')
            ->with('website')
            ->willReturn(1);
        $attribute = $this->createMock(\Magento\Customer\Model\Attribute::class);
        $attribute->expects($this->once())
            ->method('loadByCode')
            ->willReturnSelf();
        $attribute->expects($this->once())
            ->method('getId')
            ->willReturn(47);
        $this->attrFactory->expects($this->once())
            ->method('create')
            ->willReturn($attribute);

        $entityType = $this->createMock(\Magento\Eav\Model\Entity\Type::class);
        $entityType->expects($this->once())
            ->method('getId')
            ->willReturn(23);
        $this->eavConfig->expects($this->once())
            ->method('getEntityType')
            ->willReturn($entityType);
        $this->response->expects($this->once())
            ->method('setBody')
            ->with('{"error":true,"html_message":"html"}');

        $layout = $this->getMockForAbstractClass(
            \Magento\Framework\View\LayoutInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['initMessages']
        );
        $messageBlock = $this->createMock(\Magento\Framework\View\Element\Messages::class);
        $this->view->expects($this->atLeastOnce())
            ->method('getLayout')
            ->willReturn($layout);
        $layout->expects($this->once())
            ->method('initMessages');
        $layout->expects($this->once())
            ->method('getMessagesBlock')
            ->willReturn($messageBlock);
        $messageBlock->expects($this->once())
            ->method('getGroupedHtml')
            ->willReturn('html');

        $this->controller->execute();
    }
}
