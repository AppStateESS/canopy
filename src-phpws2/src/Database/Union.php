<?php

namespace phpws2\Database;

/**
 *
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class Union {

    private $db_stack;

    public function __construct(array $db_array)
    {
        foreach ($db_array as $db) {
            if (!($db instanceof \phpws2\Database\DB)) {
                throw new \Exception('createUnion only accepts \phpws2\Database\DB object arrays');
            }
        }
        $this->db_stack = $db_array;
    }

    public function select()
    {
        foreach ($this->db_stack as $db) {
            $query[] = $db->selectQuery();
        }
        $f_query = '(' . implode(') UNION (', $query) . ')';

        $qdb = \phpws2\Database::newDB();
        $qdb->loadStatement($f_query);
        return $qdb->fetchAll();
    }

}

