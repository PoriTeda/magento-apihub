<?php


namespace Nestle\Migration\Model\DataMigration;


use Nestle\Migration\Model\DataMigration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateConfigData extends AbstractDataMigration
{
    public function run(InputInterface $input, OutputInterface $output)
    {
        $this->updateConfigData($input, $output);
        return parent::run($input, $output); // TODO: Change the autogenerated stub
    }

    /**
     * @param $input
     * @param $output
     */
    private function updateConfigData($input, $output)
    {
        if (DataMigration::$IS_DEVELOPMENT) {
            DataMigration::info("updating core_config_data");
            $adapter = $this->resourceConnection->getConnection("default");
            $adapter->update($adapter->getTableName("core_config_data"), [
                "value" => "http://riki.dev.nestle.jp/"
            ], ' path = "web/unsecure/base_url"');

            $adapter->update($adapter->getTableName("core_config_data"), [
                "value" => 0
            ], ' path = "admin/url/use_custom"');

            $adapter->update($adapter->getTableName("core_config_data"), [
                "value" => 0
            ], ' path = "admin/url/use_custom_path"');

            $adapter->update($adapter->getTableName("core_config_data"), [
                "value" => 0
            ], ' path = "web/secure/use_in_adminhtml"');

            $adapter->update($adapter->getTableName("core_config_data"), [
                "value" => 0
            ], ' path = "web/cookie/cookie_secure"');

            $adapter->update($adapter->getTableName("core_config_data"), [
                "value" => 90000
            ], ' path = "admin/security/session_lifetime"');

            $adapter->update($adapter->getTableName("core_config_data"), [
                "value" => 0
            ], ' path = "admin/security/password_is_forced"');

            $adapter->update($adapter->getTableName("core_config_data"), [
                "value" => "https://stagingec2.nestle.jp"
            ], ' path LIKE "%preprod.shop%"');

            $adapter->update($adapter->getTableName("core_config_data"), [
                "value" => 0
            ], ' path = "dev/js/enable_js_bundling"');

            $adapter->update($adapter->getTableName("core_config_data"), [
                "value" => 0
            ], ' path = "dev/static/sign"');

            $adapter->update($adapter->getTableName("core_config_data"), [
                "value" => 0
            ], ' path = "dev/js/merge_files"');

            $adapter->update($adapter->getTableName("core_config_data"), [
                "value" => 0
            ], ' path = "dev/css/merge_css_files"');

//            $adapter->update($adapter->getTableName("core_config_data"), [
//                "value" => 0
//            ], ' path = "dev/js/minify_files"');
//
//            $adapter->update($adapter->getTableName("core_config_data"), [
//                "value" => 0
//            ], ' path = "dev/css/minify_files"');
        }
    }
}
