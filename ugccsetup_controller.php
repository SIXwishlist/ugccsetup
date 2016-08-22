<?php
class UGCCSetupController extends AppController {
    public function preAction() {
            parent::preaction();

            $this->view->view = "default";
            $this->structure->view = "default";
    }
}
?>
