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
 * This file contains a class that provides functions for downloading/displaying a QR code.
 *
 * @package block_qrcode
 * @copyright 2017 T Gunkel
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_qrcode;

use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Symfony\Component\HttpFoundation\Response;
use DOMDocument;
use core\datalib;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/qrcode/thirdparty/vendor/autoload.php');
require_once($CFG->dirroot . '/course/lib.php');

/**
 * Class output_image
 *
 * Downloads or displays QR code.
 *
 * @package block_qrcode
 * @copyright 2017 T Gunkel
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class output_image {
    protected $file; // QR code is saved in this file.
    protected $format;
    protected $size;
    protected $logopath;
    protected $course;

    /**
     * output_image constructor.
     * @param $courseid
     */
    public function __construct($format, $size, $courseid) {
        global $CFG;
        $this->format = $format;
        $this->size = $size;
        $this->course = get_course($courseid);

        // Set logo path.
        if (get_config('block_qrcode', 'custom_logo') == 1) {
            $this->logopath = $this->getlogopath();
            if ($this->logopath === null) {
                $file = $CFG->localcachedir . '/block_qrcode/course-' .
                    $courseid . '-' . $size . '-0'; // File path without file ending.
            } else {
                $file = $CFG->localcachedir . '/block_qrcode/course-' .
                    $courseid . '-' . $size . '-1';
            }
        } else {
            $file = $CFG->localcachedir . '/block_qrcode/course-' .
                $courseid . '-' . $size . '-0';
        }

        // Add file ending.
        if ($format == 1) {
            $file .= '.svg';
        } else {
            $file .= '.png';
        }

        $this->file = $file;
    }

    /**
     * Creates the QR code if it doesn't exist.
     */
    public function create_image() {
        global $CFG;
        // Checks if QR code already exists.
        if (!file_exists($this->file)) {
            // Checks if directory already exists.
            if (!file_exists(dirname($this->file))) {
                // Creates new directory.
                mkdir(dirname($this->file), $CFG->directorypermissions, true);
            }

            // Creates the QR code.
            $qrcode = new QrCode(course_get_url($this->course)->out());
            $qrcode->setSize($this->size);

            // Set advanced options.
            $qrcode->setMargin(10);
            $qrcode->setEncoding('UTF-8');
            $qrcode->setErrorCorrectionLevel(ErrorCorrectionLevel::HIGH);
            $qrcode->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0]);
            $qrcode->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255]);

            // Png format.
            if ($this->format == 2) {
                if (get_config('block_qrcode', 'custom_logo') == 1 && $this->logopath !== null) {
                    $qrcode->setLogoPath($this->logopath);
                    $qrcode->setLogoWidth($this->size / 2.75);
                }

                $qrcode->setWriterByName('png');
                $qrcode->writeFile($this->file);
            } else {
                $qrcode->setWriterByName('svg');

                if (get_config('block_qrcode', 'custom_logo') == 1 && $this->logopath !== null) {
                    // Insert Logo in QR code.
                    $qrcodestring = $qrcode->writeString();
                    $newqrcode = $this->modify_svg($qrcodestring);
                    file_put_contents($this->file, $newqrcode);
                } else {
                    $qrcode->writeFile($this->file);
                }
            }
        }
    }

    /**
     * Outputs file headers to initialise the download of the file / display the file.
     * @param $download true, if the QR code should be downloaded
     */
    protected function send_headers($download) {
        global $DB;
        // Caches file for 1 month.
        header('Cache-Control: public, max-age:2628000');

        if ($this->format == 2) {
            header('Content-Type: image/png');
        } else {
            header('Content-Type: image/svg+xml');
        }

        // Checks if the image is downloaded or displayed.
        if ($download) {
            // Output file header to initialise the download of the file.
            // filename: QR Code - fullname
            if ($this->format == 2) {
                header('Content-Disposition: attachment; filename="QR Code-' . clean_param($this->course->fullname, PARAM_FILE) . '.png"');
            } else {
                header('Content-Disposition: attachment; filename="QR Code-' . clean_param($this->course->fullname, PARAM_FILE). '.svg"');
            }
        }
    }

    /**
     * Outputs (downloads or displays) the QR code.
     * @param $download true, if the QR code should be downloaded
     */
    public function output_image($download) {
        $this->create_image();
        $this->send_headers($download);
        readfile($this->file);
    }

    /**
     * Inserts logo in the QR code (used for svg QR code).
     * @param $svgqrcode QR code
     * @return string XML representation of the svg image
     */
    private function modify_svg($svgqrcode) {
        // Loads QR code.
        $xmldoc = new DOMDocument();
        $xmldoc->loadXML($svgqrcode);
        $viewboxcode = $xmldoc->documentElement->getAttribute('viewBox');
        $codeheight = explode(' ', $viewboxcode)[3];

        // Loads logo.
        $xmllogo = new DOMDocument();
        $xmllogo->load($this->logopath);

        $logotargetheight = $codeheight / 5;

        $viewbox = $xmllogo->documentElement->getAttribute('viewBox');
        $logowidth = explode(' ', $viewbox)[2];
        $logoheight = explode(' ', $viewbox)[3];

        // Calculate logo width and height.
        $logotargetwidth = $logotargetheight * ($logowidth / $logoheight);

        // Calculate logo coordinates.
        $logoy = ($codeheight - $logotargetheight) / 2;
        $logox = ($codeheight - $logotargetwidth) / 2;

        $xmllogo->documentElement->setAttribute('width', $logotargetwidth);
        $xmllogo->documentElement->setAttribute('height', $logotargetheight);
        $xmllogo->documentElement->setAttribute('x', $logox);
        $xmllogo->documentElement->setAttribute('y', $logoy);

        $node = $xmldoc->importNode($xmllogo->documentElement, true);

        $xmldoc->documentElement->appendChild($node);

        return $xmldoc->saveXML();
    }

    /**
     * Generates logo file path.
     * @return string file path
     */
    private function getlogopath() {
        global $CFG;

        if ($this->format == 2) {
            $filearea = 'logo_png';
            $filepath = pathinfo(get_config('block_qrcode', 'logofile_png'), PATHINFO_DIRNAME);
            $filename = pathinfo(get_config('block_qrcode', 'logofile_png'), PATHINFO_BASENAME);
        } else {
            $filearea = 'logo_svg';
            $filepath = pathinfo(get_config('block_qrcode', 'logofile_svg'), PATHINFO_DIRNAME);
            $filename = pathinfo(get_config('block_qrcode', 'logofile_svg'), PATHINFO_BASENAME);
        }

        $fs = get_file_storage();
        $file = $fs->get_file(context_system::instance()->id, 'block_qrcode', $filearea, 0, $filepath, $filename);

        if ($file) {
            $hash = $file->get_contenthash();
            $path = $CFG->dataroot . '/filedir/' . substr($hash, 0, 2) . '/' . substr($hash, 2, 2) . '/' . substr($hash, 0);
            return $path;
        }
        return null;
    }
}