<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Rma\Test\Unit\Block\Adminhtml\Rma\Item\Attribute\Edit\Tab;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use \Magento\MediaStorage\Model\File\Validator\NotProtectedExtension;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class MainTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\Rma\Block\Adminhtml\Rma\Item\Attribute\Edit\Tab\Main */
    private $main;

    /** @var \Magento\Framework\App\Request\Http|\PHPUnit_Framework_MockObject_MockObject */
    private $requestInterface;

    /** @var \Magento\Framework\View\LayoutInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $layoutInterface;

    /** @var \Magento\Framework\Event\ManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $managerInterface;

    /** @var \Magento\Framework\UrlInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $urlInterface;

    /** @var \Magento\Framework\App\CacheInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $cacheInterface;

    /** @var \Magento\Framework\View\DesignInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $designInterface;

    /** @var \Magento\Framework\Session\Generic|\PHPUnit_Framework_MockObject_MockObject */
    private $session;

    /** @var \Magento\Framework\Session\SidResolverInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $sidResolverInterface;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $scopeConfig;

    /** @var \Magento\Framework\View\Asset\Repository|\PHPUnit_Framework_MockObject_MockObject */
    private $assetRepo;

    /** @var \Magento\Framework\View\ConfigInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $configInterface;

    /** @var \Magento\Framework\App\Cache\StateInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $cacheState;

    /** @var \Psr\Log\LoggerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $logger;

    /** @var \Magento\Framework\Escaper|\PHPUnit_Framework_MockObject_MockObject */
    private $escaper;

    /** @var \Magento\Framework\Filter\FilterManager|\PHPUnit_Framework_MockObject_MockObject */
    private $filterManager;

    /** @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $timezoneInterface;

    /** @var \Magento\Framework\Translate\Inline\StateInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $translateState;

    /** @var \Magento\Framework\Filesystem|\PHPUnit_Framework_MockObject_MockObject */
    private $appFilesystem;

    /** @var \Magento\Framework\View\FileSystem|\PHPUnit_Framework_MockObject_MockObject */
    private $viewFilesystem;

    /** @var \Magento\Framework\View\TemplateEnginePool|\PHPUnit_Framework_MockObject_MockObject */
    private $templateEnginePool;

    /** @var \Magento\Framework\App\State|\PHPUnit_Framework_MockObject_MockObject */
    private $appState;

    /** @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $storeManagerInterface;

    /** @var \Magento\Framework\AuthorizationInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $authorizationInterface;

    /** @var \Magento\Backend\Model\Session|\PHPUnit_Framework_MockObject_MockObject */
    private $backendSession;

    /** @var \Magento\Framework\Math\Random|\PHPUnit_Framework_MockObject_MockObject */
    private $random;

    /** @var \Magento\Framework\Data\Form\FormKey|\PHPUnit_Framework_MockObject_MockObject */
    private $formKey;

    /** @var \Magento\Framework\View\Page\Config|\PHPUnit_Framework_MockObject_MockObject */
    private $pageConfig;

    /** @var \Magento\Framework\Code\NameBuilder|\PHPUnit_Framework_MockObject_MockObject */
    private $nameBuilder;

    /** @var \Magento\Backend\Block\Template\Context|\PHPUnit_Framework_MockObject_MockObject */
    private $context;

    /** @var \Magento\Framework\Registry|\PHPUnit_Framework_MockObject_MockObject */
    private $registry;

    /** @var \Magento\Framework\Data\FormFactory|\PHPUnit_Framework_MockObject_MockObject */
    private $formFactory;

    /** @var \Magento\Eav\Helper\Data|\PHPUnit_Framework_MockObject_MockObject */
    private $eavHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $yesnoFactory;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $inputtypeFactory;

    /** @var \Magento\CustomAttributeManagement\Helper\Data|\PHPUnit_Framework_MockObject_MockObject */
    private $customAttributeManagementHelper;

    /** @var \Magento\Rma\Helper\Eav|\PHPUnit_Framework_MockObject_MockObject */
    private $rmaEavHelper;

    /** @var  \Magento\Framework\View\Element\Template\File\Resolver|\PHPUnit_Framework_MockObject_MockObject */
    private $resolver;

    /** @var  \Magento\Framework\View\Element\Template\File\Validator|\PHPUnit_Framework_MockObject_MockObject */
    private $validator;

    /** @var \Magento\Eav\Model\Entity\Attribute\Config|\PHPUnit_Framework_MockObject_MockObject */
    private $attributeConfig;

    /** @var NotProtectedExtension|\PHPUnit_Framework_MockObject_MockObject */
    private $extensionValidator;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp()
    {
        $this->requestInterface = $this->getMockBuilder(\Magento\Framework\App\Request\Http::class)
            ->disableOriginalConstructor()->getMock();
        $this->layoutInterface = $this->createMock(\Magento\Framework\View\LayoutInterface::class);
        $this->managerInterface = $this->createMock(\Magento\Framework\Event\ManagerInterface::class);
        $this->urlInterface = $this->createMock(\Magento\Framework\UrlInterface::class);
        $this->cacheInterface = $this->createMock(\Magento\Framework\App\CacheInterface::class);
        $this->designInterface = $this->createMock(\Magento\Framework\View\DesignInterface::class);
        $this->session = $this->createMock(\Magento\Framework\Session\Generic::class);
        $this->sidResolverInterface = $this->createMock(\Magento\Framework\Session\SidResolverInterface::class);
        $this->scopeConfig = $this->createMock(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        $this->assetRepo = $this->createMock(\Magento\Framework\View\Asset\Repository::class);
        $this->configInterface = $this->createMock(\Magento\Framework\View\ConfigInterface::class);
        $this->cacheState = $this->createMock(\Magento\Framework\App\Cache\StateInterface::class);
        $this->logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $this->escaper = $this->createMock(\Magento\Framework\Escaper::class);
        $this->filterManager = $this->createMock(\Magento\Framework\Filter\FilterManager::class);
        $this->timezoneInterface = $this->createMock(\Magento\Framework\Stdlib\DateTime\TimezoneInterface::class);
        $this->translateState = $this->createMock(\Magento\Framework\Translate\Inline\StateInterface::class);
        $this->appFilesystem = $this->createMock(\Magento\Framework\Filesystem::class);
        $this->viewFilesystem = $this->createMock(\Magento\Framework\View\FileSystem::class);
        $this->templateEnginePool = $this->createMock(\Magento\Framework\View\TemplateEnginePool::class);
        $this->appState = $this->createMock(\Magento\Framework\App\State::class);
        $this->storeManagerInterface = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);
        $this->authorizationInterface = $this->createMock(\Magento\Framework\AuthorizationInterface::class);
        $this->backendSession = $this->createMock(\Magento\Backend\Model\Session::class);
        $this->random = $this->createMock(\Magento\Framework\Math\Random::class);
        $this->formKey = $this->createMock(\Magento\Framework\Data\Form\FormKey::class);
        $this->nameBuilder = $this->createMock(\Magento\Framework\Code\NameBuilder::class);
        $this->pageConfig = $this->createMock(\Magento\Framework\View\Page\Config::class);
        $this->resolver = $this->createMock(\Magento\Framework\View\Element\Template\File\Resolver::class);
        $this->validator = $this->createMock(\Magento\Framework\View\Element\Template\File\Validator::class);

        $this->context = $this->getMockBuilder(\Magento\Backend\Block\Template\Context::class)
            ->setConstructorArgs(
                [
                    'request' => $this->requestInterface,
                    'layout' => $this->layoutInterface,
                    'eventManager' => $this->managerInterface,
                    'urlBuilder' => $this->urlInterface,
                    'cache' => $this->cacheInterface,
                    'design' => $this->designInterface,
                    'session' => $this->session,
                    'sidResolver' => $this->sidResolverInterface,
                    'storeConfig' => $this->scopeConfig,
                    'assetRepo' => $this->assetRepo,
                    'viewConfig' => $this->configInterface,
                    'cacheState' => $this->cacheState,
                    'logger' => $this->logger,
                    'escaper' => $this->escaper,
                    'filterManager' => $this->filterManager,
                    'localeDate' => $this->timezoneInterface,
                    'inlineTranslation' => $this->translateState,
                    'filesystem' => $this->appFilesystem,
                    'viewFileSystem' => $this->viewFilesystem,
                    'enginePool' => $this->templateEnginePool,
                    'appState' => $this->appState,
                    'storeManager' => $this->storeManagerInterface,
                    'pageConfig' => $this->pageConfig,
                    'resolver' => $this->resolver,
                    'validator' => $this->validator,
                    'authorization' => $this->authorizationInterface,
                    'backendSession' => $this->backendSession,
                    'mathRandom' => $this->random,
                    'formKey' => $this->formKey,
                    'nameBuilder' => $this->nameBuilder
                ]
            )
            ->getMock();
        $this->context->expects($this->any())->method('getScopeConfig')->will($this->returnValue($this->scopeConfig));

        $this->registry = $this->createMock(\Magento\Framework\Registry::class);
        $this->formFactory = $this->createMock(\Magento\Framework\Data\FormFactory::class);
        $this->eavHelper = $this->createMock(\Magento\Eav\Helper\Data::class);
        $this->yesnoFactory = $this->createPartialMock(
            \Magento\Config\Model\Config\Source\YesnoFactory::class,
            ['create']
        );
        $this->inputtypeFactory = $this->createPartialMock(
            \Magento\Eav\Model\Adminhtml\System\Config\Source\InputtypeFactory::class,
            ['create']
        );
        $this->attributeConfig = $this->createMock(\Magento\Eav\Model\Entity\Attribute\Config::class);
        $this->customAttributeManagementHelper = $this->createMock(
            \Magento\CustomAttributeManagement\Helper\Data::class
        );
        $this->rmaEavHelper = $this->createMock(\Magento\Rma\Helper\Eav::class);
        $this->extensionValidator = $this->createMock(NotProtectedExtension::class);

        $this->main = (new ObjectManagerHelper($this))->getObject(
            \Magento\Rma\Block\Adminhtml\Rma\Item\Attribute\Edit\Tab\Main::class,
            [
                'context' => $this->context,
                'registry' => $this->registry,
                'formFactory' => $this->formFactory,
                'eavData' => $this->eavHelper,
                'yesnoFactory' => $this->yesnoFactory,
                'inputTypeFactory' => $this->inputtypeFactory,
                'attributeConfig' => $this->attributeConfig,
                'attributeHelper' => $this->customAttributeManagementHelper,
                'rmaEav' => $this->rmaEavHelper,
                'extensionValidator' => $this->extensionValidator,
            ]
        );
    }

    public function testUsedInFormsAndIsVisibleFieldsDependency()
    {
        $fieldset = $this->createMock(\Magento\Framework\Data\Form\Element\Fieldset::class);
        $fieldset->expects($this->any())->method('addField')->will($this->returnSelf());
        $form = $this->createPartialMock(\Magento\Framework\Data\Form::class, ['addFieldset', 'getElement']);
        $form->expects($this->any())->method('addFieldset')->will($this->returnValue($fieldset));
        $form->expects($this->any())->method('getElement')->will($this->returnValue($fieldset));
        $this->formFactory->expects($this->any())->method('create')->will($this->returnValue($form));

        $yesno = $this->createMock(\Magento\Config\Model\Config\Source\Yesno::class);
        $this->yesnoFactory->expects($this->any())->method('create')->will($this->returnValue($yesno));

        $inputtype = $this->createMock(\Magento\Config\Model\Config\Source\Yesno::class);
        $this->inputtypeFactory->expects($this->any())->method('create')
            ->will($this->returnValue($inputtype));

        $this->customAttributeManagementHelper->expects($this->any())->method('getAttributeElementScopes')
            ->will($this->returnValue([]));

        $this->customAttributeManagementHelper->expects($this->any())->method('getFrontendInputOptions')
            ->will($this->returnValue([]));

        $dependenceBlock = $this->createMock(\Magento\Backend\Block\Widget\Form\Element\Dependence::class);
        $dependenceBlock->expects($this->any())->method('addFieldMap')->will($this->returnSelf());

        $this->layoutInterface->expects($this->once())->method('createBlock')
            ->with(\Magento\Backend\Block\Widget\Form\Element\Dependence::class)
            ->will($this->returnValue($dependenceBlock));
        $this->layoutInterface->expects($this->any())->method('setChild')->with(null, null, 'form_after')
            ->will($this->returnSelf());

        $this->appFilesystem->expects($this->any())->method('getDirectoryRead')
            ->will($this->throwException(new \Exception('test')));

        $this->main->setAttributeObject(
            new \Magento\Framework\DataObject(['entity_type' => new \Magento\Framework\DataObject([])])
        );

        $this->extensionValidator->expects($this->once())
            ->method('getProtectedFileExtensions')
            ->willReturn([]);

        $reflection = new \ReflectionClass(get_class($this->main));
        $reflectionProperty = $reflection->getProperty('_eventManager');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->main, $this->managerInterface);
        $reflectionProperty = $reflection->getProperty('_localeDate');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->main, $this->timezoneInterface);
        $reflectionProperty = $reflection->getProperty('_urlBuilder');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->main, $this->urlInterface);
        $reflectionProperty = $reflection->getProperty('_layout');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->main, $this->layoutInterface);
        $reflectionProperty = $reflection->getProperty('_appState');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->main, $this->appState);
        $reflectionProperty = $reflection->getProperty('resolver');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->main, $this->resolver);
        $reflectionProperty = $reflection->getProperty('_filesystem');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->main, $this->appFilesystem);

        try {
            $this->main->toHtml();
            $this->fail();
        } catch (\Exception $e) {
            $this->assertEquals('test', $e->getMessage());
        }
    }
}
