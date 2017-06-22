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
 * PHPUnit data generator tests
 * @package block_qrcode
 * @category test
 * @copyright 2017 T Gunkel
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * PHPUnit data generator testcase
 * @package block_groups
 * @category test
 * @copyright 2017 T Gunkel
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_qrcode_generator_testcase extends advanced_testcase {
    public function test_generator() {
        global $DB;
        $this->resetAfterTest(true);

        $beforeblocks = $DB->count_records('block_instances');

        $generator = $this->getDataGenerator()->get_plugin_generator('block_qrcode');
        $this->assertInstanceOf('block_qrcode_generator', $generator);
        $this->assertEquals('qrcode', $generator->get_blockname());

        $generator->create_instance();
        $this->assertEquals($beforeblocks+1, $DB->count_records('block_instances'));
    }
}