<?php
namespace Riki\Sales\Block\Adminhtml\Order\Create;
use Riki\Questionnaire\Model\Questionnaire as QuestionnaireModel;
use Riki\Questionnaire\Model\QuestionnaireFactory;

/**
 * Class Questionnaire
 * @package Riki\Sales\Block\Adminhtml\Order\Create
 */
class Questionnaire extends \Magento\Sales\Block\Adminhtml\Order\Create\AbstractCreate
{
    /**
     * @var \Riki\Questionnaire\Model\QuestionnaireFactory
     */
    protected $_questionnaireFactory;

    /**
     * @var \Riki\Questionnaire\Helper\Data
     */
    protected $_questionnaireHelper;

    /**
     * Questionnaire constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Model\Session\Quote $sessionQuote
     * @param \Magento\Sales\Model\AdminOrder\Create $orderCreate
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Riki\Questionnaire\Helper\Data $questionnaireHelper
     * @param QuestionnaireFactory $questionnaireFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Magento\Sales\Model\AdminOrder\Create $orderCreate,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Riki\Questionnaire\Helper\Data $questionnaireHelper,
        QuestionnaireFactory $questionnaireFactory,
        array $data = []
    ){
        parent::__construct(
            $context,
            $sessionQuote,
            $orderCreate,
            $priceCurrency,
            $data
        );

        $this->_questionnaireHelper = $questionnaireHelper;
        $this->_questionnaireFactory = $questionnaireFactory;

    }

    /**
     * Get questionnaire data on create order admin
     * 
     * @return array
     */
    public function getQuestionnaires()
    {
        $quoteItems = $this->getQuote()->getAllVisibleItems();
        $output = $skuArr = [];

        foreach ($quoteItems as $item) {
            $skuArr[] = $item->getSku();
        }
        if (!empty($skuArr)) {
            $itemData = $this->_questionnaireHelper->getQuestionnaireBySKUs(
                $skuArr,
                QuestionnaireModel::VISIBILITY_NONE
            );
            if (!empty($itemData)) {
                $output['questionnaire'] = $itemData;
            }
        }
        if (empty($output)) {
            $output['questionnaire'] = $this->_questionnaireHelper->getQuestionnaireDefault(QuestionnaireModel::VISIBILITY_NONE);
        }

        return $output;
    }
    
}
