<?php

class Customer_Model_Customer extends Core_Model_Default
{

    const IMAGE_PATH = '/images/customer';

    protected $_social_datas = array();
    protected $_types = array('facebook');
    protected $_social_instances = array();

    public function __construct($datas = array()) {
        parent::__construct($datas);
        $this->_db_table = 'Customer_Model_Db_Table_Customer';
    }

    public function findByEmail($email) {
        return $this->find($email, 'email');
    }

    public function findBySocialId($id, $type) {
        $datas = $this->getTable()->findBySocialId($id, $type);
        if(!empty($datas)) {
            $this->setData($datas)
                ->setId($datas['customer_id'])
            ;
        }

        return $this;
    }

    public function getName() {
        return $this->getFirstname() . ' ' . $this->getLastname();
    }

    public function getFacebook() {
        return $this->getSocialObject('facebook');
    }

    public function authenticate($password) {
        return $this->_checkPassword($password, $this->getPassword());
    }

    public function getSocialObject($name, $params = array()) {

        if(empty($this->_social_instances[$name])) {
            if(empty($params)) $params = $this->getSocialDatas($name);
            if(in_array($name, $this->_types)) {
                $social_datas = !empty($params['datas']) ? $params['datas'] : array();
                $class = 'Customer_Model_Customer_Type_'.ucfirst($name);
                $this->_social_instances[$name] = new $class(array('social_id' => $params['social_id'], 'social_datas' => $social_datas, 'application' => $this->getApplication()));
                $this->_social_instances[$name]->setCustomer($this);
            }
        }

        return !empty($this->_social_instances[$name]) ? $this->_social_instances[$name] : null;
    }

    public function getSocialDatas($name = null) {
        if(!$this->getId()) return null;
        if(is_null($name)) return $this->_social_datas;
        if(empty($this->_social_datas[$name])) {
            $this->_social_datas = $this->getTable()->findSocialDatas($this->getId());
        }
        return !empty($this->_social_datas[$name]) ? $this->_social_datas[$name] : null;
    }

    /**
     *
     * @param array $datas => ('type' => 'facebook', 'id' => $id, 'data' => $data);
     * @return Customer_Model_Customer
     */
    public function setSocialDatas(array $datas) {
        $this->_social_datas = $datas;
        return $this;
    }

    /**
     *
     * @param string $type 'facebook', etc...
     * @param array $datas => ('id' => $id, 'data' => $data);
     * @return Customer_Model_Customer
     */
    public function setSocialData($type, array $datas) {
        $this->_social_datas[$type] = $datas;
        return $this;
    }

    public function canPostSocialMessage() {

        foreach($this->_types as $type) {
            if($this->getSocialObject($type)->isValid()) return true;
        }
        return false;

    }

    public function addSocialPost($customer_message, $message_type, $points = null) {
        if($this->canPostSocialMessage()) {
            $this->getTable()->addSocialPost($this->getId(), $customer_message, $message_type, $points);
        }
        return $this;
    }

    public function deleteSocialPost($post_id) {
        $this->getTable()->deleteSocialPost($this->getId(), $post_id);
        return $this;
    }

    public function postSocialMessage($pos, $datas) {

        $isOk = true;

        foreach($this->_types as $type) {
            if($social = $this->getSocialObject($type) AND $social->isValid()) {

                $message = $social->prepareMessage($datas['message_type'], $pos, $datas['points']);
                $customer_message = !empty($datas['customer_message']) ? $datas['customer_message'] : "";
                $social->postMessage($pos, $customer_message, $message);
            }
        }

        return $isOk;
    }

    public function findAllPosts() {
        return $this->getTable()->findAllPosts();
    }

    public function isSamePassword($password) {
        return $this->getPassword() == $this->_encrypt($password);
    }

    public function setPassword($password) {
        if(strlen($password) < 6) throw new Exception($this->_('The password must be at least 6 characters'));
        $this->setData('password', $this->_encrypt($password));
        return $this;
    }

    public function getImagePath() {
        return Core_Model_Directory::getPathTo(self::IMAGE_PATH);
    }

    public function getBaseImagePath() {
        return Core_Model_Directory::getBasePathTo(self::IMAGE_PATH);
    }

    public function getImageLink() {
        if($this->getData('image') AND is_file($this->getBaseImagePath() . '/' . $this->getImage())) return $this->getImagePath() . '/' . $this->getImage();
        else return $this->getNoImage();
    }
    //
    public function getNoImage() {
        return $this->getImagePath().'/placeholder/no-image.png';
    }

    public function save() {
        parent::save();
        if(!is_null($this->_social_datas)) {
            $datas = array();
            foreach($this->_social_datas as $type => $data) {
                $datas[] = array('type' => $type, 'social_id' => $data['id'], 'datas' => serialize(!empty($data['datas']) ? $data['datas'] : array()));
            }
            $this->getTable()->insertSocialDatas($this->getId(), $datas);
        }
    }

    private function _checkPassword($password, $hash) {
        return $this->_encrypt($password) == $hash;
    }

    private function _encrypt($password) {
        return sha1($password);
    }

}