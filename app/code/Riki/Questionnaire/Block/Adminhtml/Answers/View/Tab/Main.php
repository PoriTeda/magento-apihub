<?php
namespace Riki\Questionnaire\Block\Adminhtml\Answers\View\Tab;
use Magento\Backend\Block\Widget\Form;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Stdlib\DateTime;
use Magento\Backend\Block\Widget;
use Magento\Sales\Model\OrderFactory;
use Riki\Questionnaire\Model\ChoiceFactory;
use Riki\Questionnaire\Model\QuestionFactory;
use Riki\Questionnaire\Model\QuestionnaireFactory;
use Magento\Catalog\Model\ProductFactory;

/**
 * Class Main
 * @package Riki\Questionnaire\Block\Adminhtml\Answers\View\Tab
 */
class Main extends Widget implements TabInterface
{
    /**
     * @var string
     */
    protected $_template = 'answers/view/main.phtml';

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var ChoiceFactory
     */
    protected $_choiceFactory;

    /**
     * @var QuestionFactory
     */
    protected $_questionFactory;

    /**
     * @var \Magento\Framework\DataObject[]
     */
    protected $_values = [];

    /**
     * @var \Riki\Questionnaire\Model\Answers
     */
    protected $_answerInstance;

    /**
     * @var \Riki\Questionnaire\Model\Answers
     */
    protected $_answers;

    /**
     * @var QuestionnaireFactory
     */
    protected $_questionnaireFactory;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $_customerRepository;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $_orderRepository;

    /**
     * Main constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\Questionnaire\Model\Answers $answers
     * @param QuestionFactory $questionFactory
     * @param ChoiceFactory $choiceFactory
     * @param QuestionnaireFactory $questionnaireFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepositoryInterface
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Riki\Questionnaire\Model\Answers $answers,
        \Riki\Questionnaire\Model\QuestionFactory $questionFactory,
        \Riki\Questionnaire\Model\ChoiceFactory $choiceFactory,
        QuestionnaireFactory $questionnaireFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepositoryInterface,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_answers = $answers;
        $this->_questionFactory = $questionFactory;
        $this->_choiceFactory = $choiceFactory;
        $this->_questionnaireFactory = $questionnaireFactory;
        $this->_productRepository = $productRepository;
        $this->_customerRepository = $customerRepository;
        $this->_orderRepository = $orderRepositoryInterface;
        parent::__construct($context, $data);
    }

    /**
     * Get Answer current
     *
     * @return mixed|\Riki\Questionnaire\Model\Answers
     */
    public function getAnswers()
    {
        if (!$this->_answerInstance) {
            $answer = $this->_coreRegistry->registry('current_answers');
            if ($answer) {
                $this->_answerInstance = $answer;
            } else {
                $this->_answerInstance = $this->_answers;
            }
        }
        return $this->_answerInstance;
    }

    /**
     * Set answer
     *
     * @param $answers
     *
     * @return $this
     */
    public function setAnswers($answers)
    {
        $this->_answerInstance = $answers;

        return $this;
    }

    /**
     * Get Current answer id
     *
     * @return mixed
     */
    public function getCurrentAnswerId()
    {
        return $this->getAnswers()->getId();
    }

    /**
     * Get current questionnaire data
     *
     * @return \Riki\Questionnaire\Model\Questionnaire|mixed
     */
    public function getQuestionnaireCurrent()
    {
        $questionnaireId = $this->getAnswers()->getEnqueteId();
        return $this->_questionnaireFactory->create()->load($questionnaireId);
    }

    /**
     * @return \Magento\Customer\Model\Customer|mixed
     */
    public function getCustomerCurrent()
    {
        $customerId = $this->getAnswers()->getCustomerId();

        return $this->_customerRepository->getById($customerId);
    }

    /**
     * Get URL to edit the customer.
     *
     * @return string
     */
    public function getCustomerViewUrl()
    {
        if (!$this->getAnswers()->getCustomerId()) {
            return '';
        }

        return $this->getUrl('customer/index/edit', ['id' => $this->getAnswers()->getCustomerId()]);
    }

    /**
     * Get URL to edit the customer.
     *
     * @return string
     */
    public function getEnqueteViewUrl()
    {
        if (!$this->getAnswers()->getEnqueteId()) {
            return '';
        }

        return $this->getUrl('questionnaire/questions/edit', ['enquete_id' => $this->getAnswers()->getEnqueteId()]);
    }

    /**
     * Get URL to edit the customer.
     *
     * @return string
     */
    public function getOrderViewUrl()
    {
        if (!$this->getAnswers()->getOrderId()) {
            return '';
        }

        return $this->getUrl('sales/order/view', ['order_id' => $this->getAnswers()->getOrderId()]);
    }

    /**
     * Get URL to edit the profile.
     *
     * @return string
     */
    public function getProfileViewUrl()
    {
        $profileId = $this->getAnswers()->getData('entity_id');
        if (!$profileId) {
            return '';
        }

        return $this->getUrl('profile/profile/edit', ['id' => $profileId]);
    }

    /**
     * Get URL to view detail product by SKU
     *
     * @return string
     */
    public function getProductViewUrl()
    {
        if ($this->getQuestionnaireCurrent()->getLinkedProductSku()=='')
        {
            return '';
        }
        $sku = $this->getQuestionnaireCurrent()->getLinkedProductSku();
        try{
            $product = $this->_productRepository->get($sku);
            $productId = $product->getId();
            return $this->getUrl('catalog/product/edit', ['id' => $productId]);

        }catch (\Exception $e){
            return '';
        }


    }

    /**
     * Get current order increment of answer
     *
     * @return string
     */
    public function getOrderIncrement()
    {
        if ($this->getOrder()!== null) {
            return $this->getOrder()->getIncrementId();
        }
        return '';
    }

    /**
     * Get Order current
     *
     * @return \Magento\Sales\Model\Order|null
     */
    public function getOrder()
    {
        $orderId = $this->getAnswers()->getData('entity_id');
        if (!$orderId) {
            return null;
        }
        $order = $this->_orderRepository->get($orderId);
        return $order;
    }

    /**
     * Render html enquete code
     *
     * @return string
     */
    public function renderEnqueteCode()
    {
        $htmlResult = '';
        if ($_enqueteViewUrl = $this->getEnqueteViewUrl()) {
            $htmlResult .= '<a href="'.$_enqueteViewUrl.'" target="_blank">';
            $htmlResult .= '<span>'.$this->escapeHtml($this->getQuestionnaireCurrent()->getCode()).'</span>';
            $htmlResult .= '</a>';
        } else {
            $htmlResult .= $this->escapeHtml($this->getQuestionnaireCurrent()->getCode());
        }
        return $htmlResult;
    }

    /**
     * Render html enquete name
     *
     * @return string
     */
    public function renderEnqueteName()
    {
        $htmlResult = '';

        if ($_enqueteViewUrl = $this->getEnqueteViewUrl()) {
            $htmlResult .= '<a href="'.$_enqueteViewUrl.'" target="_blank">';
            $htmlResult .= '<span>'.$this->escapeHtml($this->getQuestionnaireCurrent()->getName()).'</span>';
            $htmlResult .= '</a>';
        } else {
            $htmlResult .= $this->escapeHtml($this->getQuestionnaireCurrent()->getName());
        }

        return $htmlResult;
    }

    /**
     * Render html customer id
     * 
     * @return string
     */
    public function renderCustomerId()
    {
        $htmlResult = '';

        if ($_customerUrl = $this->getCustomerViewUrl()) {
            $htmlResult .= '<a href="'.$_customerUrl.'" target="_blank">';
            $htmlResult .= '<span>'.$this->escapeHtml($this->getAnswers()->getCustomerId()).'</span>';
            $htmlResult .= '</a>';
        } else {
            $htmlResult .= $this->escapeHtml($this->getAnswers()->getCustomerId());
        }

        return $htmlResult;
    }
    
    /**
     * Render html customer consumer db id
     * 
     * @return string
     */
    public function renderCustomerCode()
    {
        $htmlResult = '';
        $consumerObject = $this->getCustomerCurrent()->getCustomAttribute('consumer_db_id');
        $consumerId = !is_null($consumerObject) ? $consumerObject->getValue() : null;
        if ($_customerUrl = $this->getCustomerViewUrl() && $consumerId != null) {
            $htmlResult .= '<a href="'.$_customerUrl.'" target="_blank">';
            $htmlResult .= '<span>'.$this->escapeHtml($consumerId).'</span>';
            $htmlResult .= '</a>';
        } elseif ($consumerId != null) {
            $htmlResult .= $this->escapeHtml($consumerId);
        }

        return $htmlResult;
    }

    /**
     * Render html entity type
     *
     * @return string
     */
    public function renderEntityType()
    {
        $entityTypeTitle = '';
        $enqueteType = $this->getQuestionnaireCurrent()->getData('enquete_type');
        switch ($enqueteType){
            case \Riki\Questionnaire\Model\Questionnaire::CHECKOUT_QUESTIONNAIRE:
                $entityTypeTitle = __('Checkout Questionnaire');
                break;
            case \Riki\Questionnaire\Model\Questionnaire::DISENGAGEMENT_QUESTIONNAIRE:
                $entityTypeTitle =  __('Disengagement Questionnaire');
        }

        return $this->escapeHtml($entityTypeTitle);
    }

    /**
     * Render html entity id
     *
     * @return string
     */
    public function renderEntityId()
    {
        $htmlResult = '';
        $enqueteType = $this->getQuestionnaireCurrent()->getData('enquete_type');
        switch ($enqueteType){
            case \Riki\Questionnaire\Model\Questionnaire::CHECKOUT_QUESTIONNAIRE:
                if ($_orderUrl = $this->getOrderViewUrl()) {
                    $htmlResult .= '<a href="'.$_orderUrl.'" target="_blank">';
                    $htmlResult .= '<span>'.$this->escapeHtml($this->getOrderIncrement()).'</span>';
                    $htmlResult .= '</a>';
                } else {
                    $htmlResult .= $this->escapeHtml($this->getOrderIncrement());
                }
                break;
            case \Riki\Questionnaire\Model\Questionnaire::DISENGAGEMENT_QUESTIONNAIRE:
                if ($profileUrl = $this->getProfileViewUrl()) {
                    $htmlResult .= '<a href="'.$profileUrl.'" target="_blank">';
                    $htmlResult .= '<span>'.$this->escapeHtml($this->getAnswers()->getData('entity_id')).'</span>';
                    $htmlResult .= '</a>';
                } else {
                    $htmlResult .= $this->escapeHtml($this->getAnswers()->getData('entity_id'));
                }
                break;
            default:
                break;
        }


        return $htmlResult;
    }

    /**
     * Render html SKU link
     *
     * @return string
     */
    public function renderSKU()
    {
        $htmlResult = '';

        if ($_productUrl = $this->getProductViewUrl()) {
            $htmlResult .= '<a href="'.$_productUrl.'" target="_blank">';
            $htmlResult .= '<span>'.$this->escapeHtml($this->getQuestionnaireCurrent()->getLinkedProductSku()).'</span>';
            $htmlResult .= '</a>';
        } else {
            $htmlResult .= $this->escapeHtml($this->getQuestionnaireCurrent()->getLinkedProductSku());
        }

        return $htmlResult;
    }

    /**
     * Prepare label for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Answers information');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Answers information');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }
}