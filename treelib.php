<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Project : Technical Project Manager (IEEE like)
 *
 * A special lib for handling tree-shaped entities.
 *
 * @package mod-techproject
 * @category mod
 * @author So Gerard (EISTI 2002)
 * @date 2008/03/03
 * @version phase 1
 * @contributors Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

/// Library of tree dedicated operations

/**
 * deletes into tree a full branch. note that it will work either
 * @param int $id the root node id
 * @param string $table the table where the tree is in 
 * @param boolean $istree if istree is not set, considers table as a simple ordered list
 * @return an array of deleted ids
 */
function techproject_tree_delete($id, $table, $istree = 1) {
    global $DB;

    $fatherid = $DB->get_field($table, 'fatherid', array('id' => $id));
    $deleted = tree_delete_rec($id, $table, $istree);
    techproject_tree_updateordering($fatherid, $table, $istree);
    return $deleted;
}

/**
 * deletes recursively a node and its subnodes. this is the recursion deletion
 * @return an array of deleted ids
 */
function tree_delete_rec($id, $table, $istree) {
    global $CFG, $DB;

    $deleted = array();
    if (empty($id)) {
        return $deleted;
    }

    // echo "deleting $id<br/>";
    // getting all subnodes to delete if is tree.
    if ($istree) {
        $sql = "
            SELECT 
                id,id
            FROM 
                {{$table}}
            WHERE
                fatherid = {$id}
        ";
        // deleting subnodes if any
        if ($subs = $DB->get_records_sql($sql)) {
            foreach ($subs as $aSub) {
                $deleted = array_merge($deleted, tree_delete_rec($aSub->id, $table, $istree));
            }
        }
    }

    // Deleting current node.
    $DB->delete_records($table, array('id' => $id));
    $deleted[] = $id;
    return $deleted;
}

/**
 * copies recursively a branch in a table
 *
 */
function tree_copy_rec($table, $src, $into, $srcisroot = false) {
    global $DB;

    if (!$srcisroot) {
        $srcrec = $DB->get_record("techproject_$table", array('id' => $src));
        unset($srcrec->id);
        $srcrec->fatherid = $into;
        $dstordering = 0 + $DB->get_field("techproject_$table", 'MAX(ordering)', array('fatherid' => $into));
        $srcrec->ordering = $dstordering + 1;
        $copiedid = $DB->insert_record("techproject_$table", $srcrec);
    } else {
        // fake copied to copy into $into
        $copiedid = $into;
    }
    // get childs and recurse
    if ($childs = $DB->get_records("techproject_$table", array('fatherid' => $src))) {
        foreach ($childs as $ch) {
            tree_copy_rec($table, $ch->id, $copiedid);
        }
    }
}

/**
 * reorders a level ordering properly starting from 1
 * @param parentid the node from where to reorder
 * @param table the table-tree
 */
function techproject_tree_reorderlevel($parentid, $table, $projectid = 0, $groupid = 0) {
    global $DB, $OUTPUT;

    if ($parentid == 0 && $projectid == 0) {
        echo $OUTPUT->notification("Bad reordering condition in treelib");
        return;
    }

    if ($parentid != 0) {
        $childs = $DB->get_records($table, array('fatherid' => $parentid), 'ordering', 'id,ordering');
    } else {
        $childs = $DB->get_records_select($table, " fatherid = ? AND groupid = ? AND projectid = ? ", array($parentid, $groupid, $projectid), 'ordering', 'id, ordering');
    }
    if ($childs) {
        $i = 1;
        foreach ($childs as $child) {
            $child->ordering = $i;
            $DB->update_record($table, $child);
            $i++;
        }
    }
}

/**
 * updates ordering of a tree branch from a specific node, reordering 
 * all children. 
 * @param int $id the parent node from where to reorder
 * @param string $table the table-tree
 */
function techproject_tree_updateordering($fatherid, $table, $istree) {
    global $CFG, $DB;

    // Getting ordering value of the current node.

    $res =  $DB->get_record($table, array('id' => $fatherid));
    if (!$res) {
        return;
    }

    // Getting subsequent nodes that have this father.
    $sql = "
        SELECT 
            id,id
        FROM 
            {{$table}}
        WHERE 
            fatherid = ?
        ORDER BY 
            ordering
    ";

    // reordering subsequent nodes using an object
    $ordering = 1;
    if ($nextsubs = $DB->get_records_sql($sql, array($fatherid))) {
        foreach ($nextsubs as $asub) {
            $object = new StdClass();
            $object->id = $asub->id;
            $object->ordering = $ordering;
            $DB->update_record($table, $object);
            $ordering++;
        }
    }
}

/**
 * updates ordering of a full subtree from a specific node. this can be heavy
 * @param int $id the parent node under where to reorder
 * @param string $table the table-tree
 */
function techproject_subtree_updateordering($fatherid, $table, $istree) {
    global $CFG, $DB;

    // Getting ordering value of the current node.

    $res =  $DB->get_record($table, array('id' => $id));
    if (!$res) {
        return;
    }

    // Getting subsequent nodes that have this father.
    $sql = "
        SELECT 
            id,id
        FROM 
            {{$table}}
        WHERE 
            fatherid = ?
        ORDER BY 
            ordering
    ";

    // reordering subsequent nodes using an object
    $ordering = 1;
    if ($nextsubs = $DB->get_records_sql($sql, array($id))) {
        foreach ($nextsubs as $asub) {
            $object = new StdClass();
            $object->id = $asub->id;
            $object->ordering = $ordering;
            $DB->update_record($table, $object);
            $ordering++;
            techproject_subtree_updateordering($asubid, $table, $istree);
        }
    }
}

/**
 * raises a node in the tree, reordering all what needed
 * @param int $id the id of the raised node
 * @param string $table the table-tree where to operate
 * @param boolean $istree true if is a table-tree rather than a table-list
 * @return void
 */
function techproject_tree_up($project, $group, $id, $table, $istree = 1) {
    global $CFG, $DB;

    $res =  $DB->get_record($table, array('id' => $id));
    if (!$res) {
        return;
    }

    $treeclause = ($istree) ? " AND fatherid = {$res->fatherid} " : '';

    if ($res->ordering > 1) {
        $result = false;
        $newordering = $res->ordering - 1;
        if ($resid = $DB->get_field_select($table, 'id', " groupid = ? AND projectid = ? AND ordering = ? $treeclause ORDER BY ordering", array($group, $project->id, $newordering))){
            // Swapping.
            $object = new StdClass();
            $object->id = $resid;
            $object->ordering = $res->ordering;
            $DB->update_record($table, $object);
        }

        $object = new StdClass();
        $object->id = $id;
        $object->ordering = $newordering;
        $DB->update_record($table, $object);
    }
    if ($istree) {
        techproject_tree_reorderlevel($res->fatherid, $table, $project->id, $group);
    }
}

/**
 * lowers a node on its branch. this is done by swapping ordering.
 * @param object $project the current project
 * @param int $group the current group
 * @param int $id the node id
 * @param string $table the table-tree where to perform swap
 * @param boolean $istree if not set, performs swapping on a single list
 */
function techproject_tree_down(&$project, $group, $id, $table, $istree = 1) {
    global $DB;

    $res =  $DB->get_record($table, array('id' => $id));
    $treeclause = ($istree) ? " AND fatherid = {$res->fatherid} " : '';
    $maxordering = $DB->get_field_select($table, " MAX(ordering) ", " projectid = ? AND groupid = ? $treeclause GROUP BY projectid ", array($project->id, $group));

    if ($res->ordering < $maxordering) {
        $newordering = $res->ordering + 1;
        if ($resid =  $DB->get_field_select($table, 'id', " projectid = ? AND groupid = ? AND ordering = ? $treeclause", array($project->id, $group, $newordering))) {
            // Swapping.
            $object = new StdClass();
            $object->id = $resid;
            $object->ordering = $res->ordering;
            $DB->update_record("$table", $object);
        }

        $object = new StdClass;
        $object->id = $id;
        $object->ordering = $newordering;
        $DB->update_record("$table", $object);
    }

    if ($istree) {
        techproject_tree_reorderlevel($res->fatherid, $table, $project->id, $group);
    }
}

/**
 * raises a node to the upper level. Subsequent nodes become sons of the raised node
 * @param object $project the current project
 * @param int $group the current group
 * @param int $id the node to be raised
 * @param string $table the table-tree name
 */
function techproject_tree_left(&$project, $group, $id, $table) {
    global $DB;

    $sql = "
        SELECT 
            fatherid, 
            ordering
        FROM 
            {{$table}}
        WHERE 
            id = ?
    ";
    $res =  $DB->get_record_sql($sql, array($id));
    $ordering = $res->ordering;
    $fatherid = $res->fatherid;

    $sql = "
        SELECT 
            id,
            fatherid
        FROM 
            {{$table}}
        WHERE 
            id = ?
    ";
    $resfatherid =  $DB->get_record_sql($sql, array($fatherid));
    $fatheridbis = $resfatherid->fatherid; //id grand pere

    $sql = "
        SELECT 
            id,
            ordering
        FROM 
            {{$table}}
        WHERE 
            projectid = ? AND
            groupid = ? AND
            ordering > ? AND 
            fatherid = ?
        ORDER BY 
            ordering
    ";
    $newbrotherordering = $ordering;

    if ($ress = $DB->get_records_sql($sql, array($project->id, $group, $ordering, $fatherid))) {
        foreach ($ress as $res) {
            $object = new StdClass();
            $object->id = $res->id;
            $object->ordering = $newbrotherordering;
            $DB->update_record("$table", $object);
            $newbrotherordering = $newbrotherordering + 1;
        }
    }

    // Getting father's ordering.
    $sql = "
        SELECT
            id,
            ordering
        FROM 
            {{$table}}
        WHERE 
            projectid = ? AND
            groupid = ? AND
            id = ?
    ";
    $resorderingfather =  $DB->get_record_sql($sql, array($project->id, $group, $fatherid));
    $orderingfather = $resorderingfather->ordering;

    //On decale la orderingition des freres du pere qui le suit
    $sql = "
        SELECT 
            id,
            ordering
        FROM 
            {{$table}}
        WHERE 
            projectid = ? AND
            groupid = ? AND
            ordering > ? AND
            fatherid = ?
        ORDER BY 
            ordering
    ";
    if ($resbrotherfathers = $DB->get_records_sql($sql, array($project->id, $group, $orderingfather, $fatheridbis))) {
        foreach($resbrotherfathers as $resbrotherfather){
            $idbrotherfather = $resbrotherfather->id;
            $nextordering = $resbrotherfather->ordering + 1;
            $object = new StdClass();
            $object->id = $idbrotherfather;
            $object->ordering = $nextordering;
            $DB->update_record("$table", $object);
        }
    }

    // Save new ordering.
    $newordering = $orderingfather + 1;

    $object = new StdClass();
    $object->id = $id;
    $object->ordering = $newordering;
    $object->fatherid = $fatheridbis;
    $DB->update_record("$table", $object);
}

/**
 * lowers a node within its own branch setting it as 
 * sub node of the previous sibling. The first son cannot be lowered.
 * @param object $project the current project
 * @param int $group the current group
 * @param int $id the node to be lowered
 * @param string $table the table-tree name
 */
function techproject_tree_right(&$project, $group, $id, $table){
    global $DB;

    $sql = "
        SELECT 
            fatherid, 
            ordering,
            projectid,
            groupid
        FROM 
            {{$table}}
        WHERE 
            id = ?
    ";
    $res =  $DB->get_record_sql($sql, array($id));
    $fatherid = $res->fatherid;
    $group = $res->groupid;

    // ensure level is correctly ordered
    techproject_tree_reorderlevel($fatherid, $table, $project->id, $group);
    // get the acualized ordering
    $ordering = $DB->get_field($table, 'ordering', array('id' => $id));

    if( 1 < $ordering ){
        $orderingbis = $ordering - 1;

        $sql = "
            SELECT
                id,ordering
            FROM
                {{$table}}
            WHERE
                projectid = ? AND
                groupid = ? AND
                ordering = ? AND
                fatherid = ?
        ";
        $resid = $DB->get_record_sql($sql, array($project->id, $group, $orderingbis, $fatherid));
        $newfatherid = $resid->id;

        $sql = "
            SELECT 
                id, 
                ordering
            FROM 
                {{$table}}
            WHERE 
                projectid = ? AND
                groupid = ? AND
                ordering > ? AND
                fatherid = ?
            ORDER BY
                ordering
        ";
        $newbrotherordering = $ordering;

        if ($resbrothers = $DB->get_records_sql($sql, array($project->id, $group, $ordering, $fatherid))) {
            foreach ($resbrothers as $resbrother) {
                $object = new StdClass();
                $object->id = $resbrother->id;
                $object->ordering = $newbrotherordering;
                $DB->update_record("$table", $object);
                $newbrotherordering = $newbrotherordering + 1;
            }
        }

        $maxordering = techproject_tree_get_max_ordering($project->id, $group, $table, true, $newfatherid);
        $newordering = $maxordering + 1;

        //assigning father's id
        $object = new StdClass();
        $object->id = $id;
        $object->fatherid = $newfatherid;
        $object->ordering = $newordering;
        $DB->update_record("$table", $object);
    }
}

/**
 * gets a full project tree for selection
 * @param table the table-tree name
 * @param projectid the current project module
 * @param groupid the currently working group
 * @param fatherid the father node in the tree
 * @param ordering the ordering prefix for accumulating full ordering string.
 * @return an ordered array of elements
 */
function techproject_get_tree_options($table, $projectid, $groupid, $fatherid = 0, $ordering = ''){
    global $DB;


    $sql = "
       SELECT
          id,
          ordering,
          abstract
       FROM
          {{$table}}
       WHERE
          projectid = ? AND
          groupid = ? AND
          fatherid = ?
       ORDER BY
          ordering
    ";
    // echo $sql;

    $collected = array();
    if ($elements = $DB->get_records_sql($sql, array($projectid, $groupid, $fatherid))) {
        foreach ($elements as $anElement) {
            $anElement->ordering = (empty($ordering)) ? $anElement->ordering : $ordering . '.' . $anElement->ordering;
            $collected[] = $anElement;
            $collected = array_merge($collected, techproject_get_tree_options($table, $projectid, $groupid, $anElement->id, $anElement->ordering));
        }
    }
    return $collected;
}

/**
 * get the full list of dependencies in a tree
 * @param table the table-tree
 * @param id the node from where to start of
 * @return a comma separated list of nodes
 */
function techproject_get_subtree_list($table, $id) {
    global $DB;

    $res = $DB->get_records_menu($table, array('fatherid' => $id));
    $ids = array();
    if (is_array($res)) {
        foreach (array_keys($res) as $aSub) {
            $ids[] = $aSub;
            $subs = techproject_get_subtree_list($table, $aSub);
            if (!empty($subs)) $ids[] = $subs;
        }
    }
    return(implode(',', $ids));
}

/**
 * count direct subs in a tree
 * @param table the table-tree
 * @param the node
 * @return the number of direct subs
 */
function techproject_count_subs($table, $id) {
    global $DB;

    // Counting direct subs.
    $sql = "
        SELECT 
            COUNT(id) AS nbsub
        FROM 
            {{$table}}
        WHERE 
            fatherid = ?
    ";
    $res = $DB->get_record_sql($sql, array($id));
    return $res->nbsub;
}

/**
 * count all items that are leaves in tree (effective entries)
 * @param table the table-tree
 * @param the node
 * @param returnList if true, returns a list of leave's Ids, if false, returns the leaf count
 * @return the number of leaf subs, or a list of leaves
 */
function techproject_count_leaves($table, $id, $returnList=false) {
    global $DB;

    if (techproject_count_subs($table, $id) == 0) {
        if ($id == 0) ($returnList) ? array() : 0;
        return ($returnList) ? array($id) : 1;
    }
    $leaves = 0;
    $leafIds = array();

    // Counting for direct subs.
    $sql = "
        SELECT
            id,
            abstract
        FROM
            {{$table}}
        WHERE
            fatherid = ?
    ";
    $ress = $DB->get_records_sql($sql, array($id));
    if ($ress) {
        foreach ($ress as $res) {
            if ($returnList) {
                $leafIds = array_merge($leafIds, techproject_count_leaves($table, $res->id, true));
            } else {
                $leaves += techproject_count_leaves($table, $res->id, false);
            }
        }
    }
    return ($returnList) ? $leafIds : $leaves ;
}

/**
 * propagates a calculation in the tree up to a root node. defaults to mathematic meaning
 * @param table the table-tree
 * @param field the field that has concerns in the calculation
 * @param id the id from where to propagate
 * @param function the calculation
 * @param byFather if true, the id given is the father node's id that from where the propagation is required. usefull
 * when propagating after a record is deleted
 */
function techproject_tree_propagate_up($table, $field, $id, $function = '~', $byfather = false) {
    global $DB;

    if (!$byfather) {
        if ($anode = $DB->get_record($table, array('id' => $id))) {
            $fatherid = $anode->fatherid;
        } else {
            $fatherid = 0;
        }
    } else {
       $fatherid = $id;
    }
    if ($fatherid) {
        // Get all brothers in this tree branch (including me).
        if ($res = $DB->get_records_menu($table, array('fatherid' => $fatherid), 'id', "id,$field")) {
            // Calculate mathematic meaning.
            switch ($function) {
                case '~': {
                    $fieldValue = round(array_sum(array_values($res)) / count(array_keys($res)));
                } break;
                case '+':{
                    $fieldValue = round(array_sum(array_values($res)));
                } break;
            }

            // Make a "father object".
            $object = new StdClass();
            $object->id = $fatherid;
            $object->{$field} = $fieldValue;
            $DB->update_record($table, $object);
        }

        // Continue propagation.
        techproject_tree_propagate_up($table, $field, $fatherid, $function);
    }
}

/**
 * propagates a calculation in the tree up to a root node. defaults to mathematic meaning
 * @param table the table-tree
 * @param field the field that has concerns in the calculation
 * @param id the id from where to propagate
 * @param function the calculation
 * @param byFather if true, the id given is the father node's id that from where the propagation is required. usefull
 * when propagating after a record is deleted
 */
function techproject_tree_propagate_down(&$project, $table, $field, $fatherid = 0, $function = '~') {
    global $DB;

    if ($res = $DB->get_records_select_menu($table, " fatherid = ? AND projectid = ? ", array($fatherid, $project->id), 'id', "id, $field")) {
        foreach (array_keys($res) as $resid) {
            techproject_tree_propagate_down($project, $table, $field, $resid, $function);
        }

        // Calculate mathematic meaning.
        if ($fatherid != 0) {
            $res = $DB->get_records_select_menu($table, " fatherid = ? AND projectid = ? ", array($fatherid, $project->id), 'id', "id, $field");
            switch ($function) {
                case '~': {
                    $fieldValue = round(array_sum(array_values($res)) / count(array_keys($res)));
                }
                break;
                case '+':{
                    $fieldValue = round(array_sum(array_values($res)));
                }
                break;
            }
            $object = new StdClass();
            $object->id = $fatherid;
            $object->$field = $fieldValue;
            $DB->update_record($table, $object);
        }
    }
}

/**
 * get upper branch to a node from root to node
 * @param the table-tree where to oper
 * @param id the node id to reach
 * @param includeStart true if leaf node is in the list
 * @return array of node ids
 */
function techproject_tree_get_upper_branch($table, $id, $includeStart = false, $returnordering = false, $reverse = true) {
    global $DB;

    $nodelist = array();
    $res = $DB->get_record($table, array('id' => $id));
    if ($includeStart) {
        $nodelist[] = ($returnordering) ? $res->ordering : $id ;
    }
    while (!empty($res->fatherid)) {
        $res = $DB->get_record($table, array('id' => $res->fatherid));
        $nodelist[] = ($returnordering) ? $res->ordering : $res->id;
    }
    if ($reverse) {
        $nodelist = array_reverse($nodelist);
    }
    return $nodelist;
}

/**
 * copies or moves items from a table-tree to another, respecting structure.
 * only standard descriptive attributes are copied (abstract and description), unless
 * field parameter is used. Move is destructive (loss of data is possible). Copy
 * is non destructive.
 * Associations are deleted when moved, are not reported in copy. 
 * @param array $set the set of ids to be copied/moved
 * @param string $fromtable the origin table-tree given by name
 * @param string $totable the destination table-tree given by name
 * @param string $fields a list of comma separated fields to report
 * @param string $autobind if true, auto binds the new record to the origin record
 */
function techproject_tree_copy_set($set, $fromtable, $totable, $fields = 'description,format,abstract,projectid,groupid,ordering', $autobind = false, $bindtable = '') {

    // Nothing to do
    if (count($set) == 0) {
        return;
    }

    // Stores extracted objects.
    $items = array();
    function find_node_in_tree(&$items, $itemid) {
        if (!empty($items)) {
            foreach ($items as $nodeid => $node) {
                if ($nodeid == $itemid) {
                    return $node;
                }
                if (!empty($node->childs)) {
                    if ($res = find_node_in_tree($node->childs, $itemid)) {
                        return $res;
                    }
                }
            }
        }
        return null;
    }

    /**
     * Links the clones within a tree topology from leaves, knowing the original branch sequence
     */
    function place_in_tree($table, &$flatitemids, &$items, $itemid) {
        $branch = techproject_tree_get_upper_branch($table, $itemid, false, false, false);

        /*
         * for each node climbing up the branch, do we find a node in extracted set where to
         * grip ?
         */
        foreach ($branch as $aBranchNode) {
            if (in_array($aBranchNode, $flatitemids)) {
                // Grip to that node, initializing childs if necessary.
                $node = & find_node_in_tree($items, $aBranchNode);
                if (!isset($node->childs)) {
                    $node->childs = array();
                }
                $node->childs[$itemid] = $items[$itemid];
                // $item->fatherid = $aBranchNode;
                // Cleanup from root.
                unset($items[$itemid]);
                return;
            }
        }
    }

    /**
     * recursively inserts tree structure renumbering items
     * @param int $projectid the project module id 
     * @param int $group the owning group 
     * @param string $table the table-tree where to insert by name
     * @param array $set the set of nodes to insert and make a new tree with
     */
    function insert_tree($projectid, $group, $table, &$set, $autobind = false, $bindtable = ''){
        global $DB;

        $setkeys = array_keys($set);

        // Note function is recursive so it has infinite loop protection.
        for($i = 0 ; $i < count($set) && $i < 1000; $i++) {
            // bug fix php4/php5 on reference passing assign
            if (@phpversion() >= 5.0) {
                eval(" \$insertedObject = clone(\$set[\$setkeys[$i]]);");
            } else {
                $insertedObject = $set[$setkeys[$i]];
            }
            // Get max ordering at root level.
            if ($set[$setkeys[$i]]->fatherid == 0) {
                $position = techproject_tree_get_max_ordering($projectid, $group, $table);
                // Remove child as not member in the db data record.
            } else {
                $position = techproject_tree_get_max_ordering($projectid, $group, $table, true, $set[$setkeys[$i]]->fatherid);
            }
            $insertedObject->ordering = $position + 1;

            $originalid = $insertedObject->id;

            unset($insertedObject->childs);
            $insertid = $DB->insert_record($table, $insertedObject);
            if ($autobind) {
                switch ($bindtable) {
                    case 'techproject_spec_to_req' :
                        if ($table == 'techproject_specification'){
                            $t1 = 'reqid';
                            $t2 = 'specid';
                        } else {
                            $t1 = 'specid';
                            $t2 = 'reqid';
                        }
                    break;
                    case 'techproject_task_to_spec' :
                        if ($table == 'techproject_specification'){
                            $t1 = 'taskid';
                            $t2 = 'specid';
                        } else {
                            $t1 = 'specid';
                            $t2 = 'taskid';
                        }
                    break;
                    case 'techproject_task_to_deliv' :
                        if ($table == 'techproject_deliverable'){
                            $t1 = 'taskid';
                            $t2 = 'delivid';
                        } else {
                            $t1 = 'delivid';
                            $t2 = 'taskid';
                        }
                    break;
                }
                $bind = new StdClass();
                $bind->projectid = $projectid;
                $bind->groupid = $group;
                $bind->$t1 = $originalid ; // old id
                $bind->$t2 = $insertid;
                $DB->insert_record($bindtable, $bind);
            }

            // Remap tree distributing inserted id to immediate childs and insert childs.
            if (!empty($set[$setkeys[$i]]->childs)) {
                $childkeys = array_keys($set[$setkeys[$i]]->childs);
                for ($j = 0 ; $j < count($set[$setkeys[$i]]->childs) && $j < 1000; $j++) {
                    $set[$setkeys[$i]]->childs[$childkeys[$j]]->fatherid = $insertid;
                }
                insert_tree($projectid, $group, $table, $set[$setkeys[$i]]->childs, $autobind, $bindtable);
            }
        }
    }

    // First pass, make clones of records in memory.
    $fieldArray = explode(',', $fields);
    $flatitemids = array();
    foreach($set as $anItem){
        // Get original record.
        $node = $DB->get_record($fromtable, array('id' => $anItem));

        $aClone = new StdClass();
        $aClone->id = $node->id;
        $aClone->ordering = $node->ordering;
        $aClone->fatherid = 0; // destroys all tree information as being reconstructed later
        $aClone->lastuserid = $USER->id;
        foreach ($fieldArray as $aField) {
            $aClone->$aField = mysql_real_escape_string($node->$aField);
        }
        $items[$node->id] = $aClone;
        $flatitemids[] = $node->id;
    }

    // Set project and group context using last viewed node.
    $currentprojectid = $node->projectid;
    $currentgroupid = $node->groupid;

    // Remaps tree structure of collected items.
    foreach ($flatitemids as $anitemid) {
        place_in_tree($fromtable, $flatitemids, $items, $anitemid);
    }

    insert_tree($currentprojectid, $currentgroupid, $totable, $items, $autobind, $bindtable);     

}

/**
 * get the max ordering available in sequence at a specified node
 * @param project the current project object
 * @param group the current group
 * @param table the table-tree where to search
 * @param istree true id the entity is table-tree rather than table-list
 * @param fatherid the parent node
 * @return integer the max ordering found
 */
function techproject_tree_get_max_ordering($projectid, $group, $table, $istree = false, $fatherid = 0) {
    global $DB;

    $treeclause = ($istree) ? "AND fatherid = {$fatherid}" : '';
    $sql = "
        SELECT 
            MAX(ordering) as position
        FROM 
            {{$table}}
        WHERE 
            groupid = ? AND
            projectid = ?
            ?
    ";

    if (! $result = $DB->get_record_sql($sql, array($group, $projectid, $treeclause))) {
        $result->position = 0;
    }
    return $result->position;
}

/**
 * get records in tree order
 * @param string $table entity-table name
 * @param int $projectid the current project
 * @param int $groupid the curent group the records belong to
 * @param array reference $tree the record tree as an array
 * @param int $fatherid whose father we get records for
 */
function techproject_tree_get_tree($table, $projectid, $groupid, &$tree, $fatherid = 0) {
    global $DB;

    static $deepness = 0;
    static $nodecode;

    if (!$tree) {
        $tree = array();
    }
    $sql = "
       SELECT 
          *
       FROM
          {{$table}}
       WHERE
          projectid = ? AND
          groupid = ? AND
          fatherid = ?
       ORDER BY
          ordering
    ";
    $records = $DB->get_records_sql($sql, array($projectid, $groupid, $fatherid));
    if ($records) {
        foreach($records as $key => $record){
            $record->deepness = $deepness;
            $record->nodecode = (empty($nodecode)) ? $record->ordering : "$nodecode.{$record->ordering}";
            $tree[$key] = $record;
            $deepness++;
            $oldnode = $nodecode;
            $nodecode = $record->nodecode;
            techproject_tree_get_tree($table, $projectid, $groupid, $tree, $record->id);
            $nodecode = $oldnode;
            $deepness--;
        }
    }
}

/**
 * get records in list order (for non tree entities)
 * @param string $table entity-table name
 * @param int $projectid the current project
 * @param int $groupid the curent group the records belong to
 * @param array reference $list the record list as an array
 */
function techproject_tree_get_list($table, $projectid, $groupid, &$list) {
    global $DB;

    if (!$list) {
        $list = array();
    }
    $sql = "
       SELECT 
          *
       FROM
          {{$table}}
       WHERE
          projectid = ? AND
          groupid = ?
       ORDER BY
          ordering
    ";
    $records = $DB->get_records_sql($sql, array($projectid, $groupid));

    if ($records) {
        foreach ($records as $key => $record) {
            $record->deepness = 0;
            $record->nodecode = $record->ordering ;
            $list[$key] = $record;
        }
    }
}
