<?php

require_once dirname(__FILE__) . '/dbmysql.php';

// setup the log
if (DRYRUN) {
	defined('LOGFILE') or define('LOGFILE', realpath(__DIR__.'/../migrate.log'));
	if (is_file(LOGFILE)) {
		if (!is_writable(LOGFILE)) {
			throw new exception('Unable to clear existing LOG file "'.LOGFILE.'", no write access!');
		}
		unlink(LOGFILE);
	}
	if (!is_writable(dirname(LOGFILE))) {
		throw new exception('Unable to write to the LOG file "'.LOGFILE.'"');
	}
}

if (defined('FILESBACKUP'))
{
	if (is_file(FILESBACKUP)) {
		if (!is_writable(FILESBACKUP)) {
			throw new exception('Unable to clear existing files backup file "'.FILESBACKUP.'", no write access!');
		}
		unlink(FILESBACKUP);
	}
	if (!is_writable(dirname(FILESBACKUP))) {
		throw new exception('Unable to write to the files backup file "'.FILESBACKUP.'"');
	}
	file_put_contents(FILESBACKUP, '#! /bin/bash
#
# Create a backup tarball for all attachments migrated
#

# Path to your redmine installation
# Defaults to current directory
REDMINE=.
ARCHIVE=/tmp/redmine-files.tar.gz

# check if the redmine patch is ok
if { ! -f $REDMINE/lib/redmine.rb ]; then
	echo "Directory $REDMINE does not contain a Redmine installation!"
	exit
fi

if [ ! -d $REDMINE/files ]; then
	echo "Directory $REDMINE does not contain the files folder!"
	exit
fi

TMPDIR=`mktemp -d 2>/dev/null || mktemp -d -t "tmpdir"`

');
}


class migrator
{
	/**
	 * @var DBMysql
	 */
	protected $dbOld = null;

	/**
	 * @var DBMysql
	 */
	protected $dbNew = null;

	protected $usersMapping = array();
	protected $projectsMapping = array();
	protected $issuesMapping = array();
	protected $issuesParentsMapping = array();
	protected $issuesRelationsMapping = array();
	protected $timeEntriesMapping = array();
	protected $enumerationsMapping = array();
	protected $statusMapping = array();
	protected $trackersMapping = array();
	protected $categoriesMapping = array();
	protected $versionsMapping = array();
	protected $journalsMapping = array();
	protected $modulesMapping = array();
	protected $watchersMapping = array();
	protected $messagesMapping = array();
	protected $boardsMapping = array();
	protected $newsMapping = array();
	protected $documentsMapping = array();
	protected $queryMapping = array();
	protected $mailReminderMapping = array();
	protected $wikisMapping = array();
	protected $wikipagesMapping = array();
	protected $wikiContentVersionsMapping = array();
	protected $wikiContentsMapping = array();
	protected $membersMapping = array();
	protected $rolesMapping = array();

	protected $attachmentCount = 0;

	public function __construct($host1, $db1, $user1, $pass1, $host2, $db2, $user2, $pass2)
	{
		$this->dbOld = new DBMysql($host1, $user1, $pass1);
		$this->dbOld->connect($db1);

		$this->dbNew = new DBMysql($host2, $user2, $pass2);
		$this->dbNew->connect($db2);
	}

	protected function replaceProject($idProjectOld)
	{
		if ($idProjectOld == null) {
			return null;
		}

		if (!isset($this->projectsMapping[$idProjectOld])) {
			throw new Exception("No mapping defined for old project id '$idProjectOld'");
		}

		return $this->projectsMapping[$idProjectOld];
	}

	protected function replaceUser($idUserOld)
	{
		if ($idUserOld == null) {
			return null;
		}

		if (!isset($this->usersMapping[$idUserOld])) {
			// users can be anywhere, migrate them dynamically
			$this->migrateUsers($idUserOld);
			if (!isset($this->usersMapping[$idUserOld])) {
				throw new Exception("No mapping defined for old user id '$idUserOld'");
			}
		}

		return $this->usersMapping[$idUserOld];
	}

	protected function replaceRole($idRoleOld)
	{
		if ($idRoleOld == null) {
			return null;
		}

		if (!isset($this->rolesMapping[$idRoleOld])) {
			// migrate them dynamically when needed
			$this->migrateRoles($idRoleOld);
			if (!isset($this->rolesMapping[$idRoleOld])) {
				throw new Exception("No role defined for old role id '$idRoleOld'");
			}
		}

		return $this->rolesMapping[$idRoleOld];
	}

	protected function replaceMessage($idMessageOld)
	{
		if ($idMessageOld == null) {
			return null;
		}

		if (!isset($this->messagesMapping[$idMessageOld])) {
			throw new Exception("No mapping defined for old message id '$idMessageOld'");
		}

		return $this->messagesMapping[$idMessageOld];
	}

	protected function replaceEnumeration($idEnumerationOld)
	{
		if ($idEnumerationOld == null) {
			return null;
		}

		if (!isset($this->enumerationsMapping[$idEnumerationOld])) {
			// migrate them dynamically when needed
			$this->migrateEnumerations($idEnumerationOld);
			if (!isset($this->enumerationsMapping[$idEnumerationOld])) {
				throw new Exception("No mapping defined for old enumeration id '$idEnumerationOld'");
			}
		}

		return $this->enumerationsMapping[$idEnumerationOld];
	}

	protected function replaceIssue($idIssueOld)
	{
		if ($idIssueOld == null) {
			return null;
		}

		if (!isset($this->issuesMapping[$idIssueOld])) {
			// migrate dynamically when needed
			$this->migrateIssue($idIssueOld);
			if (!isset($this->issuesMapping[$idIssueOld])) {
				throw new Exception("No issue defined for old issue id '$idIssueOld'");
			}
		}

		return $this->issuesMapping[$idIssueOld];
	}

	protected function replaceStatus($idStatusOld)
	{
		if ($idStatusOld == null) {
			return null;
		}

		if (!isset($this->statusMapping[$idStatusOld])) {
			throw new Exception("No status defined for old status id '$idStatusOld'");
		}

		return $this->statusMapping[$idStatusOld];
	}

	protected function replaceTracker($idTrackerOld)
	{
		if ($idTrackerOld == null) {
			return null;
		}

		if (!isset($this->trackersMapping[$idTrackerOld])) {
			// migrate them dynamically when needed
			$this->migrateTrackers($idTrackerOld);
			if (!isset($this->trackersMapping[$idTrackerOld])) {
				throw new Exception("No tracker defined for old tracker id '$idTrackerOld'");
			}
		}

		return $this->trackersMapping[$idTrackerOld];
	}

	protected function replaceJournal($idJournalOld)
	{
		if ($idJournalOld == null) {
			return null;
		}

		if (!isset($this->journalsMapping[$idJournalOld])) {
			throw new Exception("No journal entry defined for old journal id '$idJournalOld'");
		}

		return $this->journalsMapping[$idJournalOld];
	}

	protected function replaceCategory($idCategoryOld)
	{
		if ($idCategoryOld == null) {
			return null;
		}

		if (!isset($this->categoriesMapping[$idCategoryOld])) {
			throw new Exception("No category defined for old category id '$idCategoryOld'");
		}

		return $this->categoriesMapping[$idCategoryOld];
	}

	protected function replaceVersion($idVersionOld)
	{
		if ($idVersionOld == null) {
			return null;
		}

		if (!isset($this->versionsMapping[$idVersionOld])) {
			throw new Exception("No version defined for old version id '$idVersionOld'");
		}

		return $this->versionsMapping[$idVersionOld];
	}

	protected function replaceWiki($idWikiOld)
	{
		if ($idWikiOld == null) {
			return null;
		}

		if (!isset($this->wikisMapping[$idWikiOld])) {
			throw new Exception("No wiki defined for old wiki id '$idWikiOld'");
		}

		return $this->wikisMapping[$idWikiOld];
	}

	protected function replaceWikipage($idWikipageOld)
	{
		if ($idWikipageOld == null) {
			return null;
		}

		if (!isset($this->wikipagesMapping[$idWikipageOld])) {
			throw new Exception("No wiki page defined for old wiki page id '$idWikipageOld'");
		}

		return $this->wikipagesMapping[$idWikipageOld];
	}

	protected function replaceWikicontent($idWikicontentOld)
	{
		if ($idWikicontentOld == null) {
			return null;
		}

		if (!isset($this->wikiContentsMapping[$idWikicontentOld])) {
			throw new Exception("No wiki content defined for old wiki content id '$idWikicontentOld'");
		}

		return $this->wikiContentsMapping[$idWikicontentOld];
	}

	protected function replaceNews($idNewsOld)
	{
		if ($idNewsOld == null) {
			return null;
		}

		if (!isset($this->newsMapping[$idNewsOld])) {
			throw new Exception("No news page defined for old news id '$idNewsOld'");
		}

		return $this->newsMapping[$idNewsOld];
	}

	protected function replaceQuery($idQueryOld)
	{
		if ($idQueryOld == null) {
			return null;
		}

		if (!isset($this->queryMapping[$idQueryOld])) {
			throw new Exception("No query defined for old query id '$idQueryOld'");
		}

		return $this->queryMapping[$idQueryOld];
	}

	protected function replaceDocument($idDocumentOld)
	{
		if ($idDocumentOld == null) {
			return null;
		}

		if (!isset($this->documentsMapping[$idDocumentOld])) {
			throw new Exception("No document defined for old document id '$idDocumentOld'");
		}

		return $this->documentsMapping[$idDocumentOld];
	}

	protected function replaceBoard($idBoardOld)
	{
		if ($idBoardOld == null) {
			return null;
		}

		if (!isset($this->boardsMapping[$idBoardOld])) {
			throw new Exception("No board defined for old board id '$idBoardOld'");
		}

		return $this->boardsMapping[$idBoardOld];
	}

	protected function replaceMember($idMemberOld)
	{
		if ($idMemberOld == null) {
			return null;
		}

		if (!isset($this->membersMapping[$idMemberOld])) {
			throw new Exception("No member defined for old member id '$idMemberOld'");
		}

		return $this->membersMapping[$idMemberOld];
	}

	protected function migrateUsers($idUserOld)
	{
		// migrate the user
		$result = $this->dbOld->select('users', array('id' => $idUserOld));
		$usersOld = $this->dbOld->getAssocArrays($result);
		foreach ($usersOld as $userOld) {
			unset($userOld['id']);
			$idUserNew = $this->dbNew->insert('users', $userOld);
			$this->usersMapping[$idUserOld] = $idUserNew;

			// migrate the users preferences
			$result = $this->dbOld->select('user_preferences', array('user_id' => $idUserOld));
			$userPrefs = $this->dbOld->getAssocArrays($result);
			foreach ($userPrefs as $userPref) {
				unset($userPref['id']);
				$userPref['user_id'] = $idUserNew;
				$this->dbNew->insert('user_preferences', $userPref);
			}

			// migrate the users email addresses
			$result = $this->dbOld->select('email_addresses', array('user_id' => $idUserOld));
			$userEmails = $this->dbOld->getAssocArrays($result);
			foreach ($userEmails as $userEmail) {
				unset($userEmail['id']);
				$userEmail['user_id'] = $idUserNew;
				$this->dbNew->insert('email_addresses', $userEmail);
			}

			// migrate the users groups and group memberships
			$result = $this->dbOld->select('groups_users', array('user_id' => $idUserOld));
			$userGroups = $this->dbOld->getAssocArrays($result);
			foreach ($userGroups as $userGroup) {
				$userGroup['group_id'] = $this->replaceUser($userGroup['group_id']);
				unset($userGroup['id']);
				$userGroup['user_id'] = $idUserNew;
				$this->dbNew->insert('groups_users', $userGroup);
			}
		}
	}

	protected function migrateRoles($idRoleOld)
	{
		// migrate the role
		$result = $this->dbOld->select('roles', array('id' => $idRoleOld));
		$rolesOld = $this->dbOld->getAssocArrays($result);
		foreach ($rolesOld as $roleOld) {
			// check if this role already exists
			$result = $this->dbNew->select('roles', array('name' => $roleOld['name']));
			$rolesNew = $this->dbNew->getAssocArray($result);
			if ($rolesNew) {
				$idRoleNew = $rolesNew ['id'];
			} else {
				unset($roleOld['id']);
				$idRoleNew = $this->dbNew->insert('roles', $roleOld);
			}
			$this->rolesMapping[$idRoleOld] = $idRoleNew;
		}
	}

	protected function migrateTrackers($idTrackerOld)
	{
		// migrate the tracker
		$result = $this->dbOld->select('trackers', array('id' => $idTrackerOld));
		$trackersOld = $this->dbOld->getAssocArrays($result);
		foreach ($trackersOld as $trackerOld) {
			// check if this tracker already exists
			$result = $this->dbNew->select('trackers', array('name' => $trackerOld['name']));
			$trackersNew = $this->dbNew->getAssocArray($result);
			if ($trackersNew) {
				$idTrackerNew = $trackersNew ['id'];
			} else {
				unset($trackerOld['id']);
				$idTrackerNew = $this->dbNew->insert('trackers', $trackerOld);
			}
			$this->trackersMapping[$idTrackerOld] = $idTrackerNew;
		}
	}

	protected function migrateEnumerations($idEnumerationOld)
	{
		// migrate the enumeration
		$result = $this->dbOld->select('enumerations', array('id' => $idEnumerationOld));
		$enumerationsOld = $this->dbOld->getAssocArrays($result);
		foreach ($enumerationsOld as $enumerationOld) {
			unset($enumerationOld['id']);

			// Update fields
			$enumerationOld['is_default'] = 0;
			$enumerationOld['position'] = 99999;

			$idEnumerationNew = $this->dbNew->insert('enumerations', $enumerationOld);
			$this->enumerationsMapping[$idEnumerationOld] = $idEnumerationNew;
		}
	}

	protected function migrateCategories($idProjectOld)
	{
		$result = $this->dbOld->select('issue_categories', array('project_id' => $idProjectOld));
		$categoriesOld = $this->dbOld->getAssocArrays($result);
		foreach ($categoriesOld as $categoryOld) {
			$idCategoryOld = $categoryOld['id'];
			unset($categoryOld['id']);
			$categoryOld['project_id'] = $this->replaceProject($idProjectOld);
			$categoryOld['assigned_to_id'] = $this->replaceUser($categoryOld['assigned_to_id']);

			$idCategoryNew = $this->dbNew->insert('issue_categories', $categoryOld);
			$this->categoriesMapping[$idCategoryOld] = $idCategoryNew;
		}
	}

	protected function migrateVersions($idProjectOld)
	{
		$result = $this->dbOld->select('versions', array('project_id' => $idProjectOld));
		$versionsOld = $this->dbOld->getAssocArrays($result);
		foreach ($versionsOld as $versionOld) {
			$idVersionOld = $versionOld['id'];
			unset($versionOld['id']);
			$versionOld['project_id'] = $this->replaceProject($idProjectOld);

			$idVersionNew = $this->dbNew->insert('versions', $versionOld);
			$this->versionsMapping[$idVersionOld] = $idVersionNew;

			$this->migrateAttachments($idVersionOld, $idVersionNew, 'Version');
			$this->migrateWatchers($idVersionOld, $idVersionNew, 'Version');
		}
	}

	protected function migrateJournals($idIssueOld)
	{
		$result = $this->dbOld->select('journals', array('journalized_id' => $idIssueOld));
		$journalsOld = $this->dbOld->getAssocArrays($result);
		foreach ($journalsOld as $journal) {
			$idJournalOld = $journal['id'];
			unset($journal['id']);

			// Update fields
			$journal['user_id'] = $this->replaceUser($journal['user_id']);
			$journal['journalized_id'] = $this->replaceIssue($idIssueOld);

			$idJournalNew = $this->dbNew->insert('journals', $journal);
			$this->journalsMapping[$idJournalOld] = $idJournalNew;

			$this->migrateJournalsDetails($idJournalOld);
		}
	}

	protected function migrateTimeEntries($idProjectOld)
	{
		$result = $this->dbOld->select('time_entries', array('project_id' => $idProjectOld));
		$timeEntriesOld = $this->dbOld->getAssocArrays($result);
		foreach ($timeEntriesOld as $timeEntry) {
			$idTimeEntryOld = $timeEntry['id'];
			unset($timeEntry['id']);

			// Update fields
			$timeEntry['project_id'] = $this->replaceProject($timeEntry['project_id']);
			$timeEntry['issue_id'] = $this->replaceIssue($timeEntry['issue_id']);
			$timeEntry['user_id'] = $this->replaceUser($timeEntry['user_id']);
			$timeEntry['activity_id'] = $this->replaceEnumeration($timeEntry['activity_id']);

			$idTimeEntryNew = $this->dbNew->insert('time_entries', $timeEntry);
			$this->timeEntriesMapping[$idTimeEntryOld] = $idTimeEntryNew;
		}
	}

	protected function migrateModules($idProjectOld)
	{
		$result = $this->dbOld->select('enabled_modules', array('project_id' => $idProjectOld));
		$modulesOld = $this->dbOld->getAssocArrays($result);
		foreach ($modulesOld as $module) {
			$idModuleOld = $module['id'];
			unset($module['id']);

			// Update fields
			$module['project_id'] = $this->replaceProject($module['project_id']);

			$idModuleNew = $this->dbNew->insert('enabled_modules', $module);
			$this->modulesMapping[$idModuleOld] = $idModuleNew;
		}
	}

	protected function migrateJournalsDetails($idJournalOld)
	{
		$result = $this->dbOld->select('journal_details', array('journal_id' => $idJournalOld));
		$journalDetailsOld = $this->dbOld->getAssocArrays($result);
		foreach ($journalDetailsOld as $journalDetail) {
			unset($journalDetail['id']);

			// Update fields
			$journalDetail['journal_id'] = $this->replaceJournal($idJournalOld);

			// Update fields
			if (in_array($journalDetail['prop_key'], array('parent_id', 'label_relates_to', 'label_copied_to', 'label_copied_from'))) {
				$journalDetail['old_value'] = $this->replaceIssue($journalDetail['old_value']);
				$journalDetail['value'] = $this->replaceIssue($journalDetail['value']);
			}
			if ($journalDetail['prop_key'] == 'assigned_to_id') {
				$journalDetail['old_value'] = $this->replaceUser($journalDetail['old_value']);
				$journalDetail['value'] = $this->replaceUser($journalDetail['value']);
			}
			if ($journalDetail['prop_key'] == 'tracker_id') {
				try {
					$journalDetail['old_value'] = $this->replaceTracker($journalDetail['old_value']);
					$journalDetail['value'] = $this->replaceTracker($journalDetail['value']);
				} catch (\Exception $e) {
					// ignore
				}
			}
			if ($journalDetail['prop_key'] == 'status_id') {
				try {
					$journalDetail['old_value'] = $this->replaceStatus($journalDetail['old_value']);
					$journalDetail['value'] = $this->replaceStatus($journalDetail['value']);
				} catch (\Exception $e) {
					// ignore
				}
			}
			if ($journalDetail['prop_key'] == 'priority_id') {
				try {
					$journalDetail['old_value'] = $this->replaceEnumeration($journalDetail['old_value']);
					$journalDetail['value'] = $this->replaceEnumeration($journalDetail['value']);
				} catch (\Exception $e) {
					// ignore
				}
			}

			$this->dbNew->insert('journal_details', $journalDetail);
		}
	}

	protected function migrateAttachments($idOld, $idNew, $type)
	{
		$result = $this->dbOld->select('attachments', array('container_id' => $idOld, 'container_type' => $type));
		$attachmentsOld = $this->dbOld->getAssocArrays($result);
		foreach ($attachmentsOld as $aOld) {
			unset($aOld['id']);
			$aOld['container_id'] = $idNew;

			if (defined('FILESBACKUP'))
			{
				file_put_contents(FILESBACKUP, 'cp $REDMINE/files/'.$aOld['disk_filename'].' $TMPDIR'.PHP_EOL, FILE_APPEND);
			}

			// Update fields for new version of issue
			$aOld['author_id'] = $this->replaceUser($aOld['author_id']);

			$idANew = $this->dbNew->insert('attachments', $aOld);
			$this->attachmentCount++;
		}
	}

	protected function migrateWatchers($idOld, $idNew, $type)
	{
		$result = $this->dbOld->select('watchers', array('watchable_id' => $idOld, 'watchable_type' => $type));
		$watchersOld = $this->dbOld->getAssocArrays($result);
		foreach ($watchersOld as $watcherOld) {
			$idWatcherOld = $watcherOld['id'];
			unset($watcherOld['id']);
			$watcherOld['watchable_id'] = $idNew;

			// Update fields for watchers
			$watcherOld['user_id'] = $this->replaceUser($watcherOld['user_id']);

			$idWatcherNew = $this->dbNew->insert('watchers', $watcherOld);
			$this->watchersMapping[$idWatcherOld] = $idWatcherNew;
		}
	}

	protected function migrateQueries($idProjectOld)
	{
		$result = $this->dbOld->select('queries', array('project_id' => $idProjectOld));
		$queriesOld = $this->dbOld->getAssocArrays($result);
		foreach ($queriesOld as $queryOld) {
			$idQueryOld = $queryOld['id'];
			unset($queryOld['id']);

			// Update fields for new version of queries
			$queryOld['project_id'] = $this->replaceProject($idProjectOld);
			$queryOld['user_id'] = $this->replaceUser($queryOld['user_id']);

			$idQueryNew = $this->dbNew->insert('queries', $queryOld);
			$this->queryMapping[$idQueryOld] = $idQueryNew;
		}
	}

	protected function migrateMailReminders($idProjectOld)
	{
		$result = $this->dbOld->select('mail_reminders', array('project_id' => $idProjectOld));
		$remindersOld = $this->dbOld->getAssocArrays($result);
		foreach ($remindersOld as $reminderOld) {
			$idReminderOld = $reminderOld['id'];
			unset($reminderOld['id']);

			// Update fields for new version of emailreminders
			$reminderOld['project_id'] = $this->replaceProject($idProjectOld);
			$reminderOld['query_id'] = $this->replaceQuery($reminderOld['query_id']);

			$idReminderNew = $this->dbNew->insert('mail_reminders', $reminderOld);
			$this->mailReminderMapping[$idReminderOld] = $idReminderNew;
		}
	}

	// messages shows could be empty, parent_id need to set null if it is 0
	protected function migrateMessages($idBoardOld)
	{
		$result = $this->dbOld->select('messages', array('board_id' => $idBoardOld));
		$messagesOld = $this->dbOld->getAssocArrays($result);
		foreach ($messagesOld as $message) {
			$idMessageOld = $message['id'];
			unset($message['id']);

			// Update fields
			$message['author_id'] = $this->replaceUser($message['author_id']);
			$message['board_id'] = $this->replaceBoard($idBoardOld);
			$message['parent_id'] = $this->replaceMessage($message['parent_id']);
			// last_reply_id not processed

			$idMessageNew = $this->dbNew->insert('messages', $message);
			$this->messagesMapping[$idMessageOld] = $idMessageNew;

			$this->migrateAttachments($idMessageOld, $idMessageNew, 'Message');
			$this->migrateWatchers($idMessageOld, $idMessageNew, 'Message');
		}
	}

	protected function migrateBoards($idProjectOld)
	{
		$result = $this->dbOld->select('boards', array('project_id' => $idProjectOld));
		$boardsOld = $this->dbOld->getAssocArrays($result);
		foreach ($boardsOld as $boardOld) {
			$idBoardOld = $boardOld['id'];
			unset($boardOld['id']);

			// Update fields for new version of board
			$boardOld['project_id'] = $this->replaceProject($idProjectOld);
			$boardOld['position'] = 99999;

			$idBoardNew = $this->dbNew->insert('boards', $boardOld);
			$this->boardsMapping[$idBoardOld] = $idBoardNew;

			$this->migrateMessages($idBoardOld);
		}
	}

	protected function migrateNews($idProjectOld)
	{
		$result = $this->dbOld->select('news', array('project_id' => $idProjectOld));
		$newssOld = $this->dbOld->getAssocArrays($result);
		foreach ($newssOld as $newsOld) {
			$idNewsOld = $newsOld['id'];
			unset($newsOld['id']);

			// Update fields for new version of news
			$newsOld['project_id'] = $this->replaceProject($idProjectOld);
			$newsOld['author_id'] = $this->replaceUser($newsOld['author_id']);

			$idNewsNew = $this->dbNew->insert('news', $newsOld);
			$this->newsMapping[$idNewsOld] = $idNewsNew;
		}
	}

	protected function migrateDocuments($idProjectOld)
	{
		$result = $this->dbOld->select('documents', array('project_id' => $idProjectOld));
		$documentsOld = $this->dbOld->getAssocArrays($result);
		foreach ($documentsOld as $documentOld) {
			$idDocumentOld = $documentOld['id'];
			unset($documentOld['id']);

			// Update fields for new version of document
			$documentOld['project_id'] = $this->replaceProject($idProjectOld);
			$documentOld['category_id'] = $this->replaceEnumeration($documentOld['category_id']);

			$idDocumentNew = $this->dbNew->insert('documents', $documentOld);
			$this->documentsMapping[$idDocumentOld] = $idDocumentNew;

			$this->migrateAttachments($idDocumentOld, $idDocumentNew, 'Document');
			$this->migrateWatchers($idDocumentOld, $idDocumentNew, 'Document');
		}
	}

	protected function migrateWikiContentVersions($idWikiPageOld, $idWikiContentOld)
	{
		$result = $this->dbOld->select('wiki_content_versions', array('page_id' => $idWikiPageOld, 'wiki_content_id' => $idWikiContentOld));
		$wikiContentVersionsOld = $this->dbOld->getAssocArrays($result);
		foreach ($wikiContentVersionsOld as $wikiContentVersionOld) {
			$idWikiContentVersionOld = $wikiContentVersionOld['id'];
			unset($wikiContentVersionOld['id']);

			// Update fields for new version of wiki content versions
			$wikiContentVersionOld['page_id'] = $this->replaceWikipage($idWikiPageOld);
			$wikiContentVersionOld['author_id'] = $this->replaceUser($wikiContentVersionOld['author_id']);
			$wikiContentVersionOld['wiki_content_id'] = $this->replaceWikicontent($idWikiContentOld);

			$idWikiContentVersionNew = $this->dbNew->insert('wiki_content_versions', $wikiContentVersionOld);
			$this->wikiContentVersionsMapping[$idWikiContentVersionOld] = $idWikiContentVersionNew;
		}
	}

	protected function migrateWikiContents($idWikiPageOld)
	{
		$result = $this->dbOld->select('wiki_contents', array('page_id' => $idWikiPageOld));
		$wikiContentsOld = $this->dbOld->getAssocArrays($result);
		foreach ($wikiContentsOld as $wikiContentOld) {
			$idWikiContentOld = $wikiContentOld['id'];
			unset($wikiContentOld['id']);

			// Update fields for new version of wiki content
			$wikiContentOld['page_id'] = $this->replaceWikipage($idWikiPageOld);
			$wikiContentOld['author_id'] = $this->replaceUser($wikiContentOld['author_id']);

			$idWikiContentNew = $this->dbNew->insert('wiki_contents', $wikiContentOld);
			$this->wikiContentsMapping[$idWikiContentOld] = $idWikiContentNew;

			$this->migrateWikiContentVersions($idWikiPageOld, $idWikiContentOld);
		}
	}

	protected function migrateWikiPageParents($idWikiOld)
	{
		$result = $this->dbOld->query("SELECT * FROM wiki_pages WHERE wiki_id =" . $idWikiOld . " and parent_id > 0");
		$wikipagesOld = $this->dbOld->getAssocArrays($result);
		foreach ($wikipagesOld as $wikipageOld) {
			$idWikiPageNew = $this->replaceWikipage($wikipageOld['id']);
			unset($wikipageOld['id']);

			// Update fields for new version of wiki page parent_id
			$wikipageOld['wiki_id'] = $this->replaceWiki($idWikiOld);
			$wikipageOld['parent_id'] = $this->replaceWikipage($wikipageOld['parent_id']);

			$idWikiPageNew = $this->dbNew->update('wiki_pages', $wikipageOld, array('id' => $idWikiPageNew));
		}
	}

	protected function migrateWikiPages($idWikiOld)
	{
		$result = $this->dbOld->select('wiki_pages', array('wiki_id' => $idWikiOld));
		$wikipagesOld = $this->dbOld->getAssocArrays($result);
		foreach ($wikipagesOld as $wikipageOld) {
			$idWikiPageOld = $wikipageOld['id'];
			unset($wikipageOld['id']);

			// Update fields for new version of wiki pages
			$wikipageOld['wiki_id'] = $this->replaceWiki($idWikiOld);
			$wikipageOld['parent_id'] = null;        // can not imagine the mapping

			$idWikiPageNew = $this->dbNew->insert('wiki_pages', $wikipageOld);
			$this->wikipagesMapping[$idWikiPageOld] = $idWikiPageNew;

			$this->migrateWikiContents($idWikiPageOld);

			$this->migrateAttachments($idWikiPageOld, $idWikiPageNew, 'WikiPage');
			$this->migrateWatchers($idWikiPageOld, $idWikiPageNew, 'WikiPage');
		}

		$this->migrateWikiPageParents($idWikiOld);
	}

	protected function migrateWikis($idProjectOld)
	{
		$result = $this->dbOld->select('wikis', array('project_id' => $idProjectOld));
		$wikisOld = $this->dbOld->getAssocArrays($result);
		foreach ($wikisOld as $wikiOld) {
			$idWikiOld = $wikiOld['id'];
			unset($wikiOld['id']);

			// Update fields for new version of wiki
			$wikiOld['project_id'] = $this->replaceProject($idProjectOld);

			$idWikiNew = $this->dbNew->insert('wikis', $wikiOld);
			$this->wikisMapping[$idWikiOld] = $idWikiNew;

			$this->migrateWikiPages($idWikiOld);
		}
	}

	protected function migrateIssues($idProjectOld)
	{
		$result = $this->dbOld->select('issues', array('project_id' => $idProjectOld));
		$issuesOld = $this->dbOld->getAssocArrays($result);
		foreach ($issuesOld as $issueOld) {
			$this->migrateIssue($issueOld);
		}
	}

	protected function migrateIssue($idIssueOld)
	{
		if (is_array($idIssueOld)) {
			$issuesOld = array($idIssueOld);
		} else {
			$result = $this->dbOld->select('issues', array('id' => $idIssueOld));
			$issuesOld = $this->dbOld->getAssocArrays($result);
		}

		foreach ($issuesOld as $issueOld) {
			$idIssueOld = $issueOld['id'];
			unset($issueOld['id']);

			// Update fields for new version of issue
			$issueOld['project_id'] = $this->replaceProject($issueOld['project_id']);
			$issueOld['assigned_to_id'] = $this->replaceUser($issueOld['assigned_to_id']);
			$issueOld['author_id'] = $this->replaceUser($issueOld['author_id']);
			$issueOld['priority_id'] = $this->replaceEnumeration($issueOld['priority_id']);
			$issueOld['status_id'] = $this->replaceStatus($issueOld['status_id']);
			$issueOld['category_id'] = $this->replaceCategory($issueOld['category_id']);
			$issueOld['tracker_id'] = $this->replaceTracker($issueOld['tracker_id']);
			$issueOld['fixed_version_id'] = $this->replaceVersion($issueOld['fixed_version_id']);
			$issueOld['parent_id'] = null;
			$issueOld['lft'] = 1;
			$issueOld['rgt'] = 2;
			$idIssueNew = $this->dbNew->insert('issues', $issueOld);
			$this->issuesMapping[$idIssueOld] = $idIssueNew;

			$this->migrateIssueRelations($idIssueOld);
			$this->migrateJournals($idIssueOld);
			$this->migrateAttachments($idIssueOld, $idIssueNew, 'Issue');
			$this->migrateWatchers($idIssueOld, $idIssueNew, 'Issue');
		}
	}

	protected function migrateIssuesParents($idProjectOld)
	{
		$result = $this->dbOld->select('issues', array('project_id' => $idProjectOld));
		$issuesOld = $this->dbOld->getAssocArrays($result);
		foreach ($issuesOld as $issueOld) {
			$idIssueOld = $issueOld['id'];
			if ($issueOld['parent_id'] > 0) {

				// Update parents for issues
				$issueUpdate['parent_id'] = $this->replaceIssue($issueOld['parent_id']);

				$idParentIssueNew = $this->dbNew->update('issues', $issueUpdate, array('id' => $this->replaceIssue($issueOld['id'])));
				$this->issuesParentsMapping[$idIssueOld] = $idParentIssueNew;
			}
		}
	}

	protected function migrateIssueRelations($idIssueOld)
	{
		$result = $this->dbOld->select('issue_relations', array('issue_from_id' => $idIssueOld));
		$relationsOld = $this->dbOld->getAssocArrays($result);
		foreach ($relationsOld as $relation) {
			$idRelationOld = $relation['id'];
			unset($relation['id']);

			// Update fields for relations
			$relation['issue_from_id'] = $this->replaceIssue($relation['issue_from_id']);
			$relation['issue_to_id'] = $this->replaceIssue($relation['issue_to_id']);

			$idRelationNew = $this->dbNew->insert('issue_relations', $relation);
			$this->issuesRelationsMapping[$idRelationOld] = $idRelationNew;
		}
	}

	protected function migrateMembers($idProjectOld)
	{
		$result = $this->dbOld->select('members', array('project_id' => $idProjectOld));
		$membersOld = $this->dbOld->getAssocArrays($result);
		foreach ($membersOld as $memberOld) {
			$idMemberOld = $memberOld['id'];
			unset($memberOld['id']);

			// Update fields for new version of member
			$memberOld['project_id'] = $this->replaceProject($idProjectOld);
			$memberOld['user_id'] = $this->replaceUser($memberOld['user_id']);

			$idMemberNew = $this->dbNew->insert('members', $memberOld);
			$this->membersMapping[$idMemberOld] = $idMemberNew;
		}

		// migrate the roles for each member, has to be done in two stages
		// because all members need to be migrated for the interited_from column
		foreach ($this->membersMapping as $idMemberOld => $idMemberNew)
		{
			$result = $this->dbOld->select('member_roles', array('member_id' => $idMemberOld));
			$memberRolesOld = $this->dbOld->getAssocArrays($result);
			foreach ($memberRolesOld as $memberRoleOld) {
				$idMemberRoleOld = $memberRoleOld['id'];
				unset($memberRoleOld['id']);

				// Update fields for new version of member role
				$memberRoleOld['member_id'] = $idMemberNew;
				$memberRoleOld['role_id'] = $this->replaceRole($memberRoleOld['role_id']);
				$memberRoleOld['inherited_from'] = $this->replaceMember($memberRoleOld['inherited_from']);

				$this->dbNew->insert('member_roles', $memberRoleOld);
			}
		}
	}

	protected function migrateProjectTrackers($idProjectOld, $idProjectNew)
	{
		// migrate the project trackers
		$result = $this->dbOld->select('projects_trackers', array('project_id' => $idProjectOld));
		$projectTrackersOld = $this->dbOld->getAssocArrays($result);
		foreach ($projectTrackersOld as $projectTrackerOld) {
			unset($projectTrackerOld['id']);

			// Update fields for new version of member role
			$projectTrackerOld['project_id'] = $idProjectNew;
			$projectTrackerOld['tracker_id'] = $this->replaceTracker($projectTrackerOld['tracker_id']);

			$this->dbNew->insert('projects_trackers', $projectTrackerOld);
		}
	}

	protected function fixOrdering()
	{
		// reorder Boards
		$result = $this->dbNew->select('boards', null, array('id'), 'position');
		$boards = $this->dbNew->getAssocArrays($result);
		$order = 1;
		foreach ($boards as $board) {
			$this->dbNew->update('boards', array('position' => $order++), array('id' => $board['id']));
		}

		// reorder Enumerations
		$result = $this->dbNew->select('enumerations', null, array('type'), null, true);
		$types = $this->dbNew->getAssocArrays($result);
		foreach ($types as $type) {
			$result = $this->dbNew->select('enumerations', array('type' => $type['type']), array('id'), 'position');
			$enumerations = $this->dbNew->getAssocArrays($result);
			$order = 1;
			foreach ($enumerations as $enumeration) {
				$this->dbNew->update('enumerations', array('position' => $order++), array('id' => $enumeration['id']));
			}
		}

		// reorder Roles
		$result = $this->dbNew->select('roles', null, array('id'), 'position');
		$roles = $this->dbNew->getAssocArrays($result);
		$order = 1;
		foreach ($roles as $role) {
			$this->dbNew->update('roles', array('position' => $order++), array('id' => $role['id']));
		}

		// reorder Trackers
		$result = $this->dbNew->select('trackers', null, array('id'), 'position');
		$trackers = $this->dbNew->getAssocArrays($result);
		$order = 1;
		foreach ($trackers as $tracker) {
			$this->dbNew->update('trackers', array('position' => $order++), array('id' => $tracker['id']));
		}
	}

	public function setEnumerationsMapping($mapping)
	{
		if (is_array($mapping)) {
			$this->enumerationsMapping = $mapping;
		}
	}

	public function setStatusMapping($mapping)
	{
		if (is_array($mapping)) {
			$this->statusMapping = $mapping;
		}
	}

	public function setTrackerMapping($mapping)
	{
		if (is_array($mapping)) {
			$this->trackersMapping = $mapping;
		}
	}

	public function setUsersMapping($mapping)
	{
		if (is_array($mapping)) {
			$this->usersMapping = $mapping;
		}
	}

	public function setRolesMapping($mapping)
	{
		if (is_array($mapping)) {
			$this->rolesMapping = $mapping;
		}
	}

	public function migrateProject($idProjectOld)
	{
		if (empty($idProjectOld)) {
			throw new Exception("No project id or list of projects given. Migration stopped.");
		}

		// we need some time and space
		ini_set('memory_limit', '128M');
		ini_set('max_execution_time', 0);

		if (!is_array($idProjectOld)) {
			$idProjectOld = array($idProjectOld);
		}
		$idProjectOld = '`id` IN ('.implode(',', $idProjectOld).')';

		$result = $this->dbOld->select('projects', $idProjectOld);
		$projectsOld = $this->dbOld->getAssocArrays($result);

		echo 'Migrating projects:' . PHP_EOL;
		echo '===================' . PHP_EOL;
		foreach ($projectsOld as $projectOld) {
			$idProjectOld = $projectOld['id'];
			unset($projectOld['id']);
			$projectOld['parent_id'] = null;
			$projectOld['lft'] = 1;
			$projectOld['rgt'] = 2;
			$this->dbNew->update('projects', array('lft' => '= lft+2', 'rgt' => '= rgt+2'));
			$idProjectNew = $this->dbNew->insert('projects', $projectOld);

			echo "migrating old project #$idProjectOld as new project #$idProjectNew".PHP_EOL;
			$this->projectsMapping[$idProjectOld] = $idProjectNew;
			$this->migrateVersions($idProjectOld);
			$this->migrateCategories($idProjectOld);
			$this->migrateIssues($idProjectOld);
			$this->migrateIssuesParents($idProjectOld);
			$this->migrateNews($idProjectOld);
			$this->migrateQueries($idProjectOld);
			$this->migrateMailReminders($idProjectOld);
			$this->migrateDocuments($idProjectOld);
			$this->migrateBoards($idProjectOld);
			$this->migrateTimeEntries($idProjectOld);
			$this->migrateModules($idProjectOld);
			$this->migrateWikis($idProjectOld);
			$this->migrateMembers($idProjectOld);
			$this->migrateAttachments($idProjectOld, $idProjectNew, 'Project');
			$this->migrateWatchers($idProjectOld, $idProjectNew, 'Project');
			$this->migrateProjectTrackers($idProjectOld, $idProjectNew);
		}

		// some tables have ordering columns
		$this->fixOrdering();

		echo PHP_EOL . 'Migration results:' . PHP_EOL;
		echo '==================' . PHP_EOL;
		echo 'users: ' . count($this->usersMapping) . PHP_EOL;
		echo 'projects: ' . count($this->projectsMapping) . PHP_EOL;
		echo 'issues: ' . count($this->issuesMapping) . PHP_EOL;
		echo 'issue parents: ' . count($this->issuesParentsMapping) . PHP_EOL;
		echo 'issue relations: ' . count($this->issuesRelationsMapping) . PHP_EOL;
		echo 'attachments: ' . $this->attachmentCount . PHP_EOL;
		echo 'time entries: ' . count($this->timeEntriesMapping) . PHP_EOL;
		echo 'enumerations: ' . count($this->enumerationsMapping) . PHP_EOL;
		echo 'status: ' . count($this->statusMapping) . PHP_EOL;
		echo 'trackers: ' . count($this->trackersMapping) . PHP_EOL;
		echo 'categories: ' . count($this->categoriesMapping) . PHP_EOL;
		echo 'versions: ' . count($this->versionsMapping) . PHP_EOL;
		echo 'journals: ' . count($this->journalsMapping) . PHP_EOL;
		echo 'enabled modules: ' . count($this->modulesMapping) . PHP_EOL;
		echo 'watchers: ' . count($this->watchersMapping) . PHP_EOL;
		echo 'messages: ' . count($this->messagesMapping) . PHP_EOL;
		echo 'boards: ' . count($this->boardsMapping) . PHP_EOL;
		echo 'news: ' . count($this->newsMapping) . PHP_EOL;
		echo 'documents: ' . count($this->documentsMapping) . PHP_EOL;
		echo 'queries: ' . count($this->queryMapping) . PHP_EOL;
		echo 'mail reminders: ' . count($this->mailReminderMapping) . PHP_EOL;
		echo 'members: ' . count($this->membersMapping) . PHP_EOL;
		echo 'roles: ' . count($this->rolesMapping) . PHP_EOL;
		echo 'wikis: ' . count($this->wikisMapping) . PHP_EOL;
		echo 'wiki pages: ' . count($this->wikipagesMapping) . PHP_EOL;
		echo 'wiki contents: ' . count($this->wikiContentsMapping) . PHP_EOL;
		echo 'wiki content versions: ' . count($this->wikiContentVersionsMapping) . PHP_EOL;

		if (defined('FILESBACKUP')) {
			file_put_contents(FILESBACKUP, '
# create the tarball

if [ -f $ARCHIVE ]; then
	rm -f $ARCHIVE
fi
cd %TMPDIR
tar -czf $ARCHIVE *
cd -

# cleanup
rm -rf $TMPDIR

echo Redmine attachment backup created in $ARCHIVE

', FILE_APPEND);
		}
	}
}
