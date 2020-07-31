###### setup:upgrade
1. Cannot process definition to array for type
File: vendor/magento/framework/Setup/Declaration/Schema/Db/DefinitionAggregator.php

3. modify table `customer_group` which has a foreign key constraint to custom table `amasty_rewards_rule_customer_group` but they are not compatible 
5. catalog_product_index_price already exist. Because current db has run indexed but in new magento instance, it will not check condition and always create new table. We can remove it and rerun reindex

7. Currently, table `sales_shipment_track` has indexes for column `track_number` but in magento 2.3+ that column will be modified to `text` type. Mysql can not index for text type. => we need remove index.

8. Mgt try to add constrain to table `catalogrule_product` and table `catalogrule_product_price` but currently, data in table is duplicated, so we will create plugin to ignore this function. See `Nestle/Migration/Plugin/Declaration/Schema/Operations/AddComplexElementPlugin.php`

9. Upgrade module Riki_StockPoint error when check version 1.1.1. See `Riki/ArReconciliation/Setup/SetupHelper.php` 
Wrong resource name

10. `vendor/magento/module-sales/Setup/Patch/Data/FillQuoteAddressIdInSalesOrderAddress.php`. 
Fix by create preference `Nestle/Migration/Setup/Patch/Data/FillQuoteAddressIdInSalesOrderAddress.php`

11. Fix core error: Argument 1 passed to Magento\CatalogStaging\Setup\Patch\Data\MigrateCatalogProducts::updateCustomDesignDateFields() must be an instance of Magento\CatalogStaging\Setup\Patch\Data\Category, instance of Magento\Catalog\Model\Category\Interceptor given, called in /var/www/magento-upgrade/vendor/magento/module-catalog-staging/Setup/Patch/Data/MigrateCatalogProducts.php on line 204 and defined in /var/www/magento-upgrade/vendor/magento/module-catalog-staging/Setup/Patch/Data/MigrateCatalogProducts.php

12. Fix core bug magento. Magento\Framework\DB\Adapter\Pdo\Mysql::insertArray. When data passing to this method is empty, we should not run query

13. Fix get resource connection vendor/klarna/module-kp/Setup/UpgradeData.php

14. Config xml need to fix logic: 
- Riki/MessageQueue/etc/queue.xml

15. error when applying patch `vendor/magento/module-logging/Setup/Patch/Data/ConvertDataSerializedToJson
`
###### di:compile
1. Declaration of Riki\SalesRule\Model\Validator::_getRules() should be compatible with Magento\SalesRule\Model\Validator::_getRules(?Magento\Quote\Model\Quote\Address $
     address = NULL) in /var/www/magento-upgrade/app/code/Riki/SalesRule/Model/Validator.php on line 246
     
2. Riki\ThirdPartyImportExport\Model\Amqp\Queue::reject
3. Riki\Catalog\Model\Export\RowCustomizer::getFormattedBundleOptionValues($product)
4. Riki\Catalog\Block\Product\View\Type\Bundle::getOptions()
5. Riki\ImportExport\Model\Import\Address::_prepareDataForUpdate
6. Riki\ImportExport\Model\Import\Customer::isAttributeValid
7. Riki\ImportExport\Model\ResourceModel\Import\Customer\Storage::addCustomer
8. app/code/Riki/Subscription/Plugin/GoogleTagManager/Observer/SetGoogleAnalyticsOnCartAddObserver.php missing observer Magento\GoogleTagManager\Observer\SetGoogleAnalyticsOnCartAddObserver
9. Riki/Cms/Block/Adminhtml/Block/Edit/Form.php missing class \Magento\Cms\Block\Adminhtml\Block\Edit\Form
10. Source class "\Riki\MasterDataHistory\Model\Import\Proxy\Product\ResourceModel" for "Riki\MasterDataHistory\Model\Import\Proxy\Product\ResourceModelFactory" generation does no
      t exist
11. Riki\ThirdPartyImportExport\Setup missing class Magento\MysqlMq\Setup\InstallData
12. Riki\EmailMarketing\Model\Observer
13. app/code/Riki/MasterDataHistory/Model/Import/Product.php constructor wrong type of instance 
14.app/code/Riki/SalesRule/Model/ResourceModel/Rule.php constructor error
15. app/code/Riki/ThirdPartyImportExport/Model/Amqp/Queue.php constructor error
16. Riki/Sales/Controller/Adminhtml/Order/Create/Reorder.php constructor error 
17. Riki/ImportExport/Model/Import/Address.php constructor error
