<?php
namespace Riki\ThirdPartyImportExport\Block\Order;

use Magento\Customer\Model\Context;

class View extends \Magento\Framework\View\Element\Template
{
    protected $_template = 'order/view.phtml';

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $_httpContext;

    /**
     * View constructor.
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    )
    {
        $this->_httpContext = $httpContext;
        parent::__construct($context, $data);
    }

    /**
     * Get back url
     *
     * @return string
     */
    public function getBackUrl()
    {
        if ($this->_httpContext->getValue(Context::CONTEXT_AUTH)) {
            return $this->getUrl('*/*/history');
        }
        return $this->getUrl('*/*/form');
    }

    /**
     * Get title
     *
     * @return \Magento\Framework\Phrase
     */
    public function getBackTitle()
    {
        if ($this->_httpContext->getValue(Context::CONTEXT_AUTH)) {
            return __('Back to My Orders');
        }
        return __('View Another Order');
    }
}
