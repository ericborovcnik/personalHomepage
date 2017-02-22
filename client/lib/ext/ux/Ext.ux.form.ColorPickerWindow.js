/**
 * Klasse zur Behandlung vom ColorPickerWindow
 * 
 * @author			Mike Ladurner				mike.ladurner@wb-informatik.ch
 */

Ext.namespace( 'Ext.ux' );

/**
 * Diese Funktion erlaubt es, ein Fenster mit ColorPicker zu öffnen.
 * @param 		editor  		Editor der Zelle
 * @param			value				Startwert zur Darstellung
 */
Ext.ux.form.ColorPickerWindow = function(editor,value) {
	//Startwert Weiss definieren falls kein spezifischer Wert mitgegeben wird.
	if(!value) value='FFFFFF';
	
	//Fenster (Ext.Window) erstellen mit ColorPicker als Item.
	var window = new Ext.Window({
		modal: 		true,		
		width: 		358,
		height: 	229,
		title: 		_('Farbe auswählen'),
		items:		[
		  new Ext.ux.ColorPicker({
		  	 listeners: 	{
		  		select: 		function(a,v){
		  			
		  			//Nach dem auswählen der Farbe wird der Wert abgefangen, das Fenster 
		  			//gelöscht und der Wert wieder per fireEvent SELECT weitergegeben.
		  			window.fireEvent('select',v)
		  			window.destroy();
		  		}
		  	}
		  })
		],
		listeners: {
			select:		function(v){
				
				//Wert wird wiederum abgefangen, dann wird der Wert im gridEditor gesetzt.
				//Nachdem der Wert gesetzt worden ist, wird das Feld nicht automatisch
				//einen Update durchführen, was jedoch mit einem Workaround machbar ist.
				
				//Editing vom GridEditor muss auf true gesetzt werden, damit Befehle ausgeführt
				//werden. Ist dies getan, muss mit beispielsweise "DoHide()" die Zelle mit dem 
				//Store interagieren. 
				editor.gridEditor.setValue('#'+v.toUpperCase());
				editor.gridEditor.editing = true;
				editor.gridEditor.doHide();
			}
		}
	});	
	
	window.show();
	
	//Hier wird noch die vorherige Farbe gesetzt damit man auch von jener ausgehen kann.
	
	
	
	window.items.items[0].setColor(value.substr(1,6));
	window.doLayout();
}
