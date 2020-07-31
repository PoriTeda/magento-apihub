<?php

namespace Riki\FairAndSeasonalGift\Controller\Adminhtml;

abstract class Fair extends \Magento\Backend\App\Action
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * @var Registry
     */
    protected $registry;
    /**
     * @var jsonHelper
     */
    protected $_jsonHelper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_dateTime;

    /**
     * @var \Riki\FairAndSeasonalGift\Model\FairFactory
     */
    protected $_fairFactory;

    /**
     * @var \Riki\FairAndSeasonalGift\Model\FairConnectionFactory
     */
    protected $_fairConnectionFactory;

    /**
     * @var \Riki\FairAndSeasonalGift\Model\FairDetailFactory
     */
    protected $_fairDetailFactory;

    /**
     * @var \Riki\FairAndSeasonalGift\Model\FairRecommendation
     */
    protected $_fairRecommendationFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Riki\FairAndSeasonalGift\Model\FairFactory $fairFactory,
        \Riki\FairAndSeasonalGift\Model\FairConnectionFactory $fairConnectionFactory,
        \Riki\FairAndSeasonalGift\Model\FairDetailFactory $fairDetailFactory,
        \Riki\FairAndSeasonalGift\Model\FairRecommendationFactory $fairRecommendationFactory
    ) {
        parent::__construct($context);
        $this->context = $context;
        $this->resultForwardFactory = $resultForwardFactory;
        $this->registry = $registry;
        $this->_jsonHelper = $jsonHelper;
        $this->_logger = $logger;
        $this->_dateTime = $dateTime;
        $this->_fairFactory = $fairFactory;
        $this->_fairConnectionFactory = $fairConnectionFactory;
        $this->_fairDetailFactory = $fairDetailFactory;
        $this->_fairRecommendationFactory = $fairRecommendationFactory;
    }

    /**
     * {@inheritdoc}
     * @param \Magento\Backend\Model\View\Result\Page\Interceptor $resultPage
     * @return \Magento\Backend\Model\View\Result\Page\Interceptor
     */
    protected function initPage($resultPage)
    {
        $resultPage->setActiveMenu('Magento_Catalog::catalog');
        $resultPage->getConfig()->getTitle()->prepend(__('Fair Management'));

        return $resultPage;
    }

    /**
     * Current template model
     *
     * @return \Riki\FairAndSeasonalGift\Model\Fair
     */
    public function initModel()
    {
        $model = $this->_fairFactory->create();

        if ($this->getRequest()->getParam('fair_id')) {
            $model->load($this->getRequest()->getParam('fair_id'));
        }

        $this->registry->register('current_fair', $model);

        return $model;
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->context->getAuthorization()->isAllowed('Riki_FairAndSeasonalGift::fair_seasonal');
    }
}
