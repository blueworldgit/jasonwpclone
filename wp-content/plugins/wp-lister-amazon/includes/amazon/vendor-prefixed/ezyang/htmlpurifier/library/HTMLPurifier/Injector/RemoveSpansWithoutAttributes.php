<?php

/**
 * Injector that removes spans with no attributes
 *
 * @license LGPL-2.1-or-later
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */
class WPLab_Amazon_HTMLPurifier_Injector_RemoveSpansWithoutAttributes extends WPLab_Amazon_HTMLPurifier_Injector
{
    /**
     * @type string
     */
    public $name = 'RemoveSpansWithoutAttributes';

    /**
     * @type array
     */
    public $needed = array('span');

    /**
     * @type WPLab_Amazon_HTMLPurifier_AttrValidator
     */
    private $attrValidator;

    /**
     * Used by AttrValidator.
     * @type WPLab_Amazon_HTMLPurifier_Config
     */
    private $config;

    /**
     * @type WPLab_Amazon_HTMLPurifier_Context
     */
    private $context;

    /**
     * @type SplObjectStorage
     */
    private $markForDeletion;

    public function __construct()
    {
        $this->markForDeletion = new SplObjectStorage();
    }

    public function prepare($config, $context)
    {
        $this->attrValidator = new WPLab_Amazon_HTMLPurifier_AttrValidator();
        $this->config = $config;
        $this->context = $context;
        return parent::prepare($config, $context);
    }

    /**
     * @param WPLab_Amazon_HTMLPurifier_Token $token
     */
    public function handleElement(&$token)
    {
        if ($token->name !== 'span' || !$token instanceof WPLab_Amazon_HTMLPurifier_Token_Start) {
            return;
        }

        // We need to validate the attributes now since this doesn't normally
        // happen until after MakeWellFormed. If all the attributes are removed
        // the span needs to be removed too.
        $this->attrValidator->validateToken($token, $this->config, $this->context);
        $token->armor['ValidateAttributes'] = true;

        if (!empty($token->attr)) {
            return;
        }

        $nesting = 0;
        while ($this->forwardUntilEndToken($i, $current, $nesting)) {
        }

        if ($current instanceof WPLab_Amazon_HTMLPurifier_Token_End && $current->name === 'span') {
            // Mark closing span tag for deletion
            $this->markForDeletion->attach($current);
            // Delete open span tag
            $token = false;
        }
    }

    /**
     * @param WPLab_Amazon_HTMLPurifier_Token $token
     */
    public function handleEnd(&$token)
    {
        if ($this->markForDeletion->contains($token)) {
            $this->markForDeletion->detach($token);
            $token = false;
        }
    }
}

// vim: et sw=4 sts=4
