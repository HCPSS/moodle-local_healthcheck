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
 * Checks for overdue individual emails.
 *
 * @package    local_healthchecker
 * @copyright  2019 Howard County Public School System <webmaster@hcpss.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_healthcheck\checker;

defined('MOODLE_INTERNAL') || die();

/**
 * Checks for overdue individual emails.
 *
 * @package    local_healthchecker
 * @copyright  2019 Howard County Public School System <webmaster@hcpss.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class overdue_individual_checker {

    /**
     * The amount of time we give the emails to send before we report it as
     * late.
     *
     * @var number
     */
    private $timepadding;

    /**
     * @var number
     */
    private $editdelay;

    /**
     * @var \moodle_database
     */
    private $db;

    public function __construct($timepadding = 1800) {
        global $DB;

        $this->db          = $DB;
        $this->timepadding = $timepadding;
        $this->editdelay   = (int)get_config('core', 'maxeditingtime');
    }

    /**
     * Are there overdue individual emails.
     *
     * @return boolean
     */
    public function check() {
        $now = time();

        $count = $this->db->count_records_select('forum_posts', '
            mailed = 0 AND
            ((mailnow = 1 AND created < ?) OR (mailnow = 0 AND created < ?))
        ', [
            $now - $this->timepadding,
            $now - $this->timepadding - $this->editdelay,
        ]);

        return $count > 0;
    }
}
