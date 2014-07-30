<?php
/*
* This file is part of phpMorphy project
*
* Copyright (c) 2007-2012 Kamaev Vladimir <heromantor@users.sourceforge.net>
*
*     This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU Lesser General Public
* License as published by the Free Software Foundation; either
* version 2 of the License, or (at your option) any later version.
*
*     This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
* Lesser General Public License for more details.
*
*     You should have received a copy of the GNU Lesser General Public
* License along with this library; if not, write to the
* Free Software Foundation, Inc., 59 Temple Place - Suite 330,
* Boston, MA 02111-1307, USA.
*/

class phpMorphy_Generator_StorageHelper_Shm implements phpMorphy_Generator_StorageHelperInterface {
    /**
     * @return string
     */
    function getType() {
        return 'shm';
    }

    /**
     * @return string
     */
    function prolog() { return '$__shm = $this->resource[\'shm_id\']; $__offset = $this->resource[\'offset\']'; }

    /**
     * @return string
     */
    function seek($offset) { return ''; }

    /**
     * @return string
     */
    function read($offset, $len) { return "shmop_read(\$__shm, \$__offset + ($offset), $len)"; }
}