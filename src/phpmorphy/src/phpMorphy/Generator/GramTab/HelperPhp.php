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

class phpMorphy_Generator_GramTab_HelperPhp {
    /**
     * @param phpMorphy_Dict_GramTab_ConstStorage $constsStorage
     * @param string $const
     * @return string
     */
    function grammemConstName(phpMorphy_Dict_GramTab_ConstStorage $constsStorage, $const) {
        return $this->constName($constsStorage, $const, 'G');
    }

    /**
     * @param phpMorphy_Dict_GramTab_ConstStorage $constsStorage
     * @param string $const
     * @return string
     */
    function posConstName(phpMorphy_Dict_GramTab_ConstStorage $constsStorage, $const) {
        return $this->constName($constsStorage, $const, 'P');
    }

    /**
     * @param phpMorphy_Dict_GramTab_ConstStorage $constsStorage
     * @param string[] $values
     * @return string
     */
    function metaGrammemConstValue(phpMorphy_Dict_GramTab_ConstStorage $constsStorage, $values) {
        foreach($values as &$value) {
            $value = $this->constName($constsStorage, $value, 'G');
        }

        return 'array(' . implode(', ', $values) . ')';
    }

    /**
     * @param phpMorphy_Dict_GramTab_ConstStorage $constsStorage
     * @param string $const
     * @param string $prefix
     * @return string
     */
    private function constName(phpMorphy_Dict_GramTab_ConstStorage $constsStorage, $const, $prefix) {
        return 'PMY_' . $constsStorage->getLanguageShort() . "{$prefix}_" . $const;
    }
}