<?php
class UgccsetupPlugin extends Plugin {
    public function __construct() {
        $this->loadConfig(dirname(__FILE__) . DS . "config.json");
    }
    
    public function install($plugin_id) {
        Loader::loadModels($this, array("UGCCSetup.UGCCSetupSettings"));
        $this->UGCCSetupSettings->setSettings(null, array("enabled" => false, "free_server_owner_id" => -1));
    }

	public function getEvents() {
	return array(
			array(
				"event" => "Invoices.setClosed",
				"callback" => array("this", "handleCInvoice")
			)
		);
	}

	private function handleCInvoice($event) {
		$params = $events->getParams();
		$invoice_id = $params['invoice_id'];

	}

	public function makeServer($game, $slots, $username) {

	}
}
?>
