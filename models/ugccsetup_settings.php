<?php

class UgccsetupSettings extends AppModel {
        public function __construct() {
                parent::__construct();

                if (!isset($this->SettingsCollection)) {
                        Loader::loadComponents($this, array("SettingsCollection"));
                }

        }

        public function getSettings($company_id=null) {
                if ($company_id == null) {
                        $company_id = Configure::get("Blesta.company_id");
                }
                $supported_settings = $this->supportedSettings();
                $all_settings = $this->SettingsCollection->fetchSettings(null, $company_id);
                $settings = array();
                foreach ($all_settings as $setting => $value) {
                        if (($index = array_search($setting, $supported_settings)) !== false) {
                                $settings[$index] = $value;
                        }
                }
                return $settings;
        }

        public function setSettings($company_id=null, array $settings) {
                if ($company_id === null) {
                        $company_id = Configure::get("Blesta.company_id");
                }
                if (!isset($this->Companies)) {
                        Loader::loadModels($this, array('Companies'));
                }

                $updated_settings = array();
                foreach ($this->supportedSettings() as $key => $name) {
                    if (array_key_exists($key, $settings)) {
                            $updated_settings[$name] = $settings[$key];
                    }
                }

                $this->Companies->setSettings($company_id, $updated_settings);
        }

        public function supportedSettings() {
            return array(
                    'enabled' => 'Ugccsetup.enabled',
                    'free_server_id' => 'Ugccsetup.free_server_id',
                    "ugcc_user" => "Ugccsetup.ugcc_user",
                    "ugcc_token" => "Ugccsetup.ugcc_token",
                    "api_url" => "Ugccsetup.api_token",
                    "notify_enable" => "Ugccsetup.notify_enable",
                    "notify_verbose"
                    "notify_addresses" => "Ugccsetup.notify_addresses"
            );
        }
}
?>
