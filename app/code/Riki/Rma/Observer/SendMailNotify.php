<?php
namespace Riki\Rma\Observer;

class SendMailNotify implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var  \Riki\Rma\Model\Rma
     */
    protected $rma;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $_customerRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $_priceHelper;

    /**
     * SendMailNotify constructor.
     *
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\Pricing\Helper\Data $priceHelper
    )
    {
        $this->_logger = $logger;
        $this->_priceHelper = $priceHelper;
        $this->_customerRepository = $customerRepository;
    }

    /**
     * Set rma
     *
     * @param \Riki\Rma\Model\Rma  $rma
     *
     * @return  $this
     */
    public function setRma(\Riki\Rma\Model\Rma  $rma)
    {
        $this->rma = $rma;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->rma) {
            return;
        }

        $variables = $observer->getEvent()->getVars();
        if (!$variables) {
            return;
        }

        $variables->setReturnNumber($this->rma->getIncrementId());
        $return_amount = empty($this->rma->getData('total_return_amount_adjusted'))
            ? 0
            : $this->rma->getData('total_return_amount_adjusted');
        $variables->setReturnAmount($this->_priceHelper->currency($return_amount, true, false));
        $variables->setConsumerDbId($this->rma->getConsumerDbId());

        $observer->getEvent()->setVars($variables);

        $this->rma = null;
    }
}