<?php

class Weblink_Application_MultiController extends Application_Controller_Default
{

    public function editpostAction() {

        if($datas = $this->getRequest()->getPost()) {

            try {
                $isNew = false;
                $application = $this->getApplication();

                // Test s'il y a un value_id
                if(empty($datas['value_id'])) throw new Exception($this->_('An error occurred while saving. Please try again later.'));

                // Récupère l'option_value en cours
                $option_value = new Application_Model_Option_Value();
                $option_value->find($datas['value_id']);

                $html = array(
                    'success' => '1',
                    'success_message' => $this->_('Link has been successfully saved'),
                    'message_timeout' => 2,
                    'message_button' => 0,
                    'message_loader' => 0
                );

                // Prépare la weblink
                $weblink = $option_value->getObject();
                if(!$weblink->getId()) {
                    $weblink->setValueId($datas['value_id']);
                }

                // S'il y a une cover image
                if(!empty($datas['file'])) {

                    if(!empty($datas['file'])) {
                        $relative_path = '/feature/weblink/cover/';
                        $folder = Application_Model_Application::getBaseImagePath().$relative_path;
                        $path = Application_Model_Application::getBaseImagePath().$relative_path;
                        $file = Core_Model_Directory::getTmpDirectory(true).'/'.$datas['file'];
                        if(!is_dir($path)) mkdir($path, 0777, true);
                        if(!copy($file, $folder.$datas['file'])) {
                            throw new exception($this->_('An error occurred while saving your picture. Please try againg later.'));
                        } else {
                            $weblink->setCover($relative_path.$datas['file']);
                        }

                        if(empty($datas['link'])) $html['success_message'] = $this->_("The image has been successfully saved");
                    }
                }
                else if(!empty($datas['remove_cover'])) {
                    $weblink->setCover(null);
                    if(empty($datas['link'])) $html['success_message'] = $this->_("The image has been successfully deleted");
                }
                // Sauvegarde le weblink
                $weblink->save();

                if(!empty($datas['link'])) {
                    $link_datas = $datas['link'];

                    if(empty($link_datas['url']) OR !Zend_Uri::check($link_datas['url'])) {
                        throw new Exception($this->_('Please enter a valid url'));
                    }

                    // Prépare le link
                    $link = new Weblink_Model_Weblink_Link();
                    if(!empty($link_datas['link_id'])) {
                        $link->find($link_datas['link_id']);
                    }

                    $isNew = !$link->getId();
                    $link_datas['weblink_id'] = $weblink->getId();

                    // Test s'il y a un picto
                    if(!empty($link_datas['picto']) AND file_exists(Core_Model_Directory::getTmpDirectory(true).'/'.$link_datas['picto'])) {
                        $relative_path = '/feature/weblink/pictos/';
                        $folder = Application_Model_Application::getBaseImagePath().$relative_path;
                        $path = Application_Model_Application::getBaseImagePath().$relative_path;
                        $file = Core_Model_Directory::getTmpDirectory(true).'/'.$link_datas['picto'];
                        if(!is_dir($path)) mkdir($path, 0777, true);
                        if(!copy($file, $folder.$link_datas['picto'])) {
                            throw new exception($this->_("An error occurred while saving your picto. Please try againg later."));
                        } else {
                            $link_datas['picto'] = $relative_path.$link_datas['picto'];
                        }
                    }
                    // Sauvegarde le link
                    $link->addData($link_datas);
                    $isDeleted = $link->getIsDeleted();
                    $link->save();

                    if($isDeleted) {
                        $html['success_message'] = $this->_('Link has been successfully deleted');
                        $html['is_deleted'] = 1;
                    }
                }

                if($isNew) {
                    $html['row_html'] = $this->getLayout()->addPartial('row_', 'admin_view_default', 'weblink/application/multi/edit/row.phtml')
                        ->setCurrentLink($link)
                        ->setCurrentOptionValue($option_value)
                        ->toHtml()
                    ;
                }

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
                    'file' => $file
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

}