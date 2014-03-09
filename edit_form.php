<?php

class block_cms_navigation_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        global $DB, $COURSE, $OUTPUT, $CFG;

        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

		$mform->addElement('advcheckbox', 'config_forceglobal', get_string('forceglobal', 'block_cms_navigation'), '');

		$menus = $DB->get_records('local_cms_navi', array('course' => $COURSE->id));

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
		
		$config = unserialize(base64_decode($this->block->instance->configdata));
		
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

		$mform->addElement('html', $str);

    }

    function set_data($defaults) {
        parent::set_data($defaults);
    }
}
