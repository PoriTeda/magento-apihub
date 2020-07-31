<?php
namespace Bluecom\Paygent\Block\Adminhtml\Button;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

abstract class Generic implements ButtonProviderInterface
{
    /**
     * Parameter key
     */
    const REQUEST_ID_KEY = 'id';

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Magento\Backend\Block\Widget\Context
     */
    protected $context;

    protected $modelFactory;

    /**
     * Generic constructor.
     * @param \Magento\Backend\Block\Widget\Context $context
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context
    )
    {
        $this->request = $context->getRequest();
        $this->context = $context;
    }

    public function getButtonData()
    {
        if (!$this->canRender()) {
            return [];
        }

        return $this->getData();
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [];
    }

    /**
     * @return bool
     */
    public function canRender()
    {
        return true;
    }

    /**
     * @return mixed
     */
    public function getModel()
    {
        return $this->modelFactory
            ->create()
            ->load($this->request->getParam(self::REQUEST_ID_KEY));
    }

    /**
     * Generate url by route and parameters
     *
     * @param   string $route
     * @param   array $params
     * @return  string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
