<?php
/**
 * TODO
 * The URL parameter s and c need to become more self explaining. 
 * For some reason we mixed them up so that c is used for the shortener string, 
 * instead for the course id.
 * 
 * @category Moodle
 * @package  Local_policy_overview
 * @author   Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license  GPL https://www.gnu.org/licenses/gpl-3.0.de.html
 * @link     URL shortener, short URL
 */

require_once dirname(__FILE__) . '/../../config.php';   
$context = context_system::instance();
global $USER, $PAGE, $DB, $CFG;

require_login();
$PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot.'/local/policy_overview/index.php');
$PAGE->set_pagelayout('course');
$PAGE->set_title("Zustimmung und Richtlinien");
echo $OUTPUT->header();


$message = '';

// Track the previous page to go back after the changes.
$policy_back = $CFG->wwwroot;
if (isset($_SESSION['policy_back'])) {
    if (isset($_SERVER['HTTP_REFERER']) && !is_null($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'policy.php') === false) {
        $policy_back = $_SERVER['HTTP_REFERER'];
        $_SESSION['policy_back'] = $policy_back;
    } else {
        $policy_back = $_SESSION['policy_back'];
    }
} else {
    if (isset($_SERVER['HTTP_REFERER']) && !is_null($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'policy.php') === false) {
        $policy_back = $_SERVER['HTTP_REFERER'];
        $_SESSION['policy_back'] = $policy_back;
    }
}

// change policy status
if (isset($_GET['policy']) && isset($_GET['status']) && isset($_GET['version'])) {

    $entry = $DB->get_record(
        "tool_policy_acceptances",
        array(
            "userid" => (int)$USER->id,
            "policyversionid" => (int)$_GET['version']
        )
    );

    $time = time();
    if ($entry === false) {
        $lang = 'de_feu';
        $sql = '
            INSERT INTO ' . $CFG->prefix . 'tool_policy_acceptances (
                policyversionid,
                userid,
                status,
                lang,
                usermodified,
                timecreated,
                timemodified
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ';
        $res = $DB->execute(
            $sql,
            array(
                (int)$_GET['version'],
                (int)$USER->id,
                (int)$_GET['status'],
                $lang,
                (int)$USER->id,
                $time,
                $time
            )
        );
    } else {
        $sql = '
            UPDATE ' . $CFG->prefix . 'tool_policy_acceptances 
            SET status=?, timemodified=?
            WHERE policyversionid=? AND userid=?';
        $res = $DB->execute($sql, array((int)$_GET['status'], $time, (int)$_GET['version'], (int)$USER->id));
    }

    $message = 'Eine Richtlinie wurde aktualisiert.';
}

// fetch policies
$query = '
SELECT 	v.name, 
		a.status, 
		a.timecreated as acceptance, 
		v.timecreated as creation, 
		p.id as id, 
		v.id as version
FROM ' . $CFG->prefix . 'tool_policy as p
LEFT JOIN ' . $CFG->prefix . 'tool_policy_acceptances as a 
ON p.currentversionid = a.policyversionid
AND a.userid = ?
INNER JOIN ' . $CFG->prefix . 'tool_policy_versions as v 
ON p.currentversionid = v.id
';
$res = $DB->get_records_sql($query, array((int)$USER->id));
//get_records("tool_policy_acceptances", array("userid" => (int)$USER->id ));
echo '<policy-container>Hallo</policy-container>';
$PAGE->requires->js_call_amd('local_policy_overview/Policy', 'init', array('policies' => $res, 'message' => $message, 'backurl' => $policy_back));
echo $OUTPUT->footer();
