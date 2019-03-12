<?php

namespace phpws2\Database\Datatype;

/*
 * See docs/AUTHORS and docs/COPYRIGHT for relevant info.
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */

class Character extends \phpws2\Database\Datatype {

    /**
     * If true, char is actually a varchar!
     * @var boolean
     */
    protected $varchar = false;

    /**
     * Creates a database varchar
     * @param string $name
     * @param integer $length
     */
    public function __construct(\phpws2\Database\Table $table, $name, $length=255)
    {
        parent::__construct($table, $name);
        $this->size = new \phpws2\Variable\IntegerVar(null, $this->varName());
        $this->size->setRange(0, 255);
        $this->setSize($length);
        $this->default = new \phpws2\Variable\StringVar(null, $this->varName());
        $this->default->setLimit($length);
        $this->default->allowNull(true);
    }

    /**
     * Loads an string variable into the default parameter.
     */
    protected function loadDefault()
    {
        $this->default = new \phpws2\Variable\StringVar(null, 'default');
    }
    
    public function setSize($size) {
        $this->size->set($size);
    }

    private function varName()
    {
        return $this->varchar ? 'VARCHAR' : 'CHAR';
    }
}
