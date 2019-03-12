<?php

namespace phpws2\Database;

/**
 * A database data type
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 * @package phpws2
 * @subpackage DB
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
abstract class Datatype extends \Canopy\Data
{
    /**
     * The name of the datatype/column name
     * @var string
     */
    protected $name = null;

    /**
     * Default value of data type
     * @var \phpws2\Variable
     */
    protected $default = null;

    /**
     * @var \phpws2\Variable Value of data type.
     */
    protected $value = null;

    /**
     * If true, the column will report itself as null
     * @var boolean
     */
    protected $is_null = false;
    protected $table = null;
    protected $check = null;

    protected $is_primary_key = false;
    protected $is_unique = false;

    /**
     * Size of datatype. Used with character types, floats, etc.
     * @var string
     */
    protected $size = null;

    /**
     * Creates a variable object for the default.
     */
    abstract protected function loadDefault();

    public function __construct(Table $table, $name)
    {
        $this->setName($name);
        $this->table = $table;
        $this->loadDefault();
    }

    /**
     * Creates a Datatype object for insertion into the passed table. The table's
     * database engine type is checked first. This allows database specific data
     * type instructions to be used.
     *
     * @param \phpws2\Database\Table $table
     * @param string $name Name of column/data type
     * @param string $type Data type
     * @param string $value Default value for column
     * @return \phpws2\Database\Datatype Returns an extension of Datatype
     * @throws \Exception
     */
    public static function factory(Table $table, $name, $type, $value = null)
    {
        $engine = (string)$table->db->getDatabaseType();
        if ($engine === 'mysqli') {
            $engine = 'mysql';
        }
        $alltypes = $table->getDatatypeList();
        $type = strtolower($type);
        if (empty($type)) {
            throw new \Exception('Data type was empty');
        }
        if (!isset($alltypes[$type])) {
            throw new \Exception("Unknown data type: $type");
        }
        $class_name = ucwords($alltypes[$type]);
        $class_file = PHPWS_SOURCE_DIR . "src-phpws2/src/Database/Datatype/$class_name.php";
        $engine_file = PHPWS_SOURCE_DIR . "src-phpws2/src/Database/Engine/$engine/Datatype/$class_name.php";

        if (is_file($engine_file)) {
            $datatype_name = "\phpws2\Database\Engine\\$engine\Datatype\\$class_name";
        } elseif (is_file($class_file)) {
            $datatype_name = "\phpws2\Database\Datatype\\$class_name";
        } else {
            throw new \Exception("Unknown class name: $class_name");
        }
        $object = new $datatype_name($table, $name);
        if ($object->default instanceof \phpws2\Variable) {
            $object->setDefault($value);
        }
        return $object;
    }

    /**
     * Returns NULL or NOT NULL based on is_null parameter
     * @return string
     */
    public function getIsNullString()
    {
        if ($this->is_null) {
            return 'null';
        } else {
            return 'not null';
        }
    }

    /**
     *
     * @return boolean
     */
    public function getIsNull()
    {
        return $this->is_null;
    }

    /**
     * Sets whether the database type is NULL (true) or NOT NULL (false)
     * @param boolean $null
     */
    public function setIsNull($null)
    {
        $this->is_null = (bool) $null;
        return $this;
    }

    public function setName($name)
    {
        $this->name = \phpws2\Variable::factory('alphanumeric', $name);
    }

    public function getName()
    {
        return \phpws2\Database\DB::delimit((string) $this->name);
    }

    /**
     * Returns the data type for an alter or create query. Note that
     * getIsNull must directly follow getDefault.
     * @return string
     */
    public function __toString()
    {
        $q[] = (string) $this->getName();
        $q[] = $this->getParameterString();
        return implode(' ', $q);
    }

    /**
     * Returns datatype's column parameters, i.e. the column type, size, default, etc.
     * @return string
     */
    public function getParameterString()
    {
        $datatype = $this->getDatatype();
        $q[] = $datatype;
        if (!is_null($this->size)) {
            $q[] = '(' . $this->getSize() . ')';
        }
        $q[] = $this->getExtraInfo();
        $q[] = $this->getDefaultString();
        // this MUST be next after getDefault
        $q[] = $this->getIsNullString();

        return implode(' ', $q);
    }

    public function getExtraInfo()
    {
        return null;
    }

    /**
     * Extended in varchar and char.
     * @return string The current data type.
     */
    public function getDatatype()
    {
        return strtolower($this->popClass());
    }

    /**
     * Returns default value as a string for the query. Note that
     * if the default is null and null is allowed, just "default" will be
     * returned. This works because getIsNull is called afterwards.
     *
     * @return string
     */
    public function getDefaultString()
    {
        /**
         * Text cannot have a default value. loadDefault should prevent data
         * types slipping through without setting this.
         */
        if (is_null($this->default)) {
            return null;
        }

        if ($this->default->isNull() && $this->is_null) {
            return 'default null';
        }
        return "default " . $this->table->db->quote($this->default);
    }

    public function getDefault()
    {
        return $this->default;
    }

    /**
     *
     * The default may be set to NULL (Text datatype does this) in case default
     * should not be listed
     * @param type $value
     */
    public function setDefault($value)
    {
        if (is_null($value)) {
            if ($this->default instanceof \phpws2\Variable) {
                $this->default->set(null);
            } else {
                $this->default = null;
            }
        } elseif ($this->default instanceof \phpws2\Variable) {
            $this->default->set($value);
        } else {
            $this->default = new \phpws2\Variable\StringVar((string) $value);
        }
        return $this;
    }

    /**
     * Removes a default status.
     */
    public function nullDefault()
    {
        $this->default = null;
    }

    /**
     * Adds the current datatype to the associated table.
     * @param string $after Name of column to place new column after. Null
     * puts new column at the end of the table. 'FIRST' makes it the first column.
     */
    public function add($after = null)
    {
        if (!empty($after)) {
            if ($after !== 'FIRST') {
                $field = new Field($this->table, $after);
                $after = 'AFTER ' . $field->getName();
            }
        }
        $query = 'ALTER TABLE ' . $this->table->getFullName() . ' ADD COLUMN ' .
                $this->__toString() . ' ' . $after;
        return $this->table->db->exec($query);
    }

    /**
     * Calls a CHANGE alteration for renaming the column.
     * THIS IS A MYSQL SPECIFIC COMMAND. Use the Table class alter method instead.
     *
     * @param string $new_name
     * @deprecated
     */
    public function change($new_name)
    {
        $old_name = $this->getName();
        $this->setName($new_name);
        $query = 'ALTER TABLE ' . $this->table->getFullName() . ' CHANGE ' .
                $old_name . ' ' . $this->__toString();
        $this->table->db->exec($query);
    }

    /**
     * Calls a MODIFY alteration based on the current datatype settings.
     * THIS IS A MYSQL SPECIFIC COMMAND. Use the Table class alter method instead.
     * @param string $after Name of column to place new column after. Null
     *  puts new column at the end of the table. 'FIRST' makes it the first column.
     * @deprecated
     */
    public function modify($after = null)
    {
        $query = 'ALTER TABLE ' . $this->table->getFullName() . ' MODIFY ' .
                $this->__toString() . ' ' . $after;
        return $this->table->db->exec($query);
    }

    public function getTable()
    {
        return $this->table;
    }

    /**
     * Receives an integer between 0 - 255 for the varchar length
     * @param integer $length
     */
    public function setSize($size)
    {
        $this->size = $size;
        return $this;
    }

    public function getSize()
    {
        return $this->size;
    }

    public function setIsPrimaryKey($key)
    {
        $this->is_primary_key = (bool)$key;
    }

    public function setIsUnique($key)
    {
        $this->is_unique = (bool)$key;
    }

    public function getIsPrimaryKey()
    {
        return $this->is_primary_key;
    }

    public function getIsUnique()
    {
        return $this->is_unique;
    }

}
