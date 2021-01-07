<?php

/*
 * Copyright (C) 2016 Matthew McNaney <mcnaneym@appstate.edu>.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301  USA
 */

/**
 * Load Composer's autoloader, so we can autoload Composer-installed dependencies
 * from the /vendor directory
 * 
 * @author Matthew McNaney <mcnaneym at appstate dot edu>
 */
$composerAutoload = PHPWS_SOURCE_DIR . 'vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
} else {
    echo '<p>You need to run <code style="background-color : lightgreen">composer install</code> in the canopy directory.</p>';
    exit;
}
