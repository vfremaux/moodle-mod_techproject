<?php 

/**
 *
 *
 *
 *
 */

if ($work == 'dodelete') {
    $milestoneid = required_param('milestoneid', PARAM_INT);
    techproject_tree_delete($milestoneid, 'techproject_milestone', 0); // uses list option switch
    // Cleans up any assigned task.
    $query = "
       UPDATE
          {techproject_task}
       SET
          milestoneid = NULL
       WHERE
          milestoneid = $milestoneid
    ";
    $DB->execute($query);

    // cleans up any assigned deliverable
    $query = "
       UPDATE
          {techproject_deliverable}
       SET
          milestoneid = NULL
       WHERE
          milestoneid = $milestoneid
    ";
    $DB->execute($query);
    add_to_log($course->id, 'techproject', 'changemilestone', "view.php?id=$cm->id&view=milestone&group={$currentgroupid}", 'delete', $cm->id);
} elseif ($work == 'doclearall') {
    // delete all records. POWERFUL AND DANGEROUS COMMAND.
    $DB->delete_records('techproject_milestone', array('projectid' => $project->id));

    // do reset all milestone assignation in project
    $query = "
       UPDATE
          {techproject_task}
       SET
          milestoneid = NULL
       WHERE
          projectid = {$project->id} AND
          groupid = {$currentgroupid}
    ";
    $DB->execute($query);

    // do reset all milestone assignation in project
    $query = "
       UPDATE
          {techproject_deliverable}
       SET
          milestoneid = NULL
       WHERE
          projectid = {$project->id} AND
          groupid = {$currentgroupid}
    ";
    $DB->execute($query);
    add_to_log($course->id, 'techproject', 'changemilestones', "view.php?id=$cm->id&view=milestone&group={$currentgroupid}", 'clear', $cm->id);
} elseif ($work == 'up') {
    $milestoneid = required_param('milestoneid', PARAM_INT);
    techproject_tree_up($project, $currentgroupid,$milestoneid, 'techproject_milestone', 0);
} elseif ($work == 'down') {
    $milestoneid = required_param('milestoneid', PARAM_INT);
    techproject_tree_down($project, $currentgroupid,$milestoneid, 'techproject_milestone', 0);
} elseif ($work == 'sortbydate'){
    $milestones = array_values($DB->get_records_select('techproject_milestone', "projectid = {$project->id} AND groupid = {$currentgroupid}"));

    function sortByDate($a, $b){
        if ($a->deadline == $b->deadline) return 0;
        return ($a->deadline > $b->deadline) ? 1 : -1;
    }

    usort($milestones, 'sortByDate');
    // reorders in memory and stores back
    $ordering = 1;
    foreach ($milestones as $aMilestone) {
        $aMilestone->ordering = $ordering;
        $DB->update_record('techproject_milestone', $aMilestone);
        $ordering++;
    }
}
