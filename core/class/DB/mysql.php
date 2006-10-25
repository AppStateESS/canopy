<?php

/**
 * Mysql specific library
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @version $Id$
 */

define ('DB_USE_AFTER', TRUE);

class mysql_PHPWS_SQL {

    function export(&$info){
        switch ($info['type']){
        case 'int':
            if (!isset($info['len']) || $info['len'] > 6)
                $setting = 'INT';
            else
                $setting = 'SMALLINT';
            break;
    
        case 'blob':
            $setting = 'TEXT';
            $info['flags'] = NULL;
            break;
    
        case 'string':
            $setting = 'CHAR(' . $info['len'] . ')';
            break;
    
        case 'date':
            $setting = 'DATE';
            break;
    
        case 'real':
            $setting = 'FLOAT';
            break;
    
        case 'timestamp':
            $setting = 'TIMESTAMP';
            $info['flags'] = NULL;
            break;

        }

        return $setting;
    }


    function renameColumn($table, $column_name, $new_name, $specs)
    {
        $table = PHPWS_DB::addPrefix($table);
        $sql = sprintf('ALTER TABLE %s CHANGE %s %s %s',
                       $table, $column_name, $new_name, $specs['parameters']);
        return $sql;
    }

    function getLimit($limit){
        $sql[] = 'LIMIT ' . $limit['total'];
    
        if (isset($limit['offset'])) {
            $sql[] = ', ' . $limit['offset'];
        }

        return implode(' ', $sql);
    }

    function readyImport(&$query){
        return;
    }

    function randomOrder()
    {
        return 'rand()';
    }

    function dropSequence($table)
    {
        $table = PHPWS_DB::addPrefix($table);
        $result = $GLOBALS['PHPWS_DB']['connection']->query("DROP TABLE $table");
        if (PEAR::isError($result)) {
            return $result;
        }

        return TRUE;
    }


    function dropTableIndex($name, $table)
    {
        $table = PHPWS_DB::addPrefix($table);
        return sprintf('DROP INDEX %s ON %s', $name, $table);
    }
}

?>
