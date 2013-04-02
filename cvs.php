<?php // $Id: cvs.php,v 1.1 2011-06-20 16:20:00 vf Exp $

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

    print_simple_box(get_string('notimplementedyet', 'techproject'), 'center', '50%');
?>