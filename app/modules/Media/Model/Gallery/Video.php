<?php

class Media_Model_Gallery_Video extends Core_Model_Default {

    protected $_type_instance;
    protected $_types = array(
        'youtube',
        'itunes',
        'vimeo'
    );
    protected $_offset = 1;

    public function __construct($params = array()) {
        parent::__construct($params);
        $this->_db_table = 'Media_Model_Db_Table_Gallery_Video';
        return $this;
    }

    public function find($id, $field = null) {
        parent::find($id, $field);
        if($this->getId()) {
            $this->_addTypeDatas();
        }

        return $this;
    }

    public function findAll($values = array(), $order = null, $params = array()) {
        $rows = parent::findAll($values, $order, $params);
        foreach($rows as $row) {
            $row->_addTypeDatas();
        }
        return $rows;
    }

    public function getTypeInstance() {

        if(!$this->_type_instance) {
            $type = $this->getTypeId();
            if(in_array($type, $this->_types)) {
                $class = 'Media_Model_Gallery_Video_'.ucfirst($type);
                $this->_type_instance = new $class();
                $this->_type_instance->addData($this->getData());
            }
        }

        return !empty($this->_type_instance) ? $this->_type_instance : null;

    }

    public function save() {
        $isDeleted = $this->getIsDeleted();
        parent::save();
        if(!$isDeleted) {
            if($this->getTypeInstance()->getId()) $this->getTypeInstance()->delete();
            $this->getTypeInstance()->setData($this->_getTypeInstanceData())->setGalleryId($this->getId())->save();
        }
        return $this;
    }

    public function getAllTypes() {
        if($this->getTypeInstance()) {
            return $this->getTypeInstance()->getAllTypes();
        }
        return array();
    }

    public function getVideos() {
        if($this->getId() AND $this->getTypeInstance()) {
            return $this->getTypeInstance()->getVideos($this->_offset);
        }
        return array();
    }

    public function setOffset($offset) {
        $this->_offset = $offset;
        return $this;
    }

    protected function _addTypeDatas() {
        if($this->getTypeInstance()) {
            $this->getTypeInstance()->find($this->getId());
            if($this->getTypeInstance()->getId()) {
                $this->addData($this->getTypeInstance()->getData());
            }
        }

        return $this;
    }

    protected function _getTypeInstanceData() {
        $fields = $this->getTypeInstance()->getFields();
        $datas = array();
        foreach($fields as $field) {
            $datas[$field] = $this->getData($field);
        }

        return $datas;
    }
}
