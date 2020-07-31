<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Reminder\Test\Unit\Model;

use Magento\Reminder\Model\Rule;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class RuleTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Reminder\Model\Rule
     */
    protected $rule;

    /**
     * @var \Magento\Framework\Model\Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextMock;

    /**
     * @var \Magento\Framework\Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registryMock;

    /**
     * @var \Magento\Framework\Data\FormFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $formFactoryMock;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $timezoneMock;

    /**
     * @var \Magento\Reminder\Model\Rule\Condition\Combine\RootFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $rootFactoryMock;

    /**
     * @var \Magento\Rule\Model\Action\CollectionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $collectionFactoryMock;

    /**
     * @var \Magento\Customer\Model\CustomerFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $customerFactoryMock;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManagerMock;

    /**
     * @var \Magento\SalesRule\Model\CouponFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $couponFactoryMock;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTimeFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dateTimeFactoryMock;

    /**
     * @var \Magento\SalesRule\Model\Rule|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $ruleMock;

    /**
     * @var \Magento\Reminder\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $reminderHelperMock;

    /**
     * @var \Magento\Reminder\Model\ResourceModel\Rule|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $ruleResourceMock;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $transportBuilderMock;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $stateMock;

    /**
     * @var \Magento\Quote\Model\QueryResolver|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $queryResolverMock;

    /**
     * @var \Magento\Framework\Event\Manager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventManagerMock;

    /**
     * @var \Magento\Framework\Data\Collection\AbstractDb|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dbMock;

    /**
     * @var \Magento\Customer\Model\Customer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $customerMock;

    /**
     * @var \Magento\SalesRule\Model\Coupon|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $couponMock;

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp()
    {
        $this->eventManagerMock = $this->getMockBuilder(\Magento\Framework\Event\Manager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock = $this->getMockBuilder(\Magento\Framework\Model\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock->expects($this->any())->method('getEventDispatcher')->willReturn($this->eventManagerMock);

        $this->registryMock = $this->getMockBuilder(\Magento\Framework\Registry::class)
            ->getMock();
        $this->formFactoryMock = $this->getMockBuilder(\Magento\Framework\Data\FormFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->timezoneMock = $this->getMockBuilder(\Magento\Framework\Stdlib\DateTime\TimezoneInterface::class)
            ->getMock();
        $this->rootFactoryMock = $this->getMockBuilder(
            \Magento\Reminder\Model\Rule\Condition\Combine\RootFactory::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->collectionFactoryMock = $this->getMockBuilder(\Magento\Rule\Model\Action\CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->customerFactoryMock = $this->getMockBuilder(\Magento\Customer\Model\CustomerFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->storeManagerMock = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->getMock();
        $this->couponFactoryMock = $this->getMockBuilder(\Magento\SalesRule\Model\CouponFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->dateTimeFactoryMock = $this->getMockBuilder(\Magento\Framework\Stdlib\DateTime\DateTimeFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->ruleMock = $this->getMockBuilder(\Magento\SalesRule\Model\Rule::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->reminderHelperMock = $this->getMockBuilder(\Magento\Reminder\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->ruleResourceMock = $this->getMockBuilder(\Magento\Reminder\Model\ResourceModel\Rule::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->transportBuilderMock = $this->getMockBuilder(\Magento\Framework\Mail\Template\TransportBuilder::class)
            ->setMethods(
                [
                    'setTemplateIdentifier',
                    'setTemplateOptions',
                    'setTemplateVars',
                    'setFrom',
                    'addTo',
                    'getTransport',
                    'sendMessage'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->stateMock = $this->getMockBuilder(\Magento\Framework\Translate\Inline\StateInterface::class)
            ->getMock();
        $this->queryResolverMock = $this->getMockBuilder(\Magento\Quote\Model\QueryResolver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $selectMock = $this->getMockBuilder(\Magento\Framework\DB\Select::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dbMock = $this->getMockBuilder(\Magento\Framework\Data\Collection\AbstractDb::class)
            ->disableOriginalConstructor()
            ->setMethods(['addDateFilter', 'getResource', 'addIsActiveFilter', 'getSelect', '_fetchAll'])
            ->getMock();
        $this->dbMock->expects($this->any())->method('addDateFilter')->willReturnSelf();
        $this->dbMock->expects($this->any())->method('addIsActiveFilter')->willReturnSelf();
        $this->dbMock->expects($this->any())->method('getSelect')->willReturn($selectMock);

        $this->customerMock = $this->getMockBuilder(\Magento\Customer\Model\Customer::class)
            ->setMethods(['load', 'getId', 'getStoreId', 'getStore', 'getWebsiteId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerFactoryMock->expects($this->any())->method('create')->willReturn($this->customerMock);

        $extensionAttributeFactoryMock = $this->createMock(\Magento\Framework\Api\ExtensionAttributesFactory::class);
        $attributeValueFactoryMock = $this->createMock(\Magento\Framework\Api\AttributeValueFactory::class);

        $this->prepareObjectManager([
            [
                \Magento\Framework\Api\ExtensionAttributesFactory::class,
                $extensionAttributeFactoryMock
            ],
            [
                \Magento\Framework\Api\AttributeValueFactory::class,
                $attributeValueFactoryMock
            ],
            [
                \Magento\Framework\Serialize\Serializer\Json::class,
                $this->getSerializerMock()
            ]
        ]);

        $this->rule = new Rule(
            $this->contextMock,
            $this->registryMock,
            $this->formFactoryMock,
            $this->timezoneMock,
            $this->rootFactoryMock,
            $this->collectionFactoryMock,
            $this->customerFactoryMock,
            $this->storeManagerMock,
            $this->couponFactoryMock,
            $this->dateTimeFactoryMock,
            $this->ruleMock,
            $this->reminderHelperMock,
            $this->ruleResourceMock,
            $this->transportBuilderMock,
            $this->stateMock,
            $this->queryResolverMock,
            $this->dbMock
        );
    }

    /**
     * Get mock for serializer
     *
     * @return \Magento\Framework\Serialize\Serializer\Json|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getSerializerMock()
    {
        $serializerMock = $this->getMockBuilder(\Magento\Framework\Serialize\Serializer\Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['serialize', 'unserialize'])
            ->getMock();

        $serializerMock->expects($this->any())
            ->method('serialize')
            ->will(
                $this->returnCallback(
                    function ($value) {
                        return json_encode($value);
                    }
                )
            );

        $serializerMock->expects($this->any())
            ->method('unserialize')
            ->will(
                $this->returnCallback(
                    function ($value) {
                        return json_decode($value, true);
                    }
                )
            );

        return $serializerMock;
    }

    /**
     * @return void
     */
    protected function setSavePreconditions()
    {
        $conditionMock = $this->getMockBuilder(\Magento\Rule\Model\Condition\Combine::class)
            ->disableOriginalConstructor()
            ->setMethods(['setRule', 'setId', 'setPrefix', 'asArray'])
            ->getMock();
        $conditionMock->expects($this->once())->method('setRule')->willReturnSelf();
        $conditionMock->expects($this->once())->method('setId')->willReturnSelf();
        $conditionMock->expects($this->once())->method('setPrefix')->willReturnSelf();
        $conditionMock->expects($this->once())->method('asArray')->willReturn([]);

        $this->rootFactoryMock->expects($this->any())->method('create')->willReturn($conditionMock);

        $actionMock = $this->getMockBuilder(\Magento\Rule\Model\Action\Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['setRule', 'setId', 'setPrefix', 'asArray'])
            ->getMock();
        $actionMock->expects($this->once())->method('setRule')->willReturnSelf();
        $actionMock->expects($this->once())->method('setId')->willReturnSelf();
        $actionMock->expects($this->once())->method('setPrefix')->willReturnSelf();
        $actionMock->expects($this->once())->method('asArray')->willReturn([]);

        $this->collectionFactoryMock->expects($this->any())->method('create')->willReturn($actionMock);

        $this->eventManagerMock->expects($this->at(0))->method('dispatch')->with('model_save_before');
        $this->eventManagerMock->expects($this->at(1))->method('dispatch')->with('core_abstract_save_before');
    }

    /**
     * @return void
     */
    public function testBeforeSaveNew()
    {
        $this->setSavePreconditions();

        $this->assertTrue($this->rule->isObjectNew());
        $this->rule->beforeSave();
    }

    /**
     * @return void
     */
    public function testBeforeSaveExisting()
    {
        $this->setSavePreconditions();

        $this->rule->setId(1);
        $this->rule->setSalesruleId(1);

        $this->assertFalse($this->rule->isObjectNew());
        $this->rule->beforeSave();
    }

    /**
     * @return void
     */
    public function testGetConditionsInstance()
    {
        $conditionMock = $this->getMockBuilder(\Magento\Rule\Model\Condition\Combine::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->rootFactoryMock->expects($this->once())->method('create')->willReturn($conditionMock);

        $this->assertInstanceOf(\Magento\Rule\Model\Condition\Combine::class, $this->rule->getConditionsInstance());
    }

    /**
     * @return void
     */
    public function testGetActionsInstance()
    {
        $actionMock = $this->getMockBuilder(\Magento\Rule\Model\Action\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collectionFactoryMock->expects($this->once())->method('create')->willReturn($actionMock);

        $this->assertInstanceOf(\Magento\Rule\Model\Action\Collection::class, $this->rule->getActionsInstance());
    }

    /**
     * @param string $label
     * @param string $description
     *
     * @return array
     */
    protected function setSendReminderEmailsPreconditions($label = 'label', $description = 'description')
    {
        $storeId = 11;
        $customerId = 1;
        $ruleId = 2;
        $couponId = 3;
        $storeData = ['template_id' => 0, 'label' => $label, 'description' => $description];
        $recipient = ['customer_id' => $customerId, 'rule_id' => $ruleId, 'coupon_id' => $couponId];

        $this->transportBuilderMock->expects($this->once())->method('setTemplateIdentifier')->willReturnSelf();
        $this->transportBuilderMock->expects($this->once())->method('setTemplateOptions')->willReturnSelf();
        $this->transportBuilderMock->expects($this->once())->method('setTemplateVars')->willReturnSelf();
        $this->transportBuilderMock->expects($this->once())->method('setFrom')->willReturnSelf();
        $this->transportBuilderMock->expects($this->once())->method('addTo')->willReturnSelf();
        $this->transportBuilderMock->expects($this->once())->method('getTransport')->willReturnSelf();

        $this->couponMock = $this->getMockBuilder(\Magento\SalesRule\Model\Coupon::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->couponMock->expects($this->once())->method('load')->with($couponId)->willReturnSelf();

        $this->couponFactoryMock->expects($this->once())->method('create')->willReturn($this->couponMock);

        $dateMock = $this->getMockBuilder(\Magento\Framework\Stdlib\DateTime\DateTime::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dateTimeFactoryMock->expects($this->once())->method('create')->willReturn($dateMock);

        $this->ruleResourceMock
            ->expects($this->once())
            ->method('getCustomersForNotification')
            ->willReturn([$recipient]);
        $this->ruleResourceMock
            ->expects($this->once())
            ->method('getStoreTemplateData')
            ->with($ruleId, $storeId)
            ->willReturn($storeData);

        $this->customerMock->expects($this->once())->method('load')->with($customerId)->willReturnSelf();
        $this->customerMock->expects($this->any())->method('getId')->willReturn($customerId);

        $this->dbMock->expects($this->any())->method('_fetchAll')->willReturn([]);

        $this->rule->setDefaultLabel('default label');
        $this->rule->setDefaultDescription('default description');

        return ['customer_id' => $customerId, 'rule_id' => $ruleId, 'coupon_id' => $couponId, 'store_id' => $storeId];
    }

    /**
     * Run test SendReminderEmails
     *
     * @param string $storeLabel
     * @param string $storeDescription
     * @param string $resultLabel
     * @param string $resultDescription
     *
     * @dataProvider storeDataProvider
     * @return void
     */
    public function testSendReminderEmails($storeLabel, $storeDescription, $resultLabel, $resultDescription)
    {
        $result = $this->setSendReminderEmailsPreconditions($storeLabel, $storeDescription);

        $storeMock = $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storeMock->expects($this->any())->method('getId')->willReturn($result['store_id']);

        $this->customerMock->expects($this->once())->method('getStoreId')->willReturn($result['store_id']);
        $this->customerMock->expects($this->once())->method('getStore')->willReturn($storeMock);

        $this->transportBuilderMock->expects($this->once())->method('sendMessage')->willReturnSelf();

        $templateVars = [
            'store' => $storeMock,
            'coupon' => $this->couponMock,
            'customer' => $this->customerMock,
            'promotion_name' => $resultLabel,
            'promotion_description' => $resultDescription,
        ];

        $this->transportBuilderMock->expects($this->once())
            ->method('setTemplateVars')
            ->with($templateVars)
            ->willReturnSelf();

        $this->ruleResourceMock
            ->expects($this->once())
            ->method('addNotificationLog')
            ->with($result['rule_id'], $result['customer_id']);

        $this->rule->sendReminderEmails();
    }

    /**
     * @return void
     */
    public function testSendReminderEmailsWithException()
    {
        $result = $this->setSendReminderEmailsPreconditions();

        $storeMock = $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storeMock->expects($this->any())->method('getId')->willReturn($result['store_id']);

        $this->customerMock->expects($this->once())->method('getStoreId')->willReturn(0);
        $this->customerMock->expects($this->once())->method('getWebsiteId')->willReturn(1);

        $websiteMock = $this->getMockBuilder(\Magento\Store\Model\Website::class)
            ->disableOriginalConstructor()
            ->getMock();
        $websiteMock->expects($this->once())->method('getDefaultStore')->willReturn($storeMock);

        $this->storeManagerMock->expects($this->once())->method('getWebsite')->willReturn($websiteMock);

        $phrase = new \Magento\Framework\Phrase('text');

        $this->transportBuilderMock
            ->expects($this->once())
            ->method('sendMessage')
            ->will(
                $this->throwException(new \Magento\Framework\Exception\MailException($phrase))
            );

        $this->ruleResourceMock
            ->expects($this->once())
            ->method('updateFailedEmailsCounter')
            ->with($result['rule_id'], $result['customer_id']);

        $this->rule->sendReminderEmails();
    }

    /**
     * @return void
     */
    public function testGetStoreData()
    {
        $this->ruleResourceMock
            ->expects($this->once())
            ->method('getStoreTemplateData')
            ->with(1, 2)
            ->willReturn(['template_id' => 0]);
        $this->assertEquals(['template_id' => 'magento_reminder_email_template'], $this->rule->getStoreData(1, 2));
    }

    /**
     * @return void
     */
    public function testGetStoreDataWithNull()
    {
        $this->ruleResourceMock
            ->expects($this->once())
            ->method('getStoreTemplateData')
            ->with(1, 2)
            ->willReturn(null);
        $this->assertFalse($this->rule->getStoreData(1, 2));
    }

    /**
     * @return void
     */
    public function testDetachSalesRule()
    {
        $salesRuleId = 1;
        $this->ruleResourceMock
            ->expects($this->once())
            ->method('detachSalesRule')
            ->with($salesRuleId);
        $this->rule->detachSalesRule($salesRuleId);
    }

    /**
     * Data provider for test
     * @return array
     */
    public function storeDataProvider()
    {
        return [
            'case1' => [
                'storeLabel' => 'label',
                'storeDescription' => 'description',
                'resultLabel' => 'label',
                'resultDescription' => 'description',
            ],
            'case2' => [
                'storeLabel' => '',
                'storeDescription' => '',
                'resultLabel' => 'default label',
                'resultDescription' => 'default description',
            ]
        ];
    }

    /**
     * @param $map
     */
    private function prepareObjectManager($map)
    {
        $objectManagerMock = $this->getMockBuilder(\Magento\Framework\ObjectManagerInterface::class)
            ->setMethods(['getInstance'])
            ->getMockForAbstractClass();
        $objectManagerMock->expects($this->any())->method('getInstance')->willReturnSelf();
        $objectManagerMock->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($map));
        $reflectionClass = new \ReflectionClass(\Magento\Framework\App\ObjectManager::class);
        $reflectionProperty = $reflectionClass->getProperty('_instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($objectManagerMock);
    }
}
