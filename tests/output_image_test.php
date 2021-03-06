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
 * PHPUnit output image testcase
 * @package block_qrcode
 * @category test
 * @copyright 2017 T Gunkel
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * PHPUnit output image testcase
 * @package block_groups
 * @category test
 * @copyright 2017 T Gunkel
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_qrcode_output_image_testcase extends advanced_testcase {

    /**
     * Tests, if the image is created.
     */
    public function test_create_image() {
        global $CFG;
        $this->resetAfterTest(true);

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();

        $size = 150;
        $file = $CFG->localcachedir.'/block_qrcode/course-'.$course->id. '-'.$size.'-0.svg';
        $outputimg = new block_qrcode\output_image(
            course_get_url($course->id)->out(),
            $course->fullname,
            1,
            $size,
            context_system::instance()->id,
            $course->id);
        $outputimg->create_image();
        $this->assertFileExists($file);
    }

    /**
     * Tests, if the QR code is created without a logo although custom logo is checked
     * when no logo is uploaded.
     */
    public function test_no_logo() {
        global $CFG;
        $this->resetAfterTest(true);

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();

        set_config('custom_logo', true, 'block_qrcode');
        $this->assertEquals(1, get_config('block_qrcode', 'custom_logo'));
        $this->assertFalse(get_config('block_qrcode', 'logofile_svg'));

        $size = 150;
        $file = $CFG->localcachedir.'/block_qrcode/course-'.$course->id. '-'.$size.'-0.svg';
        $outputimg = new block_qrcode\output_image(
            course_get_url($course->id)->out(),
            $course->fullname,
            1,
            $size,
            context_system::instance()->id,
            $course->id);
        $outputimg->create_image();
        $this->assertFileExists($file);
    }
}
