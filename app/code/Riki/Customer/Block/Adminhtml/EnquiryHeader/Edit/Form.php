<?php
namespace Riki\Customer\Block\Adminhtml\EnquiryHeader\Edit;

use \Magento\Backend\Block\Widget\Form\Generic;

class Form extends Generic
{

    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    /**
     * @var \Riki\Customer\Model\Config\Source\EnquiryHeaderCategory
     */
    protected $enQuiryHeaderCategory;

    protected $model;
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;
    /**
     * @var boolean
     */
    protected $showJavaScript;

    protected $hiddenButton;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        \Riki\Customer\Model\Config\Source\EnquiryHeaderCategory $enQuiryHeaderCategory,
        \Riki\Customer\Model\EnquiryHeader $model,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        array $data = []
    ) {
        $this->_systemStore = $systemStore;
        $this->enQuiryHeaderCategory = $enQuiryHeaderCategory;
        $this->model = $model;
        $this->urlBuilder         = $context->getUrlBuilder();
        $this->customerRepository = $customerRepositoryInterface;
        $this->customerFactory    = $customerFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Init form
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('enquiryheader_form');
        $this->setTitle(__('Enquiry Information'));
    }

    /**
     * Get customer ID
     *
     * @param $customerId
     *
     * @return \Magento\Customer\Api\CustomerRepositoryInterface
     */
    public function getCustomerById($customerId){
        /**@var \Magento\Customer\Api\CustomerRepositoryInterface $customer */
        $customer = $this->customerFactory->create()->load($customerId);
        return $customer;
    }

    /**
     * Render form
     *
     * @return null
     */
    public function loadDataForm(){
        $arrData = null;
        $idNameInLayout = $this->getParentBlock()->getNameInLayout();
        $data = $this->getLayout()->getBlock($idNameInLayout)->getData();
        if(isset($data['enquiryDetail'])){
            $arrData = $data['enquiryDetail'];
            //set consumer name
            /**@var \Magento\Customer\Api\CustomerRepositoryInterface $customerDetail */
            $customerDetail = $this->getCustomerById($arrData['customer_id']);
            if($customerDetail){
                $arrData['consumer_name'] = $customerDetail->getData('lastname') .' '. $customerDetail->getData('firstname');
            }
        }

        return $arrData;
    }

    /**
     * Render form enquiry
     *
     * @param \Magento\Framework\Data\Form $form
     * @return \Magento\Framework\Data\Form
     */
    public function renderFormEnquiry(\Magento\Framework\Data\Form $form,$enquiryId){

        $form->setHtmlIdPrefix('enquiryheader_'.$enquiryId.'_');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Enquiry Information'), 'class' => 'fieldset-wide']
        );


        //only use on form enquiry
        if($this->showJavaScript){
            $addButtonSearchOrder    = [
                'label' => __('Search Order Number'),
                'onclick' => "if($(searchorder).visible()){
                           $(searchorder).hide(); $$('.action-searchorder span')[0].innerHTML = '".__('Search Order Number')."';}else{
                           $(searchorder).show(); $$('.action-searchorder span')[0].innerHTML = '".__('Hide Search Order Number')."';}",
                'class' => 'action-add action-secondary action-searchorder ',
                'style' => 'margin-top:10px;'
            ];
        }else{
            //use on order,customer profile
            $addButtonSearchOrder    = [
                'label' => __('Search Order Number'),
                'class' => 'action-add action-secondary action-searchorder btnSearchOrderId',
                'style' => 'margin-top:10px;'
            ];
        }


        $showSearchOrder = $showButtonSearch = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')->setData($addButtonSearchOrder)->toHtml();
        if ($this->hiddenButton=='search_order'){
            $fieldset->addField(
                'order_id',
                'text',
                ['name' => 'order_id',
                    'label' => __('Order number'),
                    'title' => __('Order number'),
                ]
            );
        }else {
            $fieldset->addField(
                'order_id',
                'text',
                ['name' => 'order_id',
                    'label' => __('Order number'),
                    'title' => __('Order number'),
                    'after_element_html' => $showSearchOrder
                ]
            );
        }

        $fieldSearchGridOrderId = $fieldset->addField(
            'search_grid_order_id',
            'text',
            ['name' => 'search_grid_order_id',
                'label' => __('Order number'),
                'title' => __('Order number'),
                'required' => false,
            ]
        );


        $fieldSearchGridOrderId->setRenderer($this->getLayout()->createBlock('\Riki\Customer\Block\Adminhtml\EnquiryHeader\Edit\SearchOrder','',['data' => ['enquiry_id' => $enquiryId]]));

        //only use on form enquiry
        if ($this->showJavaScript) {

            $addButtonSearchCustomer    = [
                'label' => __('Search Customer ID'),
                'onclick' => "if($(searchcustomer).visible()){
                          $(searchcustomer).hide(); $$('.action-searchcustomer span')[0].innerHTML = '".__('Search Customer ID')."';}else{
                          $(searchcustomer).show(); $$('.action-searchcustomer span')[0].innerHTML = '".__('Hide Search Customer ID')."' ;}",
                'class' => 'action-add action-secondary action-searchcustomer',
                'style' => 'margin-top:10px;'
            ];

        } else {
            //use on order,customer profile

            $addButtonSearchCustomer    = [
                'label' => __('Search Customer ID'),
                'class' => 'action-add action-secondary action-searchcustomer btnSearchCustomerId',
                'style' => 'margin-top:10px;'
            ];

        }

        $fieldset->addField(
            'consumer_name',
            'text',
            ['name' => 'consumer_name',
                'label' => __('Consumer\'s name'),
                'title' => __('Consumer\'s name'),
                'required' => false,
                'disabled'=>true
            ]
        );

        $showButtonSearchCustomer =  $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')->setData($addButtonSearchCustomer)->toHtml();
        if ($this->hiddenButton=='search_customer' || $this->hiddenButton=='search_order' ){
            $fieldset->addField(
                'customer_id',
                'text',
                ['name' => 'customer_id',
                    'label' => __('Customer ID'),
                    'title' => __('Customer ID'),
                    'readonly'=>true,
                    'required' => true,
                ]
            );
        }else {
            $fieldset->addField(
                'customer_id',
                'text',
                ['name' => 'customer_id',
                    'label' => __('Customer ID'),
                    'title' => __('Customer ID'),
                    'required' => true,
                    'after_element_html' => $showButtonSearchCustomer
                ]
            );
        }



        $fieldSearchGridCustomerId = $fieldset->addField(
            'search_grid_customer_id',
            'text',
            ['name' => 'search_grid_customer_id',
                'label' => __('Customer ID'),
                'title' => __('Customer ID'),
                'required' => false,
            ]
        );

        $fieldSearchGridCustomerId->setRenderer($this->getLayout()->createBlock('\Riki\Customer\Block\Adminhtml\EnquiryHeader\Edit\SearchCustomer','',['data' => ['enquiry_id' => $enquiryId]]));

        $fieldset->addField(
            'enquiry_category_id',
            'select',
            ['name' => 'enquiry_category_id', 'label' => __('Category'), 'title' => __('Category'), 'required' => true,'values' => $this->enQuiryHeaderCategory->getOptionArray()]
        );

        $fieldset->addField(
            'enquiry_title',
            'text',
            ['name' => 'enquiry_title', 'label' => __('Title'), 'title' => __('Title'), 'required' => true]
        );

        $fieldset->addField(
            'enquiry_text',
            'textarea',
            ['name' => 'enquiry_text', 'label' => __('Text'), 'title' => __('Text'), 'required' => true]
        );

        //redirect link when create enquiry from order
        $fieldset->addField(
            'back_to_customer_profile',
            'hidden',
            ['name' => 'back_to_customer_profile']
        );

        return $form;
    }

    /**
     * Prepare form
     *
     * @return $this
     */
    protected function _prepareForm()
    {

        /** @var \Riki\Customer\Model\EnquiryHeader $model */
        $model = $this->_coreRegistry->registry('enqueryheader');

        $idNameInLayout = $this->getParentBlock()->getNameInLayout();
        $aData = $this->getLayout()->getBlock($idNameInLayout)->getData();
        $enquiryId = 0;
        if(isset($aData['enquiryDetail'])){
            $enquiryId = isset($aData['enquiryDetail']['id'])?$aData['enquiryDetail']['id']:0;
            $this->hiddenButton = isset($aData['enquiryDetail']['hidden_button']) ? $aData['enquiryDetail']['hidden_button'] : null;
        }

        //load data when edit data from order detail,customer
        $dataFromOrderDetail = $this->loadDataForm();

        if (is_array($dataFromOrderDetail) && count($dataFromOrderDetail) >0){

            //not show event javascript
            $this->showJavaScript=false;

            $form = $this->renderFormforOrderCustomer($dataFromOrderDetail,$enquiryId);

        } else{

            //not show event javascript
            $this->showJavaScript=true;

            //render form default
            $form = $this->renderFormEnquiryDefault($model,$enquiryId);

        }

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     *  Render form for order,customer profile
     *
     * @param $dataFromOrderDetail
     * @return \Magento\Framework\Data\Form
     */
    public function renderFormforOrderCustomer($dataFromOrderDetail,$enquiryId){

        $action = $this->urlBuilder->getUrl('customer/enquiryheader/save');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $action, 'method' => 'post']]
        );

        //render form default
        $fieldSet = $this->renderFormEnquiry($form,$enquiryId);

        if(isset($dataFromOrderDetail['id']) && $dataFromOrderDetail['id'] !=null ){
            $fieldSet->addField('id', 'hidden', ['name' => 'id']);
        }

        $addButtonEdit    = [
            'label' => __('Save data'),
            'type'=>'submit',
            'class' => 'action-add action-secondary action-searchcustomer',
            'style' => 'margin-top:10px;'
        ];

        //return to back link
        $fieldSet->addField(
            'return_back_link',
            'hidden',
            [
                'name' => 'return_back_link',
                'class'=> 'return_back_link',
                'after_element_html' => $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')->setData($addButtonEdit)->toHtml()
            ]
        );
        $fieldSet->addField(
            'not_validate_order',
            'hidden',
            [
                'name' => 'not_validate_order',
            ]
        );
        $fieldSet->addField(
            'current_order_id',
            'hidden',
            [
                'name' => 'current_order_id',
            ]
        );
        $fieldSet->addField(
            'current_customer_id',
            'hidden',
            [
                'name' => 'current_customer_id',
            ]
        );

        //set default value if have
        $form->setValues($dataFromOrderDetail);

        return $form;
    }

    /**
     * Render form default when create,edit enquiry
     *
     * @param \Riki\Customer\Model\EnquiryHeader $model
     *
     * @return \Magento\Framework\Data\Form
     */
    public function renderFormEnquiryDefault(\Riki\Customer\Model\EnquiryHeader $model,$enquiryId){

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post']]
        );

        //render form default
        $fieldSet = $this->renderFormEnquiry($form,$enquiryId);

        $arrData = $model->getData();
        if( isset($arrData['customer_id']) &&  $arrData['customer_id'] != null){
            $customerDetail = $this->getCustomerById($arrData['customer_id']);
            if($customerDetail){
                $arrData['consumer_name'] = $customerDetail->getData('lastname') .' '. $customerDetail->getData('firstname');
            }
        }

        if ($model && $model->getId() ) {
            $fieldSet->addField('id', 'hidden', ['name' => 'id']);
        }


        //set default value if have
        if($model){
            $form->setValues($arrData);
        }

        return $form;
    }


}