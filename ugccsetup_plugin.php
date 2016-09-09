<?php
class UgccsetupPlugin extends Plugin {
    public function __construct() {
        $this->loadConfig(dirname(__FILE__) . DS . "config.json");
    }
    
    public function install($plugin_id) {
        Loader::loadModels($this, array("Ugccsetup.UgccsetupSettings"));
        $this->UgccsetupSettings->setSettings(null, array("enabled" => false, "free_server_owner_id" => -1, "ugcc_user" => -1, "ugcc_token" => "", "api_url" => ""));
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
                Loader::loadModels($this, array("Invoices"));
                Loader::loadModels($this, array("Clients"));

                $params = $events->getParams();
		        $invoice_id = $params["invoice_id"];
                $invoice = $this->Invoices->get($invoice_id);
                if ($invoice["paid"] == 0)
                        warn(true, "Invoice not paid");
                if ($invoice["total"] < $invoice["paid"]) {
                        warn(false, "Invoice not fully paid. Client paid " . $invoice['paid'] . "but total was " . $invoice['total']);
                }

                $username = $this->Clients->get($invoice["client_id"]);
                if ($username == "")
                        $username = preg_replace("/[^A-Za-z0-9]/", "", $invoice["email"]);
                if ($username == "") {
                        return warn(true, "Invalid username");
                }
                // TODO parse extras from invoice
                $extras = NULL;
                $server = makeServer($invoice['service_id'], $username, $invoice["email"], $extras);
                
	}
    //$extras is an array of extra ids to install
	public function makeServer($service_id, $username, $email, $extras=NULL) {
            $server = getServerByUID($this->UgccsetupSettings->getSetting("free_server_owner_id"));
            if ($server == NULL){
                warn(true, "No servers left to setup");
            }
            $uid = getUID($username);
            if ($uid == NULL) {
                    $info = explode(",", sendCommand("newuser", NULL, $username));
                    $uid = $info[0];
            }
            $services_str = file_get_contents("setup.json");
            if ($services_str == false){
                warn(true, "No services specified in setup.json");
            }
            $services = json_decode($string, true)['services'];
            $service = NULL;
            foreach($services as $s){
                    if ($s['id'] == $service_id){
                        $service = $s;
                    }
            }
            if ($service == NULL){
                warn(true, "Invalid service ID " . $service_id . ". Make sure setup.json is correctly formatted and up to date");
            }
            
            
            // Do actual ugcc server setup
            // This should be the last thing we do
            sendCommand("updatedbserver", $server, "user", $uid);
            //TODO set server game to service['game']
            sendCommand("updatedbserver", $server, "var1", $service['game_slots']);
            //TODO setup mumble server with service['mumble_slots']
            sendCommand("updatedbserver", $server, "var5", $mumbleport);
            foreach ($extras as $extra) {
                sendCommand("extra", $server, $extra);
            }

    }
    
    // Never returns if $fatal is true
    public function warn($fatal, $warning_msg) {
        if ($fatal) {
            warn(false, $warning_msg);
            exit();
        } 
        else {
            //TODO send email warning
        }

    }

    public function sendCommand($command, $server_id=NULL, $o1=NULL, $o2=NULL){
        $params = array(
                "u" => $this->UgccsetupSettings->getSetting("ugcc_user"),
                "p" => $this->UgccsetupSettings->getSetting("ugcc_token"),
                "i" => $server_id,
                "c" => $command,
                "o1" => $o1,
                "o2" => $o2,
        );
        $ugcc = curl_init($this->UgccsetupSettings->getSetting("api_url"));
        curl_setopt($ugcc, CURLOPT_POST, 1);
        curl_setopt($ugcc, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ugcc, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ugcc);
        curl_close($ugcc);
        if (substr($response, 0, 2) != "OK") {
            return warn(true, "Error connecting to UGCC:\n" . $response);
        }
        else {
            return substr($response, 5);
        }
    }

    public function getServerByUID($uid) {
        //TODO cache server list
        $servers = explode(",", str_replace("\n", ",", sendCommand("listservers")));
        $server_key = array_search($uid, $list);
        if ($server_key == false)
                return NULL;
        return $servers[$server_key - 1];
    }

    public function getUID($username) {
        //TODO cache user list
        $users = explode(",", str_replace("\n", ",", sendCommand('listusers')));
        $user_key = array_search($uid, $list);
        if ($user_key == false)
                return NULL;
        return $users[$user_key - 1];
    }
}
?>
