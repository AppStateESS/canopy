<?php

namespace phpws2\Form;

/**
 *
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class Label extends \phpws2\Tag {

    protected $for;

    private $required = false;

    public function __construct($text=null, $for = null)
    {
        if (isset($for)) {
            $this->setFor($for);
        }
        parent::__construct('label', $text);
    }

    public function setFor($for)
    {
        $this->for = strip_tags($for);
    }

    public function __toString()
    {
        $label = parent::__toString();

        if ($this->required) {
            $label .= ' <i class="required fa fa-asterisk"></i>';
        }
        return $label;
    }


    public function setRequired($required)
    {
        $this->required = (bool) $required;
    }
}
