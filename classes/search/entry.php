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
 * Diary entries search.
 *
 * @package    mod_diary
 * @copyright  2019 AL Rachels (drachels@drachels.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_diary\search;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/diary/lib.php');

/**
 * Diary entries search.
 *
 * @package    mod_diary
 * @copyright  2019 AL Rachels (drachels@drachels.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class entry extends \core_search\base_mod {

    /**
     * Returns recordset containing required data for indexing diary entries.
     *
     * @param int $modifiedfrom timestamp
     * @return moodle_recordset
     */
    public function get_recordset_by_timestamp($modifiedfrom = 0) {
        global $DB;

        $sql = "SELECT de.*, j.course FROM {diary_entries} de
                  JOIN {diary} d ON d.id = de.diary
                WHERE de.modified >= ? ORDER BY de.modified ASC";
        return $DB->get_recordset_sql($sql, array($modifiedfrom));
    }

    /**
     * Returns the documents associated with this diary entry id.
     *
     * @param stdClass $entry diary entry.
     * @param array    $options
     * @return \core_search\document
     */
    public function get_document($entry, $options = array()) {
        global $DB;

        try {
            $cm = $this->get_cm('diary', $entry->diary, $entry->course);
            $context = \context_module::instance($cm->id);
        } catch (\dml_missing_record_exception $ex) {
            // Notify it as we run here as admin, we should see everything.
            debugging('Error retrieving mod_diary ' . $entry->id . ' document, not all required data is available: ' .
                $ex->getMessage(), DEBUG_DEVELOPER);
            return false;
        } catch (\dml_exception $ex) {
            // Notify it as we run here as admin, we should see everything.
            debugging('Error retrieving mod_diary' . $entry->id . ' document: ' . $ex->getMessage(), DEBUG_DEVELOPER);
            return false;
        }

        // Prepare associative array with data from DB.
        $doc = \core_search\document_factory::instance($entry->id, $this->componentname, $this->areaname);

        // Not a nice solution to copy a subset of the content but I don't want
        // to use a kind of "Firstname Lastname diary entry"
        // because of i18n (the entry can be searched by both the student and
        // any course teacher (they all have different languages).
        $doc->set('title', shorten_text(content_to_text($entry->text, $entry->format), 50));
        $doc->set('content', content_to_text($entry->text, $entry->format));
        $doc->set('contextid', $context->id);
        $doc->set('courseid', $entry->course);
        $doc->set('userid', $entry->userid);
        $doc->set('owneruserid', \core_search\manager::NO_OWNER_ID);
        $doc->set('modified', $entry->modified);

        // Check if this document should be considered new.
        if (isset($options['lastindexedtime']) && ($options['lastindexedtime'] < $entry->modified)) {
            // If the document was created after the last index time, it must be new.
            $doc->set_is_new(true);
        }

        return $doc;
    }

    /**
     * Whether the user can access the document or not.
     *
     * @throws \dml_missing_record_exception
     * @throws \dml_exception
     * @param int $id Glossary entry id
     * @return bool
     */
    public function check_access($id) {
        global $USER;

        try {
            $entry = $this->get_entry($id);
            $cminfo = $this->get_cm('diary', $entry->diary, $entry->course);
        } catch (\dml_missing_record_exception $ex) {
            return \core_search\manager::ACCESS_DELETED;
        } catch (\dml_exception $ex) {
            return \core_search\manager::ACCESS_DENIED;
        }

        if (!$cminfo->uservisible) {
            return \core_search\manager::ACCESS_DENIED;
        }

        if ($entry->userid != $USER->id && !has_capability('mod/diary:manageentries', $cminfo->context)) {
            return \core_search\manager::ACCESS_DENIED;
        }

        return \core_search\manager::ACCESS_GRANTED;
    }

    /**
     * Link to diary entry.
     *
     * @param \core_search\document $doc
     * @return \moodle_url
     */
    public function get_doc_url(\core_search\document $doc) {
        global $USER;

        $entry = $this->get_entry($doc->get('itemid'));
        $contextmodule = \context::instance_by_id($doc->get('contextid'));

        $entryuserid = $doc->get('userid');
        if ($entryuserid == $USER->id) {
            $url = '/mod/diary/view.php';
        } else {
            // Teachers see student's entries in the report page.
            $url = '/mod/diary/report.php#entry-' . $entryuserid;
        }
        return new \moodle_url($url, array('id' => $contextmodule->instanceid));
    }

    /**
     * Link to the diary.
     *
     * @param \core_search\document $doc
     * @return \moodle_url
     */
    public function get_context_url(\core_search\document $doc) {
        $contextmodule = \context::instance_by_id($doc->get('contextid'));
        return new \moodle_url('/mod/diary/view.php', array('id' => $contextmodule->instanceid));
    }

    /**
     * Returns the specified diary entry checking the internal cache.
     *
     * Store minimal information as this might grow.
     *
     * @throws \dml_exception
     * @param int $entryid
     * @return stdClass
     */
    protected function get_entry($entryid) {
        global $DB;

        return $DB->get_record_sql("SELECT de.*, j.course FROM {diary_entries} de
                                      JOIN {diary} j ON d.id = de.diary
                                    WHERE de.id = ?", array('id' => $entryid), MUST_EXIST);
    }
}
