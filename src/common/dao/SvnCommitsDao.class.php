<?php
/*
 * Copyright (c) Xerox, 2009. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2009. Xerox Codendi Team.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once('include/DataAccessObject.class.php');

class SvnCommitsDao extends DataAccessObject {
    
    public function __construct($da) {
        parent::__construct($da);
        $this->table_name = 'svn_commits';
    }
    
    public function statsByGroupId($group_id, $duration) {
        $group_id = $this->da->escapeInt($group_id);
        $duration = $this->da->escapeInt($duration);
        $sql = "SELECT whoid,
                        TO_DAYS(FROM_UNIXTIME(date)) - TO_DAYS(FROM_UNIXTIME(0)) as day,
                        count(id) AS nb_commits
                FROM $this->table_name
                WHERE DATEDIFF(NOW(), FROM_UNIXTIME(date)) < $duration
                  AND group_id = $group_id
                GROUP BY whoid, day
                ORDER BY whoid, day";
        return $this->retrieve($sql);
    }
}


?>