<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdminGws\Test\Unit\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\AdminGws\Model\Controllers as Ctrl;

/**
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ControllersTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\AdminGws\Model\Controllers
     */
    protected $_model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_roleMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_storeManagerMock;

    /**
     * Controller request object
     *
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_ctrlRequestMock;

    /**
     * Controller response object
     *
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $responseMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_controllerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $collectionsFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $collectionsMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $categoryRepositoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_objectManager;

    protected function setUp()
    {
        $helper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->_roleMock = $this->createMock(\Magento\AdminGws\Model\Role::class);
        $this->_objectManager = $this->createMock(\Magento\Framework\ObjectManagerInterface::class);
        $this->_storeManagerMock = $this->createMock(\Magento\Store\Model\StoreManager::class);
        $this->responseMock = $this->createPartialMock(
            \Magento\Framework\App\ResponseInterface::class,
            ['setRedirect', 'sendResponse']
        );
        $this->_controllerMock = $this->createMock(\Magento\Backend\App\Action::class);
        $this->_ctrlRequestMock = $this->createMock(\Magento\Framework\App\Request\Http::class);
        $this->collectionsFactoryMock = $this->createPartialMock(
            \Magento\AdminGws\Model\ResourceModel\CollectionsFactory::class,
            ['create']
        );
        $this->collectionsMock = $this->createPartialMock(
            \Magento\AdminGws\Model\ResourceModel\Collections::class,
            ['getUsersOutsideLimitedScope', 'getRolesOutsideLimitedScope']
        );

        $coreRegistry = $this->createMock(\Magento\Framework\Registry::class);

        $this->categoryRepositoryMock = $this->getMockForAbstractClass(
            \Magento\Catalog\Api\CategoryRepositoryInterface::class,
            [],
            '',
            false
        );

        $this->_model = $helper->getObject(
            \Magento\AdminGws\Model\Controllers::class,
            [
                'role' => $this->_roleMock,
                'registry' => $coreRegistry,
                'objectManager' => $this->_objectManager,
                'storeManager' => $this->_storeManagerMock,
                'response' => $this->responseMock,
                'request' => $this->_ctrlRequestMock,
                'collectionsFactory' => $this->collectionsFactoryMock,
                'categoryRepository' => $this->categoryRepositoryMock
            ]
        );
    }

    protected function tearDown()
    {
        unset($this->_controllerMock);
        unset($this->_ctrlRequestMock);
        unset($this->responseMock);
        unset($this->_model);
        unset($this->_objectManager);
        unset($this->_roleMock);
        unset($this->collectionsFactoryMock);
        unset($this->collectionsMock);
        unset($this->categoryRepositoryMock);
    }

    /**
     * Test deny access if role has no allowed website ids and there are considering actions to deny
     */
    public function testValidateRuleEntityActionRoleHasntWebSiteIdsAndConsideringActionsToDenyForwardAvoidCycling()
    {
        $this->_ctrlRequestMock->expects($this->at(0))
            ->method('getActionName')
            ->will($this->returnValue(Ctrl::ACTION_EDIT));

        $this->_ctrlRequestMock->expects($this->at(1))
            ->method('getActionName')
            ->will($this->returnValue(Ctrl::ACTION_DENIED));

        $this->_roleMock->expects($this->once())->method('getWebsiteIds')->will($this->returnValue(null));

        $this->_model->validateRuleEntityAction();
    }

    /**
     * Test deny access if role has no allowed website ids and there are considering actions to deny
     */
    public function testValidateRuleEntityActionRoleHasntWebSiteIdsAndConsideringActionsToDenyForward()
    {
        $this->_ctrlRequestMock->expects($this->at(0))
            ->method('getActionName')
            ->will($this->returnValue(Ctrl::ACTION_EDIT));

        $this->_ctrlRequestMock->expects(
            $this->at(1)
        )->method(
            'getActionName'
        )->will(
            $this->returnValue('any_action')
        );
        $this->_ctrlRequestMock->expects($this->once())->method('initForward');
        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'setActionName'
        )->with(
            $this->equalTo(Ctrl::ACTION_DENIED)
        )->will(
            $this->returnSelf()
        );
        $this->_ctrlRequestMock->expects($this->once())->method('setDispatched')->with($this->equalTo(false));

        $this->_roleMock->expects($this->once())->method('getWebsiteIds')->will($this->returnValue(null));

        $this->_model->validateRuleEntityAction();
    }

    /**
     * Test stop further validating if role has any allowed website ids and
     * there are considering any action which is not in deny list
     */
    public function testValidateRuleEntityActionWhichIsNotInDenyList()
    {
        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getActionName'
        )->will(
            $this->returnValue('any_action')
        );

        $this->_roleMock->expects($this->once())->method('getWebsiteIds')->will($this->returnValue(null));
        $this->assertTrue($this->_model->validateRuleEntityAction($this->_controllerMock));
    }

    /**
     * Test stop further validating if there is no an appropriate entity id in request params
     */
    public function testValidateRuleEntityActionNoAppropriateEntityIdInRequestParams()
    {
        $this->_ctrlRequestMock->expects($this->once())
            ->method('getActionName')
            ->will($this->returnValue(Ctrl::ACTION_EDIT));
        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->will($this->returnValue(null));
        $this->_roleMock->expects($this->once())->method('getWebsiteIds')->will($this->returnValue([1]));
        $this->assertTrue($this->_model->validateRuleEntityAction($this->_controllerMock));
    }

    /**
     * Test get valid entity model class name
     *
     * @param $controllerName string
     * @param $modelName string
     * @dataProvider validateRuleEntityActionGetValidModuleClassNameDataProvider
     */
    public function testValidateRuleEntityActionGetValidModuleClassName($controllerName, $modelName)
    {
        $this->_ctrlRequestMock->expects($this->once())
            ->method('getActionName')
            ->will($this->returnValue(Ctrl::ACTION_EDIT));
        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getControllerName'
        )->will(
            $this->returnValue($controllerName)
        );
        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->will($this->returnValue(1));

        $this->_roleMock->expects($this->once())->method('getWebsiteIds')->will($this->returnValue([1]));

        $this->_objectManager->expects(
            $this->once()
        )->method(
            'create'
        )->with(
            $this->equalTo($modelName)
        )->will(
            $this->returnValue(null)
        );

        $this->assertTrue($this->_model->validateRuleEntityAction($this->_controllerMock));
    }

    public function validateRuleEntityActionGetValidModuleClassNameDataProvider()
    {
        return [
            ['promo_catalog', \Magento\CatalogRule\Model\Rule::class],
            ['promo_quote', \Magento\SalesRule\Model\Rule::class],
            ['reminder', \Magento\Reminder\Model\Rule::class],
            ['customersegment', \Magento\CustomerSegment\Model\Segment::class]
        ];
    }

    /*
     * Test get entity model class name invalid controller name
     */
    public function testValidateRuleEntityActionGetModuleClassNameWithInvalidController()
    {
        $this->_ctrlRequestMock->expects($this->once())
            ->method('getActionName')
            ->will($this->returnValue(Ctrl::ACTION_EDIT));
        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getControllerName'
        )->will(
            $this->returnValue('some_other')
        );
        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->will($this->returnValue(1));

        $this->_roleMock->expects($this->once())->method('getWebsiteIds')->will($this->returnValue([1]));

        $this->_objectManager->expects($this->exactly(0))->method('create');

        $this->assertTrue($this->_model->validateRuleEntityAction($this->_controllerMock));
    }

    /*
     * Test deny action if specified rule entity doesn't exist
     */
    public function testValidateRuleEntityActionDenyActionIfSpecifiedRuleEntityDoesntExist()
    {
        $this->_ctrlRequestMock->expects($this->at(0))
            ->method('getActionName')
            ->will($this->returnValue(Ctrl::ACTION_EDIT));
        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getControllerName'
        )->will(
            $this->returnValue('promo_catalog')
        );
        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->will($this->returnValue(1));

        $this->_roleMock->expects($this->once())->method('getWebsiteIds')->will($this->returnValue([1]));

        $modelMock = $this->createMock(\Magento\CatalogRule\Model\Rule::class);
        $modelMock->expects($this->once())->method('load')->with(1);
        $modelMock->expects($this->once())->method('getId')->will($this->returnValue(false));

        $this->_objectManager->expects($this->exactly(1))->method('create')->will($this->returnValue($modelMock));

        $this->_ctrlRequestMock->expects($this->at(1))
            ->method('getActionName')
            ->will($this->returnValue(Ctrl::ACTION_DENIED));
        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'setActionName'
        )->will(
            $this->returnValue($this->_ctrlRequestMock)
        );

        $this->assertEmpty($this->_model->validateRuleEntityAction());
    }

    /*
     * Test deny actions what lead to changing data if role has no exclusive access to assigned to rule entity websites
     */
    public function testValidateRuleEntityActionDenyActionIfRoleHasNoExclusiveAccessToAssignedToRuleEntityWebsites()
    {
        $modelMock = $this->createMock(\Magento\CatalogRule\Model\Rule::class);

        $this->_ctrlRequestMock->expects($this->at(0))
            ->method('getActionName')
            ->will($this->returnValue(Ctrl::ACTION_EDIT));
        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getControllerName'
        )->will(
            $this->returnValue('promo_catalog')
        );
        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->will($this->returnValue([1]));

        $this->_roleMock->expects($this->once())->method('getWebsiteIds')->will($this->returnValue([1]));
        $this->_roleMock->expects(
            $this->once()
        )->method(
            'hasExclusiveAccess'
        )->with(
            $this->equalTo([0 => 1, 2 => 2])
        )->will(
            $this->returnValue(false)
        );

        $this->_objectManager->expects($this->exactly(1))->method('create')->will($this->returnValue($modelMock));

        $modelMock->expects($this->once())->method('load')->with([1]);
        $modelMock->expects($this->once())->method('getId')->will($this->returnValue(1));
        $modelMock->expects($this->once())->method('getOrigData')->will($this->returnValue([1, 2]));

        $this->_ctrlRequestMock->expects($this->at(1))
            ->method('getActionName')
            ->will($this->returnValue(Ctrl::ACTION_DENIED));
        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'setActionName'
        )->will(
            $this->returnValue($this->_ctrlRequestMock)
        );

        $this->assertEmpty($this->_model->validateRuleEntityAction());
    }

    /*
     * Test deny action if role has no access to assigned to rule entity websites
     */
    public function testValidateRuleEntityActionDenyActionIfRoleHasNoAccessToAssignedToRuleEntityWebsites()
    {
        $this->_ctrlRequestMock->expects($this->at(0))
            ->method('getActionName')
            ->will($this->returnValue(Ctrl::ACTION_EDIT));
        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->will($this->returnValue([1]));
        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getControllerName'
        )->will(
            $this->returnValue('promo_catalog')
        );

        $modelMock = $this->createMock(\Magento\CatalogRule\Model\Rule::class);
        $modelMock->expects($this->once())->method('load')->with([1]);
        $modelMock->expects($this->once())->method('getId')->will($this->returnValue(1));
        $modelMock->expects($this->once())->method('getOrigData')->will($this->returnValue([1, 2]));

        $this->_objectManager->expects($this->exactly(1))->method('create')->will($this->returnValue($modelMock));
        $this->_roleMock->expects($this->once())->method('getWebsiteIds')->will($this->returnValue([1]));

        $this->_ctrlRequestMock->expects($this->at(1))
            ->method('getActionName')
            ->will($this->returnValue(Ctrl::ACTION_DENIED));

        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'setActionName'
        )->will(
            $this->returnValue($this->_ctrlRequestMock)
        );

        $this->_roleMock->expects(
            $this->once()
        )->method(
            'hasExclusiveAccess'
        )->with(
            $this->equalTo([0 => 1, 2 => 2])
        )->will(
            $this->returnValue(true)
        );

        $this->_roleMock->expects(
            $this->once()
        )->method(
            'hasWebsiteAccess'
        )->with(
            $this->equalTo([0 => 1, 2 => 2])
        )->will(
            $this->returnValue(false)
        );

        $this->assertEmpty($this->_model->validateRuleEntityAction());
    }

    /**
     * @param array $post
     * @param boolean $result
     * @param boolean $isAll
     *
     * @dataProvider validateCmsHierarchyActionDataProvider
     */
    public function testValidateCmsHierarchyAction(array $post, $isAll, $result)
    {
        $this->_ctrlRequestMock->expects($this->any())
            ->method('getPost')
            ->will($this->returnValue($post));
        $this->_ctrlRequestMock->expects($this->any())
            ->method('setActionName')
            ->will($this->returnSelf());
        $websiteId = (isset($post['website'])) ? $post['website'] : 1;
        $websiteMock = $this->getMockBuilder(\Magento\Store\Model\Website::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();
        $websiteMock->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($websiteId));

        $storeId = (isset($post['store'])) ? $post['store'] : 1;
        $storeMock = $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getWebsite'])
            ->getMock();
        $storeMock->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($storeId));
        $storeMock->expects($this->any())
            ->method('getWebsite')
            ->will($this->returnValue($websiteMock));

        $this->_storeManagerMock->expects($this->any())
            ->method('getStore')
            ->will($this->returnValue($storeMock));

        $hasExclusiveAccess = in_array($websiteMock->getId(), [1]);
        $hasExclusiveStoreAccess = in_array($storeMock->getId(), [2]);

        $this->_roleMock->expects($this->any())
            ->method('hasExclusiveAccess')
            ->will($this->returnValue($hasExclusiveAccess));

        $this->_roleMock->expects($this->any())
            ->method('hasExclusiveStoreAccess')
            ->will($this->returnValue($hasExclusiveStoreAccess));

        $this->_roleMock->expects($this->any())
            ->method('getIsAll')
            ->will($this->returnValue($isAll));

        $this->assertEquals($result, $this->_model->validateCmsHierarchyAction());
    }

    /**
     * Data provider for testValidateCmsHierarchyAction()
     *
     * @return array
     */
    public function validateCmsHierarchyActionDataProvider()
    {
        return [
            [[], true, true],
            [[], false, false],
            [['website' => 1, 'store' => 1], false, false],
            [['store' => 2], false, true],
            [['store' => 1], false, false],
        ];
    }

    /*
     * Test validate rule entity action with valid params
     */
    public function testValidateRuleEntityActionWithValidParams()
    {
        $this->_ctrlRequestMock->expects($this->once())
            ->method('getActionName')
            ->will($this->returnValue(Ctrl::ACTION_EDIT));
        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getControllerName'
        )->will(
            $this->returnValue('promo_catalog')
        );
        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->will($this->returnValue([1]));

        $this->_roleMock->expects($this->once())->method('getWebsiteIds')->will($this->returnValue([1]));

        $modelMock = $this->createMock(\Magento\CatalogRule\Model\Rule::class);
        $modelMock->expects($this->once())->method('load')->with([1]);
        $modelMock->expects($this->once())->method('getId')->will($this->returnValue(1));
        $modelMock->expects($this->once())->method('getOrigData')->will($this->returnValue([1, 2]));

        $this->_objectManager->expects($this->exactly(1))->method('create')->will($this->returnValue($modelMock));

        $this->_roleMock->expects(
            $this->once()
        )->method(
            'hasExclusiveAccess'
        )->with(
            $this->equalTo([0 => 1, 2 => 2])
        )->will(
            $this->returnValue(true)
        );

        $this->_roleMock->expects(
            $this->once()
        )->method(
            'hasWebsiteAccess'
        )->with(
            $this->equalTo([0 => 1, 2 => 2])
        )->will(
            $this->returnValue(true)
        );

        $this->assertTrue($this->_model->validateRuleEntityAction());
    }

    /**
     * Test validation admin user without param "user_id"
     */
    public function testValidateAdminUserActionWithoutId()
    {
        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'getParam'
        )->with(
            $this->equalTo('user_id')
        )->will(
            $this->returnValue(null)
        );
        $this->assertEquals(true, $this->_model->validateAdminUserAction());
    }

    /**
     * Test validation admin user with not limited id
     */
    public function testValidateAdminUserActionWithNotLimitedId()
    {
        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'getParam'
        )->with(
            $this->equalTo('user_id')
        )->will(
            $this->returnValue(1)
        );

        $this->collectionsMock->expects(
            $this->any()
        )->method(
            'getUsersOutsideLimitedScope'
        )->will(
            $this->returnValue([])
        );

        $this->collectionsFactoryMock->expects(
            $this->any()
        )->method(
            'create'
        )->will(
            $this->returnValue($this->collectionsMock)
        );

        $this->assertEquals(true, $this->_model->validateAdminUserAction());
    }

    /**
     * Test validation admin user with no limited id
     */
    public function testValidateAdminUserActionWithLimitedId()
    {
        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'getParam'
        )->with(
            $this->equalTo('user_id')
        )->will(
            $this->returnValue(1)
        );

        $this->collectionsMock->expects(
            $this->any()
        )->method(
            'getUsersOutsideLimitedScope'
        )->will(
            $this->returnValue([1])
        );

        $this->collectionsFactoryMock->expects(
            $this->any()
        )->method(
            'create'
        )->will(
            $this->returnValue($this->collectionsMock)
        );

        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'setActionName'
        )->with(
            $this->equalTo('denied')
        )->will(
            $this->returnSelf()
        );
        $this->_ctrlRequestMock->expects($this->once())->method('setDispatched')->with($this->equalTo(false));

        $this->assertEquals(false, $this->_model->validateAdminUserAction());
    }

    /**
     * Test validation admin role without params
     */
    public function testValidateAdminRoleActionWithoutId()
    {
        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'getParam'
        )->will(
            $this->returnValue(null)
        );

        $this->assertEquals(true, $this->_model->validateAdminRoleAction());
    }

    /**
     * Test validation admin role with not limited id
     */
    public function testValidateAdminRoleActionWithNotLimitedId()
    {
        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'getParam'
        )->will(
            $this->returnValue(1)
        );

        $this->collectionsMock->expects(
            $this->any()
        )->method(
            'getRolesOutsideLimitedScope'
        )->will(
            $this->returnValue([])
        );

        $this->collectionsFactoryMock->expects(
            $this->any()
        )->method(
            'create'
        )->will(
            $this->returnValue($this->collectionsMock)
        );

        $this->assertEquals(true, $this->_model->validateAdminRoleAction());
    }

    /**
     * Test validation admin role with limited id
     */
    public function testValidateAdminRoleActionWithLimitedId()
    {
        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'getParam'
        )->will(
            $this->returnValue(1)
        );

        $this->collectionsMock->expects(
            $this->any()
        )->method(
            'getRolesOutsideLimitedScope'
        )->will(
            $this->returnValue([1])
        );

        $this->collectionsFactoryMock->expects(
            $this->any()
        )->method(
            'create'
        )->will(
            $this->returnValue($this->collectionsMock)
        );

        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'setActionName'
        )->with(
            $this->equalTo(Ctrl::ACTION_DENIED)
        )->will(
            $this->returnSelf()
        );
        $this->_ctrlRequestMock->expects($this->once())->method('setDispatched')->with($this->equalTo(false));

        $this->assertEquals(false, $this->_model->validateAdminRoleAction());
    }

    /**
     * Test validation rma attribute delete action
     */
    public function testValidateRmaAttributeDeleteAction()
    {
        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'setActionName'
        )->with(
            $this->equalTo(Ctrl::ACTION_DENIED)
        )->will(
            $this->returnSelf()
        );
        $this->_ctrlRequestMock->expects($this->once())->method('setDispatched')->with($this->equalTo(false));

        $this->assertEquals(false, $this->_model->validateRmaAttributeDeleteAction());
    }

    /**
     * Test validation rma attribute save and edit action
     */
    public function testValidateRmaAttributeSaveAction()
    {
        $websiteId = 1;

        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getPost'
        )->with(
            $this->equalTo('option')
        )->will(
            $this->returnValue(['delete' => '1'])
        );

        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'setPostValue'
        )->with(
            $this->equalTo('option')
        );

        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getParam'
        )->with(
            $this->equalTo('website')
        )->will(
            $this->returnValue($websiteId)
        );

        $websiteMock = $this->getMockBuilder(\Magento\Store\Model\Website::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();
        $websiteMock->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($websiteId));

        $this->_storeManagerMock->expects($this->any())
            ->method('getWebsite')
            ->will($this->returnValue($websiteMock));

        $this->_roleMock->expects($this->any())
            ->method('hasWebsiteAccess')
            ->will($this->returnValue(true));

        $this->assertEquals(true, $this->_model->validateRmaAttributeSaveAction());
    }

    /**
     * Test validation rma attribute save and edit action without website access
     */
    public function testValidateRmaAttributeSaveActionNoWebsiteAccess()
    {
        $websiteId = 1;

        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getPost'
        )->with(
            $this->equalTo('option')
        )->will(
            $this->returnValue([])
        );

        $this->_ctrlRequestMock->expects(
            $this->never()
        )->method(
            'setPostValue'
        )->with(
            $this->equalTo('option')
        );

        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getParam'
        )->with(
            $this->equalTo('website')
        )->will(
            $this->returnValue($websiteId)
        );

        $websiteMock = $this->getMockBuilder(\Magento\Store\Model\Website::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();
        $websiteMock->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($websiteId));

        $this->_storeManagerMock->expects(
            $this->any()
        )->method(
            'getWebsite'
        )->will(
            $this->returnValue($websiteMock)
        );

        $this->_roleMock->expects(
            $this->any()
        )->method(
            'hasWebsiteAccess'
        )->will(
            $this->returnValue(false)
        );

        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'setActionName'
        )->with(
            $this->equalTo(Ctrl::ACTION_DENIED)
        )->will(
            $this->returnSelf()
        );
        $this->_ctrlRequestMock->expects($this->once())->method('setDispatched')->with($this->equalTo(false));

        $this->assertEquals(false, $this->_model->validateRmaAttributeSaveAction());
    }

    /**
     * Test validation rma attribute save and edit action with no allowed websites and no website id
     */
    public function testValidateRmaAttributeSaveActionNoWebsiteCodeAndNoAllowedWebsites()
    {
        $websiteId = null;
        $allowedWebsiteIds = [];

        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getPost'
        )->with(
            $this->equalTo('option')
        )->will(
            $this->returnValue([])
        );

        $this->_ctrlRequestMock->expects(
            $this->never()
        )->method(
            'setPostValue'
        )->with(
            $this->equalTo('option')
        );

        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getParam'
        )->with(
            $this->equalTo('website')
        )->will(
            $this->returnValue($websiteId)
        );

        $this->_roleMock->expects(
            $this->any()
        )->method(
            'getWebsiteIds'
        )->will(
            $this->returnValue($allowedWebsiteIds)
        );

        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'setActionName'
        )->with(
            $this->equalTo('denied')
        )->will(
            $this->returnSelf()
        );
        $this->_ctrlRequestMock->expects($this->once())->method('setDispatched')->with($this->equalTo(false));

        $this->assertEquals(false, $this->_model->validateRmaAttributeSaveAction());
    }

    /**
     * Test validation rma attribute save and edit action with no allowed websites and no website id
     */
    public function testValidateRmaAttributeSaveActionRedirectToAllowedWebsites()
    {
        $websiteId = null;
        $allowedWebsiteIds = [2];

        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getPost'
        )->with(
            $this->equalTo('option')
        )->will(
            $this->returnValue([])
        );

        $this->_ctrlRequestMock->expects(
            $this->never()
        )->method(
            'setPostValue'
        )->with(
            $this->equalTo('option')
        );

        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getParam'
        )->with(
            $this->equalTo('website')
        )->will(
            $this->returnValue($websiteId)
        );

        $this->_roleMock->expects(
            $this->any()
        )->method(
            'getWebsiteIds'
        )->will(
            $this->returnValue($allowedWebsiteIds)
        );

        $this->responseMock->expects(
            $this->once()
        )->method(
            'setRedirect'
        );
        $this->assertEquals(false, $this->_model->validateRmaAttributeSaveAction());
    }

    /**
     * @param $isWebSiteLevel
     * @param $action
     * @param $id
     * @param $expectedInvoke
     * @dataProvider validateGiftCardAccountDataProvider
     */
    public function testValidateGiftCardAccount($isWebSiteLevel, $action, $id, $expectedInvoke)
    {
        $controllerMock = $this->createPartialMock(
            \Magento\GiftCardAccount\Controller\Adminhtml\Giftcardaccount\Index::class,
            ['setShowCodePoolStatusMessage']
        );

        $this->_roleMock->expects(
            $this->once()
        )->method(
            'getIsWebsiteLevel'
        )->will(
            $this->returnValue($isWebSiteLevel)
        );

        $this->_ctrlRequestMock->expects($this->any())->method('getActionName')->will($this->returnValue($action));

        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'getParam'
        )->with(
            $this->equalTo('id')
        )->will(
            $this->returnValue($id)
        );

        $this->expectsForward($expectedInvoke);
        $this->_model->validateGiftCardAccount($controllerMock);
    }

    /**
     * Data provider for testValidateCmsHierarchyAction()
     *
     * @return array
     */
    public function validateGiftCardAccountDataProvider()
    {
        return [
            'WithWebsiteLevelPermissions' => [
                'isWebSiteLevel' => true,
                'action' => '',
                'id' => null,
                'expectedInvoke' => $this->never()
            ],
            'WithoutWebsiteLevelPermissionsActionNew' => [
                'isWebSiteLevel' => false,
                'action' => Ctrl::ACTION_NEW,
                'id' => null,
                'expectedInvoke' => $this->atLeastOnce(),
            ],
            'WithoutWebsiteLevelPermissionsActionGenerate' => [
                'isWebSiteLevel' => false,
                'action' => Ctrl::ACTION_GENERATE,
                'id' => null,
                'expectedInvoke' => $this->atLeastOnce(),
            ],
            'WithoutWebsiteLevelPermissionsActionEditWithoutId' => [
                'isWebSiteLevel' => false,
                'action' => Ctrl::ACTION_EDIT,
                'id' => null,
                'expectedInvoke' => $this->atLeastOnce(),
            ],
            'WithoutWebsiteLevelPermissionsActionEdit' => [
                'isWebSiteLevel' => false,
                'action' => Ctrl::ACTION_EDIT,
                'id' => 1,
                'expectedInvoke' => $this->never(),
            ],
            'WithoutWebsiteLevelPermissionsActionNewCamelCaseActionName' => [
                'isWebSiteLevel' => false,
                'action' => 'NeW',
                'id' => null,
                'expectedInvoke' => $this->atLeastOnce(),
            ],
        ];
    }

    /**
     * @param $id
     * @param $websiteId
     * @param $roleWebsiteIds
     * @param $expectedInvoke
     * @param $expectedForwardInvoke
     * @param $expectedValue
     * @dataProvider validateGiftregistryEntityActionDataProvider
     */
    public function testValidateGiftregistryEntityAction(
        $id,
        $websiteId,
        $roleWebsiteIds,
        $expectedInvoke,
        $expectedForwardInvoke,
        $expectedValue
    ) {
        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'getParam'
        )->will(
            $this->returnValue($id)
        );

        $recourceEntityMock = $this->createPartialMock(
            \Magento\GiftRegistry\Model\ResourceModel\Entity::class,
            ['getWebsiteIdByEntityId']
        );

        $modelEntityMock = $this->createPartialMock(
            \Magento\GiftRegistry\Model\Entity::class,
            ['getResource']
        );

        $modelEntityMock->expects(
            $expectedInvoke
        )->method(
            'getResource'
        )->will(
            $this->returnValue($recourceEntityMock)
        );

        $recourceEntityMock->expects(
            $expectedInvoke
        )->method(
            'getWebsiteIdByEntityId'
        )->with(
            $this->equalTo($id)
        )->will(
            $this->returnValue($websiteId)
        );

        $this->_objectManager->expects(
            $expectedInvoke
        )->method(
            'create'
        )->with(
            $this->equalTo(\Magento\GiftRegistry\Model\Entity::class)
        )->will(
            $this->returnValue($modelEntityMock)
        );

        $this->_roleMock->expects(
            $expectedInvoke
        )->method(
            'getWebsiteIds'
        )->will(
            $this->returnValue($roleWebsiteIds)
        );

        $this->expectsForward($expectedForwardInvoke);
        $this->assertEquals($expectedValue, $this->_model->validateGiftregistryEntityAction());
    }

    /**
     * Data provider for testValidateGiftregistryEntityAction()
     *
     * @return array
     */
    public function validateGiftregistryEntityActionDataProvider()
    {
        $id = 1;
        $websiteId = 1;
        return [
            'withoutId' => [
                'id' => null,
                'websiteId' => null,
                'roleWebsiteIds' => [],
                'expectedInvoke' => $this->never(),
                'expectedForwardInvoke' => $this->any(),
                'expectedValue' => false
            ],
            'withIdNotInRoleIds' => [
                'id' => $id,
                'websiteId' => $websiteId,
                'roleWebsiteIds' => [],
                'expectedInvoke' => $this->any(),
                'expectedForwardInvoke' => $this->any(),
                'expectedValue' => false
            ],
            'withIdInRoleIds' => [
                'id' => $id,
                'websiteId' => $websiteId,
                'roleWebsiteIds' => [$websiteId],
                'expectedInvoke' => $this->any(),
                'expectedForwardInvoke' => $this->never(),
                'expectedValue' => true
            ],
        ];
    }

    /**
     * @param $actionName
     * @param $attributeId
     * @param $websiteId
     * @param $hasWebsiteAccess
     * @param $expectedForwardInvoke
     * @param $expectedValue
     * @dataProvider validateCustomerAttributeActionsDataProvider
     */
    public function testValidateCustomerAttributeActions(
        $actionName,
        $attributeId,
        $websiteId,
        $hasWebsiteAccess,
        $expectedForwardInvoke,
        $expectedValue
    ) {
        $this->_ctrlRequestMock->expects($this->at(0))->method('getActionName')->will($this->returnValue($actionName));

        $this->_ctrlRequestMock->expects(
            $this->at(1)
        )->method(
            'getParam'
        )->with(
            $this->equalTo('attribute_id')
        )->will(
            $this->returnValue($attributeId)
        );
        $this->_ctrlRequestMock->expects(
            $this->at(2)
        )->method(
            'getParam'
        )->with(
            $this->equalTo('website')
        )->will(
            $this->returnValue($websiteId)
        );

        $this->_roleMock->expects(
            $this->any()
        )->method(
            'hasWebsiteAccess'
        )->will(
            $this->returnValue($hasWebsiteAccess)
        );

        $this->expectsForward($expectedForwardInvoke);
        $this->assertEquals($expectedValue, $this->_model->validateCustomerAttributeActions());
    }

    /**
     * Data provider for testValidateCustomerAttributeActions()
     *
     * @return array
     */
    public function validateCustomerAttributeActionsDataProvider()
    {
        return [
            'actionNew' => [
                'actionName' => Ctrl::ACTION_NEW,
                'attributeId' => 1,
                'websiteId' => null,
                'hasWebsiteAccess' => false,
                'expectedForwardInvoke' => $this->once(),
                'expectedValue' => false,
            ],
            'actionDelete' => [
                'actionName' => Ctrl::ACTION_DELETE,
                'attributeId' => 1,
                'websiteId' => null,
                'hasWebsiteAccess' => false,
                'expectedForwardInvoke' => $this->once(),
                'expectedValue' => false,
            ],
            'actionEdit' => [
                'actionName' => Ctrl::ACTION_EDIT,
                'attributeId' => null,
                'websiteId' => null,
                'hasWebsiteAccess' => false,
                'expectedForwardInvoke' => $this->once(),
                'expectedValue' => false,
            ],
            'actionSave' => [
                'actionName' => Ctrl::ACTION_SAVE,
                'attributeId' => null,
                'websiteId' => null,
                'hasWebsiteAccess' => false,
                'expectedForwardInvoke' => $this->once(),
                'expectedValue' => false,
            ],
            'actionEditWithAttributeId' => [
                'actionName' => Ctrl::ACTION_EDIT,
                'attributeId' => 1,
                'websiteId' => null,
                'hasWebsiteAccess' => false,
                'expectedForwardInvoke' => $this->never(),
                'expectedValue' => true,
            ],
            'actionDoesntMatterWithoutWebAccess' => [
                'actionName' => 'DoesntMatter',
                'attributeId' => null,
                'websiteId' => 1,
                'hasWebsiteAccess' => false,
                'expectedForwardInvoke' => $this->once(),
                'expectedValue' => false,
            ],
        ];
    }

    /**
     * @param $id
     * @param $websiteId
     * @param $roleWebsiteIds
     * @param $expectedForwardInvoke
     * @dataProvider validateCustomerEditDataProvider
     */
    public function testValidateCustomerEdit($id, $websiteId, $roleWebsiteIds, $expectedForwardInvoke)
    {
        $this->expectsCustomerAction($id, $websiteId, $roleWebsiteIds, $expectedForwardInvoke);
        $this->_model->validateCustomerEdit();
    }

    /**
     * @param $id
     * @param $websiteId
     * @param $roleWebsiteIds
     * @param $expectedForwardInvoke
     * @dataProvider validateCustomerbalanceDataProvider
     */
    public function testValidateCustomerbalance($id, $websiteId, $roleWebsiteIds, $expectedForwardInvoke)
    {
        $this->expectsCustomerAction($id, $websiteId, $roleWebsiteIds, $expectedForwardInvoke);
        $this->_model->validateCustomerbalance();
    }

    /**
     * Data provider for testValidateCustomer()
     *
     * @return array
     */
    public function validateCustomerEditDataProvider()
    {
        $id = 1;
        $websiteId = 1;
        return [
            'customerWithoutId' => [
                'id' => null,
                'websiteId' => null,
                'roleWebsiteIds' => [],
                'expectedForwardInvoke' => $this->never(),
            ],
            'customerHasNoRole' => [
                'id' => $id,
                'websiteId' => $websiteId,
                'roleWebsiteIds' => [],
                'expectedForwardInvoke' => $this->once(),
            ],
            'customerHasRole' => [
                'id' => $id,
                'websiteId' => $websiteId,
                'roleWebsiteIds' => [$websiteId],
                'expectedForwardInvoke' => $this->never(),
            ],
        ];
    }

    /**
     * Data provider for testValidateCustomerbalance()
     *
     * @return array
     */
    public function validateCustomerbalanceDataProvider()
    {
        $id = 1;
        $websiteId = 1;
        return [
            'customerWithoutId' => [
                'id' => null,
                'websiteId' => null,
                'roleWebsiteIds' => [],
                'expectedForwardInvoke' => $this->once(),
            ],
            'customerHasNoRole' => [
                'id' => $id,
                'websiteId' => $websiteId,
                'roleWebsiteIds' => [],
                'expectedForwardInvoke' => $this->once(),
            ],
            'customerHasRole' => [
                'id' => $id,
                'websiteId' => $websiteId,
                'roleWebsiteIds' => [$websiteId],
                'expectedForwardInvoke' => $this->never(),
            ],
        ];
    }

    /**
     * @param $hasStoreAccess
     * @param $expectedForwardInvoke
     * @dataProvider validateCatalogProductMassActionsDataProvider
     */
    public function testValidateCatalogProductMassActions($hasStoreAccess, $expectedForwardInvoke)
    {
        $storeId = 1;
        $storeMock = $this->createPartialMock(\Magento\Store\Model\Store::class, ['getId']);
        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'getParam'
        )->with(
            $this->equalTo('store')
        )->will(
            $this->returnValue($storeId)
        );
        $this->_storeManagerMock->expects($this->any())->method('getStore')->will($this->returnValue($storeMock));

        $storeMock->expects($this->any())->method('getId')->will($this->returnValue($storeId));

        $this->_roleMock->expects(
            $this->any()
        )->method(
            'hasStoreAccess'
        )->will(
            $this->returnValue($hasStoreAccess)
        );

        $this->expectsForward($expectedForwardInvoke);
        $this->_model->validateCatalogProductMassActions();
    }

    /**
     * Data provider for testValidateCatalogProductMassActions()
     *
     * @return array
     */
    public function validateCatalogProductMassActionsDataProvider()
    {
        return [
            'hasStoreAccess' => [
                'hasStoreAccess' => true,
                'expectedForwardInvoke' => $this->never(),
            ],
            'hasNoStoreAccess' => [
                'hasStoreAccess' => false,
                'expectedForwardInvoke' => $this->once(),
            ]
        ];
    }

    /**
     * @param $isGetAll
     * @param $expectedForwardInvoke
     * @param $expectedValue
     * @dataProvider validateCatalogProductAttributeActionsDataProvider
     */
    public function testValidateCatalogProductAttributeActions($isGetAll, $expectedForwardInvoke, $expectedValue)
    {
        $this->_roleMock->expects($this->any())->method('getIsAll')->will($this->returnValue($isGetAll));
        $this->expectsForward($expectedForwardInvoke);
        $this->assertEquals($expectedValue, $this->_model->validateCatalogProductAttributeActions());
    }

    /**
     * Data provider for testValidateCatalogProductAttributeActions()
     *
     * @return array
     */
    public function validateCatalogProductAttributeActionsDataProvider()
    {
        return [
            'permissionsAreAllowed' => [
                'isAll' => true,
                'expectedForwardInvoke' => $this->never(),
                'expectedValue' => true,
            ],
            'permissionsAreNotAllowed' => [
                'isAll' => false,
                'expectedForwardInvoke' => $this->once(),
                'expectedValue' => false,
            ],
        ];
    }

    /**
     * @param $isGetAll
     * @param $attributeId
     * @param $expectedForwardInvoke
     * @param $expectedValue
     * @dataProvider validateCatalogProductAttributeCreateActionDataProvider
     */
    public function testValidateCatalogProductAttributeCreateAction(
        $isGetAll,
        $attributeId,
        $expectedForwardInvoke,
        $expectedValue
    ) {
        $this->_roleMock->expects($this->any())->method('getIsAll')->will($this->returnValue($isGetAll));
        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'getParam'
        )->with(
            $this->equalTo('attribute_id')
        )->will(
            $this->returnValue($attributeId)
        );
        $this->expectsForward($expectedForwardInvoke);
        $this->assertEquals($expectedValue, $this->_model->validateCatalogProductAttributeCreateAction());
    }

    /**
     * Data provider for testValidateCatalogProductAttributeCreateAction()
     *
     * @return array
     */
    public function validateCatalogProductAttributeCreateActionDataProvider()
    {
        $attributeId = 1;
        return [
            'permissionsAreAllowedAndAttributeIdIsSet' => [
                'isAll' => true,
                'attributeId' => $attributeId,
                'expectedForwardInvoke' => $this->never(),
                'expectedValue' => true,
            ],
            'permissionsAreAllowedAndAttributeIdIsNotSet' => [
                'isAll' => true,
                'attributeId' => null,
                'expectedForwardInvoke' => $this->never(),
                'expectedValue' => true,
            ],
            'permissionsAreNotAllowedAndAttributeIdIsSet' => [
                'isAll' => false,
                'attributeId' => $attributeId,
                'expectedForwardInvoke' => $this->never(),
                'expectedValue' => true,
            ],
            'permissionsAreNotAllowedAndAttributeIdIsNotSet' => [
                'isAll' => false,
                'attributeId' => null,
                'expectedForwardInvoke' => $this->once(),
                'expectedValue' => false,
            ],
        ];
    }

    /**
     * @param $reviewId
     * @param $reviewStoreIds
     * @param $storeIds
     * @param $expectedRedirectInvoke
     * @dataProvider validateCatalogProductReviewDataProvider
     */
    public function testValidateCatalogProductReview($reviewId, $reviewStoreIds, $storeIds, $expectedRedirectInvoke)
    {
        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'getParam'
        )->with(
            $this->equalTo('id')
        )->will(
            $this->returnValue($reviewId)
        );

        $reviewMock = $this->createPartialMock(
            \Magento\Review\Model\Review::class,
            ['load', 'getStores']
        );

        $reviewMock->expects($this->once())->method('load')->will($this->returnSelf());

        $reviewMock->expects(
            $this->once()
        )->method(
            'getStores'
        )->will(
            $this->returnValue($reviewStoreIds)
        );

        $this->_objectManager->expects(
            $this->once()
        )->method(
            'create'
        )->with(
            $this->equalTo(\Magento\Review\Model\Review::class)
        )->will(
            $this->returnValue($reviewMock)
        );

        $this->_roleMock->expects($this->any())->method('getStoreIds')->will($this->returnValue($storeIds));

        $this->expectsRedirect($expectedRedirectInvoke);
        $this->_model->validateCatalogProductReview();
    }

    /**
     * Data provider for testValidateCatalogProductReview()
     *
     * @return array
     */
    public function validateCatalogProductReviewDataProvider()
    {
        $reviewId = 1;
        return [
            'allowIfReviewHasAccess' => [
                'reviewId' => $reviewId,
                'reviewStoreIds' => [1],
                'storeIds' => [1, 2, 3],
                'expectedRedirectInvoke' => $this->never(),
            ],
            'redirectIfReviewHasNoAccess' => [
                'reviewId' => $reviewId,
                'reviewStoreIds' => [1],
                'storeIds' => [2, 3],
                'expectedRedirectInvoke' => $this->once(),
            ]
        ];
    }

    /**
     * @param $storeId
     * @param $hasStoreAccess
     * @param $expectedRedirectInvoke
     * @dataProvider validateCatalogProductEditDataProvider
     */
    public function testValidateCatalogProductEdit($storeId, $hasStoreAccess, $expectedRedirectInvoke)
    {
        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'getParam'
        )->will(
            $this->returnValue($storeId)
        );

        $storeMock = $this->createPartialMock(\Magento\Store\Model\Store::class, ['getId']);
        $storeMock->expects($this->any())->method('getId')->will($this->returnValue($storeId));

        $this->_storeManagerMock->expects(
            $this->any()
        )->method(
            'getStore'
        )->will(
            $this->returnValue($storeMock)
        );

        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->will($this->returnValue($hasStoreAccess));
        $this->expectsRedirect($expectedRedirectInvoke);
        $this->_model->validateCatalogProductEdit();
    }

    /**
     * Data provider for testValidateCatalogProductEditData()
     *
     * @return array
     */
    public function validateCatalogProductEditDataProvider()
    {
        $storeId = 1;
        return [
            'allowStoreInRequestWhenStoreIdIsEmpty' => [
                'storeId' => null,
                'hasStoreAccess' => false,
                'expectedRedirectInvoke' => $this->never(),
            ],
            'allowIfHasStoreAccess' => [
                'storeId' => $storeId,
                'hasStoreAccess' => true,
                'expectedRedirectInvoke' => $this->never(),
            ],
            'redirectIfNoStoreAcces' => [
                'storeId' => $storeId,
                'hasStoreAccess' => false,
                'expectedRedirectInvoke' => $this->once(),
            ],
        ];
    }

    /**
     * @param $actionName
     * @param $categoryId
     * @param $isWebsiteLevel
     * @param $allowedRootCategories
     * @param $categoryPath
     * @param $expectedForwardInvoke
     * @dataProvider validateCatalogEventsDataProvider
     */
    public function testValidateCatalogEvents(
        $actionName,
        $categoryId,
        $isWebsiteLevel,
        $allowedRootCategories,
        $categoryPath,
        $expectedForwardInvoke
    ) {
        $this->_ctrlRequestMock->expects($this->any())->method('getActionName')->willReturn($actionName);
        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'getParam'
        )->with(
            'category_id'
        )->willReturn(
            $categoryId
        );

        $categoryMock = $this->getMockForAbstractClass(
            \Magento\Catalog\Api\Data\CategoryInterface::class,
            [],
            '',
            false
        );
        $categoryMock->expects($this->any())->method('getPath')->willReturn($categoryPath);
        $this->categoryRepositoryMock->expects($this->any())->method('get')->will($this->returnValue($categoryMock));

        $this->_roleMock->expects($this->any())->method('getIsWebsiteLevel')->willReturn($isWebsiteLevel);
        $this->_roleMock->expects($this->any())->method('getAllowedRootCategories')->willReturn($allowedRootCategories);

        $this->expectsForward($expectedForwardInvoke);
        $this->_model->validateCatalogEvents();
    }

    /**
     * Test exception in validateCatalogEvents
     */
    public function testValidateCatalogEventsException()
    {
        $this->_ctrlRequestMock->expects($this->any())->method('getActionName')->willReturn(Ctrl::ACTION_NEW);
        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->with('category_id')->willReturn(1);
        $this->categoryRepositoryMock->expects(
            $this->any()
        )->method(
            'get'
        )->willThrowException(
            new NoSuchEntityException
        );
        $this->expectsForward($this->once());
        $this->_model->validateCatalogEvents();
    }

    /**
     * Data provider for testValidateCatalogEvents()
     *
     * @return array
     */
    public function validateCatalogEventsDataProvider()
    {
        return [
            'allowIfActionNameIsNotNew' => [
                'actionName' => Ctrl::ACTION_EDIT,
                'categoryId' => null,
                'isWebsiteLevel' => null,
                'allowedRootCategories' => null,
                'categoryPath' => null,
                'expectedForwardInvoke' => $this->never(),
            ],
            'forwardIfActionNameIsNewWithoutCategory' => [
                'actionName' => Ctrl::ACTION_NEW,
                'categoryId' => null,
                'isWebsiteLevel' => null,
                'allowedRootCategories' => null,
                'categoryPath' => null,
                'expectedForwardInvoke' => $this->once(),
            ],
            'forwardIfActionNameIsNewWithCategoryAndWithoutWebsiteLevelAndWithoutAllowedCategory' => [
                'actionName' => Ctrl::ACTION_NEW,
                'categoryId' => 1,
                'isWebsiteLevel' => false,
                'allowedRootCategories' => ['testCategory1'],
                'categoryPath' => 'testCategory2',
                'expectedForwardInvoke' => $this->once(),
            ],
            'allowIfActionNameIsNewWithCategoryAndAccess' => [
                'actionName' => Ctrl::ACTION_NEW,
                'categoryId' => 1,
                'isWebsiteLevel' => true,
                'allowedRootCategories' => ['testCategory1'],
                'categoryPath' => 'testCategory1',
                'expectedForwardInvoke' => $this->never(),
            ],
        ];
    }

    /**
     * @param $id
     * @param $isWebsiteLevel
     * @param $allowedRootCategories
     * @param $categoryPath
     * @param $hasStoreAccess
     * @param $expectedForwardInvoke
     * @param $expectedRedirectInvoke
     * @dataProvider validateCatalogEventEditDataProvider
     */
    public function testValidateCatalogEventEdit(
        $id,
        $isWebsiteLevel,
        $allowedRootCategories,
        $categoryPath,
        $hasStoreAccess,
        $expectedForwardInvoke,
        $expectedRedirectInvoke
    ) {

        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->willReturn($id);
        $this->_roleMock->expects($this->any())->method('getIsWebsiteLevel')->willReturn($isWebsiteLevel);

        $catalogEventMock = $this->createPartialMock(
            \Magento\CatalogEvent\Model\Event::class,
            ['load', 'getCategoryId', 'getId']
        );
        $catalogEventMock->expects($this->any())->method('load')->willReturnSelf();
        $catalogEventMock->expects($this->any())->method('getCategoryId')->willReturn(1);
        $catalogEventMock->expects($this->any())->method('getId')->willReturn(1);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            \Magento\CatalogEvent\Model\Event::class
        )->willReturn(
            $catalogEventMock
        );

        $categoryMock = $this->getMockForAbstractClass(
            \Magento\Catalog\Api\Data\CategoryInterface::class,
            [],
            '',
            false
        );
        $categoryMock->expects($this->any())->method('getPath')->willReturn($categoryPath);
        $this->categoryRepositoryMock->expects($this->any())->method('get')->will($this->returnValue($categoryMock));
        $this->_roleMock->expects($this->any())->method('getAllowedRootCategories')->willReturn($allowedRootCategories);

        $storeMock = $this->createPartialMock(\Magento\Store\Model\Store::class, ['getId']);
        $storeMock->expects($this->any())->method('getId')->will($this->returnValue(1));

        $this->_storeManagerMock->expects($this->any())->method('getStore')->will($this->returnValue($storeMock));
        $this->_storeManagerMock->expects(
            $this->any()
        )->method(
            'getDefaultStoreView'
        )->will(
            $this->returnValue($storeMock)
        );
        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->will($this->returnValue($hasStoreAccess));
        $this->expectsForward($expectedForwardInvoke);
        $this->expectsRedirect($expectedRedirectInvoke);
        $this->_model->validateCatalogEventEdit();
    }

    /**
     * Test exception in validateCatalogEvents
     */
    public function testValidateCatalogEventEditException()
    {
        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->willReturn(1);
        $this->_roleMock->expects($this->any())->method('getIsWebsiteLevel')->willReturn(true);

        $catalogEventMock = $this->createPartialMock(
            \Magento\CatalogEvent\Model\Event::class,
            ['load', 'getCategoryId']
        );
        $catalogEventMock->expects($this->any())->method('load')->willReturnSelf();
        $catalogEventMock->expects($this->any())->method('getCategoryId')->willReturn(1);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            \Magento\CatalogEvent\Model\Event::class
        )->willReturn(
            $catalogEventMock
        );

        $this->categoryRepositoryMock->expects(
            $this->any()
        )->method(
            'get'
        )->willThrowException(
            new NoSuchEntityException
        );
        $this->expectsForward($this->once());
        $this->_model->validateCatalogEventEdit();
    }

    /**
     * Data provider for testValidateCatalogEvents()
     *
     * @return array
     */
    public function validateCatalogEventEditDataProvider()
    {
        $storeId = 1;
        return [
            'allow' => [
                'id' => null,
                'isWebsiteLevel' => true,
                'allowedRootCategories' => null,
                'categoryPath' => null,
                'hasStoreAccess' => null,
                'expectedForwardInvoke' => $this->never(),
                'expectedRedirectInvoke' => $this->never(),
            ],
            'forwardIfCategoryNotAllowed' => [
                'id' => $storeId,
                'isWebsiteLevel' => true,
                'allowedRootCategories' => ['testCategory2'],
                'categoryPath' => 'testCategory1',
                'hasStoreAccess' => null,
                'expectedForwardInvoke' => $this->once(),
                'expectedRedirectInvoke' => $this->never(),
            ],
            'redirectIfCategoryAllowedButStoreInRequestNotAllowed' => [
                'id' => $storeId,
                'isWebsiteLevel' => true,
                'allowedRootCategories' => ['testCategory1'],
                'categoryPath' => 'testCategory1',
                'hasStoreAccess' => false,
                'expectedForwardInvoke' => $this->never(),
                'expectedRedirectInvoke' => $this->once(),
            ],
            'allowIfCategoryAllowedAndStoreInRequestAllowed' => [
                'id' => $storeId,
                'isWebsiteLevel' => true,
                'allowedRootCategories' => ['testCategory1'],
                'categoryPath' => 'testCategory1',
                'hasStoreAccess' => true,
                'expectedForwardInvoke' => $this->never(),
                'expectedRedirectInvoke' => $this->never(),
            ],
        ];
    }

    /**
     * @param $actionName
     * @param $parentId
     * @param $categoryPath
     * @param $allowedRootCategories
     * @param $exclusiveCategoryAccess
     * @param $expectedForwardInvoke
     * @dataProvider validateCatalogCategoriesAddDataProvider
     */
    public function testValidateCatalogCategoriesAdd(
        $actionName,
        $parentId,
        $categoryPath,
        $allowedRootCategories,
        $exclusiveCategoryAccess,
        $expectedForwardInvoke
    ) {
        $this->_ctrlRequestMock->expects($this->any())->method('getActionName')->willReturn($actionName);
        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->with('parent')->willReturn($parentId);

        $categoryMock = $this->getMockForAbstractClass(
            \Magento\Catalog\Api\Data\CategoryInterface::class,
            [],
            '',
            false
        );
        $categoryMock->expects($this->any())->method('getPath')->willReturn($categoryPath);
        $this->categoryRepositoryMock->expects($this->any())->method('get')->willReturn($categoryMock);
        $this->_roleMock->expects($this->any())->method('getAllowedRootCategories')->willReturn($allowedRootCategories);
        $this->_roleMock->expects(
            $this->any()
        )->method(
            'hasExclusiveCategoryAccess'
        )->willReturn(
            $exclusiveCategoryAccess
        );
        $this->expectsForward($expectedForwardInvoke);
        $this->_model->validateCatalogCategories();
    }

    /**
     * @return array
     */
    public function validateCatalogCategoriesAddDataProvider()
    {
        return [
            'allowIfNotAddAndEdit' => [
                'actionName' => Ctrl::ACTION_DELETE,
                'parentId' => null,
                'categoryPath' => null,
                'allowedRootCategories' => null,
                'exclusiveCategoryAccess' => null,
                'expectedForwardInvoke' => $this->never()
            ],
            'allowIfAddAndHasPermission' => [
                'actionName' => Ctrl::ACTION_ADD,
                'parentId' => 1,
                'categoryPath' => 'testCategory1',
                'allowedRootCategories' => ['testCategory1'],
                'exclusiveCategoryAccess' => true,
                'expectedForwardInvoke' => $this->never()
            ],
            'forwardIfAddAndNoAllowedCategory' => [
                'actionName' => Ctrl::ACTION_ADD,
                'parentId' => 1,
                'categoryPath' => 'testCategory1',
                'allowedRootCategories' => ['testCategory2'],
                'exclusiveCategoryAccess' => true,
                'expectedForwardInvoke' => $this->once()
            ],
            'forwardIfAddAndNoExclusiveCategoryAccess' => [
                'actionName' => Ctrl::ACTION_ADD,
                'parentId' => 1,
                'categoryPath' => 'testCategory1',
                'allowedRootCategories' => ['testCategory1'],
                'exclusiveCategoryAccess' => false,
                'expectedForwardInvoke' => $this->once()
            ],
        ];
    }

    /**
     * @param $actionName
     * @param $parentId
     * @param $id
     * @param $categoryPath
     * @param $allowedRootCategories
     * @param $exclusiveCategoryAccess
     * @param $expectedForwardInvoke
     * @dataProvider validateCatalogCategoriesEditDataProvider
     */
    public function testValidateCatalogCategoriesEdit(
        $actionName,
        $parentId,
        $id,
        $categoryPath,
        $allowedRootCategories,
        $exclusiveCategoryAccess,
        $expectedForwardInvoke
    ) {
        $this->_ctrlRequestMock->expects($this->any())->method('getActionName')->willReturn($actionName);
        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'getParam'
        )->willReturnMap(
            [
                ['id', null, $id],
                ['parent', null, $parentId]
            ]
        );

        $categoryMock = $this->getMockForAbstractClass(
            \Magento\Catalog\Api\Data\CategoryInterface::class,
            [],
            '',
            false
        );
        $categoryMock->expects($this->any())->method('getPath')->willReturn($categoryPath);
        $this->categoryRepositoryMock->expects($this->any())->method('get')->willReturn($categoryMock);
        $this->_roleMock->expects($this->any())->method('getAllowedRootCategories')->willReturn($allowedRootCategories);
        $this->_roleMock->expects(
            $this->any()
        )->method(
            'hasExclusiveCategoryAccess'
        )->willReturn(
            $exclusiveCategoryAccess
        );
        $this->expectsForward($expectedForwardInvoke);
        $this->_model->validateCatalogCategories();
    }

    /**
     * test exception in validateCatalogCategories
     */
    public function testValidateCatalogCategoriesEditException()
    {
        $this->_ctrlRequestMock->expects($this->any())->method('getActionName')->willReturn(Ctrl::ACTION_EDIT);
        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->with('id')->willReturn(1);

        $this->categoryRepositoryMock->expects(
            $this->any()
        )->method(
            'get'
        )->willThrowException(
            new NoSuchEntityException
        );
        $this->_roleMock->expects($this->any())->method('getAllowedRootCategories')->willReturn([]);
        $this->expectsForward($this->once());
        $this->_model->validateCatalogCategories();
    }

    /**
     * @return array
     */
    public function validateCatalogCategoriesEditDataProvider()
    {
        return [
            'allowIfNotAddAndEdit' => [
                'actionName' => Ctrl::ACTION_DELETE,
                'parentId' => null,
                'id' => null,
                'categoryPath' => null,
                'allowedRootCategories' => null,
                'exclusiveCategoryAccess' => null,
                'expectedForwardInvoke' => $this->never()
            ],
            'allowIfEditAndHasPermissionAndNoId' => [
                'actionName' => Ctrl::ACTION_EDIT,
                'parentId' => 1,
                'id' => null,
                'categoryPath' => 'testCategory1',
                'allowedRootCategories' => ['testCategory1'],
                'exclusiveCategoryAccess' => true,
                'expectedForwardInvoke' => $this->never()
            ],
            'forwardIfEditAndNoAllowedCategoryAndNoId' => [
                'actionName' => Ctrl::ACTION_EDIT,
                'parentId' => 1,
                'id' => null,
                'categoryPath' => 'testCategory1',
                'allowedRootCategories' => ['testCategory2'],
                'exclusiveCategoryAccess' => true,
                'expectedForwardInvoke' => $this->once()
            ],
            'forwardIfEditAndNoExclusiveCategoryAccessAndNoId' => [
                'actionName' => Ctrl::ACTION_EDIT,
                'parentId' => 1,
                'id' => null,
                'categoryPath' => 'testCategory1',
                'allowedRootCategories' => ['testCategory1'],
                'exclusiveCategoryAccess' => false,
                'expectedForwardInvoke' => $this->once()
            ],
            'allowIfEditAndHasPermissionAndId' => [
                'actionName' => Ctrl::ACTION_EDIT,
                'parentId' => null,
                'id' => 1,
                'categoryPath' => 'testCategory1',
                'allowedRootCategories' => ['testCategory1'],
                'exclusiveCategoryAccess' => null,
                'expectedForwardInvoke' => $this->never()
            ],
            'forwardIfEditAndNoAllowedCategoryAndId' => [
                'actionName' => Ctrl::ACTION_EDIT,
                'parentId' => null,
                'id' => 1,
                'categoryPath' => 'testCategory1',
                'allowedRootCategories' => ['testCategory2'],
                'exclusiveCategoryAccess' => null,
                'expectedForwardInvoke' => $this->once()
            ],
        ];
    }

    /**
     * test validateSalesOrderCreation
     */
    public function testValidateSalesOrderCreation()
    {
        $this->_roleMock->expects($this->any())->method('getWebsiteIds')->willReturn([]);
        $this->expectsForward($this->once());
        $this->_model->validateSalesOrderCreation();
    }

    /**
     * test validateSalesOrderViewAction
     *
     * @dataProvider validateActionsDataProvider
     */
    public function testValidateSalesOrderViewAction($hasStoreAccess, $expectedForwardInvoke)
    {
        $salesOrderMock = $this->createPartialMock(
            \Magento\Sales\Model\Order::class,
            ['load', 'getStoreId', 'getId']
        );
        $salesOrderMock->expects($this->any())->method('load')->willReturnSelf();
        $salesOrderMock->expects($this->any())->method('getId')->willReturn(1);
        $salesOrderMock->expects($this->any())->method('getStoreId')->willReturn(1);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            \Magento\Sales\Model\Order::class
        )->willReturn(
            $salesOrderMock
        );

        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->with('order_id')->willReturn(1);
        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->willReturn($hasStoreAccess);
        $this->expectsForward($expectedForwardInvoke);
        $this->_model->validateSalesOrderViewAction();
    }

    /**
     * test validateSalesOrderCreditmemoViewAction
     *
     * @dataProvider validateActionsDataProvider
     */
    public function testValidateSalesOrderCreditmemoViewAction($hasStoreAccess, $expectedForwardInvoke)
    {
        $orderCreditmemoMock = $this->createPartialMock(
            \Magento\Sales\Model\Order\Creditmemo::class,
            ['load', 'getStoreId', 'getId']
        );
        $orderCreditmemoMock->expects($this->any())->method('load')->willReturnSelf();
        $orderCreditmemoMock->expects($this->any())->method('getId')->willReturn(1);
        $orderCreditmemoMock->expects($this->any())->method('getStoreId')->willReturn(1);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            \Magento\Sales\Model\Order\Creditmemo::class
        )->willReturn(
            $orderCreditmemoMock
        );

        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->with('creditmemo_id')->willReturn(1);
        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->willReturn($hasStoreAccess);
        $this->expectsForward($expectedForwardInvoke);
        $this->_model->validateSalesOrderCreditmemoViewAction();
    }

    /**
     * test validateSalesOrderInvoiceViewAction
     *
     * @dataProvider validateActionsDataProvider
     */
    public function testValidateSalesOrderInvoiceViewAction($hasStoreAccess, $expectedForwardInvoke)
    {
        $orderInvoiceMock = $this->createPartialMock(
            \Magento\Sales\Model\Order\Invoice::class,
            ['load', 'getStoreId', 'getId']
        );
        $orderInvoiceMock->expects($this->any())->method('load')->willReturnSelf();
        $orderInvoiceMock->expects($this->any())->method('getId')->willReturn(1);
        $orderInvoiceMock->expects($this->any())->method('getStoreId')->willReturn(1);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            \Magento\Sales\Model\Order\Invoice::class
        )->willReturn(
            $orderInvoiceMock
        );

        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->with('invoice_id')->willReturn(1);
        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->willReturn($hasStoreAccess);
        $this->expectsForward($expectedForwardInvoke);
        $this->_model->validateSalesOrderInvoiceViewAction();
    }

    /**
     * test validateSalesOrderShipmentViewAction
     *
     * @dataProvider validateActionsDataProvider
     */
    public function testValidateSalesOrderShipmentViewAction($hasStoreAccess, $expectedForwardInvoke)
    {
        $orderShipmentMock = $this->createPartialMock(
            \Magento\Sales\Model\Order\Shipment::class,
            ['load', 'getStoreId', 'getId']
        );
        $orderShipmentMock->expects($this->any())->method('load')->willReturnSelf();
        $orderShipmentMock->expects($this->any())->method('getId')->willReturn(1);
        $orderShipmentMock->expects($this->any())->method('getStoreId')->willReturn(1);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            \Magento\Sales\Model\Order\Shipment::class
        )->willReturn(
            $orderShipmentMock
        );

        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->with('shipment_id')->willReturn(1);
        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->willReturn($hasStoreAccess);
        $this->expectsForward($expectedForwardInvoke);
        $this->_model->validateSalesOrderShipmentViewAction();
    }

    /**
     * test validateSalesOrderCreditmemoCreateAction
     *
     * @dataProvider validateActionsDataProvider
     */
    public function testValidateSalesOrderCreditmemoCreateAction($hasStoreAccess, $expectedForwardInvoke)
    {
        $orderCreditmemoMock = $this->createPartialMock(
            \Magento\Sales\Model\Order\Creditmemo::class,
            ['load', 'getStoreId', 'getId']
        );
        $orderCreditmemoMock->expects($this->any())->method('load')->willReturnSelf();
        $orderCreditmemoMock->expects($this->any())->method('getId')->willReturn(1);
        $orderCreditmemoMock->expects($this->any())->method('getStoreId')->willReturn(1);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            \Magento\Sales\Model\Order\Creditmemo::class
        )->willReturn(
            $orderCreditmemoMock
        );

        $this->_ctrlRequestMock->expects($this->at(0))->method('getParam')->with('order_id')->willReturn(null);
        $this->_ctrlRequestMock->expects($this->at(1))->method('getParam')->with('invoice_id')->willReturn(null);
        $this->_ctrlRequestMock->expects($this->at(2))->method('getParam')->with('creditmemo_id')->willReturn(1);
        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->willReturn($hasStoreAccess);
        $this->expectsForward($expectedForwardInvoke);
        $this->_model->validateSalesOrderCreditmemoCreateAction();
    }

    /**
     * test validateSalesOrderInvoiceCreateAction
     *
     * @dataProvider validateActionsDataProvider
     */
    public function testValidateSalesOrderInvoiceCreateAction($hasStoreAccess, $expectedForwardInvoke)
    {
        $orderInvoiceMock = $this->createPartialMock(
            \Magento\Sales\Model\Order\Invoice::class,
            ['load', 'getStoreId', 'getId']
        );
        $orderInvoiceMock->expects($this->any())->method('load')->willReturnSelf();
        $orderInvoiceMock->expects($this->any())->method('getId')->willReturn(1);
        $orderInvoiceMock->expects($this->any())->method('getStoreId')->willReturn(1);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            \Magento\Sales\Model\Order\Invoice::class
        )->willReturn(
            $orderInvoiceMock
        );

        $this->_ctrlRequestMock->expects($this->at(0))->method('getParam')->with('order_id')->willReturn(null);
        $this->_ctrlRequestMock->expects($this->at(1))->method('getParam')->with('invoice_id')->willReturn(1);
        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->willReturn($hasStoreAccess);
        $this->expectsForward($expectedForwardInvoke);
        $this->_model->validateSalesOrderInvoiceCreateAction();
    }

    /**
     * test validateSalesOrderShipmentCreateAction
     *
     * @dataProvider validateActionsDataProvider
     */
    public function testValidateSalesOrderShipmentCreateAction($hasStoreAccess, $expectedForwardInvoke)
    {
        $orderShipmentMock = $this->createPartialMock(
            \Magento\Sales\Model\Order\Shipment::class,
            ['load', 'getStoreId', 'getId']
        );
        $orderShipmentMock->expects($this->any())->method('load')->willReturnSelf();
        $orderShipmentMock->expects($this->any())->method('getId')->willReturn(1);
        $orderShipmentMock->expects($this->any())->method('getStoreId')->willReturn(1);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            \Magento\Sales\Model\Order\Shipment::class
        )->willReturn(
            $orderShipmentMock
        );

        $this->_ctrlRequestMock->expects($this->at(0))->method('getParam')->with('order_id')->willReturn(null);
        $this->_ctrlRequestMock->expects($this->at(1))->method('getParam')->with('shipment_id')->willReturn(1);
        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->willReturn($hasStoreAccess);
        $this->expectsForward($expectedForwardInvoke);
        $this->_model->validateSalesOrderShipmentCreateAction();
    }

    /**
     * test validateSalesOrderMassAction
     *
     * @dataProvider validateActionsDataProvider
     */
    public function testValidateSalesOrderMassAction($hasStoreAccess, $expectedForwardInvoke)
    {
        $salesOrderMock = $this->createPartialMock(
            \Magento\Sales\Model\Order::class,
            ['load', 'getStoreId', 'getId']
        );
        $salesOrderMock->expects($this->any())->method('load')->willReturnSelf();
        $salesOrderMock->expects($this->any())->method('getId')->willReturn(1);
        $salesOrderMock->expects($this->any())->method('getStoreId')->willReturn(1);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            \Magento\Sales\Model\Order::class
        )->willReturn(
            $salesOrderMock
        );

        $this->_ctrlRequestMock->expects($this->any())->method('getParam')
            ->with('order_ids', [])
            ->willReturn([1, 2, 3]);
        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->willReturn($hasStoreAccess);
        $this->expectsForward($expectedForwardInvoke);
        $this->_model->validateSalesOrderMassAction();
    }

    /**
     * test validateSalesOrderEditStartAction
     *
     * @dataProvider validateActionsDataProvider
     */
    public function testValidateSalesOrderEditStartAction($hasStoreAccess, $expectedForwardInvoke)
    {
        $salesOrderMock = $this->createPartialMock(
            \Magento\Sales\Model\Order::class,
            ['load', 'getStoreId', 'getId']
        );
        $salesOrderMock->expects($this->any())->method('load')->willReturnSelf();
        $salesOrderMock->expects($this->any())->method('getId')->willReturn(1);
        $salesOrderMock->expects($this->any())->method('getStoreId')->willReturn(1);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            \Magento\Sales\Model\Order::class
        )->willReturn(
            $salesOrderMock
        );

        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->with('order_id')->willReturn(1);
        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->willReturn($hasStoreAccess);
        $this->expectsForward($expectedForwardInvoke);
        $this->_model->validateSalesOrderEditStartAction();
    }

    /**
     * test validateSalesOrderShipmentTrackAction with store access
     */
    public function testValidateSalesOrderShipmentTrackActionHasStoreAccess()
    {
        $orderShipmentTrackMock = $this->createPartialMock(
            \Magento\Sales\Model\Order\Shipment\Track::class,
            ['load', 'getStoreId', 'getId']
        );
        $orderShipmentTrackMock->expects($this->any())->method('load')->willReturnSelf();
        $orderShipmentTrackMock->expects($this->any())->method('getId')->willReturn(1);
        $orderShipmentTrackMock->expects($this->any())->method('getStoreId')->willReturn(1);

        $this->_objectManager->expects(
            $this->at(0)
        )->method(
            'create'
        )->with(
            \Magento\Sales\Model\Order\Shipment\Track::class
        )->willReturn(
            $orderShipmentTrackMock
        );

        $orderShipmentMock = $this->createPartialMock(
            \Magento\Sales\Model\Order\Shipment::class,
            ['load', 'getStoreId', 'getId']
        );
        $orderShipmentMock->expects($this->any())->method('load')->willReturnSelf();
        $orderShipmentMock->expects($this->any())->method('getId')->willReturn(1);
        $orderShipmentMock->expects($this->any())->method('getStoreId')->willReturn(1);

        $this->_objectManager->expects(
            $this->at(1)
        )->method(
            'create'
        )->with(
            \Magento\Sales\Model\Order\Shipment::class
        )->willReturn(
            $orderShipmentMock
        );

        $this->_ctrlRequestMock->expects($this->at(0))->method('getParam')->with('track_id')->willReturn(1);
        $this->_ctrlRequestMock->expects($this->at(1))->method('getParam')->with('order_id')->willReturn(null);
        $this->_ctrlRequestMock->expects($this->at(2))->method('getParam')->with('shipment_id')->willReturn(1);
        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->willReturn(true);
        $this->expectsForward($this->never());
        $this->_model->validateSalesOrderShipmentTrackAction();
    }

    /**
     * test validateSalesOrderShipmentTrackAction with no store access
     */
    public function testValidateSalesOrderShipmentTrackActionHasNoStoreAccess()
    {
        $orderShipmentTrackMock = $this->createPartialMock(
            \Magento\Sales\Model\Order\Shipment\Track::class,
            ['load', 'getStoreId', 'getId']
        );
        $orderShipmentTrackMock->expects($this->any())->method('load')->willReturnSelf();
        $orderShipmentTrackMock->expects($this->any())->method('getId')->willReturn(1);
        $orderShipmentTrackMock->expects($this->any())->method('getStoreId')->willReturn(1);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            \Magento\Sales\Model\Order\Shipment\Track::class
        )->willReturn(
            $orderShipmentTrackMock
        );

        $this->_ctrlRequestMock->expects($this->at(0))->method('getParam')->with('track_id')->willReturn(1);
        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->willReturn(false);
        $this->expectsForward($this->once());
        $this->_model->validateSalesOrderShipmentTrackAction();
    }

    /**
     * test validateCheckoutAgreementEditAction
     *
     * @dataProvider validateActionsDataProvider
     */
    public function testValidateCheckoutAgreementEditAction($hasStoreAccess, $expectedForwardInvoke)
    {
        $checkoutAgreementMock = $this->createPartialMock(
            \Magento\CheckoutAgreements\Model\Agreement::class,
            ['load', 'getStoreId', 'getId']
        );
        $checkoutAgreementMock->expects($this->any())->method('load')->willReturnSelf();
        $checkoutAgreementMock->expects($this->any())->method('getId')->willReturn(1);
        $checkoutAgreementMock->expects($this->any())->method('getStoreId')->willReturn([1]);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            \Magento\CheckoutAgreements\Model\Agreement::class
        )->willReturn(
            $checkoutAgreementMock
        );

        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->with('id')->willReturn(1);
        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->willReturn($hasStoreAccess);
        $this->expectsForward($expectedForwardInvoke);
        $this->_model->validateCheckoutAgreementEditAction();
    }

    /**
     * test validateUrlRewriteEditAction
     *
     * @dataProvider validateActionsDataProvider
     */
    public function testValidateUrlRewriteEditAction($hasStoreAccess, $expectedForwardInvoke)
    {
        $urlRewriteMock = $this->createPartialMock(
            \Magento\UrlRewrite\Model\UrlRewrite::class,
            ['load', 'getStoreId', 'getId']
        );
        $urlRewriteMock->expects($this->any())->method('load')->willReturnSelf();
        $urlRewriteMock->expects($this->any())->method('getId')->willReturn(1);
        $urlRewriteMock->expects($this->any())->method('getStoreId')->willReturn(1);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            \Magento\UrlRewrite\Model\UrlRewrite::class
        )->willReturn(
            $urlRewriteMock
        );

        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->with('id')->willReturn(1);
        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->willReturn($hasStoreAccess);
        $this->expectsForward($expectedForwardInvoke);
        $this->_model->validateUrlRewriteEditAction();
    }

    public function validateActionsDataProvider()
    {
        return [
            'hasStoreAccess' => [
                'hasStoreAccess' => true,
                'expectedForwardInvoke' => $this->never(),
            ],
            'hasNoStoreAccess' => [
                'hasStoreAccess' => false,
                'expectedForwardInvoke' => $this->once(),
            ]
        ];
    }

    /**
     * test validateAttributeSetActions
     */
    public function testValidateAttributeSetActions()
    {
        $this->expectsForward($this->once());
        $this->_model->validateAttributeSetActions();
    }

    /**
     * test validateManageCurrencyRates
     */
    public function testValidateManageCurrencyRates()
    {
        $this->_ctrlRequestMock->expects($this->any())->method('getActionName')->willReturn(Ctrl::ACTION_FETCH_RATES);
        $this->expectsForward($this->once());
        $this->_model->validateManageCurrencyRates();
    }

    /**
     * test validateTransactionalEmails
     */
    public function testValidateTransactionalEmails()
    {
        $this->_ctrlRequestMock->expects($this->any())->method('getActionName')->willReturn(Ctrl::ACTION_DELETE);
        $this->expectsForward($this->once());
        $this->_model->validateTransactionalEmails();
    }

    /**
     * test validatePromoCatalogApplyRules
     */
    public function testValidatePromoCatalogApplyRules()
    {
        $this->expectsForward($this->once());
        $this->_model->validatePromoCatalogApplyRules();
    }

    /**
     * test promoCatalogIndexAction
     */
    public function testPromoCatalogIndexAction()
    {
        $controllerMock = $this->createPartialMock(
            \Magento\Backend\Test\Unit\App\Action\Stub\ActionStub::class,
            ['setDirtyRulesNoticeMessage']
        );
        $this->assertEquals($this->_model, $this->_model->promoCatalogIndexAction($controllerMock));
    }

    /**
     * test validateNoWebsiteGeneric
     *
     * @dataProvider validateNoWebsiteGenericDataProvider
     */
    public function testValidateNoWebsiteGeneric(
        $denyActions,
        $saveAction,
        $idFieldName,
        $websiteIds,
        $expectedForwardInvoke
    ) {
        $this->_ctrlRequestMock->expects($this->any())->method('getActionName')->willReturn(Ctrl::ACTION_DELETE);
        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->with('id')->willReturn(1);
        $this->_roleMock->expects($this->any())->method('getWebsiteIds')->willReturn($websiteIds);
        $this->expectsForward($expectedForwardInvoke);
        $this->_model->validateNoWebsiteGeneric($denyActions, $saveAction, $idFieldName);
    }

    public function validateNoWebsiteGenericDataProvider()
    {
        return [
            'hasStoreAccess' => [
                'denyActions' => [Ctrl::ACTION_NEW, Ctrl::ACTION_DELETE],
                'saveAction' => Ctrl::ACTION_SAVE,
                'idFieldName' => 'id',
                'websiteIds' => [1],
                'expectedForwardInvoke' => $this->never(),
            ],
            'hasNoStoreAccess' => [
                'denyActions' => [Ctrl::ACTION_NEW, Ctrl::ACTION_DELETE],
                'saveAction' => Ctrl::ACTION_SAVE,
                'idFieldName' => 'id',
                'websiteIds' => null,
                'expectedForwardInvoke' => $this->once(),
            ]
        ];
    }

    /**
     * test blockCustomerGroupSave
     */
    public function testBlockCustomerGroupSave()
    {
        $this->expectsForward($this->once());
        $this->_model->blockCustomerGroupSave();
    }

    /**
     * test blockIndexAction
     */
    public function testBlockIndexAction()
    {
        $this->expectsForward($this->once());
        $this->_model->blockIndexAction();
    }

    /**
     * test blockTaxChange
     */
    public function testBlockTaxChange()
    {
        $this->expectsForward($this->once());
        $this->_model->blockTaxChange();
    }

    /**
     * Expect for customer Action
     *
     * @param $id
     * @param $websiteId
     * @param $roleWebsiteIds
     * @param $expectedForwardInvoke
     */
    protected function expectsCustomerAction($id, $websiteId, $roleWebsiteIds, $expectedForwardInvoke)
    {
        $customerMock = $this->createPartialMock(
            \Magento\Customer\Model\Customer::class,
            ['load', 'getId', 'getWebsiteId']
        );

        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->with('id')->willReturn($id);
        $customerMock->expects($this->any())->method('load')->with($id)->willReturnSelf();
        $customerMock->expects($this->any())->method('getWebsiteId')->willReturn($websiteId);
        $customerMock->expects($this->any())->method('getId')->willReturn($id);

        $this->_roleMock->expects($this->any())->method('getRelevantWebsiteIds')->willReturn($roleWebsiteIds);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            \Magento\Customer\Model\Customer::class
        )->willReturn(
            $customerMock
        );
        $this->expectsForward($expectedForwardInvoke);
    }

    /**
     * @param $expectedForwardInvoke
     */
    protected function expectsForward($expectedForwardInvoke)
    {
        $this->_ctrlRequestMock->expects(
            $expectedForwardInvoke
        )->method(
            'setActionName'
        )->with(
            Ctrl::ACTION_DENIED
        )->willReturnSelf();
        $this->_ctrlRequestMock->expects($this->any())->method('setDispatched')->with(false);
    }

    /**
     * @param $expectedRedirectInvoke
     */
    protected function expectsRedirect($expectedRedirectInvoke)
    {
        $this->responseMock->expects($expectedRedirectInvoke)->method('setRedirect');
    }
}
