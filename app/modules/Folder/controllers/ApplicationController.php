<?php

class Folder_ApplicationController extends Application_Controller_Default
{

    public function editpostAction() {

        if($datas = $this->getRequest()->getPost()) {

            try {
                $application = $this->getApplication();

                // Test s'il y a un value_id
                if(empty($datas['value_id'])) throw new Exception($this->_('An error occurred while saving'));

                if(empty($datas['title'])) throw new Exception($this->_('Folder title is required'));

                // Récupère l'option_value en cours
                $option_value = new Application_Model_Option_Value();
                $option_value->find($datas['value_id']);

                $folder = new Folder_Model_Folder();
                $category = new Folder_Model_Category();
                if(!empty($datas['category_id'])) {
                    $category->find($datas['category_id'], 'category_id');
                }

                if($datas['parent_id'] == 'null') {
                    unset($datas['parent_id']);
                    //Assigne le nom de catégorie root à la feature
                    $option_value->setTabbarName($datas['title'])->save();
                } else {
                    $datas['pos'] = $category->getNextCategoryPosition($datas['parent_id']);
                }

                if(!empty($datas['file'])) {
                    $relative_path = '/feature/folder/';
                    $path = Application_Model_Application::getBaseImagePath().$relative_path;
                    $file = Core_Model_Directory::getTmpDirectory(true).'/'.$datas['file'];
                    if(!is_dir($path)) mkdir($path, 0777, true);
                    if(!copy($file, $path.$datas['file'])) {
                        throw new exception($this->_('An error occurred while saving. Please try again later.'));
                    } else {
                        $datas['picture'] = $relative_path.$datas['file'];
                    }
                } else if(!empty($datas['remove_picture'])) {
                    $datas['picture'] = null;
                }

                $category->addData($datas)->save();

                //Change root category
                if(!isset($datas['parent_id'])) {
                    $folder->find($option_value->getId(), 'value_id');
                    $folder->setValueId($datas['value_id'])
                        ->setRootCategoryId($category->getId())
                        ->save();
                    $parent_id = 'null';
                } else {
                    $parent_id = $datas['parent_id'];
                }

                $html = array(
                    'success' => '1',
                    'success_message' => $this->_('Infos successfully saved'),
                    'category_id' => $category->getId(),
                    'parent_id' => $parent_id,
                    'message_timeout' => 2,
                    'message_button' => 0,
                    'message_loader' => 0
                );

            }
            catch(Exception $e) {
                $html = array(
                    'message' => $e->getMessage(),
                    'message_button' => 1,
                    'message_loader' => 1
                );
            }

            $this->getLayout()->setHtml(Zend_Json::encode($html));

        }

    }

    public function cropAction() {

        if($datas = $this->getRequest()->getPost()) {
            try {
                $uploader = new Core_Model_Lib_Uploader();
                $file = $uploader->savecrop($datas);

                $datas = array(
                    'success' => 1,
                    'file' => $file,
                    'message_success' => 'Enregistrement réussi',
                    'message_button' => 0,
                    'message_timeout' => 2,
                );
            } catch (Exception $e) {
                $datas = array(
                    'error' => 1,
                    'message' => $e->getMessage()
                );
            }
            $this->getLayout()->setHtml(Zend_Json::encode($datas));
         }

    }

    public function renderformAction() {
        if($datas = $this->getRequest()->getParams()) {

            try {
                $partial_form = $this->getLayout()->addPartial('form_category', 'core_view_mobile_default', 'folder/application/edit/form.phtml')
                    ->setValueId($datas['option_value_id'])
                    ->setCategoryId($datas['category_id'])
                    ->setParentId($datas['parent_id'])
                    ->toHtml();
                ;

                $subcategories_html = "";

                if(!empty($datas['load_subcategories'])) {
                    $subcategories_html = $this->getLayout()
                        ->addPartial('folder_manage_sidebar', 'Core_View_Default', 'folder/application/edit/sidebar.phtml')
                        ->setValueId($datas['option_value_id'])
                        ->setParentId($datas['category_id'])
                        ->toHtml()
                    ;
                }

                $html = array(
                    'success' => 1,
                    'form' => $partial_form
                );
                if(!empty($subcategories_html)) {
                    $html['subcategories_html'] = $subcategories_html;
                }

            } catch(Exception $e) {
                $html = array('message' => $e->getMessage());
            }

            $this->getLayout()->setHtml(Zend_Json::encode($html));

        }
    }

    public function addfeatureAction() {
        if($datas = $this->getRequest()->getPost()) {

            try {

                // Test s'il y a un value_id
                if(empty($datas['value_id'])) throw new Exception($this->_('An error occurred while saving'));

                // Récupère l'option_value en cours
                $option_value = new Application_Model_Option_Value();
                $option_value->find($datas['value_id']);

                $category = new Folder_Model_Category();
                $category->find($datas['category_id'], 'category_id');

                // Récupère l'option_value en cours
                $category_option_value = new Application_Model_Option_Value();
                $category_option_value->find($datas['category_value_id']);

                $next_positon = $category_option_value->getNextFolderCategoryPosition($datas['category_id']);

                $option_folder = new Application_Model_Option();
                $option_folder->find(array('code' => 'folder'));
                $option_folder_id = $option_folder->getOptionId();

                if( $category_option_value->getFolderCategoryId() == $datas['category_id']
                    || $category_option_value->getOptionId() == $option_folder_id
                ) {
                    throw new Exception($this->_('You cannot add this feature'));
                }

                $category_option_value
                    ->setFolderId($datas['value_id'])
                    ->setFolderCategoryPosition($next_positon)
                    ->setFolderCategoryId($category->getCategoryId())
                    ->save();

                $html = array('success' => 1, 'folder_id' => $datas['value_id']);

            } catch(Exception $e) {
                $html = array('message' => $e->getMessage());
            }

            $this->getLayout()->setHtml(Zend_Json::encode($html));
        }

    }

    public function orderfeaturesAction() {

        if($datas = $this->getRequest()->getPost()) {

            try {
                $option_values = $this->getRequest()->getParam('option_value');

                if(empty($option_values)) throw new Exception($this->_('An error occurred while saving. Please try again later.'));
                $i = 0;
                foreach($option_values as $index => $option_value) {
                    $category_option_value = new Application_Model_Option_Value();
                    $category_option_value->find($option_value);
                    if($category_option_value->getFolderCategoryId()) {
                        $category_option_value->setFolderCategoryPosition($i)->save();
                        $i++;
                    }
                }

                // Renvoie OK
                $html = array(
                    'success' => 1
                );
            }
            catch(Exception $e) {
                $html = array(
                    'message' => $e->getMessage(),
                    'message_button' => 1,
                    'message_loader' => 1
                );
            }

            $this->getLayout()->setHtml(Zend_Json::encode($html));

        }

    }

    public function ordercategoriesAction() {

        if($datas = $this->getRequest()->getParams()) {

            try {

                // Récupère les positions
                $positions = $this->getRequest()->getParam('category');
                // Supprime la root cat en conservant les index
                reset($positions);
                $key = key($positions);
                unset($positions[$key]);

                if(empty($positions)) throw new Exception($this->_('An error occurred while saving. Please try again later.'));

                $position = 0;
                foreach($positions as $index => $parent_category) {
                    $category = new Folder_Model_Category();
                    $category->find($index, 'category_id');
                    $category
                        ->setParentId($parent_category)
                        ->setPos($position)
                        ->save();
                    $position+=1;
                }

                // Renvoie OK
                $html = array('success' => 1);

            }
            catch(Exception $e) {
                $html = array(
                    'message' => $e->getMessage(),
                    'message_button' => 1,
                    'message_loader' => 1
                );
            }

            $this->getLayout()->setHtml(Zend_Json::encode($html));

        }

    }

    public function deletecategoryAction() {

        if($datas = $this->getRequest()->getParams()) {

            try {
                // Test s'il y a un value_id
                if(empty($datas['value_id'])) throw new Exception($this->_('An error occurred while saving'));

                // Récupère l'option_value en cours
                $option_value = new Application_Model_Option_Value();
                $option_value->find($datas['value_id']);

                $category = new Folder_Model_Category();
                $category->find($datas['category_id']);

                $parent_id = $category->getParentId();
                $category->delete();

                $value_ids = array();
                $option_value = new Application_Model_Option_Value();
                $option_values = $option_value->findAll(array('a.app_id' => $this->getApplication()->getId()), 'position ASC');
                foreach($option_values as $option_value) {
                    if($option_value->getFolderId() OR $option_value->getCode() == "folder") continue;
                    $value_ids[] = $option_value->getId();
                }

                $html = array(
                    'success' => 1,
                    'parent_id' => $parent_id,
                    'value_ids' => $value_ids
                );
            }
            catch(Exception $e) {
                $html = array(
                    'message' => $e->getMessage(),
                    'message_button' => 1,
                    'message_loader' => 1
                );
            }

            $this->getLayout()->setHtml(Zend_Json::encode($html));

        }

    }

}