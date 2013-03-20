<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @package Ilios
 *
 * This abstract superclass houses common functionality which seemingly will be needed by
 * a plethora of concrete controllers. The majority of this could be handled just as well
 * through a utilities class, but this seems more architecturally beautiful.
 */
abstract class Ilios_Base_Controller extends CI_Controller
{
    /**
     * Constructor.
     */
    public function __construct ()
    {
        parent::__construct();

        $this->load->model('Audit_Event', 'auditEvent', TRUE);
        $this->load->model('Canned_Queries', 'queries', TRUE);
        $this->load->model('Cohort', 'cohort', TRUE);
        $this->load->model('Competency', 'competency', TRUE);
        $this->load->model('Course', 'course', TRUE);
        $this->load->model('Discipline', 'discipline', TRUE);
        $this->load->model('Learning_Material', 'learningMaterial', TRUE);
        $this->load->model('Mesh', 'mesh', TRUE);
        $this->load->model('Offering', 'offering', TRUE);
        $this->load->model('Program_Year', 'programYear', TRUE);
        $this->load->model('Session', 'iliosSession', TRUE);    // NOT 'session' to avoid collision
        $this->load->model('Session_Type', 'sessionType', TRUE);
        $this->load->model('User', 'user', TRUE);
        $this->load->model('User_Role', 'userRole', TRUE);
        $this->load->model('School', 'school', TRUE);
        $this->load->model('Permission', 'permission', TRUE);
    }

    /**
     * Prints a the JSON-formatted language pack object.
     *
     * Returned is a populated JS data structure and an object 'ilios_i18nVendor' which
     * contains a single method getI18NString(key) to access to the included language-pack
     */
    public function getI18NJavascriptVendor ()
    {
        // no authorization/authentication check whatsoever wanted here!

        $lang = $this->getLangToUse();
        $contentSeparator = " ";   //"\n";
        header("Content-Type: application/javascript");

        echo '/** autogenerated **/' . $contentSeparator;

        $jsArrayName = 'i18nMap';
        $this->languagemap->dumpI18NStringsForLanguageAsJavascript($lang, $jsArrayName, $contentSeparator);

        echo 'var ilios_i18nVendor = {' . $contentSeparator . 'getI18NString: function (key) {';
        echo $contentSeparator. 'return ' . $jsArrayName . '[key]; },' . $contentSeparator;
        echo 'write: function (prefix, key, suffix) {' . $contentSeparator;
        echo 'document.write(prefix + ilios_i18nVendor.getI18NString(key) + suffix); } };';
    }


    /**
     * echos a crafted XML result ready to be received for use as an XHRDataSource
     */
    protected function outputQueryResultsAsXML ($dbQueryResult)
    {
        header("Content-Type: text/xml");
        $this->generateOpeningResultSetXMLElementForResultSize($dbQueryResult->num_rows());
        $this->generateResultBlockXMLForQueryResults($dbQueryResult);
        $this->generateClosingResultSetXMLElement();
    }

    /**
     * Unless you need to craft an XML response covering multiple query results, just use
     *     outputQueryResultsAsXML
     */
    protected function generateOpeningResultSetXMLElementForResultSize ($resultSize)
    {
        echo '<ResultSet totalResultsReturned="' . $resultSize . '" totalResultsAvailable="'
                    . $resultSize . '">';
    }

    /**
     * Unless you need to craft an XML response covering multiple query results, just use
     *     outputQueryResultsAsXML
     */
    protected function generateClosingResultSetXMLElement ()
    {
        echo '</ResultSet>';
    }

    /**
     * Unless you need to craft an XML response covering multiple query results, just use
     *     outputQueryResultsAsXML
     */
    protected function generateResultBlockXMLForQueryResults ($dbQueryResult)
    {
        $nl = "";
        $tab = "";

        foreach ($dbQueryResult->result_array() as $row) {
            echo $tab . '<Result>' . $nl;


            foreach ($row as $tagName => $contents) {
                echo $tab . $tab . '<' . $tagName . '><![CDATA[' . $contents . ']]></'
                                    . $tagName . '>' . $nl;
            }

            echo $tab . '</Result>' . $nl;
        }
    }

    /**
     * @todo add code docs
     */
    protected function getPreferencesArrayForUser ()
    {
        $rhett = array();

        if (! $this->session->userdata('username')) {
            $rhett['py_archiving'] = 'false';
            $rhett['course_archiving'] = 'false';
            $rhett['course_rollover'] = 'false';
            $rhett['lang'] = $this->getLangToUse();
        } else {
            // $userId setting left for future developers should they want to have prefs stored in
            //          the db keyed by user_id
            // $userId = $this->session->userdata('uid');

            $rhett['py_archiving'] = $this->session->userdata('py_archiving') ? 'true' : 'false';
            $rhett['course_archiving'] = $this->session->userdata('course_archiving') ? 'true'
                                                                                      : 'false';
            $rhett['course_rollover'] = $this->session->userdata('course_rollover') ? 'true'
                                                                                    : 'false';
            $rhett['lang'] = $this->getLangToUse();
        }
        return $rhett;
    }

    /**
     * @todo add code docs
     * taken from http://roshanbh.com.np/2007/12/getting-real-ip-address-in-php.html
     */
    protected function getClientIPAddress ()
    {
        $ip = "";

        if (! empty($_SERVER['HTTP_CLIENT_IP'])) {                // shared internet
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        elseif (! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {    // proxy
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }

    /**
     * Of use when we get back a std obj from a db query (like via the first_row() method) and we
     *     want to ship an array back to the client.
     */
    protected function convertStdObjToArray ($stdObj)
    {
        $rhett = $stdObj;

        if (is_array($stdObj) || is_object($stdObj)) {
            $rhett = array();

            foreach ($stdObj as $key => $val) {
                $rhett[$key] = $this->convertStdObjToArray($val);
            }
        }

        return $rhett;
    }

    /**
     * Returns the language key as specified in the application configuration.
     * @return string
     */
    protected function getLangToUse ()
    {
        return $this->config->item('ilios_default_lang_locale');
    }

    /**
     * @todo add code docs
     */
    protected function populateI18NStringsForContentContainerGenerator (&$data, $lang)
    {
        $key = 'general.phrases.publish_all';
        $data['publish_all_string'] = $this->languagemap->getI18NString($key, $lang);

        $key = 'general.phrases.publish_now';
        $data['publish_now_string'] = $this->languagemap->getI18NString($key, $lang);

        $key = 'general.phrases.publish_course';
        $data['publish_course_string'] = $this->languagemap->getI18NString($key, $lang);

        $key = 'general.phrases.publish_session';
        $data['publish_session_string'] = $this->languagemap->getI18NString($key, $lang);

        $key = 'general.phrases.save_all';
        $data['save_all_string'] = $this->languagemap->getI18NString($key, $lang);

        $key = 'general.phrases.save_all_draft';
        $data['save_all_draft_string'] = $this->languagemap->getI18NString($key, $lang);

        $key = 'general.phrases.save_draft';
        $data['save_draft_string'] = $this->languagemap->getI18NString($key, $lang);

        $key = 'general.phrases.reset_form';
        $data['reset_form_string'] = $this->languagemap->getI18NString($key, $lang);
    }

    /**
     * @todo add code docs
     */
    protected function populateForAddNewMembersDialog (&$data, $lang)
    {
        $key = 'general.phrases.add_members';
        $data['add_members_string'] = $this->languagemap->getI18NString($key, $lang);

        $key = 'add_members_dialog.manual_entry';
        $data['manual_entry_string'] = $this->languagemap->getI18NString($key, $lang);

        $key = 'general.user.first_name';
        $data['first_name_string'] = $this->languagemap->getI18NString($key, $lang);

        $key = 'general.user.middle_name';
        $data['middle_name_string'] = $this->languagemap->getI18NString($key, $lang);

        $key = 'general.user.last_name';
        $data['last_name_string'] = $this->languagemap->getI18NString($key, $lang);

        $key = 'general.user.phone';
        $data['phone_string'] = $this->languagemap->getI18NString($key, $lang);

        $key = 'general.user.email';
        $data['email_string'] = $this->languagemap->getI18NString($key, $lang);

        $key = 'general.user.uc_id';
        $data['uc_id_string'] = $this->languagemap->getI18NString($key, $lang);

        $key = 'add_members_dialog.add_user';
        $data['add_user_string'] = $this->languagemap->getI18NString($key, $lang);

        $key = 'add_members_dialog.from_csv';
        $data['from_csv_string'] = $this->languagemap->getI18NString($key, $lang);

        $key = 'general.text.csv_user_upload_1';
        $data['csv_user_upload_1_string'] = $this->languagemap->getI18NString($key, $lang);

        $key = 'general.text.csv_user_upload_2';
        $data['csv_user_upload_2_string'] = $this->languagemap->getI18NString($key, $lang);

        $key = 'general.terms.done';
        $data['done_string'] = $this->languagemap->getI18NString($key, $lang);

        $key = 'general.terms.upload';
        $data['upload_string'] = $this->languagemap->getI18NString($key, $lang);
    }

    /**
     * Returns a non-associative array of ids which represent school_id from the school table which
     *     are associated to the cohorts (cohorts -> program year ->* schools). $cohorts is assumed to
     *     be homogeneous in object representations of rows from the Cohort table.
     */
    protected function getSchoolIdsForCohorts ($cohorts)
    {
        $rhett = array();

        foreach ($cohorts as $cohort) {
            $programYearId = $cohort->program_year_id;

            $stewards = $this->programYear->getStewardsForProgramYear($programYearId);
            if (! is_null($stewards)) {
                foreach ($stewards as $steward) {
                    $schoolId = ($steward['parent_school_id'] == -1) ? $steward['row_id']
                                                                     : $steward['parent_school_id'];

                    array_push($rhett, $schoolId);
                }
            }
        }
        return array_unique($rhett);
    }



    /**
     * Searches the user store for enabled users matching a given name/name-fragment
     * and who are have been assigned the "Faculty" role
     * @param string $name the user name/name-fragment
     * @return CI_DB_result a db query result object
     * @todo replace hardwired role-title text with constant
     */
    protected function getFacultyFilteredOnNameMatch ($name)
    {
        return $this->user->getUsersFilteredOnNameMatchWithRoleTitle($name, 'Faculty');
    }

    /**
     * Searches the user store for enabled users matching a given name/name-fragment
     * and who are have been assigned the "Course Director" role
     * @param string $name the user name/name-fragment
     * @return CI_DB_result a db query result object
     * @todo replace hardwired role-title text with constant
     */
    protected function getDirectorsFilteredOnNameMatch ($name)
    {
        return $this->user->getUsersFilteredOnNameMatchWithRoleTitle($name, 'Course Director');
    }

    /**
     * @todo add code docs
     * @param string $filename
     * @param boolean $returnByteCount
     * @return boolean|number
     */
    protected function streamFileContentsChunked ($filename, $returnByteCount = true)
    {
        $chunkSizeInBytes = 1 * (1024 * 1024);
        $buffer = '';
        $bytesStreamed = 0;

        $handle = fopen($filename, 'rb');

        if ($handle === false) {
            return false;
        }

        while (!feof($handle)) {
            $buffer = fread($handle, $chunkSizeInBytes);
            echo $buffer;
            ob_flush();
            flush();

            if ($returnByteCount) {
                $bytesStreamed += strlen($buffer);
            }
        }

        $status = fclose($handle);

        if ($returnByteCount && $status) {
            return $bytesStreamed;
        }

        return $status;
    }

    /**
     * Aggregates a complete representation of a course for a given id
     * from its various sub-components (sessions, cohorts etc.)
     * @param integer $courseId
     * @param boolean $includePublishedOnly pass TRUE to exclude non-fully-published course components (sessions, learning materials etc).
     * @param boolean $sortSessionsByStartDate pass TRUE to sort sessions by start date, or FALSE to sort session by title
     * @return array
     * @todo flesh out doc-block
     */
    protected function _buildCourseTree ($courseId, $includePublishedOnly = true, $sortSessionsByStartDate = false)
    {
        $rhett = array();

        $userId = $this->session->userdata('uid');
        $activeSchoolId = $this->session->userdata('school_id');

        $courseRow = $this->course->getRowForPrimaryKeyId($courseId);
        $rhett['course_id'] = $courseRow->course_id;
        $rhett['title'] = $courseRow->title;
        $rhett['start_date'] = $courseRow->start_date;
        $rhett['end_date'] = $courseRow->end_date;
        $rhett['publish_event_id'] = $courseRow->publish_event_id;
        $rhett['course_level'] = $courseRow->course_level;
        $rhett['year'] = $courseRow->year;
        $rhett['external_id'] = $courseRow->external_id;
        $rhett['unique_id'] = $this->course->getUniqueId($courseId);
        $rhett['locked'] = $courseRow->locked;
        $rhett['published_as_tbd'] = $courseRow->published_as_tbd;
        $rhett['clerkship_type_id'] = $courseRow->clerkship_type_id;


        $results = $this->course->getProgramCohortDetailsForCourse($courseId);
        if (is_null($results)) {
            $rhett['error'] = 'Unable to fetch cohorts for course.';
        } else {
            $rhett['cohorts'] = array();
            for ($i = 0, $n = count($results); $i < $n; $i++) {
                $cohort = $results[$i];
                $cohort['is_active_school'] = ((int) $activeSchoolId === (int) $cohort['school_id']) ? true : false;
                $rhett['cohorts'][] = $cohort;
            }
        }

        if (! isset($results['error'])) {

            $results = $this->course->getAppliedCompetenciesForCourse($courseId);
            if (is_null($results)) {
                $rhett['error'] = 'Unable to fetch competencies for course.';
            } else {
                $rhett['competencies'] = $results;
            }
        }

        if (! isset($results['error'])) {
            $results = $this->course->getDisciplinesForCourse($courseId);
            if (is_null($results)) {
                $rhett['error'] = 'Unable to fetch disciplines for course.';
            } else {
                $rhett['disciplines'] = $results;
            }
        }

        if (! isset($results['error'])) {
            $results = $this->course->getDirectorsForCourse($courseId);
            if (is_null($results)) {
                $rhett['error'] = 'Unable to fetch directors for course.';
            } else {
                $rhett['directors'] = $results;
            }
        }

        if (! isset($results['error'])) {
            $results = $this->course->getMeshTermsForCourse($courseId);
            if (is_null($results)) {
                $rhett['error'] = 'Unable to fetch MeSH terms for course.';
            } else {
                $rhett['mesh_terms'] = $results;
            }
        }

        if (! isset($results['error'])) {
            $results = $this->course->getObjectivesForCourse($courseId);
            if (is_null($results)) {
                $rhett['error'] = 'Unable to fetch objectives for course.';
            } else {
                $rhett['objectives'] = $results;
            }
        }

        if (! isset($results['error'])) {
            $results = $this->learningMaterial->getLearningMaterialsForCourse($courseId, $includePublishedOnly);
            if (is_null($results)) {
                $rhett['error'] = 'Unable to fetch learning materials for course.';
            } else {
                $rhett['learning_materials'] = $results;
            }
        }

        if (! isset($results['error'])) {
            $results = $this->iliosSession->getSessionsForCourse($courseId, $userId, $includePublishedOnly, $includePublishedOnly, $includePublishedOnly, $sortSessionsByStartDate);
            if (is_null($results)) {
                $rhett['error'] = 'Unable to fetch sessions for course.';
            } else {
                $rhett['sessions'] = $results;
            }
            $rhett['learners'] = $this->queries->getLearnerGroupIdAndTitleForCourse($courseId);
        }
        return $rhett;
    }

    /**
     * Returns all competencies and their subdomains, grouped by their owning schools.
     * The currently "active" school (that's the school currently active in the user session) is indicated as such.
     * @return array a nested associative array of school -> competency -> subcompetency data
     */
    protected function _getSchoolCompetencies ()
    {
        $rhett = array();

        $activeSchoolId = $this->session->userdata('school_id');
        $schoolIds = $this->school->getAllSchools();

        foreach ($schoolIds as $schoolId) {
            $competencies = $this->competency->getCompetencyTree($schoolId);
            $rhett[] = array(
                'school_id' => $schoolId,
                'competencies' => $competencies,
                // indicate which school is the currently active one
                'is_active_school' => ($schoolId === $activeSchoolId) ? true : false
            );
        }
        return $rhett;
    }

    /**
     * Prints a JSON-formatted array with a generic, i18ned "access denied" error message,
     * keyed off by "error".
     *
     * E.g.
     * <code>["error": "You are not allowed to perform this action"]</code>
     *
     * @param String $lang the language key
     */
    protected function _printAuthorizationFailedXhrResponse ($lang)
    {
        $this->_printErrorXhrResponse('general.error.not_allowed_to_perform_action', $lang);
    }

    /**
     * Prints a JSON-formatted array with an error message for a given message key in a given language,
     * keyed off by "error".
     *
     * @param String $key the message key
     * @param String $lang the language key
     */
    protected function _printErrorXhrResponse ($key, $lang)
    {
        $msg = $this->languagemap->getI18NString($key, $lang);
        $rhett['error'] = $msg;
        header("Content-Type: text/plain");
        echo json_encode($rhett);
    }

    /**
     * Prints the "(access) forbidden" page, displaying a given "access denied" message.
     * @param string $lang the language key
     * @param array $data data array passed to the page template
     * @param string $key the message key
     * @todo conceptually, this is a special case of an error page. generalize this.
     */
    protected function _viewAccessForbiddenPage ($lang, $data = array(), $key = 'general.error.not_allowed_to_view_page')
    {
        $data['forbidden_warning_text'] = $this->languagemap->getI18NString($key, $lang);
        $this->load->view('common/forbidden', $data);
        return;
    }

    /**
     * Toggles the "active" school in the user session to the given school.
     * @param int $schoolId the school id
     * @return boolean TRUE on success, FALSE on failure
     */
    protected function _setActiveSchool ($schoolId)
    {
        $valid_schools = $this->_getAvailableSchools();

        if ($valid_schools && in_array($schoolId, $valid_schools)) {
            $this->session->set_userdata('school_id', $schoolId);
            return true;
        }
        return false;
    }

    /**
     * Retrieves a list of school identifiers that the current user has read permissions for.
     * Note: this will always include the user's "primary school", regardless of read permissions (or lack thereof)
     * set for that school.
     * @return array a list of school ids.
     */
    protected function _getAvailableSchools ()
    {
        $userId = $this->session->userdata('uid');
        $primarySchoolId = $this->session->userdata('primary_school_id');
        $allSchools = $this->school->getAllSchools();
        $availSchools = array("$primarySchoolId");

        foreach ($this->permission->getPermissionsForUser($userId) as $pObject) {
            if (isset($pObject['object']) && ($pObject['object']['object_name'] == 'school') &&
                isset($pObject['can_read']) && $pObject['can_read'] &&
                ($pObject['object']['school_id'] != $primarySchoolId) &&
                in_array($pObject['object']['school_id'], $allSchools)) {

                $availSchools[] = $pObject['object']['school_id'];
            }
        }
        return $availSchools;
    }
}
