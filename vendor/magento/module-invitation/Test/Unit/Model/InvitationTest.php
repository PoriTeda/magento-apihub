<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Invitation\Test\Unit\Model;

use \Magento\Invitation\Model\Invitation;
use Magento\TestFramework\Bootstrap;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class InvitationTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject  */
    private $context;

    /** @var \PHPUnit_Framework_MockObject_MockObject  */
    private $registry;

    /** @var \PHPUnit_Framework_MockObject_MockObject  */
    private $invitationData;

    /** @var \PHPUnit_Framework_MockObject_MockObject  */
    private $resource;

    /** @var \PHPUnit_Framework_MockObject_MockObject  */
    private $storeManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject  */
    private $config;

    /** @var \PHPUnit_Framework_MockObject_MockObject  */
    private $historyFactory;

    /** @var \PHPUnit_Framework_MockObject_MockObject  */
    private $customerFactory;

    /** @var \PHPUnit_Framework_MockObject_MockObject  */
    private $transactionBuilder;

    /** @var \PHPUnit_Framework_MockObject_MockObject  */
    private $mathRandom;

    /** @var \PHPUnit_Framework_MockObject_MockObject  */
    private $dateTime;

    /** @var \PHPUnit_Framework_MockObject_MockObject  */
    private $scopeConfig;

    /** @var \PHPUnit_Framework_MockObject_MockObject  */
    private $invitationStatus;

    /** @var \PHPUnit_Framework_MockObject_MockObject  */
    private $customerRepository;

    /** @var Invitation  */
    private $invitation;

    /** @var  \Magento\Customer\Api\Data\CustomerInterface | \PHPUnit_Framework_MockObject_MockObject */
    private $customer;

    public function setUp()
    {
        $this->context = $this->getMockBuilder(\Magento\Framework\Model\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->registry = $this->getMockBuilder(\Magento\Framework\Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->invitationData = $this->getMockBuilder(\Magento\Invitation\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resource = $this->getMockBuilder(\Magento\Invitation\Model\ResourceModel\Invitation::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManager = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->config = $this->getMockBuilder(\Magento\Invitation\Model\Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->historyFactory = $this
            ->getMockBuilder(\Magento\Invitation\Model\Invitation\HistoryFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(["create"])
            ->getMock();
        $this->customerFactory = $this
            ->getMockBuilder(\Magento\Customer\Model\CustomerFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(["create"])
            ->getMock();
        $this->transactionBuilder = $this->getMockBuilder(\Magento\Framework\Mail\Template\TransportBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mathRandom = $this->getMockBuilder(\Magento\Framework\Math\Random::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dateTime = $this->getMockBuilder(\Magento\Framework\Stdlib\DateTime::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->scopeConfig = $this->getMockBuilder(\Magento\Framework\App\Config\ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->invitationStatus = $this->getMockBuilder(\Magento\Invitation\Model\Invitation\Status::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerRepository = $this
            ->getMockBuilder(\Magento\Customer\Api\CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(["save", "getById", "get", "getList", "deleteById", "delete"])
            ->getMock();

        $this->customer = $this
            ->getMockBuilder(\Magento\Customer\Api\Data\CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $helper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->invitation = $helper->getObject(Invitation::class, [
            "context" => $this->context,
            "registry" => $this->registry,
            "invitationData" => $this->invitationData,
            "resource" => $this->resource,
            "storeManager" => $this->storeManager,
            "config" => $this->config,
            "historyFactory" => $this->historyFactory,
            "customerFactory" => $this->customerFactory,
            "transactionBuilder" => $this->transactionBuilder,
            "mathRandom" => $this->mathRandom,
            "dateTime" => $this->dateTime,
            "scopeConfig" => $this->scopeConfig,
            "status" => $this->invitationStatus
        ]);

        $helper->setBackwardCompatibleProperty(
            $this->invitation,
            "customerRepository",
            $this->customerRepository
        );
    }

    public function testAssignInvitationDataToCustomer()
    {
        $customerId = 1;
        $customerGroupId = 2;

        $this->invitation->setGroupId($customerGroupId); //setting group id

        $this->customer->expects($this->any())
            ->method("getId")
            ->willReturn($customerId);
        $this->customer->expects($this->once())
            ->method("setGroupId")
            ->with($customerGroupId);

        $this->customerRepository->expects($this->once())
            ->method("getById")
            ->with($customerId)
            ->willReturn($this->customer);

        $this->customerRepository->expects($this->once())
            ->method("save")
            ->with($this->customer);

        $this->invitation->assignInvitationDataToCustomer($customerId);
    }

    /**
     * @expectedException \Magento\Framework\Exception\InputException
     */
    public function testAcceptWithInputExceptionWhenMissingInvitationId()
    {
        $websiteId  = 1;
        $referalId  = 1;

        $this->invitation->accept($websiteId, $referalId);
    }

    /**
     * @expectedException \Magento\Framework\Exception\InputException
     */
    public function testAcceptWithInputExceptionWhenStatusIsIncorrect()
    {
        $websiteId  = 1;
        $referalId  = 1;
        $newStatus = Invitation\Status::STATUS_NEW;
        $acceptedStatus = Invitation\Status::STATUS_ACCEPTED;

        $this->invitationStatus->expects($this->atLeastOnce())
            ->method("getCanBeAcceptedStatuses")
            ->willReturn([$acceptedStatus]);
        $this->invitation->setStatus($newStatus);
        $this->invitation->setId(1);
        $this->invitation->accept($websiteId, $referalId);
    }

    /**
     * @expectedException \Magento\Framework\Exception\InputException
     */
    public function testAcceptWithInputExceptionWhenWebsiteIdIsIncorrect()
    {
        $websiteId  = 1;
        $anotherWebsiteId = 14;
        $referalId  = 1;
        $newStatus = Invitation\Status::STATUS_NEW;
        $storeId = 1;

        $this->invitationStatus->expects($this->atLeastOnce())
            ->method("getCanBeAcceptedStatuses")
            ->willReturn([$newStatus]);
        $this->invitation->setStatus($newStatus);
        $this->invitation->setId(1);

        $store = $this->getMockBuilder(\Magento\Store\Api\Data\StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $store->expects($this->atLeastOnce())
            ->method("getId")
            ->willReturn($storeId);
        $store->expects($this->atLeastOnce())
            ->method("getWebsiteId")
            ->willReturn($anotherWebsiteId);
        $this->storeManager->expects($this->atLeastOnce())
            ->method("getStore")
            ->willReturn($store);

        $this->invitation->accept($websiteId, $referalId);
    }

    public function provideCustomerId()
    {
        return [
            [12],
            ["12"]
        ];
    }

    /**
     * @expectedException \Magento\Framework\Exception\NoSuchEntityException
     * @dataProvider provideCustomerId
     */
    public function testAcceptWithNoSuchCustomerEntity($referalId)
    {
        $invitationId = 4;
        $websiteId  = 1;
        $customerId = 2;
        $storeId    = 1;
        $newStatus = Invitation\Status::STATUS_NEW;
        $customerGroupId = 2;

        $this->invitation->setGroupId($customerGroupId); //setting group id

        $this->customer->expects($this->any())
            ->method("getId")
            ->willReturn(null);

        $this->customerRepository->expects($this->once())
            ->method("getById")
            ->with($referalId)
            ->willReturn($this->customer);

        $this->invitation->setStatus($newStatus);
        $this->invitation->setCustomerId($customerId);
        $this->invitation->setId($invitationId);

        $this->resource->expects($this->once())
            ->method("trackReferral")
            ->with($customerId, $referalId);
        $this->invitationStatus->expects($this->atLeastOnce())
            ->method("getCanBeAcceptedStatuses")
            ->willReturn([$newStatus]);
        $store = $this->getMockBuilder(\Magento\Store\Api\Data\StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $store->expects($this->atLeastOnce())
            ->method("getId")
            ->willReturn($storeId);
        $store->expects($this->atLeastOnce())
            ->method("getWebsiteId")
            ->willReturn($websiteId);
        $this->storeManager->expects($this->atLeastOnce())
            ->method("getStore")
            ->willReturn($store);

        $this->invitation->accept($websiteId, $referalId);
    }

    public function testAcceptWithTrackReferal()
    {
        $invitationId = 4;
        $websiteId  = 1;
        $referalId  = 1;
        $customerId = 2;
        $storeId    = 1;
        $newStatus = Invitation\Status::STATUS_NEW;
        $customerGroupId = 2;

        $this->invitation->setGroupId($customerGroupId); //setting group id

        $this->customer->expects($this->any())
            ->method("getId")
            ->willReturn($referalId);
        $this->customer->expects($this->once())
            ->method("setGroupId")
            ->with($customerGroupId);

        $this->customerRepository->expects($this->once())
            ->method("getById")
            ->with($referalId)
            ->willReturn($this->customer);

        $this->invitation->setStatus($newStatus);
        $this->invitation->setCustomerId($customerId);
        $this->invitation->setId($invitationId);

        $this->resource->expects($this->once())
            ->method("trackReferral")
            ->with($customerId, $referalId);
        $this->invitationStatus->expects($this->atLeastOnce())
            ->method("getCanBeAcceptedStatuses")
            ->willReturn([$newStatus]);
        $store = $this->getMockBuilder(\Magento\Store\Api\Data\StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $store->expects($this->atLeastOnce())
            ->method("getId")
            ->willReturn($storeId);
        $store->expects($this->atLeastOnce())
            ->method("getWebsiteId")
            ->willReturn($websiteId);
        $this->storeManager->expects($this->atLeastOnce())
            ->method("getStore")
            ->willReturn($store);

        $this->invitation->accept($websiteId, $referalId);
    }
}
