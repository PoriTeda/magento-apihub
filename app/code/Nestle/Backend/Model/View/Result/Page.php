<?php

namespace Nestle\Backend\Model\View\Result;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Translate;
use Magento\Framework\View;

class Page extends \Magento\Backend\Model\View\Result\Page
{
    /**
     * @var \Magento\Framework\App\ResponseFactory
     */
    protected $responseFactory;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $url;

    /**
     * @var \Magento\Framework\App\Cache\TypeListInterface
     */
    protected $cacheTypeList;

    public function __construct(
        View\Element\Template\Context $context,
        View\LayoutFactory $layoutFactory,
        View\Layout\ReaderPool $layoutReaderPool,
        Translate\InlineInterface $translateInline,
        View\Layout\BuilderFactory $layoutBuilderFactory,
        View\Layout\GeneratorPool $generatorPool,
        View\Page\Config\RendererFactory $pageConfigRendererFactory,
        View\Page\Layout\Reader $pageLayoutReader,
        $template,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\ResponseFactory $responseFactory
    ) {
        parent::__construct(
            $context,
            $layoutFactory,
            $layoutReaderPool,
            $translateInline,
            $layoutBuilderFactory,
            $generatorPool,
            $pageConfigRendererFactory,
            $pageLayoutReader,
            $template
        );
        $this->responseFactory = $responseFactory;
        $this->url = $context->getUrlBuilder();
        $this->cacheTypeList = $cacheTypeList;
    }

    /**
     * Define active menu item in menu block
     *
     * @param string $itemId current active menu item
     * @return $this
     * @throws LocalizedException
     */
    public function setActiveMenu($itemId)
    {
        /** @var $menuBlock \Magento\Backend\Block\Menu */
        $menuBlock = $this->layout->getBlock('menu');
        //For some reason layout has been broken, clean backend caches then reload the page
        if (!$menuBlock) {
            $this->cleanCache();
            $currentRequest = $this->url->getUrl('*/*/*', ['_current' => true]);
            $this->responseFactory->create()->setRedirect($currentRequest)->sendResponse();
            exit();
        }

        $menuBlock->setActive($itemId);
        $parents = $menuBlock->getMenuModel()->getParentItems($itemId);
        foreach ($parents as $item) {
            /** @var $item \Magento\Backend\Model\Menu\Item */
            $this->getConfig()->getTitle()->prepend($item->getTitle());
        }
        return $this;
    }

    private function cleanCache()
    {
        $this->eventManager->dispatch('adminhtml_cache_flush_all');
        $types = ['layout'];
        foreach ($types as $type) {
            $this->cacheTypeList->cleanType($type);
        }
    }
}