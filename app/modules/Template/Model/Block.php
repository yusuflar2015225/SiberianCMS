<?php

class Template_Model_Block extends Core_Model_Default {

    const PATH_IMAGE = '/images/application';

    public function __construct($params = array()) {
        parent::__construct($params);
        $this->_db_table = 'Template_Model_Db_Table_Block';
        return $this;
    }

    public function findByDesign($design_id) {
        return $this->getTable()->findByDesign($design_id);
    }

    public function getName() {
        return $this->_($this->getData('name'));
    }

    public function useImageLink() {
        return $this->getData('use_image_link');
    }

    public function applyToAll() {
        return $this->getData('apply_to_all');
    }

    public function isUniform($block) {
        return
            $this->getId() == $block->getId()
            && ($this->getUseColor() == $block->getUseColor() || ($this->getUseColor() && !$block->getUseColor()))
            && ($this->getUseBackgroundColor() == $block->getUseBackgroundColor() || ($this->getUseBackgroundColor() && !$block->getUseBackgroundColor()))
            && ($this->useImageLink() == $block->useImageLink() || ($this->useImageLink() && !$block->useImageLink()))
        ;
    }

    public function getBackgroundImage($type = null) {
        $background_image = $this->getData('background_image');
        if(!empty($background_image)) {
            if($type == 'normal') $background_image .= '.jpg';
            else if($type == 'retina') $background_image .= '@2x.jpg';
            else if($type == 'retina4') $background_image .= '-568h@2x.jpg';
        }
        else {
            $background_image = '/media/admin/mobile/natif/no-background.png';
        }

        return $background_image;
    }

    public function setBackgroundImage($background_image) {
        $background_image = str_replace(self::PATH_IMAGE, "", $background_image);
        $this->setData('background_image', $background_image);
    }

    public function getImageColor() {
        if($this->getData('image_color')) return $this->getData('image_color');
        else return $this->getColor();
    }

    public function colorize($tile_path, $option, $color = null, $flat = true) {

        if(!is_file($tile_path)) return '';

        // Créé les chemins
        $application = $this->getApplication();
        $dst = '/'.$this->getCode().'/'.$option->getCode().'_'.uniqid().'.png';
        $base_dst = Application_Model_Application::getBaseImagePath().'/'.$dst;

        if(!is_dir(dirname($base_dst))) mkdir(dirname($base_dst), 0777, true);

        if(!$color) $color = $this->getImageColor();
        $color = str_replace('#', '', $color);
        $rgb = $this->toRgb($color);

        list($width, $height) = getimagesize($tile_path);
        $tile = imagecreatefromstring(file_get_contents($tile_path));

        if($tile) {
            for($x=0; $x<$width;$x++) {
                for($y=0;$y<$height;$y++) {
                    $colors = imagecolorat($tile, $x, $y);
                    $current_rgb = imagecolorsforindex($tile, $colors);
                    if($flat) {
                        $color = imagecolorallocatealpha($tile, $rgb['red'], $rgb['green'], $rgb['blue'], $current_rgb['alpha']);
                    }
                    else {
                        $color = imagecolorallocatealpha($tile, $current_rgb['red']*$rgb['red']/255, $current_rgb['green']*$rgb['green']/255, $current_rgb['blue']*$rgb['blue']/255, $current_rgb['alpha']);
                    }
                    imagesetpixel($tile, $x, $y, $color);
                }
            }
            $filename = basename($tile_path);
            imagesavealpha($tile, true);
            if(!@imagepng($tile, $base_dst)) {
                $dst = '';
            }
        }

        return $dst;
    }

    public function getColorRGB() {
        return $this->toRgb($this->getData("color"));
    }

    public function getBackgroundColorRGB() {
        return $this->toRgb($this->getData("background_color"));
    }

    public function toRgb($hexStr, $returnAsString = false, $seperator = ','){

        $hexStr = preg_replace("/[^0-9A-Fa-f]/", '', $hexStr);
        $rgbArray = array();

        if (strlen($hexStr) == 6) {
            $colorVal = hexdec($hexStr);
            $rgbArray['red'] = 0xFF & ($colorVal >> 0x10);
            $rgbArray['green'] = 0xFF & ($colorVal >> 0x8);
            $rgbArray['blue'] = 0xFF & $colorVal;
        }
        elseif (strlen($hexStr) == 3) {
            $rgbArray['red'] = hexdec(str_repeat(substr($hexStr, 0, 1), 2));
            $rgbArray['green'] = hexdec(str_repeat(substr($hexStr, 1, 1), 2));
            $rgbArray['blue'] = hexdec(str_repeat(substr($hexStr, 2, 1), 2));
        }
        else {
            return false;
        }

        return $returnAsString ? implode($seperator, $rgbArray) : $rgbArray;
    }

}
