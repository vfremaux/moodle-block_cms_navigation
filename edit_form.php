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
 * Block instance editing form.
 *
 * @package    block_cms_navigation
 * @author Moodle 1.9 Janne Mikkonen
 * @reauthor Moodle 2.x Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_cms_navigation_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        global $DB, $COURSE, $OUTPUT, $CFG;

		if (!empty($this->block->instance)){
			$config = unserialize(base64_decode($this->block->instance->configdata));
		} else {
			$config = false;
		}

        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

		$mform->addElement('advcheckbox', 'config_forceglobal', get_string('forceglobal', 'block_cms_navigation'), '');

		if (!empty($config->forceglobal)){
			$menus = $DB->get_records('local_cms_navi', array('course' => SITEID));
		} else {
			$menus = $DB->get_records('local_cms_navi', array('course' => $COURSE->id));
		}

		$menuoptions = array();
	    if (!empty($menus)) {
	        foreach ($menus as $menu) {
	        	$menuoptions[$menu->id] = format_string($menu->name);
	        }
	    }
		$mform->addElement('select', 'config_menu', get_string('choosemenu', 'block_cms_navigation'), $menuoptions);

		$mform->addElement('text', 'config_title', get_string('menuname', 'block_cms_navigation'), array('size' => 40));
		$mform->setType('config_title', PARAM_CLEANHTML);
		
		$pixmenus = $OUTPUT->pix_url('menus', 'local_cms');
		$pixpages = $OUTPUT->pix_url('pages', 'local_cms');

		$strmanagepages = get_string('managepages', 'block_cms_navigation');
		$strmanagemenus = get_string('managemenus', 'block_cms_navigation');
		$managemenuslink = $CFG->wwwroot.'/local/cms/menus.php?course='.$COURSE->id.'&sesskey='.sesskey();
		$managepageslink = $CFG->wwwroot.'/local/cms/pages.php?course='.$COURSE->id.'&sesskey='.sesskey();
		
		if (!empty($config->menu)) $managepageslink .= '&menuid='.$config->menu;
		
		$str = '<center><table width="70%">';
		$str .= '<tr>';
		$str .= '<td align="center"><a href="'.$managemenuslink.'" title="'.$strmanagemenus.'">
                <img src="'.$pixmenus.'" width="50" height="50" alt="'.$strmanagemenus.'" border="0" /></a><br />
                <a href="'.$managemenuslink.'">'.$strmanagemenus.'</a></td>';
		$str .= '<td align="center"><a href="'.$managepageslink.'" title="'.$strmanagepages.'">
                <img src="'.$pixpages.'" width="50" height="50" alt="'.$strmanagepages.'" border="0" /></a><br />
                <a href="'.$managepageslink.'">'.$strmanagepages.'</a></td>';
		$str .= '</tr>';
		$str .= '</table></center>';

		$mform->addElement('header', 'head3', get_string('manageitems', 'block_cms_navigation'));
		$mform->setExpanded('head3');

		$mform->addElement('html', $str);

    }

    function set_data($defaults) {
        parent::set_data($defaults);
    }
}
