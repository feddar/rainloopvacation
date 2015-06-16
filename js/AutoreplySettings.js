
(function () {

	/**
	 * @constructor
	 */
	function AutoreplyUserSettings()
	{
		this.vacationEnabled = ko.observable('');
		this.msgsubject = ko.observable('');
		this.msgbody = ko.observable('');

		this.startY = ko.observable();
		this.startM = ko.observable();
		this.startD = ko.observable();

		this.endY = ko.observable();
		this.endM = ko.observable();
		this.endD = ko.observable();

		this.loading = ko.observable(false);
		this.saving = ko.observable(false);

		this.savingOrLoading = ko.computed(function () {
			return this.loading() || this.saving();
		}, this);
	}

	AutoreplyUserSettings.prototype.vacationAjaxSaveData = function ()
	{
		var self = this;

		if (this.saving())
		{
			return false;
		}

		this.saving(true);

		window.rl.pluginRemoteRequest(function (sResult, oData) {

			self.saving(false);

			if (window.rl.Enums.StorageResultType.Success === sResult && oData && oData.Result)
			{
				// true
			}
			else
			{
				// false
			}

		}, 'AjaxSaveAutoreplySettings', {
			'Active': this.vacationEnabled(),
			'Subject': this.msgsubject(),
			'Body': this.msgbody(),
			'DateStart': this.startY() + '-' + this.startM() + '-' + this.startD(),
			'DateEnd': this.endY() + '-' + this.endM() + '-' + this.endD()
		});
	};

	AutoreplyUserSettings.prototype.onBuild = function () // special function
	{
		var self = this;

		this.loading(true);

		window.rl.pluginRemoteRequest(function (sResult, oData) {

			self.loading(false);

			if (window.rl.Enums.StorageResultType.Success === sResult && oData && oData.Result)
			{
				var Start = oData.Result.DateStart
				var End = oData.Result.DateEnd


				self.vacationEnabled(oData.Result.Active || '');
				self.msgsubject(oData.Result.Subject || '');
				self.msgbody(oData.Result.Body || '');
				self.startY( (Start == null) ? '' : new Date(Start).getFullYear());
				self.startM( (Start == null) ? '' : new Date(Start).getMonth() + 1);
				self.startD( (Start == null) ? '' : new Date(Start).getDate());
				self.endY( (End == null) ? '' : new Date(End).getFullYear());
				self.endM( (End == null) ? '' : new Date(End).getMonth() + 1);
				self.endD( (End == null) ? '' : new Date(End).getDate());
			}

		}, 'AjaxGetAutoreplySettings');

	};

	window.rl.addSettingsViewModel(AutoreplyUserSettings, 'AutoreplySettingsTag',
		'VACATION_SETTINGS_PLUGIN/TAB_NAME', 'custom');

}());
