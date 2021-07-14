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
 * Completion Progress overview table.
 *
 * @package    block_completion_progress
 * @copyright  2021 Jonathon Fowler <fowlerj@usq.edu.au>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace block_completion_progress\table;

defined('MOODLE_INTERNAL') || die;

use core_table\local\filter\filterset;

class overview extends \core_user\table\participants {
    /**
     * @var int $blockinstanceid The block instance id
     */
    protected $blockinstanceid;

    public function out($pagesize, $useinitialsbar, $downloadhelpbutton = '') {
        global $CFG, $OUTPUT, $PAGE;

        // Define the headers and columns.
        $headers = [];
        $columns = [];

        $bulkoperations = has_capability('moodle/course:bulkmessaging', $this->context);
        if ($bulkoperations) {
            $mastercheckbox = new \core\output\checkbox_toggleall('participants-table', true, [
                'id' => 'select-all-participants',
                'name' => 'select-all-participants',
                'label' => get_string('selectall'),
                'labelclasses' => 'sr-only',
                'classes' => 'm-1',
                'checked' => false,
            ]);
            $headers[] = $OUTPUT->render($mastercheckbox);
            $columns[] = 'select';
        }

        // Get the list of fields we have to hide.
        $hiddenfields = array();
        if (!has_capability('moodle/course:viewhiddenuserfields', $this->context)) {
            $hiddenfields = array_flip(explode(',', $CFG->hiddenuserfields));
        }

        $headers[] = get_string('fullname');
        $columns[] = 'fullname';

        // Do not show the columns if it exists in the hiddenfields array.
        if (!isset($hiddenfields['lastaccess'])) {
            $headers[] = get_string('lastcourseaccess');
        }

        $this->define_columns($columns);
        $this->define_headers($headers);

        // The name column is a header.
        $this->define_header_column('fullname');

        // Make this table sorted by last name by default.
        $this->sortable(true, 'lastname');

        $this->no_sorting('select');

        $this->set_attribute('id', 'participants');

        \table_sql::out($pagesize, $useinitialsbar, $downloadhelpbutton);
    }

    /**
     * Set filters and build table structure.
     *
     * @param filterset $filterset The filterset object to get the filters from.
     */
    public function set_filterset(filterset $filterset): void {
        $this->blockinstanceid = $filterset->get_filter('blockinstanceid')->current();
        parent::set_filterset($filterset);
    }

    /**
     * Guess the base url for the overview table.
     */
    public function guess_base_url(): void {
        $this->baseurl = new \moodle_url('/blocks/completion_progress/noverview.php', [ //XXXX
            'id' => $this->courseid,
            'bid' => $this->blockinstanceid,
        ]);
    }
}
