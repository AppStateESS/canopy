<?php
/**
 * See docs/AUTHORS and docs/COPYRIGHT for relevant info.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 *
 * @version $Id$
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @package
 * @license http://opensource.org/licenses/gpl-3.0.html
 */

require_once PHPWS_SOURCE_DIR . 'core/class/Form2/Option.php';

class Select extends Base {
    protected $multiple = null;
    private $options = null;
    //private $optgroup = null;
    private $named_options = false;

    protected $selected = null;

    public function __construct($name, array $options, $multiple=false)
    {
        parent::__construct('select');
        $this->setName($name);
        $this->addOptionArray($options);
        $this->setMultiple($multiple);
    }

    public function setName($name)
    {
        if (!$this->isProper($name)) {
            throw new PEAR_Exception(dgettext('core', 'Improper input name'));
        }
        $this->name = $name;
    }

    public function setMultiple($multiple=true)
    {
        if ($multiple) {
            $this->multiple = 'multiple';
        } else {
            $this->multiple = null;
        }
    }

    public function addOption($value, $name=null, $optgroup=null)
    {
        if (!$this->isProper($name)) {
            $name = $value;
        }
        $option = new Option($value, $name);
        if (!empty($optgroup)) {
            $option->setOptgroup($optgroup);
        }

        $this->options[$name] = $option;
        return $option;
    }

    public function addOptionArray(array $options, $optgroup=null)
    {
        foreach ($options as $key=>$value) {
            if (is_array($value)) {
                $this->addOptionArray($value, $key);
            } elseif (is_a($value, 'Option')) {
                $this->options[$value->name] = $value;
            } else {
                $this->addOption($value, $key, $optgroup);
            }
        }
    }

    public function getOption($name)
    {
        if (!isset($this->options[$name])) {
            throw new PEAR_Exception(dgettext('core', 'Select option does not exist'));
        }
        return $this->options[$name];
    }

    public function isOption($name)
    {
        return isset($this->options[$name]);
    }


    /**
     * Alternative to the parent function. The value is set right before the
     * toString is called from Tag. This allows the developer time to
     * alter the options.
     * @return unknown_type
     */
    public function __toString($with_label=false)
    {
        $optgroups_exist = false;
        if (!empty($this->options)) {
            foreach ($this->options as $option) {
                if ($option->hasOptgroup()) {
                    $optgroups[$option->getOptgroup()][] = $option->__toString();
                    $optgroups_exist = true;
                } else {
                    $value[] = $option->__toString();
                }
            }
        }
        if ($optgroups_exist) {
            foreach ($optgroups as $name=>$options) {
                $value[] = "<optgroup label=\"$name\">";
                $value[] = implode("\n", $options);
                $value[] = "</optgroup>";

            }
        }
        $this->setValue(implode("\n", $value));
        return parent::__toString($with_label);
    }

    public function setSelected($name)
    {
        if (!isset($this->options[$name])) {
            throw new PEAR_Exception(dgettext('core', 'Option index not found in select'));
        }
        $this->options[$name]->setSelected();
    }
}
?>