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

use local_healthcheck\checker\overdue_digest_checker;
use local_healthcheck\checker\overdue_individual_checker;

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');

require_once $CFG->libdir . '/clilib.php';
require_once $CFG->libdir . '/datalib.php';
require_once $CFG->dirroot . '/local/healthcheck/lib.php';
require_once $CFG->dirroot . '/user/lib.php';

$ids   = explode(',', get_config('core', 'siteadmins'));
$users = user_get_users_by_id($ids);
$from  = $DB->get_record('user', ['id' => 2]);

// Check for overdue digests.
$digest_checker = new overdue_digest_checker();
if ($digest_checker->check()) {
    $subject  = get_site()->fullname . ': possible digest delay';
    $message  = "Posts found in the forum queue that should have been sent at ";
    $message .= $digest_checker->getlastdigesttime()->format('c') . '.';

    foreach ($users as $user) {
        email_to_user($user, $from, $subject, $message);
    }
}

// Check for overdue individual emails.
$individual_checker = new overdue_individual_checker();
if ($individual_checker->check()) {
    $subject  = get_site()->fullname . ': possible individual email delay';
    $message  = "Posts found that should have been sent as individual emails by now.";

    foreach ($users as $user) {
        email_to_user($user, $from, $subject, $message);
    }
}

// Check that cron has run recently.
$cronlastrun = (int)get_config('local_healthcheck', 'healthcheck_track_cron');
if (!$cronlastrun || $cronlastrun < (time() - 3600)) {
    // Cron has not run for over an hour.
    $subject  = get_site()->fullname . ': cron may have hung';
    $message  = "Cron has not run for over an hour.";

    foreach ($users as $user) {
        email_to_user($user, $from, $subject, $message);
    }
}
