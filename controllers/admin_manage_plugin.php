<?php
class AdminManagePlugin extends AppController {
    private function init() {
        $this->parent->requireLogin();

        $this->uses(array('Ugccsetup.UgccsetupSettings'));
        
        $this->parent->structure->set('page_title', 'UGCC Setup Settings', true);

        $this->view->setView(null, 'ugccsetup.default');
    }

    public function index() {
        $this->init();

        $vars = (object) $this->UgccsetupSettings->getSettings($this->parent->company_id);

        if (!empty($this->post)) {
            $this->UgccsetupSettings->setSettings($this->parent->company_id, $this->post);
        }

        $vars = (object) $this->post;
        return $this->partial('admin_manage_plugin', compact($vars));
    }
}
?>
