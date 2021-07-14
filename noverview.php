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
 * Completion Progress block overview page
 *
 * @package    block_completion_progress
 * @copyright  2021 Jonathon Fowler <fowlerj@usq.edu.au>
 * @copyright  1999 Martin Dougiamas  http://dougiamas.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use core_table\local\filter\filter;
use core_table\local\filter\integer_filter;

define('DEFAULT_PAGE_SIZE', 20);

$id       = required_param('bid', PARAM_INT);
$courseid = required_param('id', PARAM_INT);
$page     = optional_param('page', 0, PARAM_INT);
$perpage  = optional_param('perpage', DEFAULT_PAGE_SIZE, PARAM_INT);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$coursecontext = context_course::instance($courseid);

require_login($course, false);

$block = $DB->get_record('block_instances', ['id' => $id], '*', MUST_EXIST);
$config = unserialize(base64_decode($block->configdata));

unset($id);
unset($courseid);

$blockcontext = context_block::instance($block->id);
if (!$blockcontext->is_child_of($coursecontext, false)) {
    throw new coding_exception('mismatched course and block ids given');
}

$PAGE->set_course($course);
$PAGE->set_url('/blocks/completion_progress/noverview.php',//XXX
    array(
        'bid'        => $block->id,
        'id'         => $course->id,
        'page'       => $page,
        // 'perpage'    => $perpage,
        // 'group'      => $group,
        // 'role'       => $roleselected,
    )
);
$PAGE->set_context($coursecontext);
$title = get_string('overview', 'block_completion_progress');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add($title);
$PAGE->set_pagelayout('report');

$renderer = $PAGE->get_renderer('block_completion_progress');

$cachevalue = debugging() ? -1 : (int)get_config('block_completion_progress', 'cachevalue');
$PAGE->requires->css('/blocks/completion_progress/css.php?v=' . $cachevalue);

echo $OUTPUT->header();
echo $OUTPUT->heading($title);
echo $OUTPUT->container_start('block_completion_progress');

$studentroles = role_fix_names(get_archetype_roles('student'), $coursecontext);

$filterset = new \block_completion_progress\table\overview_filterset();
$filterset->add_filter(new integer_filter('courseid', filter::JOINTYPE_DEFAULT, [(int)$course->id]));
$filterset->add_filter(new integer_filter('blockinstanceid', filter::JOINTYPE_DEFAULT, [(int)$block->id]));
$filterset->add_filter(new integer_filter('roles', filter::JOINTYPE_DEFAULT, [key($studentroles)]));

$overviewtable = new block_completion_progress\table\overview("block-completion_progress-overview-{$course->id}");
$overviewtable->set_filterset($filterset);

echo $renderer->overview_filter($coursecontext, $overviewtable->uniqueid);
$overviewtable->out($perpage, true);

$PAGE->requires->js_call_amd('block_completion_progress/progressbar', 'init', [
    'instances' => array($block->id),
]);

echo $OUTPUT->container_end();
echo $OUTPUT->footer();
