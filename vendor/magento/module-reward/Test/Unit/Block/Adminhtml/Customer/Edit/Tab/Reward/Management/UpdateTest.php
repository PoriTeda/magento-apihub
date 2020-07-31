<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Reward\Test\Unit\Block\Adminhtml\Customer\Edit\Tab\Reward\Management;

use Magento\Customer\Controller\RegistryConstants;
use Magento\Customer\Model\Config\Share;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\Data\Form\Element\Checkbox;
use Magento\Framework\Data\Form\Element\Fieldset;
use Magento\Reward\Block\Adminhtml\Customer\Edit\Tab\Reward\Management\Update;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Model\Session;
use Magento\Customer\Model\Customer;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template\File\Resolver;
use Magento\Framework\View\Element\Template\File\Validator;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\System\Store;
use Magento\Store\Model\System\StoreFactory;
use Psr\Log\LoggerInterface;

/**
 * Class UpdateTest
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 */
class UpdateTest extends \PHPUnit\Framework\TestCase
{
    /** @var Update */
    protected $model;

    /** @var Context|\PHPUnit_Framework_MockObject_MockObject */
    protected $contextMock;

    /** @var Registry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registryMock;

    /** @var FormFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $formFactoryMock;

    /** @var StoreFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $storeFactoryMock;

    /** @var CustomerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $customerRegistryMock;

    /** @var StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $storeManagerMock;

    /** @var ManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $eventManagerMock;

    /** @var ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $scopeConfigMock;

    /** @var LayoutInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $layoutMock;

    /** @var Session|\PHPUnit_Framework_MockObject_MockObject */
    protected $backendSessionMock;

    /** @var UrlInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $urlBuilderMock;

    /** @var State|\PHPUnit_Framework_MockObject_MockObject */
    protected $appStateMock;

    /** @var Resolver|\PHPUnit_Framework_MockObject_MockObject */
    protected $resolverMock;

    /** @var Filesystem|\PHPUnit_Framework_MockObject_MockObject */
    protected $filesystemMock;

    /** @var ReadInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $readMock;

    /** @var Validator|\PHPUnit_Framework_MockObject_MockObject */
    protected $validatorMock;

    /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $loggerMock;

    protected function setUp()
    {
        $this->contextMock = $this->getMockBuilder(\Magento\Backend\Block\Template\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->registryMock = $this->getMockBuilder(\Magento\Framework\Registry::class)
            ->getMock();
        $this->formFactoryMock = $this->getMockBuilder(\Magento\Framework\Data\FormFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeFactoryMock = $this->getMockBuilder(\Magento\Store\Model\System\StoreFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerRegistryMock = $this->getMockBuilder(\Magento\Customer\Model\CustomerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManagerMock = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->getMock();
        $this->eventManagerMock = $this->getMockBuilder(\Magento\Framework\Event\ManagerInterface::class)
            ->getMock();
        $this->scopeConfigMock = $this->getMockBuilder(\Magento\Framework\App\Config\ScopeConfigInterface::class)
            ->getMock();
        $this->layoutMock = $this->getMockBuilder(\Magento\Framework\View\LayoutInterface::class)
            ->getMock();
        $this->backendSessionMock = $this->getMockBuilder(\Magento\Backend\Model\Session::class)
            ->setMethods(['getCustomerFormData'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->urlBuilderMock = $this->getMockBuilder(\Magento\Framework\UrlInterface::class)
            ->getMock();
        $this->appStateMock = $this->getMockBuilder(\Magento\Framework\App\State::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resolverMock = $this->getMockBuilder(\Magento\Framework\View\Element\Template\File\Resolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->filesystemMock = $this->getMockBuilder(\Magento\Framework\Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->readMock = $this->getMockBuilder(\Magento\Framework\Filesystem\Directory\ReadInterface::class)
            ->getMock();
        $this->validatorMock = $this->getMockBuilder(\Magento\Framework\View\Element\Template\File\Validator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerMock = $this->getMockBuilder(\Psr\Log\LoggerInterface::class)
            ->getMock();

        $this->contextMock->expects($this->once())->method('getStoreManager')->willReturn($this->storeManagerMock);
        $this->contextMock->expects($this->once())->method('getEventManager')->willReturn($this->eventManagerMock);
        $this->contextMock->expects($this->once())->method('getScopeConfig')->willReturn($this->scopeConfigMock);
        $this->contextMock->expects($this->once())->method('getLayout')->willReturn($this->layoutMock);
        $this->contextMock->expects($this->once())->method('getBackendSession')->willReturn($this->backendSessionMock);
        $this->contextMock->expects($this->once())->method('getUrlBuilder')->willReturn($this->urlBuilderMock);
        $this->contextMock->expects($this->once())->method('getAppState')->willReturn($this->appStateMock);
        $this->contextMock->expects($this->once())->method('getResolver')->willReturn($this->resolverMock);
        $this->contextMock->expects($this->once())->method('getFilesystem')->willReturn($this->filesystemMock);
        $this->contextMock->expects($this->once())->method('getValidator')->willReturn($this->validatorMock);
        $this->contextMock->expects($this->once())->method('getLogger')->willReturn($this->loggerMock);

        $this->filesystemMock->expects($this->any())->method('getDirectoryRead')->willReturn($this->readMock);

        $this->model = new Update(
            $this->contextMock,
            $this->registryMock,
            $this->formFactoryMock,
            $this->storeFactoryMock,
            $this->customerRegistryMock
        );
    }

    public function testToHtml()
    {
        $customerId = 11;
        $isSingleStoreMode = false;
        $sessionData = [
            'customer' => [
                'entity_id' => $customerId,
            ],
            'reward' => [
                'reward_update_notification' => true,
                'reward_warning_notification' => true,
                'data' => 'values',
            ],
        ];
        $stores = [
            1 => [
                'label' => 'website',
                'value' => 'website',
                'children' => [
                    1 => [
                        'label' => 'store',
                        'value' => 'store',
                        'children' => [
                            1 => [
                                'label' => 'view',
                                'value' => 'view',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        /** @var \Magento\Framework\Data\Form|\PHPUnit_Framework_MockObject_MockObject $formMock */
        $formMock = $this->getMockBuilder(\Magento\Framework\Data\Form::class)
            ->disableOriginalConstructor()
            ->getMock();
        /** @var Fieldset|\PHPUnit_Framework_MockObject_MockObject $fieldsetMock */
        $fieldsetMock = $this->getMockBuilder(\Magento\Framework\Data\Form\Element\Fieldset::class)
            ->disableOriginalConstructor()
            ->getMock();
        /** @var Checkbox|\PHPUnit_Framework_MockObject_MockObject $updateFieldMock */
        $updateFieldMock = $this->getMockBuilder(\Magento\Framework\Data\Form\Element\Checkbox::class)
            ->setMethods(['setIsChecked'])
            ->disableOriginalConstructor()
            ->getMock();
        /** @var Checkbox|\PHPUnit_Framework_MockObject_MockObject $warningFieldMock */
        $warningFieldMock = $this->getMockBuilder(\Magento\Framework\Data\Form\Element\Checkbox::class)
            ->setMethods(['setIsChecked'])
            ->disableOriginalConstructor()
            ->getMock();
        /** @var Customer|\PHPUnit_Framework_MockObject_MockObject $customerMock */
        $customerMock = $this->getMockBuilder(\Magento\Customer\Model\Customer::class)
            ->setMethods(['getWebsiteId', 'getId', 'getSharingConfig'])
            ->disableOriginalConstructor()
            ->getMock();
        /** @var Store|\PHPUnit_Framework_MockObject_MockObject $storeMock */
        $storeMock = $this->getMockBuilder(\Magento\Store\Model\System\Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        /** @var Share|\PHPUnit_Framework_MockObject_MockObject $shareMock */
        $shareMock = $this->getMockBuilder(\Magento\Customer\Model\Config\Share::class)
            ->disableOriginalConstructor()
            ->getMock();

        $formMock->expects($this->exactly(2))
            ->method('addFieldset')
            ->will($this->returnValue($fieldsetMock));
        $this->formFactoryMock->expects($this->once())
            ->method('create')
            ->will($this->returnValue($formMock));

        $this->registryMock->expects($this->any())
            ->method('registry')
            ->with(RegistryConstants::CURRENT_CUSTOMER_ID)
            ->willReturn($customerId);

        $this->customerRegistryMock->expects($this->any())
            ->method('retrieve')
            ->with($customerId)
            ->willReturn($customerMock);

        $customerMock->expects($this->any())
            ->method('getId')
            ->willReturn($customerId);
        $customerMock->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn(1);
        $customerMock->expects($this->any())
            ->method('getSharingConfig')
            ->willReturn($shareMock);

        $this->storeManagerMock->expects($this->any())
            ->method('isSingleStoreMode')
            ->willReturn($isSingleStoreMode);

        $this->storeFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($storeMock);

        $storeMock->expects($this->once())
            ->method('getStoresStructure')
            ->willReturn($stores);

        $formMock->expects($this->exactly(2))
            ->method('getElement')
            ->willReturnMap(
                [
                    ['update_notification', $updateFieldMock],
                    ['warning_notification', $warningFieldMock],
                ]
            );

        $this->backendSessionMock->expects($this->once())
            ->method('getCustomerFormData')
            ->willReturn($sessionData);

        $updateFieldMock->expects($this->once())
            ->method('setIsChecked')
            ->with(true);

        $warningFieldMock->expects($this->once())
            ->method('setIsChecked')
            ->with(true);

        $formMock->expects($this->once())
            ->method('addValues')
            ->with(['data' => 'values']);

        $this->model->toHtml();
        $this->assertEquals($formMock, $this->model->getForm());
    }
}
