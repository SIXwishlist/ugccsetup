<?php
class UgccsetupPlugin extends Plugin {
    public function __construct() {
        $this->loadConfig(dirname(__FILE__) . DS . "config.json");
    }
    
    public function install($plugin_id) {
        Loader::loadModels($this, array("Ugccsetup.UgccsetupSettings"));
        $this->UgccsetupSettings->setSettings(null, array("enabled" => false, "free_server_owner_id" => -1));
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
                Loader::loadModels($this, array("Invoices");
                Loader::loadModels($this, array("Clients");

                $params = $events->getParams();
		$invoice_id = $params["invoice_id"];
                $invoice = $this->Invoices->get($invoice_id);
                if ($invoice["paid"] == 0)
                        return
                if ($invoice["total"] < $invoice["paid"]) {
                        warn(false, "Invoice not fully paid. Client paid " . $invoice['paid'] . "but total was " . $invoice['total']);
                }

                $username = $this->Clients->get($invoice["client_id"];
                if ($username == "")
                        $username = preg_replace("/[^A-Za-z0-9]/", "", $invoice["email"]);
                if ($username == "") {
                        return warn(true, "Invalid username")
                }
                
	}

	public function makeServer($service_id, $username) {
        $services = JSON.parse('setup.json');
    }

    public function warn($fatal, $warning_msg) {

    }
}
?>
