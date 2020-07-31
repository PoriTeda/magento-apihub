<?php
namespace Riki\Rma\Block\Adminhtml\Button;

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

    /**
     * {@inheritdoc}
     *
     * @return mixed[]
     */
    public function getButtonData()
    {
        if (!$this->canRender()) {
            return [];
        }

        return $this->getData();
    }

    /**
     * Get data
     *
     * @return mixed[]
     */
    public function getData()
    {
        return [];
    }

    /**
     * Can render
     *
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
