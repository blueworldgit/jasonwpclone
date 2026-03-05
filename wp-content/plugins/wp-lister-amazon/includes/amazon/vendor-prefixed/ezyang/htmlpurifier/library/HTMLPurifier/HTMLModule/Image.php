<?php

/**
 * XHTML 1.1 Image Module provides basic image embedding.
 * @note There is specialized code for removing empty images in
 *       WPLab_Amazon_HTMLPurifier_Strategy_RemoveForeignElements
 *
 * @license LGPL-2.1-or-later
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */
class WPLab_Amazon_HTMLPurifier_HTMLModule_Image extends WPLab_Amazon_HTMLPurifier_HTMLModule
{

    /**
     * @type string
     */
    public $name = 'Image';

    /**
     * @param WPLab_Amazon_HTMLPurifier_Config $config
     */
    public function setup($config)
    {
        $max = $config->get('HTML.MaxImgLength');
        $img = $this->addElement(
            'img',
            'Inline',
            'Empty',
            'Common',
            array(
                'alt*' => 'Text',
                // According to the spec, it's Length, but percents can
                // be abused, so we allow only Pixels.
                'height' => 'Pixels#' . $max,
                'width' => 'Pixels#' . $max,
                'longdesc' => 'URI',
                'src*' => new WPLab_Amazon_HTMLPurifier_AttrDef_URI(true), // embedded
            )
        );
        if ($max === null || $config->get('HTML.Trusted')) {
            $img->attr['height'] =
            $img->attr['width'] = 'Length';
        }

        // kind of strange, but splitting things up would be inefficient
        $img->attr_transform_pre[] =
        $img->attr_transform_post[] =
            new WPLab_Amazon_HTMLPurifier_AttrTransform_ImgRequired();
    }
}

// vim: et sw=4 sts=4
