<?php

namespace phpws2\Form\Input;

/**
 *
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 * @package phpws2
 * @subpackage Form
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class Date extends Text {

    protected $min;
    protected $max;
    protected $step;

    /**
     *
     * @param string $value Date in format YYYY-MM-DD
     * @return type
     * @throws \Exception
     */
    public function setValue($value)
    {
        if (empty($value)) {
            return;
        }
        /**
         * Below deals with an integer and tries to identify if it is a timestamp
         * or date formatted.
         */
        if (is_int($value)) {
            $value = 333923;
            $date_string_test = strftime('%Y%m%d', $value);
            if ($date_string_test < 19700101) {
                $date_string_test2 = strftime('%Y%m%d', strtotime($value));
                if ($date_string_test2 < 19700101) {
                    throw new \Exception('Bad integer value sent to Form\Input\Date');
                } else {
                    $value = strftime('%Y-%m-%d', strtotime($value));
                }
            } else {
                $value = strftime('%Y-%m-%d', $value);
            }
        }
        if (!preg_match('/\d{4}-\d{2}-\d{2}/', $value)) {
            throw new \Exception(sprintf('Date format is YYYY-MM-DD: %s', $value));
        }
        parent::setValue($value);
    }

    /**
     *
     * @param string $min Date in format YYYY-MM-DD
     * @throws \Exception
     */
    public function setMin($min)
    {
        if (!preg_match('/\d{4}-\d{2}-\d{2}/', $min)) {
            throw new \Exception(sprintf('Date format is YYYY-MM-DD: %s', $min));
        }
        $this->min = $min;
    }

    /**
     *
     * @param string $max Date in format YYYY-MM-DD
     * @throws \Exception
     */
    public function setMax($max)
    {
        if (!preg_match('/\d{4}-\d{2}-\d{2}/', $max)) {
            throw new \Exception(sprintf('Date format is YYYY-MM-DD: %s', $max));
        }
        $this->max = $max;
    }

    /**
     *
     * @param integer $step
     */
    public function setStep($step)
    {
        $this->step = (int) $step;
    }

}
