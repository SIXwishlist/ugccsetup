<?php class UgccsetupPlugin extends Plugin { public function __construct() { $this->loadConfig(dirname(__FILE__) . DS . "config.json"); }
    
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
                Loader::loadModels($this, array("Ugccsetup.UgccsetupSettings"));
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
    // $extras is an array of blesta extra ids
    public function makeServer($service_id, $username, $email, $extras=NULL) {
            Loader::loadModels($this, array("Ugccsetup.UgccsetupSettings");
            $server = getServerByUID($this->UgccsetupSettings->getSetting("free_server_owner_id"));
            if ($server == NULL){
                warn(true, "No servers left to setup");
            }
            $uid = getUID($username);
            if ($uid == NULL) {
                    $info = explode(",", sendCommand("newuser", NULL, $username));
                    $uid = $info[0];
            }
            $setup_str = file_get_contents("setup.json");
            if ($setup_str == false){
                warn(true, "No services specified in setup.json");
            }
            $decoded = json_decode($setup_str, true);
            $extra_ids = $decoded['extras'];
            $services = $decoded['services'];
            $service = NULL;
            foreach($services as $s){
                    if ($s['id'] == $service_id){
                        $service = $s;
                    }
            }

            if ($service == NULL){
                warn(true, "Invalid service ID " . $service_id . ". Make sure setup.json is correctly formatted and up to date");
            }
            /*
            foreach(glob("server_*.txt") as $file) {
                $ips = explode("\n", file_get_contents($file));
                if (count($ips) > 1) {
                    $ip = $ips[2];
                    
                }
            }*/
            if ($extras != NULL) {
                $extra_install_ids = [];
                foreach($extra_ids as $e) {
                        if (in_array($e['billing_id'], $extras)) {
                            foreach($e['extra_ids'] as $ex){
                                array_push($extra_install_ids, $ex);
                            }
                        }
                }
            }
            
            // Do actual ugcc server setup
            // This should be the last thing we do
            sendCommand("updatedbserver", $server, "user", $uid);
            sendCommand("updatedbserver", $server, "var1", $service['game_slots']);
            //TODO setup mumble server with service['mumble_slots']
            sendCommand("updatedbserver", $server, "var5", $mumbleport);
            if ($extras != NULL){
                    foreach ($extra_install_ids as $extra) {
                sendCommand("extra", $server, $extra);
                    }
            }

    }
    
    // Never returns if $fatal is true
    // Logs to log.txt if $log is true (default)
    public function warn($fatal, $warning_msg, $log=true) {
        if ($log) {
            $log_msg = $warning_msg;
            if ($fatal)
                    $log_msg = "FATAL: " . $warning_msg;
            // TODO
            file_put_contents("log.txt", "\n" . date('Y/m/d H:i:s') . $log_msg, FILE_APPEND);
        }
        Loader::loadModels($this, array("Email");
        $from = "no-reply@accelerateservers.com";
        $from_name = "Accelerate Servers";
        $body = array("text"=>"The following error occured when provisioning a server automatically: \n" . $warning_msg);
        if ($fatal) {
                $subject = "Fatal error setting up server";
                $body["text"] = $body["text"] . "\n This is a fatal error and the server has not been setup. Please manually provision it as soon as possible.";
        } else {
                $subject = "Non-fatal error setting up server";
                $body["text"] = $body["text"] . "\n This server may need to have its configuration adjusted manually.";
        }
        // TODO possible cache email list?
        $setup_str = file_get_contents("setup.json");
        if ($setup_str == false){
            warn(true, "Invalid setup.json");
        }
        $emails = json_decode($setup_str, true)["emails"];
        foreach($emails as $email) {
            if ($fatal || $email["nonfatal"]) {
                $this->Emails->sendCustom($from, $from_name, $email["address"], $subject, $body, NULL);
            }
        }
    }

    public function sendCommand($command, $server_id=NULL, $o1=NULL, $o2=NULL){
            Loader::loadModels($this, array("Ugccsetup.UgccsetupSettings");
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
            return warn(true, "Error connecting to UGCC: " . $response);
        }
        else {
            return substr($response, 5);
        }
    }

    public function getServerByUID($uid) {
        //TODO cache server list
        Loader::loadModels($this, array("Ugccsetup.UgccsetupSettings");
        $servers = explode(",", str_replace("\n", ",", sendCommand("listservers")));
        $server_key = array_search($uid, $list);
        if ($server_key == false)
                return NULL;
        return $servers[$server_key - 1];
    }

    public function getUID($username) {
        //TODO cache user list
        Loader::loadModels($this, array("Ugccsetup.UgccsetupSettings");
        $users = explode(",", str_replace("\n", ",", sendCommand('listusers')));
        $user_key = array_search($uid, $list);
        if ($user_key == false)
                return NULL;
        return $users[$user_key - 1];
    }
}
?>
