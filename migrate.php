<?php
/********************************************************
 *
 ********************************************************/
// set to true for a trial run that just logs the updates
define('DRYRUN', true);
define('LOGFILE', __DIR__.'/migrate.log');

 // load the migrator class
require_once dirname(__FILE__) . '/libraries/migrator.php';

// create a migration instance
$migrator = new migrator(
	'localhost', 'redmine_old', 'root', '',   // migrate from
	'localhost', 'redmine_new', 'root', ''    // migrate to
);

/*
 * Mapping between id's in the old and the new database, for already
 * existing records. Mapping are old => new.
 */

// define the enumeration map
$migrator->setEnumerationsMapping(array(
));

// define the status map (from the issue_statusses table)
$migrator->setStatusMapping(array(
));

// define the issue trackers
$migrator->setTrackerMapping(array(
));

// define the roles map
$migrator->setRolesMapping(array(
));

// map users already defined in the destination database
// note, all users not mapped will be dynamically added!
$migrator->setUsersMapping(array(
));

/**
 * Start the project migration
 */

// pass the project id's to migrate in the array
$migrator->migrateProject(array(
));
