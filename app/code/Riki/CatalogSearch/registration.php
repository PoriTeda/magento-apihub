<?php
/**
 * Module
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Module
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'Riki_CatalogSearch',
    __DIR__
);