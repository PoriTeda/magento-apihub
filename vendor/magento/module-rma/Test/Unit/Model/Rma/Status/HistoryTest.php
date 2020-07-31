<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Test\Unit\Model\Rma\Status;

use Magento\Rma\Model\Rma\Status\History;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Rma\Model\Rma\Source\Status;

/**
 * Class HistoryTest
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class HistoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var History
     */
    protected $history;

    /**
     * @var \Magento\Rma\Model\Config | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $rmaConfig;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $inlineTranslation;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $transportBuilder;

    /**
     * @var \Magento\Rma\Helper\Data | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $rmaHelper;

    /**
     * @var \Magento\Framework\Model\ResourceModel\AbstractResource | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $resource;

    /**
     * @var \Magento\Framework\Stdlib\DateTime | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $dateTime;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $dateTimeDateTime;

    /**
     * @var \Magento\Framework\Event\Manager | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventManager;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManager;

    /**
     * @var \Magento\Sales\Model\Order | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $order;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Address\Collection | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $addressCollection;

    /**
     * @var TimezoneInterface | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $localeDate;

    /**
     * @var \Magento\Rma\Model\RmaFactory | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $rmaFactory;

    /**
     * @var \Magento\Rma\Model\Rma | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $rma;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $rmaRepositoryMock;

    /**
     * @var \Magento\Sales\Model\Order\Address\Renderer | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $addressRendererMock;

    /**
     * @var \Magento\Sales\Model\Order\Address | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $addressMock;

    protected function setUp()
    {
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->eventManager = $this->createMock(\Magento\Framework\Event\Manager::class);
        $this->storeManager = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);
        $context = $this->createMock(\Magento\Framework\Model\Context::class);
        $context->expects($this->once())->method('getEventDispatcher')->will($this->returnValue($this->eventManager));
        $this->rmaConfig = $this->createPartialMock(\Magento\Rma\Model\Config::class, [
                '__wakeup',
                'getRootCommentEmail',
                'getCustomerEmailRecipient',
                'getRootCustomerCommentEmail',
                'init',
                'isEnabled',
                'getCopyTo',
                'getCopyMethod',
                'getGuestTemplate',
                'getTemplate',
                'getIdentity',
                'getRootRmaEmail',
                'getRootAuthEmail',

            ]);
        $this->rma = $this->createPartialMock(
            \Magento\Rma\Model\Rma::class,
            ['__wakeup', 'getId', 'getStatus', 'getStoreId', 'getOrder', 'getItemsForDisplay', 'load', 'getEntityId']
        );
        $this->inlineTranslation = $this->createMock(\Magento\Framework\Translate\Inline\StateInterface::class);
        $this->transportBuilder = $this->createMock(\Magento\Framework\Mail\Template\TransportBuilder::class);
        $this->rmaHelper = $this->createMock(\Magento\Rma\Helper\Data::class);
        $this->resource = $this->createMock(\Magento\Rma\Model\ResourceModel\Rma\Status\History::class);
        $this->dateTime = $this->createMock(\Magento\Framework\Stdlib\DateTime::class);
        $this->dateTimeDateTime = $this->createMock(\Magento\Framework\Stdlib\DateTime\DateTime::class);
        $this->localeDate = $this->createMock(\Magento\Framework\Stdlib\DateTime\Timezone::class);
        $this->order = $this->createPartialMock(
            \Magento\Sales\Model\Order::class,
            ['getStore', 'getBillingAddress', 'getShippingAddress', '__wakeup', 'getAddressesCollection']
        );
        $this->addressCollection = $this->createPartialMock(
            \Magento\Sales\Model\ResourceModel\Order\Address\Collection::class,
            ['getItems']
        );
        $this->rmaFactory = $this->createPartialMock(\Magento\Rma\Model\RmaFactory::class, ['create', '__wakeup']);
        $this->rmaRepositoryMock = $this->createMock(\Magento\Rma\Api\RmaRepositoryInterface::class);
        $this->addressRendererMock = $this->createMock(\Magento\Sales\Model\Order\Address\Renderer::class);
        $this->addressMock = $this->createMock(\Magento\Sales\Model\Order\Address::class);
        $this->addressRendererMock->expects($this->any())->method('format')->willReturn(1);
        $this->history = $objectManagerHelper->getObject(
            \Magento\Rma\Model\Rma\Status\History::class,
            [
                'storeManager' => $this->storeManager,
                'rmaFactory' => $this->rmaFactory,
                'rmaConfig' => $this->rmaConfig,
                'transportBuilder' => $this->transportBuilder,
                'inlineTranslation' => $this->inlineTranslation,
                'rmaHelper' => $this->rmaHelper,
                'resource' => $this->resource,
                'dateTime' => $this->dateTime,
                'dateTimeDateTime' => $this->dateTimeDateTime,
                'localeDate' => $this->localeDate,
                'context' => $context,
                'rmaRepositoryInterface' => $this->rmaRepositoryMock,
                'addressRenderer' => $this->addressRendererMock
            ]
        );
    }

    public function testGetStore()
    {
        $store = $this->createMock(\Magento\Store\Model\Store::class);
        $this->order->expects($this->once())
            ->method('getStore')
            ->will($this->returnValue($store));
        $this->history->setOrder($this->order);

        $this->assertEquals($store, $this->history->getStore());
    }

    public function testGetRma()
    {
        $this->history->setData('rma_entity_id', 10003);
        $this->rmaRepositoryMock->expects($this->any())
            ->method('get')
            ->with(10003)
            ->willReturn($this->rma);
        $this->assertEquals($this->rma, $this->history->getRma());
    }

    public function testGetStoreNoOrder()
    {
        $store = $this->createMock(\Magento\Store\Model\Store::class);
        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->will($this->returnValue($store));
        $this->assertEquals($store, $this->history->getStore());
    }

    public function testSaveComment()
    {
        $comment = 'comment';
        $visible = true;
        $isAdmin = true;
        $id = 1;
        $status = 'status';
        $emailSent = true;
        $date = 'today';

        $this->prepareSaveComment($id, $status, $date, $emailSent);

        $this->history->saveComment($comment, $visible, $isAdmin);

        $this->assertEquals($comment, $this->history->getComment());
        $this->assertEquals($visible, $this->history->isVisibleOnFront());
        $this->assertEquals($isAdmin, $this->history->isAdmin());
        $this->assertEquals($emailSent, $this->history->isCustomerNotified());
        $this->assertEquals($date, $this->history->getCreatedAt());
        $this->assertEquals($status, $this->history->getStatus());
    }

    public function testSendNewRmaEmail()
    {
        $this->stepAddressFormat();

        $storeId = 5;
        $this->rma->expects($this->once())
            ->method('getStoreId')
            ->will($this->returnValue($storeId));
        $this->rma->expects($this->once())
            ->method('getOrder')
            ->will($this->returnValue($this->order));

        $this->rmaConfig->expects($this->once())
            ->method('isEnabled')
            ->will($this->returnValue(true));

        $this->prepareTransportBuilder();

        $this->rmaRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->rma);
        $this->assertNull($this->history->getEmailSent());
        $this->history->sendNewRmaEmail();
        $this->assertTrue($this->history->getEmailSent());
    }

    public function testSendAuthorizeEmail()
    {
        $storeId = 5;
        $customerEmail = 'custom@email.com';
        $name = 'name';
        $this->stepAddressFormat();
        $this->prepareRmaModel($storeId, $name, $customerEmail);
        $this->prepareRmaConfig('bcc');
        $this->prepareTransportBuilder();

        $this->order->setCustomerEmail($customerEmail);
        $this->order->setCustomerIsGuest(false);
        $this->history->setRma($this->rma);
        $this->assertNull($this->history->getEmailSent());

        $this->history->sendAuthorizeEmail();
        $this->assertTrue($this->history->getEmailSent());
    }

    public function testSendAuthorizeEmailGuest()
    {
        $storeId = 5;
        $customerEmail = 'custom@email.com';
        $name = 'name';
        $this->stepAddressFormat();

        $this->prepareRmaModel($storeId, $name, $customerEmail);
        $this->prepareRmaConfig('copy');
        $this->prepareTransportBuilder();

        $this->order->setCustomerIsGuest(true);
        $this->addressMock->expects($this->once())
            ->method('getName')
            ->will($this->returnValue($name));

        $this->history->sendAuthorizeEmail();
        $this->assertTrue($this->history->getEmailSent());
    }

    protected function prepareTransportBuilder()
    {
        $this->transportBuilder->expects($this->atLeastOnce())
            ->method('setTemplateIdentifier')
            ->will($this->returnSelf());
        $this->transportBuilder->expects($this->atLeastOnce())
            ->method('setTemplateOptions')
            ->will($this->returnSelf());
        $this->transportBuilder->expects($this->atLeastOnce())
            ->method('setTemplateVars')
            ->will($this->returnSelf());
        $this->transportBuilder->expects($this->atLeastOnce())
            ->method('setFrom')
            ->will($this->returnSelf());
        $this->transportBuilder->expects($this->atLeastOnce())
            ->method('addTo')
            ->will($this->returnSelf());
        $this->transportBuilder->expects($this->atLeastOnce())
            ->method('addBcc')
            ->will($this->returnSelf());

        $transport = $this->getMockForAbstractClass(\Magento\Framework\Mail\TransportInterface::class);
        $transport->expects($this->atLeastOnce())
            ->method('sendMessage');

        $this->transportBuilder->expects($this->atLeastOnce())
            ->method('getTransport')
            ->will($this->returnValue($transport));
    }

    /**
     * @param string $copyMethod
     */
    protected function prepareRmaConfig($copyMethod)
    {
        $template = 'some html';
        $this->rmaConfig->expects($this->once())
            ->method('isEnabled')
            ->will($this->returnValue(true));
        if ($copyMethod == 'bcc') {
            $copyTo = 'copyTo';
        } else {
            $copyTo = ['email@com.com'];
        }
        $this->rmaConfig->expects($this->once())
            ->method('getCopyTo')
            ->will($this->returnValue($copyTo));
        $this->rmaConfig->expects($this->once())
            ->method('getCopyMethod')
            ->will($this->returnValue($copyMethod));
        if ($this->order->getCustomerIsGuest()) {
            $this->rmaConfig->expects($this->once())
                ->method('getGuestTemplate')
                ->will($this->returnValue($template));
        }
    }

    /**
     * @param $storeId
     * @param $name
     * @param $customerEmail
     */
    protected function prepareRmaModel($storeId, $name, $customerEmail)
    {
        $this->rma->expects($this->atLeastOnce())
            ->method('getStoreId')
            ->will($this->returnValue($storeId));
        $this->rma->expects($this->atLeastOnce())
            ->method('getOrder')
            ->will($this->returnValue($this->order));
        $this->rma->setCustomerName($name);
        $this->rma->setCustomerCustomEmail($customerEmail);
        $this->rma->setIsSendAuthEmail(true);
        $this->rmaRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->rma);
    }

    public function testSendCommentEmail()
    {
        $storeId = 5;
        $customerEmail = 'custom@email.com';
        $name = 'name';

        $this->prepareRmaModel($storeId, $name, $customerEmail);
        $this->prepareRmaConfig('bcc');
        $this->prepareTransportBuilder();

        $this->order->setCustomerEmail($customerEmail);
        $this->order->setCustomerName($name);
        $this->order->setCustomerIsGuest(false);
        $this->history->setRma($this->rma);
        $this->assertNull($this->history->getEmailSent());
        $this->history->sendCommentEmail();
        $this->assertTrue($this->history->getEmailSent());
    }

    public function testSendCommentEmailGuest()
    {
        $storeId = 5;
        $customerEmail = 'custom@email.com';
        $name = 'name';

        $this->prepareRmaModel($storeId, $name, $customerEmail);
        $this->prepareRmaConfig('copy');
        $this->prepareTransportBuilder();

        $address = $this->createMock(\Magento\Sales\Model\Order\Address::class);
        $address->expects($this->once())
            ->method('getName')
            ->will($this->returnValue($name));
        $this->order->expects($this->once())
            ->method('getBillingAddress')
            ->will($this->returnValue($address));

        $this->order->setCustomerEmail($customerEmail);
        $this->order->setCustomerName($name);
        $this->order->setCustomerIsGuest(true);
        $this->history->setRma($this->rma);
        $this->assertNull($this->history->getEmailSent());
        $this->history->sendCommentEmail();
        $this->assertTrue($this->history->getEmailSent());
    }

    public function testSendCustomerCommentEmail()
    {
        $storeId = 5;
        $customerEmail = 'custom@email.com';
        $name = 'name';
        $commentRoot = 'sales_email/magento_rma_customer_comment';

        $this->prepareRmaModel($storeId, $name, $customerEmail);
        $this->prepareRmaConfig('bcc');
        $this->rmaConfig->expects($this->once())
            ->method('getCustomerEmailRecipient')
            ->with($storeId)
            ->will($this->returnValue($customerEmail));
        $this->rmaConfig->expects($this->once())
            ->method('getRootCustomerCommentEmail')
            ->will($this->returnValue($commentRoot));
        $this->prepareTransportBuilder();

        $this->order->setCustomerIsGuest(false);
        $this->history->setRma($this->rma);
        $this->assertNull($this->history->getEmailSent());
        $this->history->sendCustomerCommentEmail();
        $this->assertTrue($this->history->getEmailSent());
    }

    public function testSendCustomerCommentEmailDisabled()
    {
        $this->rmaRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->rma);
        $this->rmaConfig->expects($this->once())
            ->method('isEnabled')
            ->will($this->returnValue(false));
        $this->assertEquals($this->history, $this->history->sendCustomerCommentEmail());
    }

    public function testSendAuthorizeEmailNotSent()
    {
        $this->rmaRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->rma);
        $this->rma->setIsSendAuthEmail(false);
        $this->assertEquals($this->history, $this->history->sendAuthorizeEmail());
        $this->assertNull($this->history->getEmailSent());
    }

    public function testSendRmaEmailWithItemsDisabled()
    {
        $this->rmaRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->rma);
        $this->rma->setIsSendAuthEmail(true);
        $this->rmaConfig->expects($this->once())
            ->method('isEnabled')
            ->will($this->returnValue(false));
        $this->assertEquals($this->history, $this->history->sendAuthorizeEmail());
    }

    public function testSendAuthorizeEmailFail()
    {
        $this->rmaRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->rma);
        $this->rma->setIsSendAuthEmail(false);
        $this->assertEquals($this->history, $this->history->sendAuthorizeEmail());
    }

    public function testGetCreatedAtDate()
    {
        $date = '2015-01-02 03:04:05';
        $dateObject = new \DateTime($date);
        $datetime = $dateObject->format('Y-m-d H:i:s');
        $this->localeDate->expects($this->once())
            ->method('date')
            ->with($dateObject, null, true)
            ->willReturn($datetime);

        $this->history->setCreatedAt($date);
        $this->assertEquals($datetime, $this->history->getCreatedAtDate());
    }

    /**
     * @dataProvider statusProvider
     * @param string $status
     * @param string $expected
     */
    public function testGetSystemCommentByStatus($status, $expected)
    {
        $this->assertEquals($expected, History::getSystemCommentByStatus($status));
    }

    public function statusProvider()
    {
        return [
            [Status::STATE_PENDING, __('We placed your Return request.')],
            [Status::STATE_AUTHORIZED, __('We authorized your Return request.')],
            [Status::STATE_PARTIAL_AUTHORIZED, __('We partially authorized your Return request.')],
            [Status::STATE_RECEIVED, __('We received your Return request.')],
            [Status::STATE_RECEIVED_ON_ITEM, __('We partially received your Return request.')],
            [Status::STATE_APPROVED_ON_ITEM, __('We partially approved your Return request.')],
            [Status::STATE_REJECTED_ON_ITEM, __('We partially rejected your Return request.')],
            [Status::STATE_CLOSED, __('We closed your Return request.')],
            [Status::STATE_PROCESSED_CLOSED, __('We processed and closed your Return request.')]
        ];
    }

    /**
     * @param $id
     * @param $status
     * @param $date
     * @param $emailSent
     */
    protected function prepareSaveComment($id, $status, $date, $emailSent)
    {
        $this->rma->expects($this->once())
            ->method('getEntityId')
            ->will($this->returnValue($id));
        $this->rma->expects($this->atLeastOnce())
            ->method('getStatus')
            ->will($this->returnValue($status));

        $this->dateTimeDateTime->expects($this->once())
            ->method('gmtDate')
            ->will($this->returnValue($date));

        $this->resource->expects($this->once())
            ->method('save')
            ->with($this->history);

        $this->rmaRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->rma);
        $this->history->setEmailSent($emailSent);
    }

    public function testSaveSystemComment()
    {
        $id = 1;
        $status = 'status';
        $emailSent = true;
        $date = 'today';
        $this->rma->setStatus($status);
        $this->prepareSaveComment($id, $status, $date, $emailSent);

        $this->history->saveSystemComment();

        $this->assertEquals($emailSent, $this->history->isCustomerNotified());
        $this->assertEquals($date, $this->history->getCreatedAt());
        $this->assertEquals($status, $this->history->getStatus());
    }

    private function stepAddressFormat()
    {
        $this->order->expects($this->any())
            ->method('getBillingAddress')
            ->will($this->returnValue($this->addressMock));
        $this->order->expects($this->any())
            ->method('getShippingAddress')
            ->will($this->returnValue($this->addressMock));
        $this->order->expects($this->any())
            ->method('getAddressesCollection')
            ->will($this->returnValue($this->addressCollection));
        $this->addressCollection->expects($this->any())->method('getItems')->willReturn([$this->addressMock]);
    }
}
