<?php
class AdminManagePlugin extends AppController {
    private function init() {
        $this->parent->requireLogin();

        $this->uses(array('UGCCSetup.UGCCSetupSettings'));
        
        $this->parent->structure->set('page_title', 'UGCC Setup Settings', true);

        $this->view->setView(null, 'ugccsetup.default');
    }

    public function index() {
        $this->init();

        $vars = (object) $this->UGCCSetupSettings->getSettings($this->parent->company_id);

        if (!empty($this->post)) {
            $this->UGCCSetupSettings->setSettings($this->parent->company_id, $this->post);
        }

        $vars = (object) $this->post;
        return $this->partial('admin_manage_plugin', compact($vars));
    }
}
?>
