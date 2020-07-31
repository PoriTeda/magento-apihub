<?php
namespace Riki\TmpRma\Observer;

use Magento\Framework\Event\ObserverInterface;

class TmpRmaPrepareDataForSave implements ObserverInterface
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * TmpRmaPrepareDataForSave constructor.
     *
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    ) {
        $this->timezone = $timezone;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $postData = $observer->getEvent()->getPostData();

        if (!isset($postData['returned_date'])) {
           return;
        }

        $today = $this->timezone->date()->setTime(0, 0, 0);
        $returnedDate = $this->timezone->date($postData['returned_date'])->setTime(0, 0, 0);
        if ($returnedDate > $today) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The return date must lesser the current date'));
        }
    }
}