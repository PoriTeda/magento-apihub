<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Test\Unit\Controller\Adminhtml\Customer\Attribute;

use Magento\CustomerCustomAttributes\Controller\Adminhtml\Customer\Attribute\Validate;
use Magento\Store\Model\WebsiteFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ValidateTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Backend\App\Action\Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * @var \Magento\Framework\Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $coreRegistry;

    /**
     * @var \Magento\Eav\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eavConfig;

    /**
     * @var \Magento\Customer\Model\AttributeFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $attrFactory;

    /**
     * @var \Magento\Eav\Model\Entity\Attribute\SetFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $attrSetFactory;

    /**
     * @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $request;

    /**
     * @var \Magento\Framework\App\ResponseInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $response;

    /** @var  WebsiteFactory |\PHPUnit_Framework_MockObject_MockObject */
    protected $websiteFactory;

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
            ['representJson', 'sendResponse']
        );

        $this->websiteFactory = $this->getMockBuilder(\Magento\Store\Model\WebsiteFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->initContext();
    }

    protected function initContext()
    {
        $objectHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->context = $objectHelper->getObject(
            \Magento\Backend\App\Action\Context::class,
            [
                'request' => $this->request,
                'response' => $this->response
            ]
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
            ->method('representJson')
            ->willReturnSelf();

        $controller = new Validate(
            $this->context,
            $this->coreRegistry,
            $this->eavConfig,
            $this->attrFactory,
            $this->attrSetFactory,
            $this->websiteFactory
        );

        $controller->execute();
    }
}
