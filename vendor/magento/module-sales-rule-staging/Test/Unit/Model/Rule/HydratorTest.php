<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesRuleStaging\Test\Unit\Model\Rule;

use Magento\SalesRuleStaging\Model\Rule\Hydrator;

class HydratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var Hydrator */
    protected $hydrator;

    /** @var \Magento\Backend\App\Action\Context|\PHPUnit_Framework_MockObject_MockObject */
    protected $context;

    /** @var \Magento\Staging\Model\Entity\RetrieverInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityRetriever;

    /** @var \Magento\Framework\Event\ManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $eventManager;

    /** @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $request;

    /** @var \Magento\Framework\Message\ManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $messageManager;

    /** @var \Magento\SalesRule\Model\Rule|\PHPUnit_Framework_MockObject_MockObject */
    protected $salesRule;

    /** @var \Magento\SalesRule\Model\RuleFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $toModelConverter;

    public function setUp()
    {
        $this->context = $this->getMockBuilder(\Magento\Backend\App\Action\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityRetriever = $this->getMockBuilder(\Magento\Staging\Model\Entity\RetrieverInterface::class)
            ->getMockForAbstractClass();
        $this->eventManager = $this->getMockBuilder(\Magento\Framework\Event\ManagerInterface::class)
            ->getMockForAbstractClass();
        $this->request = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->getMockForAbstractClass();
        $this->messageManager = $this->getMockBuilder(\Magento\Framework\Message\ManagerInterface::class)
            ->getMockForAbstractClass();
        $this->salesRule = $this->getMockBuilder(\Magento\SalesRule\Model\Rule::class)
            ->disableOriginalConstructor()
            ->setMethods(['validateData', 'loadPost'])
            ->getMock();
        $this->context = $this->getMockBuilder(\Magento\Backend\App\Action\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->ruleFactory = $this->getMockBuilder(\Magento\SalesRule\Model\RuleFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->hydrator = new Hydrator($this->context, $this->entityRetriever, $this->ruleFactory);
    }

    public function testHydrate()
    {
        $ruleId = 1;
        $data = [
            'rule_id' => $ruleId
        ];

        $this->context->expects($this->once())
            ->method('getEventManager')
            ->willReturn($this->eventManager);
        $this->context->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);
        $this->eventManager->expects($this->once())
            ->method('dispatch')
            ->with(
                'adminhtml_controller_salesrule_prepare_save',
                ['request' => $this->request]
            );
        $this->entityRetriever->expects($this->once())
            ->method('getEntity')
            ->with($ruleId)
            ->willReturn($this->salesRule);
        $this->ruleFactory->expects(($this->once()))
            ->method('create')
            ->willReturn($this->salesRule);
        $this->salesRule->expects($this->once())
            ->method('validateData')
            ->with(new \Magento\Framework\DataObject($data))
            ->willReturn(true);
        $this->salesRule->expects($this->once())
            ->method('loadPost')
            ->with($data)
            ->willReturnSelf();
        $this->assertSame($this->salesRule, $this->hydrator->hydrate($data));
    }

    public function testHydrateWithInvalidData()
    {
        $ruleId = 1;
        $data = [
            'rule_id' => $ruleId
        ];

        $this->context->expects($this->once())
            ->method('getEventManager')
            ->willReturn($this->eventManager);
        $this->context->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);
        $this->eventManager->expects($this->once())
            ->method('dispatch')
            ->with(
                'adminhtml_controller_salesrule_prepare_save',
                ['request' => $this->request]
            );
        $this->entityRetriever->expects($this->once())
            ->method('getEntity')
            ->with($ruleId)
            ->willReturn($this->salesRule);
        $this->ruleFactory->expects(($this->once()))
            ->method('create')
            ->willReturn($this->salesRule);
        $this->salesRule->expects($this->once())
            ->method('validateData')
            ->with(new \Magento\Framework\DataObject($data))
            ->willReturn([
                'Error message'
            ]);
        $this->context->expects($this->once())
            ->method('getMessageManager')
            ->willReturn($this->messageManager);
        $this->messageManager->expects($this->once())
            ->method('addError')
            ->with('Error message');
        $this->assertFalse($this->hydrator->hydrate($data));
    }
}
