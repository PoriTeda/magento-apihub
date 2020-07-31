<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Smtp
 */


namespace Amasty\Smtp\Block\Adminhtml\Log;

use Magento\Backend\Block\Widget;
use Magento\Backend\Block\Widget\Container;
use Magento\Framework\Registry;

class View extends Container
{
    protected $_template = 'Amasty_Smtp::log/view.phtml';

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->registry = $registry;
    }

    protected function _construct()
    {
        parent::_construct();

        $this->addButton(
            'back',
            [
                'label' => __('Back'),
                'onclick' => 'setLocation(\'' . $this->getBackUrl() . '\')',
                'class' => 'back'
            ],
            -1
        );
    }

    public function getBackUrl()
    {
        return $this->getUrl('*/*/');
    }

    public function getMessage()
    {
        return $this->registry->registry('current_log_message');
    }

    public function getDate()
    {
        $date = $this->formatDate(
            $this->getMessage()->getCreatedAt(),
            \IntlDateFormatter::LONG
        );

        return $date;
    }

    public function getTo()
    {
        $to = $this->getMessage()->getRecipientEmail();
        return $to;
    }

    public function getSubject()
    {
        return $this->getMessage()->getSubject();
    }

    public function getBody()
    {
        $body = $this->getMessage()->getBody();
        $body = preg_replace("/<style\\b[^>]*>(.*?)<\\/style>/s", "", $body);
        $body = preg_replace("/<body\\b[^>]*>/s", "", $body);
        $body = str_replace('</body>', '', $body);
        return $body;
    }
}
