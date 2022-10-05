<?php

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/upgradelib.php');

function xmldb_local_policy_overview_upgrade($oldversion) {
    global $CFG, $DB; 
    $dbman = $DB->get_manager();
    return true;
}
