<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Test class for \Magento\GiftRegistry\Model\Entity
 */
namespace Magento\GiftRegistry\Test\Unit\Model;

use Magento\Framework\Serialize\Serializer\Json;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EntityTest extends \PHPUnit\Framework\TestCase
{
    /**
     * GiftRegistry instance
     *
     * @var \Magento\GiftRegistry\Model\Entity
     */
    protected $_model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_store;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_storeManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $_transportBuilderMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $itemModelMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $stockRegistryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $itemFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $stockItemMock;

    /**
     * @var \Magento\Customer\Api\Data\AddressInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $addressDataFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $customerRepositoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $scopeConfigMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $serializerMock;

    /**
     * @var \Magento\Customer\Model\AddressFactory
     */
    private $addressFactoryMock;

    /**
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp()
    {
        $resource = $this->createMock(\Magento\GiftRegistry\Model\ResourceModel\Entity::class);

        $this->_store = $this->createMock(\Magento\Store\Model\Store::class);
        $this->_storeManagerMock = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore'])
            ->getMockForAbstractClass();
        $this->_storeManagerMock->expects($this->any())->method('getStore')->will($this->returnValue($this->_store));

        $this->_transportBuilderMock = $this->createMock(\Magento\Framework\Mail\Template\TransportBuilder::class);

        $this->_transportBuilderMock->expects($this->any())->method('setTemplateOptions')->will($this->returnSelf());
        $this->_transportBuilderMock->expects($this->any())->method('setTemplateVars')->will($this->returnSelf());
        $this->_transportBuilderMock->expects($this->any())->method('addTo')->will($this->returnSelf());
        $this->_transportBuilderMock->expects($this->any())->method('setFrom')->will($this->returnSelf());
        $this->_transportBuilderMock->expects($this->any())->method('setTemplateIdentifier')->will($this->returnSelf());
        $this->_transportBuilderMock->expects($this->any())->method('getTransport')
            ->will($this->returnValue($this->createMock(\Magento\Framework\Mail\TransportInterface::class)));

        $this->_store->expects($this->any())->method('getId')->will($this->returnValue(1));

        $appState = $this->createMock(\Magento\Framework\App\State::class);

        $eventDispatcher = $this->createMock(\Magento\Framework\Event\ManagerInterface::class);
        $cacheManager = $this->createMock(\Magento\Framework\App\CacheInterface::class);
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $actionValidatorMock = $this->createMock(\Magento\Framework\Model\ActionValidator\RemoveAction::class);
        $context = new \Magento\Framework\Model\Context(
            $logger,
            $eventDispatcher,
            $cacheManager,
            $appState,
            $actionValidatorMock
        );
        $giftRegistryData = $this->createPartialMock(\Magento\GiftRegistry\Helper\Data::class, ['getRegistryLink']);
        $giftRegistryData->expects($this->any())->method('getRegistryLink')->will($this->returnArgument(0));
        $coreRegistry = $this->createMock(\Magento\Framework\Registry::class);

        $attributeConfig = $this->createMock(\Magento\GiftRegistry\Model\Attribute\Config::class);
        $this->itemModelMock = $this->createMock(\Magento\GiftRegistry\Model\Item::class);
        $type = $this->createMock(\Magento\GiftRegistry\Model\Type::class);
        $this->stockRegistryMock = $this->createMock(\Magento\CatalogInventory\Model\StockRegistry::class);
        $this->stockItemMock = $this->createPartialMock(
            \Magento\CatalogInventory\Model\Stock\StockItemRepository::class,
            ['getIsQtyDecimal']
        );
        $session = $this->createMock(\Magento\Customer\Model\Session::class);

        $this->addressDataFactory = $this->createMock(
            \Magento\Customer\Api\Data\AddressInterfaceFactory::class,
            ['create']
        );
        $quoteRepository = $this->createMock(\Magento\Quote\Api\CartRepositoryInterface::class);
        $customerFactory = $this->createMock(\Magento\Customer\Model\CustomerFactory::class);
        $personFactory = $this->createMock(\Magento\GiftRegistry\Model\PersonFactory::class);
        $this->itemFactoryMock = $this->createMock(\Magento\GiftRegistry\Model\ItemFactory::class, ['create']);
        $this->addressFactoryMock = $this->createPartialMock(\Magento\Customer\Model\AddressFactory::class, ['create']);
        $productRepository = $this->createMock(\Magento\Catalog\Model\ProductRepository::class);
        $dateFactory = $this->createMock(\Magento\Framework\Stdlib\DateTime\DateTimeFactory::class);
        $escaper = $this->createPartialMock(\Magento\Framework\Escaper::class, ['escapeHtml']);
        $escaper->expects($this->any())->method('escapeHtml')->will($this->returnArgument(0));
        $mathRandom = $this->createMock(\Magento\Framework\Math\Random::class);
        $this->scopeConfigMock = $this->createMock(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        $quoteFactory = $this->createMock(\Magento\Quote\Model\QuoteFactory::class);
        $inlineTranslate = $this->createMock(\Magento\Framework\Translate\Inline\StateInterface::class);

        $this->customerRepositoryMock = $this->createMock(\Magento\Customer\Api\CustomerRepositoryInterface::class);

        $this->serializerMock = $this->createMock(Json::class, ['serialize', 'unserialize']);

        $this->_model = new \Magento\GiftRegistry\Model\Entity(
            $context,
            $coreRegistry,
            $giftRegistryData,
            $this->_storeManagerMock,
            $this->_transportBuilderMock,
            $type,
            $attributeConfig,
            $this->itemModelMock,
            $this->stockRegistryMock,
            $session,
            $quoteRepository,
            $customerFactory,
            $personFactory,
            $this->itemFactoryMock,
            $this->addressFactoryMock,
            $this->addressDataFactory,
            $productRepository,
            $dateFactory,
            $escaper,
            $mathRandom,
            $this->scopeConfigMock,
            $inlineTranslate,
            $quoteFactory,
            $this->customerRepositoryMock,
            $resource,
            null,
            [],
            $this->serializerMock
        );
    }

    /**
     * @param array $arguments
     * @param array $expectedResult
     * @dataProvider invalidSenderAndRecipientInfoDataProvider
     */
    public function testSendShareRegistryEmailsWithInvalidSenderAndRecipientInfoReturnsError(
        $arguments,
        $expectedResult
    ) {
        $senderEmail = 'someuser@magento.com';
        $maxRecipients = 3;
        $customerMock = $this->createMock(\Magento\Customer\Api\Data\CustomerInterface::class);
        $this->customerRepositoryMock->expects($this->once())->method('getById')->willReturn($customerMock);
        $customerMock->expects($this->once())->method('getEmail')->willReturn($senderEmail);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->willReturn($maxRecipients);
        $this->_initSenderInfo($arguments['sender_name'], $arguments['sender_message'], $senderEmail);
        $this->_model->setRecipients($arguments['recipients']);
        $result = $this->_model->sendShareRegistryEmails();

        $this->assertEquals($expectedResult['success'], $result->getIsSuccess());
        $this->assertEquals($expectedResult['error_message'], $result->getErrorMessage());
    }

    public function invalidSenderAndRecipientInfoDataProvider()
    {
        return array_merge($this->_invalidRecipientInfoDataProvider(), $this->_invalidSenderInfoDataProvider());
    }

    /**
     * Retrieve data for invalid sender cases
     *
     * @return array
     */
    protected function _invalidSenderInfoDataProvider()
    {
        return [
            [
                [
                    'sender_name' => null,
                    'sender_message' => 'Hello world',
                    'recipients' => []
                ],
                ['success' => false, 'error_message' => 'You need to enter sender data.']
            ],
            [
                [
                    'sender_name' => 'John Doe',
                    'sender_message' => null,
                    'recipients' => []
                ],
                ['success' => false, 'error_message' => 'You need to enter sender data.']
            ],
            [
                [
                    'sender_name' => 'John Doe',
                    'sender_message' => 'Hello world',
                    'recipients' => []
                ],
                ['success' => false, 'error_message' => 'Please add invitees.']
            ],
        ];
    }

    /**
     * Retrieve data for invalid recipient cases
     *
     * @return array
     */
    protected function _invalidRecipientInfoDataProvider()
    {
        return [
            [
                [
                    'sender_name' => 'John Doe',
                    'sender_message' => 'Hello world',
                    'recipients' => [['email' => 'invalid_email']]
                ],
                ['success' => false, 'error_message' => 'Please enter a valid invitee email address.']
            ],
            [
                [
                    'sender_name' => 'John Doe',
                    'sender_message' => 'Hello world',
                    'recipients' => [['email' => 'john.doe@example.com', 'name' => '']]
                ],
                ['success' => false, 'error_message' => 'Please enter an invitee name.']
            ],
            [
                [
                    'sender_name' => 'John Doe',
                    'sender_message' => 'Hello world',
                    'recipients' => []
                ],
                ['success' => false, 'error_message' => 'Please add invitees.']
            ]
        ];
    }

    /**
     * Initialize sender info
     *
     * @param string $senderName
     * @param string $senderMessage
     * @param string $senderEmail
     * @return void
     */
    protected function _initSenderInfo($senderName, $senderMessage, $senderEmail)
    {
        $this->_model->setSenderName($senderName)->setSenderMessage($senderMessage)->setSenderEmail($senderEmail);
    }

    public function testUpdateItems()
    {
        $modelId = 1;
        $productId = 1;
        $items = [
            1 => ['note' => 'test', 'qty' => 5],
            2 => ['note' => '', 'qty' => 1, 'delete' => 1]
        ];
        $this->_model->setId($modelId);
        $modelMock = $this->createPartialMock(
            \Magento\Framework\Model\AbstractModel::class,
            ['getProductId', 'getId', 'getEntityId', 'save', 'delete', 'isDeleted', 'setQty', 'setNote']
        );
        $this->itemFactoryMock->expects($this->exactly(2))->method('create')->willReturn($this->itemModelMock);
        $this->itemModelMock->expects($this->exactly(4))->method('load')->willReturn($modelMock);
        $modelMock->expects($this->atLeastOnce())->method('getId')->willReturn(1);
        $modelMock->expects($this->atLeastOnce())->method('getEntityId')->willReturn(1);
        $modelMock->expects($this->once())->method('getProductId')->willReturn($productId);
        $modelMock->expects($this->once())->method('delete');
        $modelMock->expects($this->once())->method('setQty')->with($items[1]['qty']);
        $modelMock->expects($this->once())->method('setNote')->with($items[1]['note']);
        $modelMock->expects($this->once())->method('save');
        $this->stockRegistryMock->expects($this->once())->method('getStockItem')->with($productId)
            ->willReturn($this->stockItemMock);
        $this->stockItemMock->expects($this->once())->method('getIsQtyDecimal')->willReturn(10);
        $this->assertEquals($this->_model, $this->_model->updateItems($items));
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage The gift registry item quantity is incorrect. Verify the item quantity and try again.
     */
    public function testUpdateItemsWithIncorrectQuantity()
    {
        $modelId = 1;
        $productId = 1;
        $items = [
            1 => ['note' => 'test', 'qty' => '.1']
        ];
        $this->_model->setId($modelId);
        $modelMock = $this->createPartialMock(
            \Magento\Framework\Model\AbstractModel::class,
            ['getProductId', 'getId', 'getEntityId']
        );
        $this->itemModelMock->expects($this->once())->method('load')->willReturn($modelMock);
        $modelMock->expects($this->atLeastOnce())->method('getId')->willReturn(1);
        $modelMock->expects($this->atLeastOnce())->method('getEntityId')->willReturn(1);
        $modelMock->expects($this->once())->method('getProductId')->willReturn($productId);
        $this->stockRegistryMock->expects($this->once())->method('getStockItem')->with($productId)
            ->willReturn($this->stockItemMock);
        $this->stockItemMock->expects($this->once())->method('getIsQtyDecimal')->willReturn(0);
        $this->assertEquals($this->_model, $this->_model->updateItems($items));
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage The gift registry item ID is incorrect. Verify the gift registry item ID and try again.
     */
    public function testUpdateItemsWithIncorrectItemId()
    {
        $modelId = 1;
        $items = [
            1 => ['note' => 'test', 'qty' => '.1']
        ];
        $this->_model->setId($modelId);
        $modelMock = $this->createMock(\Magento\Framework\Model\AbstractModel::class);
        $this->itemModelMock->expects($this->once())->method('load')->willReturn($modelMock);
        $this->assertEquals($this->_model, $this->_model->updateItems($items));
    }

    /**
     * @return array
     */
    public function addressDataProvider()
    {
        return [
            'withoutData' => [null],
            'withData'    => [
                ['street' => 'Baker Street'],
            ]
        ];
    }

    /**
     * @param [] $data
     * @dataProvider addressDataProvider
     */
    public function testExportAddressData($data)
    {
        $this->_model->setData('shipping_address', json_encode($data));
        $this->addressDataFactory->expects($this->once())
            ->method('create')
            ->willReturn(
                $this->getMockBuilder(\Magento\Customer\Model\Data\Address::class)
                    ->disableOriginalConstructor()
                    ->getMock()
            );

        $this->assertInstanceOf(\Magento\Customer\Model\Data\Address::class, $this->_model->exportAddressData());
    }

    /**
     * @param $shippingData
     * @param $expectedCalls
     * @dataProvider exportAddressDataProvider
     */
    public function testExportAddress($shippingData, $expectedCalls)
    {
        $this->_model->setData('shipping_address', '[]');
        
        $this->serializerMock->expects($this->any())
            ->method('unserialize')
            ->willReturn($shippingData);

        $address = $this->getMockBuilder(\Magento\Customer\Model\Address::class)
            ->setMethods(['setData'])
            ->disableOriginalConstructor()
            ->getMock();

        $address->expects($this->exactly($expectedCalls))
            ->method('setData')
            ->with($shippingData)
            ->willReturn($address);

        $this->addressFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($address);

        $this->_model->exportAddress();
    }

    public function exportAddressDataProvider()
    {
        return [
            [
                'string',
                0,
            ],
            [
                [],
                1,
            ],
        ];
    }
}
