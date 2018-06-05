<?php

namespace phpws2\Variable;

/**
 * A class to assist with float variables.
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 * @package phpws2
 * @subpackage Variable
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class DoubleVar extends FloatVar
{

    /**
     * Checks to see if value is a float.
     * @param float $value
     * @return boolean | \phpws2\Error
     */
    protected function verifyValue($value)
    {
        // is_double is just an alias of is_float
        if (!is_float($value)) {
            throw new \Exception('Value is not a double');
        }
        return true;
    }
}
