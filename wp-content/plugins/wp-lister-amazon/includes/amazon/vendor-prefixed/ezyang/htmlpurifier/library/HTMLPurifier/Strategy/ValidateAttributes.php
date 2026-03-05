<?php

/**
 * Validate all attributes in the tokens.
 *
 * @license LGPL-2.1-or-later
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

class WPLab_Amazon_HTMLPurifier_Strategy_ValidateAttributes extends WPLab_Amazon_HTMLPurifier_Strategy
{

    /**
     * @param WPLab_Amazon_HTMLPurifier_Token[] $tokens
     * @param WPLab_Amazon_HTMLPurifier_Config $config
     * @param WPLab_Amazon_HTMLPurifier_Context $context
     * @return WPLab_Amazon_HTMLPurifier_Token[]
     */
    public function execute($tokens, $config, $context)
    {
        // setup validator
        $validator = new WPLab_Amazon_HTMLPurifier_AttrValidator();

        $token = false;
        $context->register('CurrentToken', $token);

        foreach ($tokens as $key => $token) {

            // only process tokens that have attributes,
            //   namely start and empty tags
            if (!$token instanceof WPLab_Amazon_HTMLPurifier_Token_Start && !$token instanceof WPLab_Amazon_HTMLPurifier_Token_Empty) {
                continue;
            }

            // skip tokens that are armored
            if (!empty($token->armor['ValidateAttributes'])) {
                continue;
            }

            // note that we have no facilities here for removing tokens
            $validator->validateToken($token, $config, $context);
        }
        $context->destroy('CurrentToken');
        return $tokens;
    }
}

// vim: et sw=4 sts=4
