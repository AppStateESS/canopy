<?php

namespace phpws2\Variable;

/**
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @package phpws2
 * @subpackage Variable
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class DateVar extends IntegerVar
{

    protected $input_type = 'date';

    /**
     * Output format. Based on strftime.
     * @var string
     */
    protected $format = '%F';
    // If true, 0 or null will be returned as 1969-12-31
    protected $printEmpty = true;

    public function getInput()
    {
        $input = parent::getInput();
        $input->setSize(10, 10);
        return $input;
    }

    public function addTime($time)
    {
        $this->set($this->value + $time);
    }

    public function setPrintEmpty($printEmpty)
    {
        $this->printEmpty = (bool) $printEmpty;
    }

    /**
     * Sets the output format.
     *
     * @see http://php.net/manual/en/function.strftime.php
     * @param string $format
     */
    public function setFormat($format)
    {
        $this->format = $format;
    }

    public function getFormat()
    {
        return $this->format;
    }

    public function toDatabase()
    {
        return $this->value;
    }

    public function __toString()
    {
        return (string) $this->get($this->format);
    }

    public function stamp()
    {
        $this->set(time());
    }

    public function get($format = true)
    {
        if (empty($this->value) && !$this->printEmpty) {
            return '';
        } else {
            if (is_string($format)) {
                return strftime($format, $this->value);
            } elseif ($format === true && !empty($this->format)) {
                return strftime($this->format, $this->value);
            } else {
                return $this->value;
            }
        }
    }

}
