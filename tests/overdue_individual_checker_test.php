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
 * Unit test for individual email checker.
 *
 * @package    local_healthchecker
 * @copyright  2019 Howard County Public School System <webmaster@hcpss.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_healthcheck\checker\overdue_individual_checker;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once $CFG->dirroot . '/mod/forum/lib.php';

/**
 * Unit test for individual email checker.
 *
 * @package    local_healthchecker
 * @copyright  2019 Howard County Public School System <webmaster@hcpss.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_healthcheck_overdue_individual_checker_test extends advanced_testcase {

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

    /**
     * Set the posts as overdue for individual emails.
     *
     * @param int $secondsoverdue
     */
    protected function set_posts_overdue($secondsoverdue) {
        global $DB;

        $indtime = time() - $secondsoverdue;
        $DB->set_field('forum_posts', 'modified', $indtime, array('mailed' => 0));
        $DB->set_field('forum_posts', 'created', $indtime, array('mailed' => 0));
    }

    /**
     * Set all posts as mailed.
     */
    protected function set_posts_as_mailed() {
        global $DB;

        $DB->set_field('forum_posts', 'mailed', 1);
    }

    /**
     * Test the digest checker.
     */
    public function test_check() {
        /** @var \moodle_database $DB */
        global $DB, $CFG;

        $this->resetAfterTest(true);

        list(, $forum, , $user) = $this->create_forum();

        // Subscribe the user to the forum as individual emails.
        forum_set_user_maildigest($forum, 0, $user);

        $padding = 1800;
        $checker = new overdue_individual_checker($padding);
        $this->assertFalse($checker->check());

        $this->set_posts_overdue($padding - 1);
        $this->assertFalse($checker->check());

        $this->set_posts_overdue($padding + 1);
        $this->assertTrue($checker->check());

        $this->set_posts_as_mailed();
        $this->assertFalse($checker->check());
    }
}
