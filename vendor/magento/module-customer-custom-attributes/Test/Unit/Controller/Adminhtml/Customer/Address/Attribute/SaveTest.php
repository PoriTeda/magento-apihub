<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Test\Unit\Controller\Adminhtml\Customer\Address\Attribute;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Session;
use Magento\Customer\Model\Attribute;
use Magento\Customer\Model\AttributeFactory;
use Magento\CustomerCustomAttributes\Controller\Adminhtml\Customer\Address\Attribute\Save;
use Magento\CustomerCustomAttributes\Helper\Address as HelperAddress;
use Magento\CustomerCustomAttributes\Helper\Data as HelperData;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\Set as AttributeSet;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Eav\Model\Entity\Type as EntityType;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\FormData;
use Magento\Store\Model\Website;
use Magento\Store\Model\WebsiteFactory;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveTest extends \PHPUnit\Framework\TestCase
{
    /** @var  Save */
    protected $model;

    /** @var  Context |\PHPUnit_Framework_MockObject_MockObject */
    protected $context;

    /** @var  Registry |\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var  Config |\PHPUnit_Framework_MockObject_MockObject */
    protected $config;

    /** @var  AttributeFactory |\PHPUnit_Framework_MockObject_MockObject */
    protected $attributeFactory;

    /** @var  AttributeSetFactory |\PHPUnit_Framework_MockObject_MockObject */
    protected $attributeSetFactory;

    /** @var  RequestInterface |\PHPUnit_Framework_MockObject_MockObject */
    protected $request;

    /** @var  ResultFactory |\PHPUnit_Framework_MockObject_MockObject */
    protected $resultFactory;

    /** @var  Redirect |\PHPUnit_Framework_MockObject_MockObject */
    protected $resultRedirect;

    /** @var  Attribute |\PHPUnit_Framework_MockObject_MockObject */
    protected $attribute;

    /** @var  HelperData |\PHPUnit_Framework_MockObject_MockObject */
    protected $helperData;

    /** @var  HelperAddress |\PHPUnit_Framework_MockObject_MockObject */
    protected $helperAddress;

    /** @var  FilterManager |\PHPUnit_Framework_MockObject_MockObject */
    protected $filterManager;

    /** @var  ManagerInterface |\PHPUnit_Framework_MockObject_MockObject */
    protected $messageManager;

    /** @var  EntityType |\PHPUnit_Framework_MockObject_MockObject */
    protected $entityType;

    /** @var  Session |\PHPUnit_Framework_MockObject_MockObject */
    protected $session;

    /** @var  AttributeSet |\PHPUnit_Framework_MockObject_MockObject */
    protected $attributeSet;

    /** @var  WebsiteFactory |\PHPUnit_Framework_MockObject_MockObject */
    protected $websiteFactory;

    /** @var  Website |\PHPUnit_Framework_MockObject_MockObject */
    protected $website;

    /**
     * @var FormData|\PHPUnit_Framework_MockObject_MockObject
     */
    private $formDataSerializerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->prepareContext();

        $this->registry = $this->getMockBuilder(\Magento\Framework\Registry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->config = $this->getMockBuilder(\Magento\Eav\Model\Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->prepareAttributeFactory();

        $this->prepareWebsite();

        $this->helperData = $this->getMockBuilder(\Magento\CustomerCustomAttributes\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->helperAddress = $this->getMockBuilder(\Magento\CustomerCustomAttributes\Helper\Address::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->filterManager = $this->getMockBuilder(\Magento\Framework\Filter\FilterManager::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'stripTags',
            ])
            ->getMock();

        $this->entityType = $this->getMockBuilder(\Magento\Eav\Model\Entity\Type::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->formDataSerializerMock = $this->getMockBuilder(FormData::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new Save(
            $this->context,
            $this->registry,
            $this->config,
            $this->attributeFactory,
            $this->attributeSetFactory,
            $this->websiteFactory,
            $this->helperData,
            $this->helperAddress,
            $this->filterManager,
            $this->formDataSerializerMock
        );
    }

    protected function prepareContext()
    {
        $this->request = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->setMethods([
                'getPostValue',
                'isPost',
                'getPost',
                'getParam',
            ])
            ->getMockForAbstractClass();

        $this->resultFactory = $this->getMockBuilder(\Magento\Framework\Controller\ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultRedirect = $this->getMockBuilder(\Magento\Framework\Controller\Result\Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->messageManager = $this->getMockBuilder(\Magento\Framework\Message\ManagerInterface::class)
            ->setMethods([
                'addSuccess',
                'addError',
                'addErrorMessage'
            ])
            ->getMockForAbstractClass();

        $this->session = $this->getMockBuilder(\Magento\Backend\Model\Session::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'addAttributeData',
                'setAttributeData',
            ])
            ->getMock();

        $this->context = $this->getMockBuilder(\Magento\Backend\App\Action\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->context->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->request);
        $this->context->expects($this->any())
            ->method('getResultFactory')
            ->willReturn($this->resultFactory);
        $this->context->expects($this->any())
            ->method('getMessageManager')
            ->willReturn($this->messageManager);
        $this->context->expects($this->any())
            ->method('getSession')
            ->willReturn($this->session);

        $this->resultFactory->expects($this->any())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)
            ->willReturn($this->resultRedirect);
    }

    protected function prepareAttributeFactory()
    {
        $this->attribute = $this->getMockBuilder(\Magento\Customer\Model\Attribute::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'load',
                'save',
                'getId',
                'addData',
                'setData',
                'setWebsite',
                'getEntityTypeId',
                'getAttributeCode',
                'getIsUserDefined',
                'getFrontendInput',
                'getIsSystem',
            ])
            ->getMock();

        $this->attributeFactory = $this->getMockBuilder(\Magento\Customer\Model\AttributeFactory::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'create',
            ])
            ->getMock();
        $this->attributeFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->attribute);

        $this->attributeSet = $this->getMockBuilder(\Magento\Eav\Model\Entity\Attribute\Set::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getDefaultGroupId',
            ])
            ->getMock();

        $this->attributeSetFactory = $this->getMockBuilder(\Magento\Eav\Model\Entity\Attribute\SetFactory::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'create',
            ])
            ->getMock();
        $this->attributeSetFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->attributeSet);
    }

    protected function prepareWebsite()
    {
        $this->website = $this->getMockBuilder(\Magento\Store\Model\Website::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->websiteFactory = $this->getMockBuilder(\Magento\Store\Model\WebsiteFactory::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'create',
            ])
            ->getMock();

        $this->websiteFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->website);
    }

    public function testExecuteNoPostData()
    {
        $data = [];

        $this->request->expects($this->any())
            ->method('getParam')
            ->with()
            ->willReturnMap([
                ['serialized_options', '[]', ''],
            ]);
        $this->request->expects($this->once())
            ->method('getPostValue')
            ->willReturn($data);
        $this->request->expects($this->once())
            ->method('isPost')
            ->willReturn(false);

        $this->resultRedirect->expects($this->any())
            ->method('setPath')
            ->with()
            ->willReturnSelf('adminhtml/*/');

        $this->assertEquals($this->resultRedirect, $this->model->execute());
    }

    /**
     * @param array $data
     * @param int $websiteId
     * @param string $errorMessage
     * @dataProvider dataProviderExecutePostDataException
     */
    public function testExecutePostDataException(
        $data,
        $websiteId,
        $errorMessage
    ) {
        $this->request->expects($this->once())
            ->method('getPostValue')
            ->willReturn($data);
        $this->request->expects($this->once())
            ->method('isPost')
            ->willReturn(true);
        $this->request->expects($this->any())
            ->method('getParam')
            ->with()
            ->willReturnMap([
                ['website', null, $websiteId],
                ['serialized_options', '[]', ''],
            ]);

        $this->attribute->expects($this->once())
            ->method('setWebsite')
            ->with($websiteId)
            ->willReturnSelf();

        $this->helperAddress->expects($this->once())
            ->method('filterPostData')
            ->with($data)
            ->willThrowException(new LocalizedException(__($errorMessage)));

        $this->messageManager->expects($this->once())
            ->method('addError')
            ->with(__($errorMessage))
            ->willReturnSelf();

        $this->resultRedirect->expects($this->once())
            ->method('setPath')
            ->willReturnMap([
                ['adminhtml/*/edit', ['_current' => true], $this->resultRedirect],
                ['adminhtml/*/new', ['_current' => true], $this->resultRedirect],
            ]);

        $this->assertEquals($this->resultRedirect, $this->model->execute());
    }

    /**
     * 1. Data
     * 2. Website ID
     * 3. Error message
     *
     * @return array
     */
    public function dataProviderExecutePostDataException()
    {
        return [
            [['attribute_id' => 1], 1, 'error message'],
            [['item' => 1], 1, 'error message'],
        ];
    }

    public function testExecuteWrongEntityType()
    {
        $data = [
            'attribute_id' => 1
        ];
        $websiteId = 1;
        $attributeId = 1;
        $entityTypeId = 1;

        $this->request->expects($this->once())
            ->method('getPostValue')
            ->willReturn($data);
        $this->request->expects($this->once())
            ->method('isPost')
            ->willReturn(true);
        $this->request->expects($this->any())
            ->method('getParam')
            ->with()
            ->willReturnMap([
                ['website', null, $websiteId],
                ['attribute_id', null, $attributeId],
                ['serialized_options', '[]', ''],
            ]);

        $this->attribute->expects($this->once())
            ->method('setWebsite')
            ->with($websiteId)
            ->willReturnSelf();
        $this->attribute->expects($this->once())
            ->method('load')
            ->with($attributeId)
            ->willReturnSelf();
        $this->attribute->expects($this->once())
            ->method('getEntityTypeId')
            ->willReturn($entityTypeId);

        $this->helperAddress->expects($this->once())
            ->method('filterPostData')
            ->with($data)
            ->willReturn($data);

        $this->config->expects($this->once())
            ->method('getEntityType')
            ->with('customer_address')
            ->willReturn($this->entityType);

        $this->entityType->expects($this->once())
            ->method('getId')
            ->willReturn($entityTypeId + 1);

        $this->messageManager->expects($this->once())
            ->method('addError')
            ->with(__('You cannot edit this attribute.'))
            ->willReturnSelf();

        $this->session->expects($this->once())
            ->method('addAttributeData')
            ->with($data)
            ->willReturnSelf();

        $this->resultRedirect->expects($this->once())
            ->method('setPath')
            ->with('adminhtml/*/')
            ->willReturn($this->resultRedirect);

        $this->assertEquals($this->resultRedirect, $this->model->execute());
    }

    /**
     * @param array $data
     * @param array $resultData
     * @param array $attributeData
     * @param int $websiteId
     * @param int $attributeId
     * @param int $attributeSetId
     * @param int $attributeGroupId
     * @param int $entityTypeId
     * @param bool $returnBack
     * @dataProvider dataProviderExecute
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testExecute(
        $data,
        $resultData,
        $attributeData,
        $websiteId,
        $attributeId,
        $attributeSetId,
        $attributeGroupId,
        $entityTypeId,
        $returnBack
    ) {
        $this->request->expects($this->once())
            ->method('getPostValue')
            ->willReturn($data);
        $this->request->expects($this->once())
            ->method('isPost')
            ->willReturn(true);
        $this->request->expects($this->any())
            ->method('getParam')
            ->with()
            ->willReturnMap([
                ['website', null, $websiteId],
                ['attribute_id', null, $attributeId],
                ['scope_' . $attributeData['default_value'], null, $attributeData['default_value']],
                [$attributeData['default_value'], null, $attributeData['default_value']],
                ['back', false, $returnBack],
                ['serialized_options', '[]', ''],
            ]);
        $this->request->expects($this->once())
            ->method('getPost')
            ->with('use_default')
            ->willReturn([$attributeData['use_default']]);

        $website = $websiteId ?: $this->website;
        $this->attribute->expects($this->once())
            ->method('setWebsite')
            ->with($website)
            ->willReturnSelf();
        $this->attribute->expects($this->any())
            ->method('load')
            ->with($attributeId)
            ->willReturnSelf();
        $this->attribute->expects($this->any())
            ->method('getEntityTypeId')
            ->willReturn($entityTypeId);
        $this->attribute->expects($this->any())
            ->method('getAttributeCode')
            ->willReturn($attributeData['attribute_code']);
        $this->attribute->expects($this->any())
            ->method('getIsUserDefined')
            ->willReturn($attributeData['is_user_defined']);
        $this->attribute->expects($this->any())
            ->method('getFrontendInput')
            ->willReturn($attributeData['frontend_input']);
        $this->attribute->expects($this->any())
            ->method('getIsSystem')
            ->willReturn($attributeData['is_system']);

        $addDataParams = $resultData;
        $addDataParams['validate_rules'] = $attributeData['validate_rules'];
        $this->attribute->expects($this->any())
            ->method('addData')
            ->with($addDataParams)
            ->willReturnSelf();

        $this->attribute->expects($this->any())
            ->method('setData')
            ->with($attributeData['use_default'])
            ->willReturnSelf();
        $this->attribute->expects($this->any())
            ->method('save')
            ->willReturnSelf();
        $this->attribute->expects($this->any())
            ->method('getId')
            ->willReturn($attributeId);

        $this->attributeSet->expects($this->any())
            ->method('getDefaultGroupId')
            ->with($attributeSetId)
            ->willReturn($attributeGroupId);

        $this->helperData->expects($this->any())
            ->method('getAttributeBackendModelByInputType')
            ->with($data['frontend_input'])
            ->willReturn($attributeData['backend_model']);
        $this->helperData->expects($this->once())
            ->method('checkValidateRules')
            ->willReturn([]);
        $this->helperData->expects($this->any())
            ->method('getAttributeSourceModelByInputType')
            ->with($data['frontend_input'])
            ->willReturn($attributeData['source_model']);
        $this->helperData->expects($this->any())
            ->method('getAttributeBackendTypeByInputType')
            ->with($data['frontend_input'])
            ->willReturn($attributeData['backend_type']);
        $this->helperData->expects($this->any())
            ->method('getAttributeDefaultValueByInput')
            ->with($data['frontend_input'])
            ->willReturn($attributeData['default_value']);

        $this->helperData->expects($this->any())
            ->method('getAttributeValidateRules')
            ->with($data['frontend_input'], $resultData)
            ->willReturn($attributeData['validate_rules']);

        $this->helperAddress->expects($this->once())
            ->method('filterPostData')
            ->with($data)
            ->willReturn($data);

        $this->config->expects($this->once())
            ->method('getEntityType')
            ->with('customer_address')
            ->willReturn($this->entityType);

        $this->entityType->expects($this->any())
            ->method('getId')
            ->willReturn($entityTypeId);
        $this->entityType->expects($this->any())
            ->method('getDefaultAttributeSetId')
            ->willReturn($attributeSetId);

        $this->filterManager->expects($this->any())
            ->method('stripTags')
            ->with($attributeData['default_value'])
            ->willReturn($attributeData['default_value']);

        $this->messageManager->expects($this->once())
            ->method('addSuccess')
            ->with(__('You saved the customer address attribute.'))
            ->willReturnSelf();

        $this->session->expects($this->once())
            ->method('setAttributeData')
            ->with(false)
            ->willReturnSelf();

        $this->resultRedirect->expects($this->once())
            ->method('setPath')
            ->willReturnMap([
                ['adminhtml/*/edit', ['attribute_id' => $attributeId, '_current' => true], $this->resultRedirect],
                ['adminhtml/*/', [], $this->resultRedirect],
            ]);

        $this->assertEquals($this->resultRedirect, $this->model->execute());
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function dataProviderExecute()
    {
        return [
            [
                'data' => [
                    'attribute_code' => null,
                    'is_user_defined' => null,
                    'frontend_input' => 'frontend_input',
                    'is_system' => null,

                    'used_in_forms' => [],
                    'scope_default_value' => null,
                    'entity_type_id' => null,
                    'validate_rules' => null,
                ],
                'result_data' => [
                    'attribute_code' => 'attribute_code',
                    'is_user_defined' => 1,
                    'frontend_input' => 'frontend_input',
                    'is_system' => 1,

                    'used_in_forms' => ['adminhtml_customer_address'],
                    'scope_default_value' => null,
                    'entity_type_id' => 1,
                    'validate_rules' => null,
                    'default_value' => 'default_value',
                ],
                'attribute_data' => [
                    'attribute_code' => 'attribute_code',
                    'is_user_defined' => 1,
                    'frontend_input' => 'frontend_input',
                    'is_system' => 1,

                    'default_value' => 'default_value',
                    'backend_model' => 'backend_model',
                    'source_model' => 'source_model',
                    'backend_type' => 'backend_type',
                    'validate_rules' => 'validate_rules',
                    'use_default' => 'use_default',
                ],
                'website_id' => 0,
                'attribute_id' => 1,
                'attribute_set_id' => null,
                'attribute_group_id' => null,
                'entity_type_id' => 1,
                'return_back_flag' => true
            ],
            [
                'data' => [
                    'attribute_code' => null,
                    'is_user_defined' => null,
                    'frontend_input' => 'frontend_input',
                    'is_system' => null,

                    'used_in_forms' => [],
                    'scope_default_value' => null,
                    'entity_type_id' => null,
                    'validate_rules' => null,
                ],
                'result_data' => [
                    'attribute_code' => 'attribute_code',
                    'is_user_defined' => 1,
                    'frontend_input' => 'frontend_input',
                    'is_system' => 1,

                    'used_in_forms' => ['adminhtml_customer_address'],
                    'scope_default_value' => 'default_value',
                    'entity_type_id' => 1,
                    'validate_rules' => null,
                ],
                'attribute_data' => [
                    'attribute_code' => 'attribute_code',
                    'is_user_defined' => 1,
                    'frontend_input' => 'frontend_input',
                    'is_system' => 1,

                    'default_value' => 'default_value',
                    'backend_model' => 'backend_model',
                    'source_model' => 'source_model',
                    'backend_type' => 'backend_type',
                    'validate_rules' => 'validate_rules',
                    'use_default' => 'use_default',
                ],
                'website_id' => 1,
                'attribute_id' => 1,
                'attribute_set_id' => null,
                'attribute_group_id' => null,
                'entity_type_id' => 1,
                'return_back_flag' => true
            ],
            [
                'data' => [
                    'attribute_code' => null,
                    'is_user_defined' => null,
                    'frontend_input' => 'frontend_input',
                    'is_system' => null,

                    'used_in_forms' => [],
                    'scope_default_value' => null,
                    'entity_type_id' => null,
                    'validate_rules' => null,
                ],
                'result_data' => [
                    'attribute_code' => 'attribute_code',
                    'is_user_defined' => 1,
                    'frontend_input' => 'frontend_input',
                    'is_system' => 1,

                    'used_in_forms' => ['adminhtml_customer_address'],
                    'scope_default_value' => 'default_value',
                    'entity_type_id' => 1,
                    'validate_rules' => null,
                ],
                'attribute_data' => [
                    'attribute_code' => 'attribute_code',
                    'is_user_defined' => 1,
                    'frontend_input' => 'frontend_input',
                    'is_system' => 1,

                    'default_value' => 'default_value',
                    'backend_model' => 'backend_model',
                    'source_model' => 'source_model',
                    'backend_type' => 'backend_type',
                    'validate_rules' => 'validate_rules',
                    'use_default' => 'use_default',
                ],
                'website_id' => 1,
                'attribute_id' => 1,
                'attribute_set_id' => null,
                'attribute_group_id' => null,
                'entity_type_id' => 1,
                'return_back_flag' => false
            ],
            [
                'data' => [
                    'backend_model' => null,
                    'source_model' => null,
                    'backend_type' => null,
                    'is_user_defined' => null,
                    'is_system' => null,
                    'attribute_set_id' => 1,
                    'attribute_group_id' => null,

                    'used_in_forms' => null,
                    'scope_default_value' => null,
                    'entity_type_id' => null,
                    'validate_rules' => null,

                    'frontend_input' => 'frontend_input',
                ],
                'result_data' => [
                    'backend_model' => 'backend_model',
                    'source_model' => 'source_model',
                    'backend_type' => 'backend_type',
                    'is_user_defined' => 1,
                    'is_system' => 0,
                    'attribute_set_id' => 1,
                    'attribute_group_id' => 1,

                    'used_in_forms' => ['adminhtml_customer_address'],
                    'scope_default_value' => 'default_value',
                    'entity_type_id' => 1,
                    'validate_rules' => null,

                    'frontend_input' => 'frontend_input',
                ],
                'attribute_data' => [
                    'attribute_code' => 'attribute_code',
                    'is_user_defined' => 1,
                    'frontend_input' => 'frontend_input',
                    'is_system' => 1,

                    'default_value' => 'default_value',
                    'backend_model' => 'backend_model',
                    'source_model' => 'source_model',
                    'backend_type' => 'backend_type',
                    'validate_rules' => 'validate_rules',
                    'use_default' => 'use_default',
                ],
                'website_id' => 1,
                'attribute_id' => null,
                'attribute_set_id' => 1,
                'attribute_group_id' => 1,
                'entity_type_id' => 1,
                'return_back_flag' => false
            ],
        ];
    }

    /**
     * @param array $data
     * @param array $resultData
     * @param array $attributeData
     * @param int $websiteId
     * @param int $attributeId
     * @param int $entityTypeId
     * @param string $errorMessage
     * @dataProvider dataProviderExecuteSaveAndLocalizedException
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testExecuteSaveAndLocalizedException(
        $data,
        $resultData,
        $attributeData,
        $websiteId,
        $attributeId,
        $entityTypeId,
        $errorMessage
    ) {
        $this->request->expects($this->once())
            ->method('getPostValue')
            ->willReturn($data);
        $this->request->expects($this->once())
            ->method('isPost')
            ->willReturn(true);
        $this->request->expects($this->any())
            ->method('getParam')
            ->with()
            ->willReturnMap([
                ['website', null, $websiteId],
                ['attribute_id', null, $attributeId],
                ['scope_' . $attributeData['default_value'], null, $attributeData['default_value']],
                ['serialized_options', '[]', ''],
            ]);
        $this->request->expects($this->once())
            ->method('getPost')
            ->with('use_default')
            ->willReturn([$attributeData['use_default']]);

        $this->attribute->expects($this->once())
            ->method('setWebsite')
            ->with($websiteId)
            ->willReturnSelf();
        $this->attribute->expects($this->once())
            ->method('load')
            ->with($attributeId)
            ->willReturnSelf();
        $this->attribute->expects($this->once())
            ->method('getEntityTypeId')
            ->willReturn($entityTypeId);
        $this->attribute->expects($this->once())
            ->method('getAttributeCode')
            ->willReturn($attributeData['attribute_code']);
        $this->attribute->expects($this->exactly(2))
            ->method('getIsUserDefined')
            ->willReturn($attributeData['is_user_defined']);
        $this->attribute->expects($this->once())
            ->method('getFrontendInput')
            ->willReturn($attributeData['frontend_input']);
        $this->attribute->expects($this->once())
            ->method('getIsSystem')
            ->willReturn($attributeData['is_system']);

        $addDataParams = $resultData;
        $addDataParams['validate_rules'] = $attributeData['validate_rules'];
        $this->attribute->expects($this->any())
            ->method('addData')
            ->with($addDataParams)
            ->willReturnSelf();

        $this->attribute->expects($this->once())
            ->method('setData')
            ->with($attributeData['use_default'])
            ->willReturnSelf();
        $this->attribute->expects($this->once())
            ->method('save')
            ->willThrowException(new LocalizedException(__($errorMessage)));

        $this->helperData->expects($this->any())
            ->method('getAttributeDefaultValueByInput')
            ->with($data['frontend_input'])
            ->willReturn($attributeData['default_value']);

        $this->helperData->expects($this->once())
            ->method('checkValidateRules')
            ->willReturn([]);

        $this->helperData->expects($this->any())
            ->method('getAttributeValidateRules')
            ->with($data['frontend_input'], $resultData)
            ->willReturn($attributeData['validate_rules']);

        $this->helperAddress->expects($this->once())
            ->method('filterPostData')
            ->with($data)
            ->willReturn($data);

        $this->config->expects($this->once())
            ->method('getEntityType')
            ->with('customer_address')
            ->willReturn($this->entityType);

        $this->entityType->expects($this->any())
            ->method('getId')
            ->willReturn($entityTypeId);

        $this->filterManager->expects($this->any())
            ->method('stripTags')
            ->with($attributeData['default_value'])
            ->willReturn($attributeData['default_value']);

        $this->messageManager->expects($this->once())
            ->method('addError')
            ->with(__($errorMessage))
            ->willReturnSelf();

        $setDataParams = $resultData;
        $setDataParams['validate_rules'] = $attributeData['validate_rules'];
        $this->session->expects($this->once())
            ->method('setAttributeData')
            ->with($setDataParams)
            ->willReturnSelf();

        $this->resultRedirect->expects($this->once())
            ->method('setPath')
            ->with('adminhtml/*/edit', ['_current' => true])
            ->willReturn($this->resultRedirect);

        $this->assertEquals($this->resultRedirect, $this->model->execute());
    }

    /**
     * @return array
     */
    public function dataProviderExecuteSaveAndLocalizedException()
    {
        return [
            [
                'data' => [
                    'attribute_code' => null,
                    'is_user_defined' => null,
                    'is_system' => null,

                    'used_in_forms' => [],
                    'scope_default_value' => null,
                    'entity_type_id' => null,
                    'validate_rules' => null,

                    'frontend_input' => 'frontend_input',
                ],
                'result_data' => [
                    'attribute_code' => 'attribute_code',
                    'is_user_defined' => 1,
                    'is_system' => 1,

                    'frontend_input' => 'frontend_input',

                    'used_in_forms' => ['adminhtml_customer_address'],
                    'scope_default_value' => 'default_value',
                    'entity_type_id' => 1,
                    'validate_rules' => null,
                ],
                'attribute_data' => [
                    'attribute_code' => 'attribute_code',
                    'is_user_defined' => 1,
                    'frontend_input' => 'frontend_input',
                    'is_system' => 1,

                    'default_value' => 'default_value',
                    'backend_model' => 'backend_model',
                    'source_model' => 'source_model',
                    'backend_type' => 'backend_type',
                    'validate_rules' => 'validate_rules',
                    'use_default' => 'use_default',
                ],
                'website_id' => 1,
                'attribute_id' => 1,
                'entity_type_id' => 1,
                'error_message' => 'error message',
            ],
        ];
    }

    /**
     * @param array $data
     * @param array $resultData
     * @param array $attributeData
     * @param int $websiteId
     * @param int $attributeId
     * @param int $entityTypeId
     * @param string $errorMessage
     * @dataProvider dataProviderExecuteSaveAndException
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testExecuteSaveAndException(
        $data,
        $resultData,
        $attributeData,
        $websiteId,
        $attributeId,
        $entityTypeId,
        $errorMessage
    ) {
        $this->request->expects($this->once())
            ->method('getPostValue')
            ->willReturn($data);
        $this->request->expects($this->once())
            ->method('isPost')
            ->willReturn(true);
        $this->request->expects($this->any())
            ->method('getParam')
            ->with()
            ->willReturnMap([
                ['website', null, $websiteId],
                ['attribute_id', null, $attributeId],
                ['scope_' . $attributeData['default_value'], null, $attributeData['default_value']],
                ['serialized_options', '[]', ''],
            ]);
        $this->request->expects($this->once())
            ->method('getPost')
            ->with('use_default')
            ->willReturn([$attributeData['use_default']]);

        $this->attribute->expects($this->once())
            ->method('setWebsite')
            ->with($websiteId)
            ->willReturnSelf();
        $this->attribute->expects($this->once())
            ->method('load')
            ->with($attributeId)
            ->willReturnSelf();
        $this->attribute->expects($this->once())
            ->method('getEntityTypeId')
            ->willReturn($entityTypeId);
        $this->attribute->expects($this->once())
            ->method('getAttributeCode')
            ->willReturn($attributeData['attribute_code']);
        $this->attribute->expects($this->exactly(2))
            ->method('getIsUserDefined')
            ->willReturn($attributeData['is_user_defined']);
        $this->attribute->expects($this->once())
            ->method('getFrontendInput')
            ->willReturn($attributeData['frontend_input']);
        $this->attribute->expects($this->once())
            ->method('getIsSystem')
            ->willReturn($attributeData['is_system']);

        $addDataParams = $resultData;
        $addDataParams['validate_rules'] = $attributeData['validate_rules'];
        $this->attribute->expects($this->any())
            ->method('addData')
            ->with($addDataParams)
            ->willReturnSelf();

        $this->attribute->expects($this->once())
            ->method('setData')
            ->with($attributeData['use_default'])
            ->willReturnSelf();

        $exception = new \Exception(__($errorMessage));
        $this->attribute->expects($this->once())
            ->method('save')
            ->willThrowException($exception);

        $this->helperData->expects($this->any())
            ->method('getAttributeDefaultValueByInput')
            ->with($data['frontend_input'])
            ->willReturn($attributeData['default_value']);

        $this->helperData->expects($this->once())
            ->method('checkValidateRules')
            ->willReturn([]);

        $this->helperData->expects($this->any())
            ->method('getAttributeValidateRules')
            ->with($data['frontend_input'], $resultData)
            ->willReturn($attributeData['validate_rules']);

        $this->helperAddress->expects($this->once())
            ->method('filterPostData')
            ->with($data)
            ->willReturn($data);

        $this->config->expects($this->once())
            ->method('getEntityType')
            ->with('customer_address')
            ->willReturn($this->entityType);

        $this->entityType->expects($this->any())
            ->method('getId')
            ->willReturn($entityTypeId);

        $this->filterManager->expects($this->any())
            ->method('stripTags')
            ->with($attributeData['default_value'])
            ->willReturn($attributeData['default_value']);

        $this->messageManager->expects($this->once())
            ->method('addException')
            ->with($exception, __('We can\'t save the customer address attribute right now.'))
            ->willReturnSelf();

        $setDataParams = $resultData;
        $setDataParams['validate_rules'] = $attributeData['validate_rules'];
        $this->session->expects($this->once())
            ->method('setAttributeData')
            ->with($setDataParams)
            ->willReturnSelf();

        $this->resultRedirect->expects($this->once())
            ->method('setPath')
            ->with('adminhtml/*/edit', ['_current' => true])
            ->willReturn($this->resultRedirect);

        $this->assertEquals($this->resultRedirect, $this->model->execute());
    }

    /**
     * @return array
     */
    public function dataProviderExecuteSaveAndException()
    {
        return [
            [
                'data' => [
                    'attribute_code' => null,
                    'is_user_defined' => null,
                    'is_system' => null,

                    'used_in_forms' => [],
                    'scope_default_value' => null,
                    'entity_type_id' => null,
                    'validate_rules' => null,

                    'frontend_input' => 'frontend_input',
                ],
                'result_data' => [
                    'attribute_code' => 'attribute_code',
                    'is_user_defined' => 1,
                    'is_system' => 1,

                    'frontend_input' => 'frontend_input',

                    'used_in_forms' => ['adminhtml_customer_address'],
                    'scope_default_value' => 'default_value',
                    'entity_type_id' => 1,
                    'validate_rules' => null,
                ],
                'attribute_data' => [
                    'attribute_code' => 'attribute_code',
                    'is_user_defined' => 1,
                    'frontend_input' => 'frontend_input',
                    'is_system' => 1,

                    'default_value' => 'default_value',
                    'backend_model' => 'backend_model',
                    'source_model' => 'source_model',
                    'backend_type' => 'backend_type',
                    'validate_rules' => 'validate_rules',
                    'use_default' => 'use_default',
                ],
                'website_id' => 1,
                'attribute_id' => 1,
                'entity_type_id' => 1,
                'error_message' => 'error message'
            ],
        ];
    }

    /**
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function testExecuteWithOptionsDataError()
    {
        $serializedOptions = '{"key":"value"}';
        $message = "The attribute couldn't be saved due to an error. Verify your information and try again. "
            . "If the error persists, please try again later.";

        $this->request->expects($this->once())
            ->method('isPost')
            ->willReturn(true);
        $this->request->expects($this->once())
            ->method('getPostValue')
            ->willReturn(['key' => 'value']);
        $this->request
            ->expects($this->any())
            ->method('getParam')
            ->willReturnMap([
                ['serialized_options', '[]', $serializedOptions],
            ]);
        $this->formDataSerializerMock
            ->expects($this->once())
            ->method('unserialize')
            ->with($serializedOptions)
            ->willThrowException(new \InvalidArgumentException('Some exception'));
        $this->messageManager
            ->expects($this->once())
            ->method('addErrorMessage')
            ->with($message);
        $this->resultRedirect
            ->expects($this->once())
            ->method('setPath')
            ->with('adminhtml/*/edit', ['_current' => true])
            ->willReturnSelf();

        $this->assertEquals($this->resultRedirect, $this->model->execute());
    }
}
