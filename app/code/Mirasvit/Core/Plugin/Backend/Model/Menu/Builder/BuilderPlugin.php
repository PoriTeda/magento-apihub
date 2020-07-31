<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-core
 * @version   1.2.89
 * @copyright Copyright (C) 2019 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\Core\Plugin\Backend\Model\Menu\Builder;

use Magento\Backend\Model\Menu;
use Magento\Backend\Model\Menu\Item;
use Magento\Backend\Model\Menu\ItemFactory;
use Magento\Framework\UrlInterface;
use Mirasvit\Core\Block\Adminhtml\Menu as MenuBlock;
use Mirasvit\Core\Model\Config;
use Mirasvit\Core\Model\ModuleFactory;
use Mirasvit\Core\Service\CompatibilityService;

class BuilderPlugin
{
    private $config;

    private $itemFactory;

    private $moduleFactory;

    private $menuBlock;

    private $urlManager;

    public function __construct(
        Config $config,
        ItemFactory $itemFactory,
        ModuleFactory $moduleFactory,
        MenuBlock $menuBlock,
        UrlInterface $urlManager
    ) {
        $this->config        = $config;
        $this->itemFactory   = $itemFactory;
        $this->moduleFactory = $moduleFactory;
        $this->menuBlock     = $menuBlock;
        $this->urlManager    = $urlManager;
    }

    public function afterGetResult($subject, Menu $menu)
    {
        if (!$this->config->isMenuEnabled()
            || CompatibilityService::is20()
            || CompatibilityService::is21()
        ) {
            return $this->removeMenu($menu);
        }

        $installedModules = $this->moduleFactory->create()
            ->getInstalledModules();

        $moduleItems = [];

        foreach ($installedModules as $moduleName) {
            if ($moduleName === 'Mirasvit_Core') {
                continue;
            }

            $module = $this->moduleFactory->create()->load($moduleName);

            $group = $module->getGroup();

            if (!$group) {
                $group = 'Other';
            }

            switch ($moduleName) {
                case 'Mirasvit_Report':
                case 'Mirasvit_Dashboard':
                case 'Mirasvit_ReportBuilder':
                    $group = 'Advanced Reports';
                    break;

                case 'Mirasvit_SearchLanding':
                case 'Mirasvit_SearchReport':
                    $group = 'Search';
                    break;
            }

            if (!isset($moduleItems[$group])) {
                $moduleItems[$group] = [];
            }

            $nativeMenuItems = $this->filterItems($menu, $moduleName);

            foreach ($nativeMenuItems as $idx => $item) {
                $data = $item->toArray();
                unset($data['sub_menu']);

                if (!$data['action']) {
                    continue;
                }

                $url    = $this->urlManager->getUrl($data['action']);
                $urlKey = $this->normalizeUrlKey($url);

                $moduleItems[$group][$urlKey] = $data;
            }

            $items = $this->menuBlock->getItemsByModuleName($moduleName);
            foreach ($items as $idx => $item) {
                if (!is_object($item)) {
                    continue;
                }

                $urlKey = $this->normalizeUrlKey($item->getData('url'));

                $moduleItems[$group][$urlKey] = [
                    'id'       => $item->getData('url'),
                    'module'   => $moduleName,
                    'resource' => $item->getData('resource'),
                    'title'    => (string)$item->getData('title'),
                    'path'     => $item->getData('url'),
                ];
            }
        }

        ksort($moduleItems);

        $filteredItems = [];

        foreach ($moduleItems as $group => $items) {
            if ($items) {
                $filteredItems[$group] = $items;
            }
        }

        if (count($filteredItems) <= 1) {
            return $this->removeMenu($menu);
        }

        $idx = 0;
        foreach ($filteredItems as $group => $items) {
            $moduleData = [
                'title'    => $group,
                'id'       => md5($group),
                'resource' => 'Mirasvit_Core::menu',
            ];

            foreach ($items as $item) {
                $item['id'] = 'Mirasvit_Core::menu::' . $idx++;

                $moduleData['sub_menu'][] = $item;

            }
            $moduleItem = $this->itemFactory->create([
                'data' => $moduleData,
            ]);

            $menu->add($moduleItem, 'Mirasvit_Core::menu');
        }

        return $menu;
    }

    /**
     * @param Menu   $menu
     * @param string $moduleName
     *
     * @return Item[]
     */
    private function filterItems(Menu $menu, $moduleName)
    {
        $items = [];

        /** @var Item $item */
        foreach ($menu->getIterator() as $item) {
            $id = $item->getId();

            if (strpos($id, $moduleName) !== false) {
                $items[] = $item;
            }

            if ($item->getChildren()) {
                $items = array_merge($items, $this->filterItems($item->getChildren(), $moduleName));
            }
        }

        return $items;
    }

    private function normalizeUrlKey($url)
    {
        $url = str_replace('/index/', '', $url);
        $url = rtrim($url, '/');

        return $url;
    }

    private function removeMenu(Menu $menu)
    {
        $menu->remove('Mirasvit_Core::menu');

        return $menu;
    }
}