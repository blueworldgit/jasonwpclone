<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace WPLab\Amazon\PhpOffice\PhpSpreadsheet\Shared\Escher\DggContainer;

class BstoreContainer
{
    /**
     * BLIP Store Entries. Each of them holds one BLIP (Big Large Image or Picture).
     *
     * @var BstoreContainer\BSE[]
     */
    private $BSECollection = [];

    /**
     * Add a BLIP Store Entry.
     */
    public function addBSE(BstoreContainer\BSE $BSE): void
    {
        $this->BSECollection[] = $BSE;
        $BSE->setParent($this);
    }

    /**
     * Get the collection of BLIP Store Entries.
     *
     * @return BstoreContainer\BSE[]
     */
    public function getBSECollection()
    {
        return $this->BSECollection;
    }
}
