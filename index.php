<?php

function bindValueIfExists($st, $q, $name, $value, $type = PDO::PARAM_STR) {
	if (strpos($q, ":" . $name) !== FALSE)
		$st->bindValue(":" . $name, $value, $type);
}


class VacationSettingsTabPlugin extends \RainLoop\Plugins\AbstractPlugin
{
	/**
	 * @return void
	 */
	public function Init()
	{
		$this->UseLangs(true); // start use langs folder

		$this->addJs('js/AutoreplySettings.js'); // add js file

		$this->addAjaxHook('AjaxGetAutoreplySettings', 'GetAutoreplySettings');
		$this->addAjaxHook('AjaxSaveAutoreplySettings', 'SaveAutoreplySettings');

		$this->addTemplate('templates/AutoreplySettingsTag.html');
	}

	/**
	 * @return array
	 */

	public function Settings() {
		return array(
			'sType' => $this->Config()->Get('plugin', 'dbtype', ''),
			'sHost' => $this->Config()->Get('plugin', 'dbhost', ''),
			'iPort' => $this->Config()->Get('plugin', 'dbport', ''),
			'sName' => $this->Config()->Get('plugin', 'dbname', ''),
			'sUser' => $this->Config()->Get('plugin', 'dbuser', ''),
			'sPass' => $this->Config()->Get('plugin', 'dbpass', ''),
			'sQSelect' => $this->Config()->Get('plugin', 'dbselectquery', ''),
			'sQInsert' => $this->Config()->Get('plugin', 'dbinsertquery', ''),
			'sQUpdate' => $this->Config()->Get('plugin', 'dbupdatequery', '')
		);
	}


	public function GetAutoreplySettings()
	{


		try
		{
			$dbSettings = $this->Settings();

			$sDsn = $dbSettings['sType'] . ':host='.$dbSettings['sHost'].';port='.$dbSettings['iPort'].';dbname='.$dbSettings['sName'];

			$oPdo = new \PDO($sDsn, $dbSettings['sUser'], $dbSettings['sPass']);
			$oPdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

			$oStmt = $oPdo->prepare($dbSettings['sQSelect']);

			$oAccount = $this->Manager()->Actions()->GetAccount();
			$email = explode('@', $oAccount->Email());

			bindValueIfExists($oStmt, $dbSettings['sQSelect'], 'email', join('@', $email));
			bindValueIfExists($oStmt, $dbSettings['sQSelect'], 'local', $email[0]);
			bindValueIfExists($oStmt, $dbSettings['sQSelect'], 'domain', $email[1]);

			$oStmt->execute();
			$result = $oStmt->fetch(PDO::FETCH_ASSOC);

			$oPdo = null;

			// or get user's data from your custom storage ( DB / LDAP / ... ).

			return $this->ajaxResponse(__FUNCTION__, array(
				'Active' => $result['active'],
				'Subject' => $result['subject'],
				'Body' => $result['body'],
				'DateStart' => $result['date_start'],
				'DateEnd' => $result['date_end'],
			));
		}
		catch (\Exception $oException)
		{
			if ($this->oLogger)
			{
				$this->oLogger->WriteException($oException);
			}

			return $this->ajaxResponse(__FUNCTION__, array('UserFacebook' => var_export($oException, true)));
		}
	}

	/**
	 * @return array
	 */
	public function SaveAutoreplySettings()
	{
		$p = $this->Manager()->Actions()->GetActionParams();

		$p['Active'] = filter_var($p['Active'], FILTER_VALIDATE_BOOLEAN);

		if ($p['DateStart'] == '--') { $p['DateStart'] = null; }
		if ($p['DateEnd'] == '--') { $p['DateEnd'] = null; }


		$dbSettings = $this->Settings();

		$sDsn = $dbSettings['sType'] . ':host='.$dbSettings['sHost'].';port='.$dbSettings['iPort'].';dbname='.$dbSettings['sName'];

		$oPdo = new \PDO($sDsn, $dbSettings['sUser'], $dbSettings['sPass']);
		$oPdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

		$oUpdateStmt = $oPdo->prepare($dbSettings['sQUpdate']);

		$oAccount = $this->Manager()->Actions()->GetAccount();
		$email = explode('@', $oAccount->Email());

		bindValueIfExists($oUpdateStmt, $dbSettings['sQUpdate'], 'email', join('@', $email));
		bindValueIfExists($oUpdateStmt, $dbSettings['sQUpdate'], 'local', $email[0]);
		bindValueIfExists($oUpdateStmt, $dbSettings['sQUpdate'], 'domain', $email[1]);
		bindValueIfExists($oUpdateStmt, $dbSettings['sQUpdate'], 'active', $p['Active'], PDO::PARAM_BOOL);
		bindValueIfExists($oUpdateStmt, $dbSettings['sQUpdate'], 'subject', $p['Subject']);
		bindValueIfExists($oUpdateStmt, $dbSettings['sQUpdate'], 'body', $p['Body']);
		bindValueIfExists($oUpdateStmt, $dbSettings['sQUpdate'], 'date_start', $p['DateStart']);
		bindValueIfExists($oUpdateStmt, $dbSettings['sQUpdate'], 'date_end', $p['DateEnd']);

		$oUpdateStmt->execute();

		if ($oUpdateStmt->rowCount() != 1) {

			$oInsertStmt = $oPdo->prepare($dbSettings['sQInsert']);

			$oAccount = $this->Manager()->Actions()->GetAccount();
			$email = explode('@', $oAccount->Email());

			bindValueIfExists($oInsertStmt, $dbSettings['sQInsert'], 'email', join('@', $email));
			bindValueIfExists($oInsertStmt, $dbSettings['sQInsert'], 'local', $email[0]);
			bindValueIfExists($oInsertStmt, $dbSettings['sQInsert'], 'domain', $email[1]);

			$oInsertStmt->execute();

			if ($oInsertStmt->rowCount() == 1) {
				return $this->SaveAutoreplySettings(); //Retry update
			}
		}

		$r = $oUpdateStmt->rowCount();

		$oPdo = null;

		// or put user's data to your custom storage ( DB / LDAP / ... ).
		return $this->ajaxResponse(__FUNCTION__, $r);
	}

	/**
	 * @return array
	 */
	public function configMapping()
	{
		return array(
			\RainLoop\Plugins\Property::NewInstance('dbtype')
				->SetLabel('Database Type')
				->SetType(\RainLoop\Enumerations\PluginPropertyType::SELECTION)
				->SetDefaultValue(array('mysql', 'pgsql')),

			\RainLoop\Plugins\Property::NewInstance('dbhost')
				->SetLabel('Database Host')
				->SetDefaultValue('127.0.0.1'),

			\RainLoop\Plugins\Property::NewInstance('dbport')
				->SetLabel('Database Port')
				->SetType(\RainLoop\Enumerations\PluginPropertyType::INT)
				->SetDefaultValue(5432),

			\RainLoop\Plugins\Property::NewInstance('dbuser')
				->SetLabel('Database User'),

			\RainLoop\Plugins\Property::NewInstance('dbpass')
				->SetLabel('Database Password')
				->SetType(\RainLoop\Enumerations\PluginPropertyType::PASSWORD),

			\RainLoop\Plugins\Property::NewInstance('dbname')
				->SetLabel('Database Name'),

			\RainLoop\Plugins\Property::NewInstance('dbupdatequery')
				->SetLabel('Update Query')
				->SetType(\RainLoop\Enumerations\PluginPropertyType::STRING_TEXT)
				->SetDefaultValue("UPDATE autoreply SET active=:active, subject=:subject, body=:body, date_start=:date_start, date_end=:date_end WHERE local=:local AND domain=:domain")
				->SetDescription('Database query to use for updating autoreply settings. Parameter bindings: :email, :local, :domain, :active, :subject, :body, :date_start, :date_end'),

			\RainLoop\Plugins\Property::NewInstance('dbinsertquery')
				->SetLabel('Insert Query')
				->SetType(\RainLoop\Enumerations\PluginPropertyType::STRING_TEXT)
				->SetDefaultValue("INSERT INTO autoreply(local, domain) VALUES (:local,:domain)")
				->SetDescription('Database query to use for creating a non-existent record. Update will run after the insert. Parameter bindings: :email, :local, :domain'),

			\RainLoop\Plugins\Property::NewInstance('dbselectquery')
				->SetLabel('Select Query')
				->SetType(\RainLoop\Enumerations\PluginPropertyType::STRING_TEXT)
				->SetDefaultValue("SELECT active, subject, body, date_start, date_end FROM autoreply WHERE local=:local AND domain=:domain")
				->SetDescription('Database query to use. Parameter bindings: :email, :local, :domain. Expected columns: active, body, subject, body, optionally: date_start, date_end'),

			\RainLoop\Plugins\Property::NewInstance('allowed_emails')->SetLabel('Allowed emails')
				->SetType(\RainLoop\Enumerations\PluginPropertyType::STRING_TEXT)
				->SetDescription('Allowed emails, space as delimiter, wildcard supported. Example: user1@domain1.net user2@domain1.net *@domain2.net')
				->SetDefaultValue('*')
		);
	}


}

