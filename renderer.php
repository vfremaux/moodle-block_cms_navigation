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

defined('MOODLE_INTERNAL') || die();

/**
 * Block instance editing form.
 *
 * @package    block_cms_navigation
 * @category   blocks
 * @author Moodle 1.9 Janne Mikkonen
 * @author Moodle 2.x Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_cms_navigation_renderer extends plugin_renderer_base {

    public function render_tree($parentnode) {
        global $CFG, $COURSE;
        static $level = 0;

        if ($COURSE->id > SITEID) {
            $context = context_course::instance($COURSE->id);
        } else {
            $context = context_system::instance();
        }

        $config = get_config('local_cms');

        $str = '';

        if (!empty($parentnode->children)) {
            foreach ($parentnode->children as $node) {
                if (empty($node->children)) {
                    $class = 'leaf';
                } else {
                    $class = '';
                }
                if (!$node->publish && !has_capability('local/cms:editpage', $context)) {
                    continue;
                }
                $class .= ($node->publish) ? ' cms-level'.($level + 1).'on' : ' level'.$level.'off';

                if (empty($node->url)) {
                    // This is a link to a real cms page.
                    if (empty($node->pagename)) {
                        $baseurl = new moodle_url('/local/view.php', array('pid' => $node->id));
                    } else {
                        if ($node->course > SITEID) {
                            $baseurl = new moodle_url('/local/cms/view.php', array('id' => $node->course, 'page' => urlencode($node->pagename)));
                        } else {
                            if (!$CFG->slasharguments) {
                                $baseurl = new moodle_url('/local/cms/view.php', array('page' => urlencode($node->pagename)));
                            } else {
                                $baseurl = $CFG->wwwroot.'/local/cms/view.php'.$config->virtual_path.'/'. urlencode($node->pagename);
                            }
                        }
                    }
                } else {
                    $baseurl = $node->url;
                }

                $target = (!empty($node->url) && !empty($node->target)) ? ' target="'. $node->target .'"' : '';
                $str .= '<li class="'.$class.'"><a href="'.$baseurl.'"'. $target.'>'.stripslashes($node->title).'</a>'."\n";
                $level ++;
                $str .= $this->render_tree($node);
                $level--;
            }
        }
        return $str;
    }
}
