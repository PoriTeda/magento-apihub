<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogRuleStaging\Test\Unit\Model\Rule;

use Magento\CatalogRuleStaging\Model\Rule\Hydrator;

class HydratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\Backend\App\Action\Context|\PHPUnit_Framework_MockObject_MockObject */
    protected $context;

    /** @var \Magento\Framework\Event\ManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $eventManager;

    /** @var \Magento\Framework\Message\ManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $messageManager;

    /** @var \Magento\Staging\Model\Entity\RetrieverInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityRetriever;

    /** @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $request;

    /** @var \Magento\CatalogRule\Model\RuleFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $ruleFactory;

    /** @var \Magento\CatalogRule\Model\Rule|\PHPUnit_Framework_MockObject_MockObject */
    protected $rule;

    /** @var Hydrator */
    protected $hydrator;

    public function setUp()
    {
        $this->context = $this->getMockBuilder(\Magento\Backend\App\Action\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventManager = $this->getMockBuilder(\Magento\Framework\Event\ManagerInterface::class)
            ->getMockForAbstractClass();
        $this->entityRetriever = $this->getMockBuilder(\Magento\Staging\Model\Entity\RetrieverInterface::class)
            ->getMockForAbstractClass();
        $this->request = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->getMockForAbstractClass();
        $this->messageManager = $this->getMockBuilder(\Magento\Framework\Message\ManagerInterface::class)
            ->getMockForAbstractClass();
        $this->ruleFactory = $this->getMockBuilder(\Magento\CatalogRule\Model\RuleFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->rule = $this->getMockBuilder(\Magento\CatalogRule\Model\Rule::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->hydrator = new Hydrator($this->context, $this->ruleFactory, $this->entityRetriever);
    }

    public function testHydrate()
    {
        $ruleId = 1;
        $data = [
            'rule_id' => $ruleId,
            'is_active' => 1,
            'rule' => [
                'conditions' => '',
            ],
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
                'adminhtml_controller_catalogrule_prepare_save',
                ['request' => $this->request]
            );
        $this->ruleFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->rule);
        $this->rule->expects($this->once())
            ->method('validateData')
            ->with(new \Magento\Framework\DataObject($data))
            ->willReturn(true);
        $this->entityRetriever
            ->expects($this->once())
            ->method('getEntity')
            ->with($ruleId)
            ->willReturn($this->rule);
        $this->rule->expects($this->once())
            ->method('loadPost')
            ->with([
                'rule_id' => $ruleId,
                'is_active' => 1,
                'conditions' => '',
            ])
            ->willReturnSelf();
        $this->assertSame($this->rule, $this->hydrator->hydrate($data));
    }

    public function testHydrateWithInvalidData()
    {
        $ruleId = 1;
        $data = [
            'rule_id' => $ruleId,
            'is_active' => 1,
            'rule' => [
                'conditions' => '',
            ],
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
                'adminhtml_controller_catalogrule_prepare_save',
                ['request' => $this->request]
            );
        $this->ruleFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->rule);
        $this->rule->expects($this->once())
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
