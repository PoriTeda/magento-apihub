<?php

namespace Riki\Theme\Observer;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\Model\Layout\Merge;
use Magento\Framework\Serialize\SerializerInterface;

class ValidateLayoutCache implements ObserverInterface
{
    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    private $cache;

    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\Response
     */
    private $response;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $url;

    /**
     * @var \Magento\Framework\View\Layout
     */
    private $layout;

    /**
     * @var string
     */
    private $pageLayout;

    /**
     * @var \Magento\Framework\View\Layout\Reader\ContextFactory
     */
    private $readerContextFactory;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var array
     */
    private $pageLayoutCache = [];

    /**
     * @var \Magento\Framework\View\Layout\Reader\Context[]
     */
    private $readerContextCache = [];

    /**
     * ValidateLayoutCache constructor.
     *
     * @param \Magento\Framework\App\Cache\Type\Layout $cache
     * @param \Magento\Framework\HTTP\PhpEnvironment\Response $response
     * @param \Magento\Framework\UrlInterface $url
     * @param \Magento\Framework\View\Layout\Reader\ContextFactory $readerContextFactory
     * @param SerializerInterface $serializer
     */
    public function __construct(
        \Magento\Framework\App\Cache\Type\Layout $cache,
        \Magento\Framework\HTTP\PhpEnvironment\Response $response,
        \Magento\Framework\UrlInterface $url,
        \Magento\Framework\View\Layout\Reader\ContextFactory $readerContextFactory,
        SerializerInterface $serializer = null
    ) {
        $this->cache = $cache;
        $this->response = $response;
        $this->url = $url;
        $this->readerContextFactory = $readerContextFactory;
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(SerializerInterface::class);
    }

    /**
     * We check whether cache is valid. If cache is invalid, we remove broken cache and reload the page.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $this->layout = $observer->getEvent()->getData('layout');
        $cacheId = $this->layout->getUpdate()->getCacheId();

        $this->_retrieveCacheData();

        if (!$this->_isValid()) {
            if ($this->pageLayoutCache[$cacheId]) {
                $this->cache->remove('structure_' . $cacheId);
            } else {
                $this->cache->remove($cacheId);
                $this->cache->remove($cacheId . '_' . Merge::PAGE_LAYOUT_CACHE_SUFFIX);
                $this->cache->remove('structure_' . $cacheId);
            }

            $this->response->setRedirect($this->url->getCurrentUrl());
            $this->response->sendResponse();
            // @codingStandardsIgnoreStart
            exit(1);
            // @codingStandardsIgnoreEnd
        }
    }

    /**
     * Retrieve layout data from cache.
     */
    private function _retrieveCacheData()
    {
        $cacheId = $this->layout->getUpdate()->getCacheId();
        $pageLayoutCacheId = $cacheId . '_' . Merge::PAGE_LAYOUT_CACHE_SUFFIX;
        $structureCacheId = 'structure_' . $cacheId;

        $this->pageLayoutCache[$cacheId] = (string)$this->cache->load($pageLayoutCacheId);

        $result = $this->cache->load($structureCacheId);
        if ($result) {
            $data = $this->serializer->unserialize($result);
            $readerContext =  $this->createReaderContext();
            $readerContext->getPageConfigStructure()->populateWithArray($data['pageConfigStructure']);
            $readerContext->getScheduledStructure()->populateWithArray($data['scheduledStructure']);

            // @codingStandardsIgnoreStart
            $this->readerContextCache[$cacheId] = $readerContext;
            // @codingStandardsIgnoreEnd
        }
    }

    /**
     * @return \Magento\Framework\View\Layout\Reader\Context
     */
    public function createReaderContext()
    {
        return $this->readerContextFactory->create();
    }

    /**
     * When a page has layout, a valid cache will have a readerContext which has root element.
     *
     * @return bool
     */
    private function _isValid()
    {
        $cacheId = $this->layout->getUpdate()->getCacheId();
        if (!isset($this->readerContextCache[$cacheId])) {
            return true;
        }

        $handles = $this->layout->getUpdate()->getHandles();
        foreach ($handles as $handle) {
            $this->_fetchPageLayout($handle);

            if ($this->pageLayout) {
                if (!$this->pageLayoutCache[$cacheId]) {
                    return false;
                }

                $scheduledStructure = $this->readerContextCache[$cacheId]->getScheduledStructure();
                if (!$scheduledStructure->hasStructureElement('root')) {
                    return false;
                }

                break;
            }
        }

        return true;
    }

    /**
     * @param $handle
     */
    private function _fetchPageLayout($handle)
    {
        $fileLayoutUpdatesXml = $this->layout->getUpdate()->getFileLayoutUpdatesXml();
        foreach ($fileLayoutUpdatesXml->xpath("*[self::handle or self::layout][@id='{$handle}']") as $updateXml) {
            if (isset($updateXml['layout'])) {
                $this->pageLayout = (string)$updateXml['layout'];
            }

            if ($this->pageLayout) {
                break;
            }

            foreach ($updateXml->children() as $child) {
                if (strtolower($child->getName()) == 'update' && isset($child['handle'])) {
                    $this->_fetchPageLayout((string)$child['handle']);

                    if ($this->pageLayout) {
                        break;
                    }
                }
            }
        }
    }
}
