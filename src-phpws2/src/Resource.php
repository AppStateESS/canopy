<?php

namespace phpws2;

/**
 *  Abstract class forming the basis of content objects
 * @todo See Database/Object
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 * @package phpws2
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
abstract class Resource extends \Canopy\Data
{

    /**
     * Primary key of Resource
     * @var integer
     */
    protected $id;

    /**
     * Name of table associated with this resource
     * @var string
     */
    protected $table;

    /**
     *
     * @var array
     */
    protected $no_save;

    /**
     * Plugs in default Variable objects
     */
    public function __construct()
    {
        $this->id = new \phpws2\Variable\IntegerVar(0, 'id');
        $this->id->setInputType('hidden');
        $this->no_save = array();
        $this->addHiddenVariable('table');
        $this->addHiddenVariable('no_save');
    }

    /**
     * Returns name of table set to Resource
     * @return string
     * @throws \Exception Table variable was null
     */
    public function getTable()
    {
        if (empty($this->table)) {
            throw new \Exception(sprintf('Table not set in Resource object "%s"',
                    get_class($this)));
        }
        return $this->table;
    }

    public function post(\Canopy\Request $request)
    {
        $post_vars = $request->getRequestVars();
        $this->setVars($post_vars);
    }

    public function doNotSave($var)
    {
        if (is_array($var)) {
            $this->no_save = array_merge($this->no_save, $var);
        } else {
            $this->no_save[] = $var;
        }
    }

    public function getSaveVars($return_null = false)
    {
        return parent::getVars($return_null, $this->no_save);
    }

    /**
     * Forces a POST loadRequestByType
     */
    public function loadPostByType(\Canopy\Request $request,
            array $ignore = null, array $null_on_failure = null)
    {
        $this->loadRequestByType($request, $ignore, $null_on_failure, 'post');
    }

    /**
     * Forces a PATCH loadRequestByType
     */
    public function loadPatchByType(\Canopy\Request $request,
            array $ignore = null, array $null_on_failure = null)
    {
        $this->loadRequestByType($request, $ignore, $null_on_failure, 'patch');
    }

    /**
     * Forces a PUT loadRequestByType
     */
    public function loadPutByType(\Canopy\Request $request,
            array $ignore = null, $null_on_failure = null)
    {
        $this->loadRequestByType($request, $ignore, $null_on_failure, 'put');
    }

    /**
     * Tries to load the current resource with the variables from a request. Note
     * that if a variable in the request is present but not in the Resource, an error
     * will be thrown. To avoid this, added the variable to the ignore array.
     * The ignore array can also be used if you do not want something from the
     * post to be saved.
     * If a variable in the Resource is ignored, the current resource value
     * stays.
     * @param \Canopy\Request $request
     * @param array $ignore Array of variables to ignore
     * @param array $null_on_failure Missing values set these variables to null
     * @param string force_type Force a type of request method (e.g. post, put, etc.)
     * @throws \Exception
     * @throws \phpws2\Exception\WrongType
     */
    private function loadRequestByType(\Canopy\Request $request,
            array $ignore = null, array $null_on_failure = null,
            $force_type = null)
    {
        if ($null_on_failure === null) {
            $null_on_failure = array();
        }
        $variable_names = $this->getVariableNames();
        if (empty($variable_names)) {
            throw new \Exception('Resource missing variables');
        }
        unset($variable_names[array_search('table', $variable_names)]);
        unset($variable_names[array_search('no_save', $variable_names)]);
        unset($variable_names[array_search('parent', $variable_names)]);

        if (!empty($ignore) && is_array($ignore)) {
            foreach ($ignore as $ignore_name) {
                unset($variable_names[array_search($ignore_name, $variable_names)]);
            }
        }

        if ($force_type !== null) {
            $type = ucwords(strtolower($force_type));
        } else {
            $type = ucwords(strtolower($request->getMethod()));
        }
        $stringPull = "pull{$type}String";
        $arrayPull = "pull{$type}Array";
        $boolPull = "pull{$type}Boolean";
        $integerPull = "pull{$type}Integer";
        $floatPull = "pull{$type}Float";

        if (!method_exists($request, $stringPull)) {
            throw new \Exception('Unknown type request method: ' . $type);
        }

        foreach ($variable_names as $name) {
            $var = $this->$name;
            switch (1) {
                case is_subclass_of($var, '\phpws2\Variable\StringVar') || is_a($var,
                        '\phpws2\Variable\StringVar'):
                    $result = $request->$stringPull($name, true);
                    $success = $result !== false;
                    break;

                case is_subclass_of($var, '\phpws2\Variable\ArrayVar') || is_a($var,
                        '\phpws2\Variable\ArrayVar'):
                    $result = $request->$arrayPull($name, true);
                    if (is_string($result)) {
                        $result = $request->filterString($result);
                    }
                    $success = $result !== false;

                    break;

                case is_subclass_of($var, '\phpws2\Variable\BooleanVar') || is_a($var,
                        '\phpws2\Variable\BooleanVar'):
                    $result = $request->$boolPull($name, true);
                    $success = $result !== null;
                    break;

                case is_subclass_of($var, '\phpws2\Variable\IntegerVar') || is_a($var,
                        '\phpws2\Variable\IntegerVar'):
                    $result = $request->$integerPull($name, true);
                    $success = $result !== false;
                    break;

                case is_subclass_of($var, '\phpws2\Variable\FloatVar') || is_a($var,
                        '\phpws2\Variable\FloatVar'):
                    $result = $request->$floatPull($name, true);
                    $success = $result !== false;
                    break;

                default:
                    throw new \Exception('Unknown Variable type');
            }

            if ($success) {
                $var->set($result);
            } elseif (in_array($name, $null_on_failure)) {
                $var->set(null);
            } else {
                throw new \phpws2\Exception\WrongType($name, $var);
            }
        }
    }

    public function setId($id)
    {
        $this->id->set($id);
    }

    public function getId()
    {
        return $this->id->get();
    }

    public function getVariableNames()
    {
        return array_keys(get_object_vars($this));
    }

    /**
     * Returns true if this resource has been saved (i.e. has a positive id)
     * and false otherwise.
     * @return boolean
     */
    public function isSaved()
    {
        return !$this->id->isEmpty();
    }

    /*
     * @todo reapply when user permissions rewritten
      public function permitUser($permission_name, \User\User $user = null)
      {
      if (is_null($user)) {
      $user = \User\Current::get();
      }

      return \User\Permission::permit($permission_name, $this, $user);
      }
     */

    /*
     * @todo reapply when user permissions rewritten
      public function permitRole($permission_name, \User\Role $role)
      {
      return \User\Permission::permit($permission_name, $this, $role);
      }
     */

    /**
     * Saves the current resource object using the ResourceFactory class.
     * @return object
     */
    public function save()
    {
        return ResourceFactory::saveResource($this);
    }

    /**
     * Returns an associative array of Datatypes based on the Variable parameter
     * objects in the current object
     * @param \phpws2\Database\Table $table
     * @return Array
     */
    public function getVariablesAsDatatypes(\phpws2\Database\Table $table)
    {
        $vars = $this->getSaveVars();
        foreach ($vars as $variable) {
            if ($variable instanceof \phpws2\Variable) {
                if ($variable->getIsTableColumn()) {
                    $dts[$variable->getVarname()] = $variable->loadDatatype($table);
                }
            }
        }
        return empty($dts) ? null : $dts;
    }

    /**
     * Creates a new table based on the resource object. Returns table object
     * if successful
     * @param \phpws2\Database\DB $db
     * @return \phpws2\Database\Table
     */
    public function createTable(\phpws2\Database\DB $db)
    {
        $resource_table = $db->buildTable($this->getTable());
        $datatypes = $this->getVariablesAsDatatypes($resource_table);
        if (!$datatypes) {
            throw new \Exception('Resource did not return any datatypes');
        }
        $resource_table->addPrimaryIndexId();
        $resource_table->create();
        return $resource_table;
    }

    /**
     * Returns the values of the Variables as part of a resource.
     * @param boolean $return_null If true, return variables with NULL values
     * @param string|array Variables to ignore/not return
     * @param boolean $null_as_empty_string If true, Variable with a NULL value
     *   return an empty string
     * @return array
     */
    public function getVariablesAsValue($return_null = null, $hide = null,
            $null_as_empty_string = false)
    {
        $vars = $this->getVars($return_null, $hide);
        foreach ($vars as $v) {
            $set_val = $v->get();
            if ($null_as_empty_string && is_null($set_val)) {
                $set_val = '';
            }
            $values[$v->getVarname()] = $set_val;
        }
        return $values;
    }

}
