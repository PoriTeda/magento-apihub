<?php
namespace Riki\Sales\Plugin\Sales\Block\Adminhtml\Order\Create\Form;

class ModifyOrderDataJson
{
    /**
     * @var \Magento\Framework\Json\DecoderInterface
     */
    protected $jsonDecoder;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $jsonEncoder;

    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $session;

    /**
     * ModifyOrderDataJson constructor.
     * @param \Magento\Framework\Json\DecoderInterface $jsonDecoder
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Backend\Model\Session\Quote $session
     */
    public function __construct(
        \Magento\Framework\Json\DecoderInterface $jsonDecoder,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Backend\Model\Session\Quote $session
    ) {
        $this->jsonDecoder = $jsonDecoder;
        $this->jsonEncoder = $jsonEncoder;
        $this->session = $session;
    }

    /**
     * @param \Magento\Sales\Block\Adminhtml\Order\Create\Form $subject
     * @param $result
     * @return string
     */
    public function afterGetOrderDataJson(
        \Magento\Sales\Block\Adminhtml\Order\Create\Form $subject,
        $result
    ) {
        $data = $this->jsonDecoder->decode($result);

        if ($sessionUniqueKey = $this->session->getData('session_unique_key')) {
            $data['session_unique_key'] = $sessionUniqueKey;
        }

        return $this->jsonEncoder->encode($data);
    }
}