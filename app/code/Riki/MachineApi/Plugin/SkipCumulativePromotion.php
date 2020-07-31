<?php

namespace Riki\MachineApi\Plugin;

class SkipCumulativePromotion
{
    /**
     * @var \Riki\MachineApi\Helper\Data
     */
    protected $helper;

    /**
     * SkipCumulativePromotion constructor.
     *
     * @param \Riki\MachineApi\Helper\Data $helper
     */
    public function __construct(\Riki\MachineApi\Helper\Data $helper)
    {
        $this->helper = $helper;
    }

    /**
     * Free machine replacement does not include any free gift
     *
     * @param $subject
     * @param $observer
     *
     * @return array
     */
    public function beforeExecute($subject, $observer)
    {
        $quote = $observer->getQuote();

        if(!$quote instanceof \Riki\Subscription\Model\Emulator\Cart && $this->helper->isMachineApiRequest()){
            $quote->setSkipCumulativePromotion(true);
        }

        return [$observer];
    }
}
