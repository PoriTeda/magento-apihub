<?php

namespace Riki\Rma\Observer;

use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class EditRmaSetNeedSaveAgainFlag implements ObserverInterface
{
    /**
     * @var \Riki\Rma\Helper\Data
     */
    protected $rmaHelper;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Constructor.
     *
     * @param \Riki\Rma\Helper\Data $rmaHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        \Riki\Rma\Helper\Data $rmaHelper,
        LoggerInterface $logger
    )
    {
        $this->rmaHelper = $rmaHelper;
        $this->logger = $logger;
    }

    /**
     * When some fields which affect total calculation of RMA, it has to be review again.
     * Observer rma_save_before event
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @throws \Exception
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /* @var \Riki\Rma\Model\Rma $rma */
        $rma = $observer->getRma();

        // No need set the flag for new object
        if (!$rma->getId() || $this->rmaHelper->getSkipNeedToSaveAgain()) {
            return;
        }
        if ($rma->getData('skip_full_partial_validation_flag')) {
            return;
        }
        $rma->removeExtensionData('need_save_again');

        if (in_array($rma->getReturnStatus(), $this->rmaHelper->getStageTwoStatuses())) {
            $needSaveAgain = [];
            foreach ($this->rmaHelper->getNeedSaveAgainFields() as $field) {
                if ($rma->dataHasChangedFor($field)) {
                    if ($field == 'trigger_cancel_point') {
                        if (($source = $rma->getSource())) {
                            $needSaveAgain[$field] = $source->getIncrementId();
                        } else {
                            continue;
                        }
                    } else {
                        $needSaveAgain[$field] = $field;
                    }
                }
            }

            if ($needSaveAgain) {
                $rma->addExtensionData(['need_save_again' => $needSaveAgain]);
            }
        }
    }
}
