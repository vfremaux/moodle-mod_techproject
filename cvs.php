<?php // $Id: cvs.php,v 1.1.1.1 2012-08-01 10:16:10 vf Exp $

    /**
    * Project : Technical Project Manager (IEEE like)
    *
    * This screen allows remote code repository setup and control.
    *
    * @package mod-techproject
    * @category mod
    * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
    * @date 2008/03/03
    * @version phase1
    * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
    * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
    */

    if (!has_capability('mod/techproject:manage', $context)){
        print_error(get_string('notateacher','techproject'));
        return;
    }

	echo $pagebuffer;

    echo $OUTPUT->box(get_string('notimplementedyet', 'techproject'), 'center', '50%');
?>