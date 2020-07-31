<?php
namespace Riki\Rma\Plugin\Paygent\Helper;

/**
 * Class Data
 *
 * @package Riki\Rma\Plugin\Paygent\Helper
 * @deprecated
 */
class Data
{
    /**
     * @var bool|null
     */
    protected $_isRefundByPaygent;

    /** @var  \Magento\Rma\Model\Rma */
    protected $_rma;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $_customerRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * Data constructor.
     * @param \Psr\Log\LoggerInterface $logger
     *
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
    )
    {
        $this->_logger = $logger;
        $this->_customerRepository = $customerRepository;
    }


    /**
     * Setter
     *
     * @param $isRefundByPaygent
     * @return $this
     */
    public function setIsRefundByPaygent($isRefundByPaygent)
    {
        $this->_isRefundByPaygent = $isRefundByPaygent;
        return $this;
    }

    /**
     * Getter
     *
     * @return bool
     */
    public function getIsRefundByPaygent()
    {
        return $this->_isRefundByPaygent;
    }

    /**
     * Setter
     *
     * @param \Magento\Rma\Model\Rma $rma
     * @return $this
     */
    public function setRma(\Magento\Rma\Model\Rma $rma)
    {
        $this->_rma = $rma;
        return $this;
    }

    /**
     * Getter
     *
     * @return \Magento\Rma\Model\Rma
     */
    public function getRma()
    {
        return $this->_rma;
    }


    /**
     * Extend sendMailNotify
     *
     * @param \Bluecom\Paygent\Helper\Data $subject
     * @param $email
     * @param $senderInfo
     * @param $template
     * @param $variables
     * @return array
     */
    public function beforeSendMailNotify(\Bluecom\Paygent\Helper\Data $subject, $email, $senderInfo, $template, $variables)
    {
        if (!$this->getIsRefundByPaygent()) {
            return [$email, $senderInfo, $template, $variables];
        }

        $rma = $this->getRma();
        if (!$rma) {
            return [$email, $senderInfo, $template, $variables];
        }

        $variables['return_number'] = $rma->getIncrementId();
        $variables['return_amount'] = $rma->getData('total_return_amount_adjusted');

        try {
            $customer = $this->_customerRepository->getById($rma->getCustomerId());
            $consumerDbId = $customer->getCustomAttribute('consumer_db_id');
            if ($consumerDbId) {
                $variables['consumer_db_id'] = $consumerDbId->getValue();
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->_logger->critical($e);
        }

        return [$email, $senderInfo, $template, $variables];
    }
}