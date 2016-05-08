<?php

class Core_Model_Lib_Uploader extends Core_Model_Default
{

    public function upload($params = array()) {

        if (!is_dir($params['destination_folder']))
            mkdir($params['destination_folder'], 0777, true);

        $adapter = new Zend_File_Transfer_Adapter_Http();
        $adapter->setDestination($params['destination_folder']);

        $adapter->setValidators($params['validators']);

        if($adapter->getValidator('ImageSize')) {
            $adapter->getValidator('ImageSize')->setMessages(array(
                'fileImageSizeWidthTooBig' => $this->_('Image too large, %spx maximum allowed.', '%maxwidth%'),
                'fileImageSizeWidthTooSmall' => $this->_('Image not large enough, %spx minimum allowed.', '%minwidth%'),
                'fileImageSizeHeightTooBig' => $this->_('Image too high, %spx maximum allowed.', '%maxheight%'),
                'fileImageSizeHeightTooSmall' => $this->_('Image not high enough, %spx minimum allowed.', '%minheight%'),
                'fileImageSizeNotDetected' => $this->_("The image size '%s' could not be detected.", '%value%'),
                'fileImageSizeNotReadable' => $this->_("The image '%s' does not exist", '%value%')
            ));
        }

        if($adapter->getValidator('Size')) {
            $adapter->getValidator('Size')->setMessages(array(
                'fileSizeTooBig' => $this->_("Image too large, '%s' allowed.", '%max%'),
                'fileSizeTooSmall' => $this->_("Image not large enough, '%s' allowed.", '%min%'),
                'fileSizeNotFound' => $this->_("The image '%s' does not exist", '%value%')
            ));
        }

        if($adapter->getValidator('Extension')) {
            $adapter->getValidator('Extension')->setMessages(array(
                'fileExtensionFalse' => $this->_("Extension not allowed, '%s' only", '%extension%'),
                'fileExtensionNotFound' => $this->_("The file '%s' does not exist", '%value%')
            ));
        }

        $files = $adapter->getFileInfo();
        $return_file = '';
        foreach ($files as $file => $info) {
            //Créé l'image sur le serveur
            if (!$adapter->isUploaded($file)) {
                throw new Exception($this->_('An error occurred during process. Please try again later.'));
            } else if (!$adapter->isValid($file)) {
                if(count($adapter->getMessages()) == 1) {
                    $erreur_message = $this->_('Error : <br/>');
                } else {
                    $erreur_message = $this->_('Errors : <br/>');
                }
                foreach($adapter->getMessages() as $message) {
                    $erreur_message .= '- '.$message.'<br/>';
                }
                throw new Exception($erreur_message);
            } else {
                $new_name = uniqid("file_");
                if(isset($params['uniq']) AND $params['uniq'] == 1) {
                    if(isset($params['desired_name'])) {
                        $new_name = $params['desired_name'];
                    } else {
                        $new_name = $params['uniq_prefix']. uniqid() . '.png';
                    }
                    $new_pathname = $params['destination_folder'] . '/' . $new_name;
                    $adapter->addFilter(new Zend_Filter_File_Rename(array(
                        'target' => $new_pathname,
                        'overwrite' => true)
                    ));
                }
                $adapter->receive($file);
                $return_file = $new_name;
            }
        }
        return $return_file;
    }

    public function savecrop($params = array()) {

        $temp = Core_Model_Directory::getTmpDirectory(true).'/';
        $file = $temp.$params['file'];

        $source_width = $params['source_width'];
        $source_height = $params['source_height'];
        $crop_width = $params['crop_width'];
        $crop_height = $params['crop_height'];
        $targ_w = $params['output_width'];
        $targ_h = $params['output_height'];

        $folder = $temp;
        if(isset($params['dest_folder'])) {
            $folder = $params['dest_folder'];
        }

        if (!is_dir($folder)) mkdir($folder, 0777, true);

        $source = imagecreatefromstring(file_get_contents($file));
        $dest = imagecreatetruecolor($targ_w, $targ_h);
        $trans_colour = imagecolorallocatealpha($source, 0, 0, 0, 127);

        $dst_x = 0;
        $dst_y = 0;
        $src_x = $params['x1']*$source_width/$crop_width;
        $src_y = $params['y1']*$source_height/$crop_height;
        $dst_w = $targ_w;
        $dst_h = $targ_h;
        $src_w = $params['w']*$source_width/$crop_width;
        $src_h = $params['h']*$source_height/$crop_height;

        imagealphablending($dest, false);
        imagefill($dest, 0, 0, $trans_colour);
        imagecopyresampled($dest,$source,$dst_x,$dst_y,$src_x,$src_y,$dst_w,$dst_h,$src_w,$src_h);
        imagesavealpha($dest, true );

        $new_name = uniqid() . '.png';
        if(isset($params['new_name'])) {
            $new_name = $params['new_name'];
        }
        imagepng($dest, $folder.$new_name);
        return $new_name;

    }

}