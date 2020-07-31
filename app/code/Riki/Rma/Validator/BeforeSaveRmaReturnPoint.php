<?php

namespace Riki\Rma\Validator;

use Magento\Rma\Model\Rma\Source\Status;
use Riki\Rma\Model\Rma;

class BeforeSaveRmaReturnPoint extends \Magento\Framework\Validator\AbstractValidator
{
    const IGNORE_VALIDATE = 'is_ignore_validate_return_point';
    /**
     * @var \Riki\Rma\Helper\Amount
     */
    protected $rmaAmountHelper;

    /**
     * @var \Riki\Rma\Helper\Data
     */
    protected $rmaHelper;

    /**
     * CustomerPointBalance constructor.
     *
     * @param \Riki\Rma\Helper\Amount $rmaAmountHelper
     * @param \Riki\Rma\Helper\Data $rmaHelper
     */
    public function __construct(
        \Riki\Rma\Helper\Amount $rmaAmountHelper,
        \Riki\Rma\Helper\Data $rmaHelper
    ) {
        $this->rmaAmountHelper = $rmaAmountHelper;
        $this->rmaHelper = $rmaHelper;
    }

    /**
     * Sum of return point shouldn't exceed order's used point.
     *
     * @param  Rma $rma
     * @return boolean
     */
    public function isValid($rma)
    {
        /*validate data is a flag to pass this validation for some special case*/
        if (!$rma->getData(self::IGNORE_VALIDATE)) {
            $siblingRmas = $rma->getSiblingRmas();
            $capturedPoint = 0;

            foreach ($siblingRmas as $siblingRma) {
                if ($siblingRma->getStatus() != Status::STATE_CLOSED) {
                    $capturedPoint += $siblingRma->getData('total_return_point');
                }
            }

            $order = $this->rmaHelper->getRmaOrder($rma);
            $returnablePoint = max(0, $order->getUsedPoint() - $capturedPoint);

            if ($rma->getData('total_return_point') > $returnablePoint) {
                $this->_addMessages(
                    ['return point is exceeded ' => __('Return point is exceeded order\'s used point.')]
                );

                return false;
            }
        }

        return !$this->hasMessages();
    }
}
