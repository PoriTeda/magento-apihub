<?php
namespace Riki\Fraud\Plugin\Rma\Model\Rma;

class Cedyna
{
    /**
     * @var \Riki\Fraud\Helper\CedynaThreshold
     */
    protected $cedynaThreshold;

    /**
     * Cedyna constructor.
     *
     * @param \Riki\Fraud\Helper\CedynaThreshold $cedynaThreshold
     */
    public function __construct(
        \Riki\Fraud\Helper\CedynaThreshold $cedynaThreshold
    ) {
        $this->cedynaThreshold = $cedynaThreshold;
    }

    /**
     * Decrease cedyna_counter
     *
     * @param \Magento\Rma\Model\Rma $subject
     *
     * @return array
     */
    public function beforeAfterSave(\Magento\Rma\Model\Rma $subject)
    {
        if (!$subject->dataHasChangedFor('return_status')) {
            return [];
        }

        if ($subject->getData('return_status') != \Riki\Rma\Api\Data\Rma\ReturnStatusInterface::COMPLETED) {
            return [];
        }

        $this->cedynaThreshold->updateCedynaValueAfterReturnApproved($subject);

        return [];
    }
}