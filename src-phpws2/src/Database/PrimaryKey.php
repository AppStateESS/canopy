<?php

namespace phpws2\Database;

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
 *
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */

class PrimaryKey extends Constraint implements TableCreateConstraint {

    public function __construct($columns, $name = null)
    {
        if (empty($name)) {
            $name = 'key_' . uniqid();
        }
        parent::__construct($columns, $name);
    }


    public function getConstraintType()
    {
        return 'PRIMARY KEY';
    }

}

