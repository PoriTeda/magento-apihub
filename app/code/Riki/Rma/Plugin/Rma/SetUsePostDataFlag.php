<?php

namespace Riki\Rma\Plugin\Rma;

class SetUsePostDataFlag
{
    const RMA_ALLOWED_REFUND = 1;

    /**
     * @var \Riki\Rma\Helper\Amount
     */
    private $amountHelper;

    /**
     * @var \Magento\Rma\Api\RmaRepositoryInterface
     */
    private $rmaRepository;

    /**
     * @var \Riki\Rma\Helper\Refund
     */
    private $refundHelper;

    /**
     * @var \Riki\Rma\Helper\Data
     */
    private $dataHelper;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * SetUsePostDataFlag constructor.
     * @param \Riki\Rma\Helper\Amount $amountHelper
     * @param \Magento\Rma\Api\RmaRepositoryInterface $rmaRepository
     * @param \Riki\Rma\Helper\Refund $refundHelper
     * @param \Riki\Rma\Helper\Data $dataHelper
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Riki\Rma\Helper\Amount $amountHelper,
        \Magento\Rma\Api\RmaRepositoryInterface $rmaRepository,
        \Riki\Rma\Helper\Refund $refundHelper,
        \Riki\Rma\Helper\Data $dataHelper,
        \Magento\Framework\Registry $registry
    ) {
        $this->amountHelper = $amountHelper;
        $this->rmaRepository = $rmaRepository;
        $this->refundHelper = $refundHelper;
        $this->dataHelper = $dataHelper;
        $this->registry = $registry;
    }

    /**
     * We use plugin to set this flag because we don't want to override controller
     *
     * @param $subject
     * @param $data
     *
     * @return array
     */
    public function beforeSaveRma($subject, $data)
    {
        $subject->setUsePostData(true);
        return [$data];
    }

    /**
     * Plugin save rma function to set value to refund_allowed field
     * @param \Magento\Rma\Model\Rma $subject
     * @param \Closure $proceed
     * @param array $data
     * @return \Magento\Rma\Model\Rma
     */
    public function aroundSaveRma(\Magento\Rma\Model\Rma $subject, \Closure $proceed, $data)
    {
        $rma = $proceed($data);
        //just set value to refund_allowed, refund_method field if action is save new
        if (!isset($data['entity_id']) && $rma instanceof \Magento\Rma\Model\Rma) {
            $rma->setData('skip_full_partial_validation_flag', true);
            $updatedFlag = 0;
            if ($this->amountHelper->isAllowedRefund($rma)) {
                $rma->setData('refund_allowed', self::RMA_ALLOWED_REFUND);
                $updatedFlag = 1;
            }
            $refundMethods = $this->refundHelper->getRefundMethodsByPaymentMethod(
                $this->dataHelper->getRmaOrderPaymentMethodCode($rma),
                $rma
            );
            if (!empty($refundMethods) && empty($rma->getRefundMethod())) {
                $defaultRefundMethod = current(array_keys($refundMethods));
                $rma->setData('refund_method', $defaultRefundMethod);
                $updatedFlag = 1;
            }
            if ($updatedFlag) {
                if ($this->registry->registry('rma_save_more_refund_data')) {
                    $this->registry->unregister('rma_save_more_refund_data');
                }
                $this->registry->register('rma_save_more_refund_data', true);
                $this->rmaRepository->save($rma);
            }
        }
        return $rma;
    }
}
