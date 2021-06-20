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
 * Diary stats utilities for Diary.
 *
 * 2020071700 Moved these functions from lib.php to here.
 *
 * @package   mod_diary
 * @copyright AL Rachels (drachels@drachels.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_diary\local;

defined('MOODLE_INTERNAL') || die();
//define('DIARY_EVENT_TYPE_OPEN', 'open');
//define('DIARY_EVENT_TYPE_CLOSE', 'close');
use mod_diary\local\diarystats;
use stdClass;
use core_text;
//use csv_export_writer;
//use html_writer;
//use context_module;
//use calendar_event;

/**
 * Utility class for Diary stats.
 *
 * @package   mod_diary
 * @copyright AL Rachels (drachels@drachels.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class diarystats {

    /**
     * Update the diary statistics for this diary activity.
     *
     * @param $diary->text The text for this entry.
     * @return bool
     */
    public static function get_diary_stats($entry) {
    //public function get_diary_stats($entry) {
        //global $DB, $CFG;
        $precision = 1;
        $diarystats = array();
        $diarystats['words'] = self::get_stats_words($entry);
        $diarystats['chars'] = self::get_stats_chars($entry);
        $diarystats['sentences'] = self::get_stats_sentences($entry);
        $diarystats['paragraphs'] = self::get_stats_paragraphs($entry);
        $diarystats['uniquewords'] = self::get_stats_uniquewords($entry);

        //print_object('This is the $diarystats array.');
        //print_object($diarystats);

        return $diarystats;
    }

    /**
     * get_stats_chars
     */
    //protected function get_stats_chars($entry) {
    public static function get_stats_chars($entry) {
        return core_text::strlen($entry);
        //return strlen($entry);
    }

    /**
     * get_stats_words
     */
    public static function get_stats_words($entry) {
        return count_words($entry);
    }

    /**
     * get_stats_sentences
     */
    public static function get_stats_sentences($entry) {
        $items = preg_split('/[!?.]+(?![0-9])/', $entry);
        $items = array_filter($items);
        return count($items);
    }

    /**
     * get_stats_paragraphs
     */
    public static function get_stats_paragraphs($entry) {
        $items = explode("\n", $entry);
        $items = array_filter($items);
        return count($items);
    }

    /**
     * get_stats_uniquewords
     */
    public static function get_stats_uniquewords($entry) {
        $items = core_text::strtolower($entry);
        //$items = strtolower($entry);
        $items = str_word_count($items, 1);
        $items = array_unique($items);
        return count($items);
    }
}