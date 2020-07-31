<?php
namespace Riki\MachineApi\Observer;

class ValidateMachineBeforeSubmit implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Riki\MachineApi\Helper\Machine
     */
    protected $helperMachine;

    /**
     * ValidateMachineBeforeSubmit constructor.
     * @param \Riki\MachineApi\Helper\Machine $helperMachine
     */
    public function __construct(
        \Riki\MachineApi\Helper\Machine $helperMachine
    ) {
        $this->helperMachine = $helperMachine;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getQuote();

        /** Validate machine before submit */
        $result = $this->helperMachine->validateMachineFromQuote($quote);
        if ($result !== true) {
            $message = __('You will need to select one or more machines for this scheduled flight.');
            throw new \Magento\Framework\Exception\LocalizedException($message);
        }
    }
}