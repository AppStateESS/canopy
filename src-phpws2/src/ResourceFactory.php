<?php

namespace phpws2;

/**
 * Description of ResourceFactory
 *
 * @author matt
 */
class ResourceFactory
{

    /**
     * Loads a Resource from the database according to table_name.
     * If table_name is not entered, Resource is checked for a table name
     * If resource is not found in table, the resource will just be as it was passed.
     * @param \phpws2\Resource $resource
     * @param integer $id Id of resource in table
     * @param string $table_name
     * @throws \Exception
     * @return boolean True if found, false if not.
     */
    public static function loadByID(Resource $resource, $id = null, $table_name = null)
    {
        if (empty($table_name)) {
            $table_name = $resource->getTable();
        }

        if (empty($id)) {
            $id = self::pullId($resource);
        }

        $db = \phpws2\Database::newDB();
        $table = $db->addTable($table_name);
        $table->addFieldConditional('id', (int) $id);
        $result = $db->selectOneRow();
        if (!empty($result)) {
            $resource->setVars($result);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Saves a resource in the database.
     *
     * @param \phpws2\Resource $resource
     * @param string $table_name
     * @return Resource
     */
    public static function saveResource(Resource $resource, $table_name = null)
    {
        if (empty($table_name)) {
            $table_name = $resource->getTable();
        }

        $id = $resource->getId();
        $db = \phpws2\Database::newDB();
        $tbl = $db->addTable($table_name);
        $vars = $resource->getSaveVars();

        // Need to unset the id or the primary key will not increment
        unset($vars['id']);
        foreach ($vars as $name => $value) {
            if (is_a($value, '\phpws2\Variable')) {
                if ($value->getIsTableColumn()) {
                    $tbl->addValue($name, $value);
                }
            } else {
                if ($tbl->columnExists($name)) {
                    $tbl->addValue($name, $value);
                }
            }
        }
        if (empty($id)) {
            $tbl->insert();
            $last_id = (int) $tbl->getLastId();
            $resource->setId($last_id);
        } else {
            $db->addConditional($tbl->getFieldConditional('id', $id));
            $db->update();
        }
        return $resource;
    }

    /**
     * Attempts to extract the required id from a resource, throwing an exception if id
     * is null.
     * @param \phpws2\Resource $resource
     * @return integer
     * @throws \Exception
     */
    private static function pullId(Resource $resource)
    {
        $id = $resource->getId();
        if (empty($id)) {
            throw new \Exception(sprintf('Id not set in Resource "%s"',
                        get_class($resource)));
        }
        return $id;
    }

    /**
     * Removes a resource from its table.
     * @param \phpws2\Resource $resource
     * @param string $table_name Not required if in Resource->table
     * @return integer Number of rows deleted.
     */
    public static function deleteResource(Resource $resource, $table_name = null)
    {
        if (empty($table_name)) {
            $table_name = $resource->getTable();
        }
        $db = \phpws2\Database::newDB();
        $tbl = $db->addTable($table_name);
        $db->addConditional($tbl->getFieldConditional('id',
                self::pullId($resource)));
        return $db->delete();
    }

    public static function getAsJSON(Resource $resource)
    {
        $vars = $resource->getStringVars();
        return json_encode($vars);
    }

    /**
     * Receives an array of variables, creates a resource, changes variables to toString
     * results, and returns an array of those results.
     *
     * @param array $resource_array
     * @param string $class_name : Class used to instantiate objects
     * @return array
     */
    public static function makeResourceStringArray(array $resource_array, $class_name, $hide = null)
    {
        $object_stack = array();
        foreach ($resource_array as $row) {
            $obj = new $class_name;
            $obj->setVars($row);
            $object_stack[] = $obj->getStringVars(false, $hide);
        }
        return $object_stack;
    }

}
