<?php
namespace TypechoPlugin\Notice\libs;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

use Typecho;
use Widget;

class SettingAction extends Typecho\Widget implements Widget\ActionInterface
{
    private Typecho\Db $_db;
    private string $_pluginName='plugin:Notice';
    private string $_pluginBackupName='plugin:Notice-Backup';

    /**
     * @throws Typecho\Db\Exception
     */
    private function backup()
    {
        $setting = $this->_db->fetchRow($this->_db->select()->from('table.options')->where('name = ?', $this->_pluginName));
        $value = $setting['value'];
        if ($this->_db->fetchRow($this->_db->select()->from('table.options')->where('name = ?', $this->_pluginBackupName))) {
            $update = $this->_db->update('table.options')->rows(array('value' => $value))->where('name = ?', $this->_pluginBackupName);
            $updateRows = $this->_db->query($update);
            echo 1;
        } else {
            $insert = $this->_db->insert('table.options')->rows(array('name' => $this->_pluginBackupName, 'user' => '0', 'value' => $value));
            $this->_db->query($insert);
            echo 2;
        }
    }

    /**
     * @throws Typecho\Db\Exception
     */
    private function del_backup()
    {
        if ($this->_db->fetchRow($this->_db->select()->from('table.options')->where('name = ?', $this->_pluginBackupName))) {
            $delete = $this->_db->delete('table.options')->where('name = ?', $this->_pluginBackupName);
            $deletedRows = $this->_db->query($delete);
            echo 1;
        } else {
            echo -1;
        }
    }

    /**
     * @throws Typecho\Db\Exception
     */
    private function recover_backup()
    {
        if ($this->_db->fetchRow($this->_db->select()->from('table.options')->where('name = ?', $this->_pluginBackupName))) {
            $setting = $this->_db->fetchRow($this->_db->select()->from('table.options')->where('name = ?', $this->_pluginBackupName));
            $value = $setting['value'];
            $update = $this->_db->update('table.options')->rows(array('value' => $value))->where('name = ?', $this->_pluginName);
            $updateRows = $this->_db->query($update);
            echo 1;
        } else {
            echo -1;
        }
    }

    /**
     * @throws Typecho\Db\Exception
     */
    protected function init(){
        $this->_db = Typecho\Db::get();
    }

    /**
     * @throws Typecho\Db\Exception
     */
    public function action()
    {
        Typecho\Widget::widget('Widget_User')->pass('administrator');
        $this->init();
        $this->on($this->request->is('do=backup'))->backup();
        $this->on($this->request->is('do=del_backup'))->del_backup();
        $this->on($this->request->is('do=recover_backup'))->recover_backup();
    }
}
