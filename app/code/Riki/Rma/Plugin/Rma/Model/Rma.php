<?php
namespace Riki\Rma\Plugin\Rma\Model;

use \Riki\Rma\Command\ReturnComplete;

class Rma
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * Rma constructor.
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Framework\Registry $registry
    )
    {
        $this->registry = $registry;
    }

    /**
     * @param \Riki\Rma\Model\Rma $subject
     * @return array
     */
    public function beforeValidateSaveAgain(
        \Riki\Rma\Model\Rma $subject
    ) {
        if ($this->registry->registry(ReturnComplete::RETURN_COMPLETE_COMMAND_MODE)) {
            $subject->setData(\Riki\Rma\Model\Rma::SKIP_VALIDATE_NEED_TO_SAVE_AGAIN_FLAG, true);
        }

        return [];
    }
}