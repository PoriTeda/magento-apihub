<?php

namespace Riki\SubscriptionCourse\Test\Unit\Model\ResourceModel;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class CourseTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resource;

    /**
     * @var \Magento\Framework\Model\AbstractModel
     */
    protected $abstractModel;

    protected $version;

    /**
     * @var \Riki\SubscriptionCourse\Model\ResourceModel\Course
     */
    protected $object;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $dbAdapter;

    /**
     * @var \Magento\Framework\DB\Select
     */
    protected $select;
    /**
     * @var object
     */
    private $context;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $authSession;

    protected $categoryFactory;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $productFactory;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $dateTime;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $timezoneInterface;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $courseFactory;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $logger;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $storeManagerInterface;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $stdTimezone;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $productRepository;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $state;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $ruleFactory;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $customerGroup;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $customer;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $ruleProductProcessor;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $productRuleProcessor;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $salesRule;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $customerRepository;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $functionCache;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $storeRepositoryInterface;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $storeResolverInterface;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $groupRepositoryInterface;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $scopeConfigInterface;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $machineType;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $cacheInterface;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $serialize;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $session;

    protected $dataObject;

    protected $roleFactory;

    public function setUp()
    {
        $this->resource = $this->getMockBuilder('Magento\Framework\App\ResourceConnection')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        $this->dbAdapter = $this->getMockBuilder('Magento\Framework\DB\Adapter\AdapterInterface')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        $this->categoryFactory = $this->getMockBuilder('Magento\Catalog\Model\CategoryFactory')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        $this->productFactory = $this->getMockBuilder('Magento\Catalog\Model\ProductFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->dateTime = $this->getMockBuilder('Magento\Framework\Stdlib\DateTime\DateTime')
            ->disableOriginalConstructor()
            ->getMock();
        $this->timezoneInterface = $this->getMockBuilder('Magento\Framework\Stdlib\DateTime\TimezoneInterface')
            ->getMockForAbstractClass();
        $this->courseFactory = $this->getMockBuilder('Riki\SubscriptionCourse\Model\CourseFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->logger = $this->getMockBuilder('Riki\Subscription\Logger\LoggerReplaceProduct')
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManagerInterface = $this->getMockBuilder('Magento\Store\Model\StoreManagerInterface')
            ->getMockForAbstractClass();
        $this->stdTimezone = $this->getMockBuilder('Magento\Framework\Stdlib\DateTime\Timezone')
            ->disableOriginalConstructor()
            ->getMock();
        $this->productRepository = $this->getMockBuilder('Magento\Catalog\Api\ProductRepositoryInterface')
            ->getMockForAbstractClass();
        $this->searchCriteriaBuilder = $this->getMockBuilder('Magento\Framework\Api\SearchCriteriaBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $this->state = $this->getMockBuilder('Magento\Framework\App\State')
            ->disableOriginalConstructor()
            ->getMock();
        $this->ruleFactory = $this->getMockBuilder('Magento\CatalogRule\Model\RuleFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->customerGroup = $this->getMockBuilder('Bluecom\PaymentCustomer\Model\Source\Config\Customer\Group')
            ->disableOriginalConstructor()
            ->getMock();
        $this->customer = $this->getMockBuilder('Magento\Customer\Model\Customer')
            ->disableOriginalConstructor()
            ->getMock();
        $this->ruleProductProcessor = $this->getMockBuilder('Magento\CatalogRule\Model\Indexer\Rule\RuleProductProcessor')
            ->disableOriginalConstructor()
            ->getMock();
        $this->productRuleProcessor = $this->getMockBuilder('Magento\CatalogRule\Model\Indexer\Product\ProductRuleProcessor')
            ->disableOriginalConstructor()
            ->getMock();
        $this->salesRule = $this->getMockBuilder('Riki\SalesRule\Model\ResourceModel\Rule')
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerRepository = $this->getMockBuilder('Magento\Customer\Api\CustomerRepositoryInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->functionCache = $this->getMockBuilder('Riki\Framework\Helper\Cache\FunctionCache')
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeRepositoryInterface = $this->getMockBuilder('Magento\Store\Api\StoreRepositoryInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeResolverInterface = $this->getMockBuilder('Magento\Store\Api\StoreResolverInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->groupRepositoryInterface = $this->getMockBuilder('Magento\Store\Api\GroupRepositoryInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->scopeConfigInterface = $this->getMockBuilder('Magento\Framework\App\Config\ScopeConfigInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->machineType = $this->getMockBuilder('Riki\MachineApi\Model\B2CMachineSkusFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->cacheInterface = $this->getMockBuilder('Magento\Framework\App\CacheInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->serialize = $this->getMockBuilder('Magento\Framework\Serialize\Serializer\Serialize')
            ->disableOriginalConstructor()
            ->getMock();

        $this->dataObject = $this->getMockBuilder('Magento\Framework\Validator\DataObjectFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->ruleFactory = $this->getMockBuilder('Magento\Authorization\Model\RoleFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->dbAdapter->expects($this->any())
            ->method('delete')
            ->withAnyParameters()
            ->willReturn(true);

        $this->resource->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->dbAdapter);

        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->context = $this->objectManager->getObject(
            'Magento\Framework\Model\ResourceModel\Db\Context',
            [
                'resource' => $this->resource,
                'abstractModel' => $this->abstractModel,
                'version' => $this->version
            ]
        );

        // Auth  session
        $this->authSession = $this->createMock(\Magento\Backend\Model\Auth\Session::class);

        $objectManager = new ObjectManager($this);
        $this->object = $objectManager->getObject(\Riki\SubscriptionCourse\Model\ResourceModel\Course::class, [
            'authSession' => $this->authSession,
            'context' => $this->context
        ]);
    }


    public function testDeleteMergeProfileToWithNoUserFound()
    {
        $categoryIdMock = 0;
        $isAdditionMock = false;

        $userMock = $this->objectManager->getObject(\Magento\User\Model\User::class, []);
        $userMock->setUserName(null);

        $this->authSession->expects($this->any())
            ->method('__call')
            ->with('getUser')
            ->willReturn($userMock);

        $result = $this->object->deleteMergeProfileTo($categoryIdMock, $isAdditionMock);
        $this->assertEquals($this->object, $result);
    }

    public function testDeleteMergeProfileToWithUserFound()
    {
        $categoryIdMock = 0;
        $isAdditionMock = false;
        $userNameMock = 'Hello World';

        $userMock = $this->objectManager->getObject(\Magento\User\Model\User::class, []);
        $userMock->setUserName($userNameMock);

        $this->authSession->expects($this->any())
            ->method('__call')
            ->with('getUser')
            ->willReturn($userMock);

        $result = $this->object->deleteMergeProfileTo($categoryIdMock, $isAdditionMock);
        $this->assertEquals($this->object, $result);
    }

    public function testDeleteMergeProfileToWithUserNullInSession()
    {
        $categoryIdMock = 0;
        $isAdditionMock = false;

        $this->authSession->expects($this->any())
            ->method('__call')
            ->with('getUser')
            ->willReturn(null);

        $result = $this->object->deleteMergeProfileTo($categoryIdMock, $isAdditionMock);
        $this->assertEquals($this->object, $result, 'Not equal');
    }
}