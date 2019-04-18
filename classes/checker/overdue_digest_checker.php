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
 * Checks for overdue digest.
 *
 * @package    local_healthchecker
 * @copyright  2019 Howard County Public School System <webmaster@hcpss.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_healthcheck\checker;

defined('MOODLE_INTERNAL') || die();

/**
 * Checks for overdue digest.
 *
 * @package    local_healthchecker
 * @copyright  2019 Howard County Public School System <webmaster@hcpss.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class overdue_digest_checker {

    /**
     * The amount of time we give the digest to run before we report it as late.
     *
     * @var number
     */
    private $timepadding;

    /**
     * Current timestamp.
     *
     * @var number
     */
    private $time;

    /**
     * Last time the digest should have run.
     *
     * @var \DateTime
     */
    private $lastdigesttime;

    /**
     * @var \moodle_database
     */
    private $db;

    public function __construct($timepadding = 3600) {
        global $DB;

        $this->db = $DB;
        $this->timepadding = $timepadding;
        $this->time = time();

        $digestmailtime = (int)get_config('core', 'digestmailtime');

        $lastdigesttime = \DateTime::createFromFormat(
            'H:i',
            sprintf('%02d:00', $digestmailtime),
            new \DateTimeZone(get_config('core', 'timezone'))
        );

        if ($lastdigesttime->getTimestamp() > $this->time) {
            $lastdigesttime->modify('-1 day');
        }

        $this->lastdigesttime = $lastdigesttime;
    }

    /**
     * @return \DateTime
     */
    public function getlastdigesttime() {
        return $this->lastdigesttime;
    }

    /**
     * Are there overdue digests?
     *
     * @return boolean
     */
    public function check() {
        $numoverdue = $this->db->count_records_select('forum_queue', 'timemodified < ?', [
            $this->lastdigesttime->getTimestamp() - $this->timepadding
        ]);

        return $numoverdue > 0;
    }
}
