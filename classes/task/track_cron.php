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
 * Track when the last time cron was run.
 *
 * @package    local_healthchecker
 * @copyright  2019 Howard County Public School System <webmaster@hcpss.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_healthcheck\task;

use core\task\scheduled_task;

/**
 * A task to track when the last time cron was run.
 *
 * @package    local_healthchecker
 * @copyright  2019 Howard County Public School System <webmaster@hcpss.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class track_cron extends scheduled_task {

    /**
     * Task execution which syncs users with LDAP
     *
     * @see \core\task\task_base::execute()
     */
    public function execute() {
        set_config('healthcheck_track_cron', time(), 'local_healthcheck');
    }

    /**
     * (non-PHPdoc)
     * @see \core\task\scheduled_task::get_name()
     */
    public function get_name() {
        return get_string('track_cron', 'local_healthcheck');
    }
}
