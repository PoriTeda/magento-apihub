<?php
namespace Riki\Subscription\Model\Profile\WebApi;

use Riki\Subscription\Api\WebApi\TagManagerInterface;

class TagManager implements TagManagerInterface
{
    /**
     * @var \Magento\Framework\View\Element\BlockFactory
     */
    protected $blockFactory;
    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    protected $appEmulation;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var \Riki\TagManagement\Helper\Helper
     */
    protected $helperTagManager;
    /**
     * TagManager constructor.
     * @param \Magento\Framework\View\Element\BlockFactory $blockFactory
     * @param \Magento\Store\Model\App\Emulation $appEmulation
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\View\Element\BlockFactory $blockFactory,
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Riki\TagManagement\Helper\Helper $helperTagManager
    ) {
        $this->blockFactory = $blockFactory;
        $this->appEmulation = $appEmulation;
        $this->storeManager = $storeManager;
        $this->helperTagManager = $helperTagManager;
    }

    /**
     * @return mixed|void
     */
    public function getTagManager()
    {
        $this->appEmulation->startEnvironmentEmulation($this->storeManager->getStore()->getId(),
            \Magento\Framework\App\Area::AREA_FRONTEND, true);
        $htmlTagMagaerString = $this->blockFactory->createBlock('Magento\GoogleTagManager\Block\Ga')
            ->setTemplate('Magento_GoogleTagManager::ga.phtml')->toHtml();
        $strReplace = array("<script>", "</script>", "<!-- GOOGLE TAG MANAGER -->", "<!-- END GOOGLE TAG MANAGER -->");
        $htmlTagMagaerString = str_replace($strReplace, "", $htmlTagMagaerString);
        $htmlTagMagaer['google'] = trim($htmlTagMagaerString);
       // $htmlTagMagaer['yahoo'] = $this->helperTagManager->getConfigYahoo();

        return $htmlTagMagaer;
    }
}