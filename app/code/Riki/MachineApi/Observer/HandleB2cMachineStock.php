<?php
namespace Riki\MachineApi\Observer;
use Magento\Framework\Exception\LocalizedException;

class HandleB2cMachineStock implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * Event manager
     *
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Riki\MachineApi\Helper\Machine
     */
    protected $helper;

    /**
     * HandleB2cMachineStock constructor.
     * @param \Riki\MachineApi\Helper\Machine $helper
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     */
    public function __construct(
        \Riki\MachineApi\Helper\Machine $helper,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Framework\Event\ManagerInterface $eventManager
    ) {
        $this->helper = $helper;
        $this->quoteRepository = $quoteRepository;
        $this->eventManager = $eventManager;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @throws LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getQuote();

        if (!$this->helper->skipValidate($quote)) {
            return;
        }

        $oosMachineItems = $this->helper->getOosB2cMachineItems($quote);

        if (!empty($oosMachineItems)) {
            $oosMachineNameList = [];
            foreach ($oosMachineItems as $oosMachineItem) {
                $oosMachineNameList[] = $oosMachineItem->getName();
            }

            throw new LocalizedException(__(
                'Sorry, %1 is currently out of stock. Please delete %1 and select another machine or contact us for confirmation of restocking.' , implode(',', $oosMachineNameList)
            ));
        }
    }

}
