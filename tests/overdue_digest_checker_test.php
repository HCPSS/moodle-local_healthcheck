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
 * Unit test for digest checker.
 *
 * @package    local_healthchecker
 * @copyright  2019 Howard County Public School System <webmaster@hcpss.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_healthcheck\checker\overdue_digest_checker;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once $CFG->dirroot . '/mod/forum/lib.php';

/**
 * Unit test for digest checker.
 *
 * @package    local_healthchecker
 * @copyright  2019 Howard County Public School System <webmaster@hcpss.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_healthcheck_overdue_digest_checker_test extends advanced_testcase {

    /**
     * Helper function to create a forum.
     *
     * @return stdClass[]
     */
    protected function create_forum() {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $record = new stdClass();
        $record->course = $course->id;
        $record->forcesubscribe = 1;
        $forum = $this->getDataGenerator()->create_module('forum', $record);

        $record = new stdClass();
        $record->course = $course->id;
        $record->userid = 2;
        $record->mailnow = 1;
        $record->forum = $forum->id;
        $discussion = $this
            ->getDataGenerator()
            ->get_plugin_generator('mod_forum')
            ->create_discussion($record);

       return [$course, $forum, $discussion, $user];
    }

    protected function set_posts_as_overdue($secondsoverdue) {
        global $DB, $CFG;

        $sitetimezone = core_date::get_server_timezone();
        $digesttime = usergetmidnight(time(), $sitetimezone) + ($CFG->digestmailtime * 3600) - $secondsoverdue;
        $DB->set_field('forum_posts', 'modified', $digesttime, array('mailed' => 0));
        $DB->set_field('forum_posts', 'created', $digesttime, array('mailed' => 0));
        $DB->set_field('forum_queue', 'timemodified', $digesttime);
    }

    /**
     * Test the digest checker.
     */
    public function test_check() {
        /** @var \moodle_database $DB */
        global $DB, $CFG;

        $this->resetAfterTest(true);

        list(, $forum, , $user) = $this->create_forum();

        // Subscribe the user to the forum as a digest.
        forum_set_user_maildigest($forum, 1, $user);

        // Run forum cron so that the discussion is queued for digest.
        forum_cron();

        $padding = 3600;
        $checker = new overdue_digest_checker($padding);

        // No digests should be overdue
        $this->assertFalse($checker->check());

        // Set the digest as 1 second overdue.
        $this->set_posts_as_overdue($padding - 1);

        // The digest is 1 second overdue, but we are testing with an hour of
        // padding.
        $this->assertFalse($checker->check());

        // Set the digest as 1 second overdue.
        $this->set_posts_as_overdue($padding + 1);

        // The digest is 1 second overdue and we have no padding.
        $this->assertTrue($checker->check());
    }
}
