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
 * Block main class.
 *
 * @package    block_cms_navigation
 * @category   blocks
 * @author Moodle 1.9 Janne Mikkonen
 * @author Moodle 2.x Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/cms/locallib.php');

if (!defined('FRONTPAGECMS')) define ('FRONTPAGECMS', 29);

class block_cms_navigation extends block_base {

    // The pages after tree binding
    private $rootnode;

    // The raw set of pages, untreed.
    private $navipages;

    // Global settings for local cms plugin
    private $settings;

    function init() {
        $this->title = get_string('blocktitle', 'block_cms_navigation');
        $this->content_type = BLOCK_TYPE_TEXT;
        $this->navidatapages = null;
    }

    function applicable_formats() {
        return array('all' => true);
    }

    function specialization() {
        // We allow empty titles
        $this->title = isset($this->config->title) ? $this->config->title : '';
    }

    function instance_allow_multiple() {
        return true;
    }

    function is_login_required ( $menuid ) {
        global $DB;

        return $DB->get_field('local_cms_navi', 'requirelogin', array('id' => $menuid));
    }

    function is_guest_allowed ( $menuid ) {
        global $DB;

        return $DB->get_field('local_cms_navi', 'allowguest', array('id' => $menuid));
    }

    function preferred_width() {
        // The preferred value is in pixels
        return 240;
    }

    function hide_header() {
        return empty($this->title);
    }

    function has_config() {
        return false;
    }

    function build_menu_tree() {
        global $CFG;

        if (empty($this->config->menu)) {
            return;
        }

        if (empty($this->navipages)) {
            $this->navipages = cms_get_visible_pages($this->config->menu);
        }

        if (empty($this->navipages)) {
            // No pages in spite of having tried to get them.
            return;
        }

        $this->rootnode = new StdClass();
        $this->rootnode->children = [];

        foreach ($this->navipages as $pageid => $node) {
            if ($node->parentid) {
                if (!isset($this->navipages[$node->parentid])) {
                    $this->navipages[$node->parentid] = new StdClass();
                }
                if (!isset($this->navipages[$node->parentid]->children)) {
                    $this->navipages[$node->parentid]->children = [];
                }
                $arr = $this->navipages[$node->parentid]->children;
                if (!array_key_exists($pageid, $arr)) {
                    $this->navipages[$node->parentid]->children[$pageid] = $node;
                }
            } else {
                // Top level page.
                $this->rootnode->children[$pageid] = $node;
            }
        }
    }

    /**
     * get master content for the block.
     */
    function get_content() {
        global $CFG, $USER, $SITE, $COURSE, $OUTPUT, $DB, $PAGE;

        $systemcontext = context_system::instance();
        $renderer = $PAGE->get_renderer('block_cms_navigation');

        if ($this->content !== null) {
            return $this->content;
        }

        $this->settings = get_config('local_cms');
        if (empty($this->settings->virtual_path)) {
            set_config('virtual_path', '/documentation', 'local_cms');
            $this->settings->virtual_path = '/documentation';
        }

        $pagename = optional_param('page', '', PARAM_FILE);
        $pageid   = optional_param('pid', 0, PARAM_INT);

        if (defined('SITEID') &&
                ($systemcontext->id == $this->instance->parentcontextid) &&
                        $CFG->slasharguments ) {
            /*
             * Support sitelevel slasharguments.
             * in form /index.php/<pagename>
             */
            $relativepath = get_file_argument(basename($_SERVER['SCRIPT_FILENAME']));
            if (preg_match("/^\/documentation(\/[a-z0-9\_\-]+)/i", $relativepath) ) {
                $args = explode("/", $relativepath);
                $pagename = clean_param($args[2], PARAM_FILE);
            }
            unset($args, $relativepath);
        }

        $coursescopeid = (empty($this->config->forceglobal)) ? $COURSE->id : SITEID;

        // set menuid according to block configuration or if no menu has been configured yet use
        // the first available menu if it exists
        if (!empty($this->config->menu)) {
            $menuid = intval($this->config->menu);
        } else {
            if ($menus = $DB->get_records('local_cms_navi', ['course' => $coursescopeid], 'id ASC')) {
                $menu = array_pop($menus);
                if (!isset($this->config)) $this->config = new StdClass();
                $this->config->menu = $menuid = $menu->id;
                $this->instance_config_commit();
            } else {
                $menuid = 0;
                $this->content = new StdClass;
                $this->content->text = $OUTPUT->notification('nomenus', 'block_cms_navigation');
            }
        }

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        $menurequirelogin = $this->is_login_required($menuid);
        $menuallowguest   = $this->is_guest_allowed($menuid);

        // no content here
        if (($menurequirelogin && !isloggedin()) ||
            ($menurequirelogin && isguestuser() && !$menuallowguest)) {
              return $this->content;
        }

        $this->navidatapages = cms_get_visible_pages(@$this->config->menu);

        if ( empty($pageid) && !empty($pagename) ) {
            $pageid = $DB->get_field('local_cms_navi_data', 'pageid', ['pagename' => $pagename, 'naviid' => $menuid]);
        }

        // Wrap it inside div element which width you can control
        // with CSS styles in styles.php file.
        $this->content->text .= "\n" . '<div class="cms-navi">' . "\n";
        $this->build_menu_tree();
        $this->content->text .= $renderer->render_tree($this->rootnode);
        $this->content->text .= '</div>'."\n";

        if (!empty($USER->editing) && !empty($pageid)) {
            $toolbar = '';

            $stradd = get_string('add');
            $params = array('id' => $pageid, 'sesskey' => sesskey(), 'parentid' => 0, 'course' => $COURSE->id);
            $addlink = new moodle_url('/local/cms/pageadd.php', $params);
            $addicon = $OUTPUT->pix_icon('add', $stradd, 'local_cms');
            $toolbar .=  '<a title="'.$stradd.'" href="'.$addlink.'" target="reorder">'.$addicon.'</a>';

            $strreorder = get_string('reorder', 'block_cms_navigation');
            $reorderlink = new moodle_url('/local/cms/reorder.php', array('source' => $pageid, 'sesskey' => sesskey()));
            $reordericon = $OUTPUT->pix_icon('t/move', $strreorder, 'core');

            $toolbar .=  ' <a title="'.$strreorder.'" href="'.$reorderlink.'" target="reorder">'.$reordericon.'</a>';

            $this->content->footer = $toolbar;
        }

        return $this->content;

    }
}
