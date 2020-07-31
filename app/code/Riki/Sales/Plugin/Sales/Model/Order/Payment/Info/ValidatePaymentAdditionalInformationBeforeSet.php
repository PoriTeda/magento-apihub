<?php
namespace Riki\Sales\Plugin\Sales\Model\Order\Payment\Info;

class ValidatePaymentAdditionalInformationBeforeSet
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * ValidatePaymentAdditionalInformationBeforeSet constructor.
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Sales\Model\Order\Payment\Info $subject
     * @param $key
     * @param null $value
     * @return array
     */
    public function beforeSetAdditionalInformation(
        \Magento\Sales\Model\Order\Payment\Info $subject,
        $key,
        $value = null
    ) {
        if (!is_array($key)) {
            $additionalInformation = $subject->getAdditionalInformation();

            if (!is_array($additionalInformation)) {
                $key = [
                    $key => $value
                ];
                $value = null;
                $this->logger->error(__(
                    'The payment #%1 have incorrect additional information: %2',
                    $subject->getId(),
                    $additionalInformation
                ));
            }
        }

        return [$key, $value];
    }
}