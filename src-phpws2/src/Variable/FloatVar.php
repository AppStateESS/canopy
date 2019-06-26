<?php

namespace phpws2\Variable;

/**
 * A class to assist with float variables.
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 * @package phpws2
 * @subpackage Variable
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class FloatVar extends \phpws2\Variable
{

    /**
      /* We use decimal instead of float as it is used
      /* in mysql and pgsql.
     * If you must use float, overwrite the column_type
     * @var string
     */
    protected $column_type = 'decimal';
    protected $digits = 10;
    protected $decimalPoint = 1;

    /**
     * Checks to see if value is a float.
     * @param float $value
     * @return boolean | \phpws2\Error
     */
    protected function verifyValue($value)
    {
        
        if (!is_numeric($value) || !is_float((float)$value)) {
            throw new \Exception('Value is not a float');
        }
        return true;
    }

    public function setPrecision(int $digits, int $decimalPoint)
    {
        $this->digits = $digits;
        $this->decimalPoint = $decimalPoint;
    }

    /**
     * Returns the float as a string.
     * @return string
     */
    public function __toString()
    {
        return (string) $this->get();
    }

    /**
     *
     * @param \phpws2\Database\Table $table
     * @return \phpws2\Database\Datatype
     */
    public function loadDataType(\phpws2\Database\Table $table)
    {
        $size = $this->digits . ',' . $this->decimalPoint;
        $dt = parent::loadDataType($table);
        $dt->setSize($size);
        $default = "";
        if ($dt->getDefault()->get() == 0) {
            for ($i=0; $i < $this->decimalPoint; $i++) {
                $default .= '0';
            }
            $decimals = "0.$default";
            $dt->setDefault($decimals);
        }
        return $dt;
    }

}
