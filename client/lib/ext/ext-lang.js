/**
 * ext-lang		Initialisiert die Sprachmerkmale der Ext-Komponenten
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2016-01-12	eb	adapted from revis
 */
Ext.initLanguage = function() {
	Ext.UpdateManager.defaults.indicatorText = '<div class="loading-indicator">' + _('Transmitting data...')  + '</div>';
	if(Ext.View)								Ext.View.prototype.emptyText = "";
	if(Ext.grid.GridPanel)			Ext.grid.GridPanel.prototype.ddText = _('{0} records selected');
	if(Ext.TabPanelItem)				Ext.TabPanelItem.prototype.closeText = _('Close this tab');
	if(Ext.form.BasicForm)			Ext.form.BasicForm.prototype.waitTitle = _('Please wait...');
	if(Ext.form.Field)					Ext.form.Field.prototype.invalidText = _('Value is not valid');
	if(Ext.LoadMask)						Ext.LoadMask.prototype.msg = _('Transmitting data...');
	Date.monthNames = [_('January'),_('February'),_('March'),_('April'),_('May'),_('June'),_('July'),_('August'),_('September'),_('October'),_('November'),_('December')];
	Date.shortmonthNames = [_('Jan'),_('Feb'),_('Mar'),_('Apr'),_('May'),_('Jun'),_('Jul'),_('Aug'),_('Sep'),_('Oct'),_('Nov'),_('Dec')];
	Date.getShortMonthName = function(month) {
		return Date.shortmonthNames[month];
	};
	Date.monthNumbers = {};
	Date.monthNumbers[_('Jan')] = 0;
	Date.monthNumbers[_('Feb')] = 1;
	Date.monthNumbers[_('Mar')] = 2;
	Date.monthNumbers[_('Apr')] = 3;
	Date.monthNumbers[_('May')] = 4;
	Date.monthNumbers[_('Jun')] = 5;
	Date.monthNumbers[_('Jul')] = 6;
	Date.monthNumbers[_('Aug')] = 7;
	Date.monthNumbers[_('Sep')] = 8;
	Date.monthNumbers[_('Oct')] = 9;
	Date.monthNumbers[_('Nov')] = 10;
	Date.monthNumbers[_('Dec')] = 11;
	Date.getMonthNumber = function(name) {
		return Date.monthNumbers[name.substring(0, 1).toUpperCase() + name.substring(1, 3).toLowerCase()];
	};
	Date.dayNames = [_('Sunday'),_('Monday'),_('Tuesday'),_('Wednesday'),_('Thursday'),_('Friday'),_('Saturday')];
	Date.shortdayNames = [_('Sun'),_('Mon'),_('Tue'),_('Wed'),_('Thu'),_('Fri'),_('Sat')];
	Date.getShortDayName = function(day) {
		return Date.shortdayNames[day];
	}
	if(Ext.MessageBox){
		 Ext.MessageBox.buttonText = {
			ok:							_('OK')
			,cancel:				_('Cancel')
			,yes:						_('Yes')
			,no:						_('No')
		 };
	}
	if(Ext.util.Format){
		Ext.util.Format.date = function(v, format){
			if(!v) return "";
			if(!(v instanceof Date)) v = new Date(Date.parse(v));
			return v.dateFormat(format || "d.m.Y");
		};
	}
	if(Ext.DatePicker) {
		Ext.apply(Ext.DatePicker.prototype, {
			todayText:			_('Today')
			,minText:				_('Date is before the minimum date')
			,maxText:				_('Date is after the maximum date')
			,disabledDaysText:''
			,disabledDatesText:''
			,monthNames:		Date.monthNames
			,dayNames:			Date.dayNames
			,nextText:			_('Next month (Ctrl+Right)')
			,prevText:			_('Previous month (Ctrl+Left)')
			,monthYearText:	_('Select month (Ctrl+Up/Down to select year)')
			,todayTip:			_('Today (Space)')
			,format:				app.user.locale.date
			,okText:				"&#160;" + _('OK') + "&#160;"
			,cancelText:		_('Cancel')
			,startDay:			1
		 });
	}
	if(Ext.PagingToolbar){
		Ext.apply(Ext.PagingToolbar.prototype, {
			beforePageText:	_('Page')
			,afterPageText:	_('of {0}')
			,firstText:			_('First page')
			,prevText:			_('Previous page')
			,nextText:			_('Next page')
			,lastText:			_('Last page')
			,refreshText:		_('Refresh')
			,displayMsg:		_('Show record {0} - {1} of {2}')
			,emptyMsg:			_('No records available')
		});
	}
	if(Ext.form.TextField){
		 Ext.apply(Ext.form.TextField.prototype, {
				minLengthText:	_('This field should contain at least {0} characters')
				,maxLengthText:	_('This field should contain at most {0} characters')
				,blankText:			_('This field can not be empty')
				,regexText:			''
				,emptyText:			null
		 });
	}
	if(Ext.form.NumberField){
		Ext.apply(Ext.form.NumberField.prototype, {
			minText:				_('Value must be at least {0}')
			,maxText:				_('Value is at most {0}')
			,nanText:				_('{0} is not a number')
		});
	}
	if(Ext.form.DateField){
		 Ext.apply(Ext.form.DateField.prototype, {
			disabledDaysText:_('not allowed')
			,disabledDatesText:_('not allowed')
			,minText:				_('Date must be after {0}')
			,maxText:				_('Date must be before {0}')
			,invalidText:		_('{0} is not a valid date. Format must match {1}')
			,format:				app.user.locale.date
			,altFormats:		app.user.locale.date + '|Y-m-d'
		 });
	}
	if(Ext.form.ComboBox) {
		Ext.apply(Ext.form.ComboBox.prototype, {
			loadingText:		_('Loading...')
			,valueNotFoundText:undefined
		});
	}
	if(Ext.form.VTypes){
		 Ext.apply(Ext.form.VTypes, {
				emailText:			_('This field should contain an email-address (user@domain.com)')
				,urlText:				_('This field should contian an URL (http://www.domain.com)')
				,alphaText:			_('This field should only contain letters and underscore')
				,alphanumText:	_('This field should only contain number, letters and underscore')
		 });
	}
	if(Ext.form.HtmlEditor){
		Ext.apply(Ext.form.HtmlEditor.prototype, {
			createLinkText:	_('Please enter URL for hyperlink:')
			,buttonTips:		{
				bold:						{
					title:					_('Bold (Ctrl+B)')
					,text:					_('Switches bold for selected text')
					,cls:						'x-html-editor-tip'
				}
				,italic:				{
					title:					_('Italic (Ctrl+I)')
					,text:					_('Switches italic for selected text')
					,cls:						'x-html-editor-tip'
				}
				,underline:			{
					title:					_('Underline (Ctrl+U)')
					,text:					_('Switches underline for selected text')
					,cls:						'x-html-editor-tip'
				}
				,increasefontsize:{
					title:					_('Enlarge size'),
					text:						_('Enlarge fontsize.'),
					cls:						'x-html-editor-tip'
				}
				,decreasefontsize:{
					title:					_('Reduce size')
					,text:					_('Reduces fontsize.')
					,cls:						'x-html-editor-tip'
				}
				,backcolor:			{
					title:					_('Backcolor')
					,text:					_('Change backcolor of selected text')
					,cls:						'x-html-editor-tip'
				}
				,forecolor:			{
					title:					_('Forecolor')
					,text:					_('Change color of selected text')
					,cls:						'x-html-editor-tip'
				}
				,justifyleft:		{
					title:					_('Align left')
					,text:					_('Set text-alignment leftbound')
					,cls:						'x-html-editor-tip'
				}
				,justifycenter:	{
					title:					_('Align center')
					,text:					_('Set text-alignment centered')
					,cls:						'x-html-editor-tip'
				}
				,justifyright:	{
					title:					_('Align right')
					,text:					_('Set text-alignment rightbound')
					,cls:						'x-html-editor-tip'
				}
				,insertunorderedlist:{
					title:					_('Enumeration list')
					,text:					_('Start an enumeration list')
					,cls:						'x-html-editor-tip'
				}
				,insertorderedlist:{
					title:					_('Numbered list')
					,text:					_('Starts a numbered list')
					,cls:						'x-html-editor-tip'
				}
				,createlink:		{
					title:					_('Hyperlink')
					,text:					_('Create a hyperlink from selected text')
					,cls:						'x-html-editor-tip'
				}
				,sourceedit:		{
					title:					_('Edit source')
					,text:					_('Change to edit sourcetext')
					,cls:						'x-html-editor-tip'
				}
			}
		});
	}
	if(Ext.grid.GridView) {
		 Ext.apply(Ext.grid.GridView.prototype, {
				sortAscText:		_("Sort ascending")
				,sortDescText:	_("Sort descending")
				,lockText:			_("Lock column")
				,unlockText:		_("Unlock column")
				,columnsText:		_("Columns")
		 });
	}
	if(Ext.grid.GroupingView) {
		Ext.apply(Ext.grid.GroupingView.prototype, {
			emptyGroupText:	_('(None)')
			,groupByText:		_('Group this field')
			,showGroupsText:	_('Show in groups')
		});
	}
	if(Ext.grid.PropertyColumnModel){
		Ext.apply(Ext.grid.PropertyColumnModel.prototype, {
			nameText:				_('Name')
			,valueText:			_('Value')
			,dateFormat:		app.user.locale.date
		});
	}
	if(Ext.layout.BorderLayout && Ext.layout.BorderLayout.SplitRegion) {
		Ext.apply(Ext.layout.BorderLayout.SplitRegion.prototype, {
			splitTip            : _('Drag to change size'),
			collapsibleSplitTip : _('Drag to change size. Doubleclick to hide Panel')
		});
	}
	if(Ext.form.TimeField) {
		Ext.apply(Ext.form.TimeField.prototype, {
			minText:				_('Time must be greater or equal {0}')
			,maxText:				_('Time must be less or equal {0}')
			,invalidText:		_('{0} is not a valid time')
			,format:				'H:i'
		});
	}

}