/**
 * Erweiterung der Basistypen um neue Methoden
 */

/**
 * Erzeugt aus einer Zahl eine formatierte Zeichenkette
 */
String.prototype.formatNumber = function(decimals) {
	var value = parseFloat(this).toFixed(decimals);
	if(value == 'NaN') return '';
	p = value.indexOf('.');
	if(p>=0) {
		n=value.substring(0,p);
		m=value.substring(p+1);
	} else {
		n=value;
		m='';
	}
	negative=false;
	if(n.indexOf('-') == 0) {
		negative=true;
		n = n.substring(1);
	}
	cnt=0;
	result='';
	for(i=n.length-1;i>=0;i--) {
		cnt++;
		result = n.substring(i,i+1) + result;
		if(cnt==3 && i>0) {
			result = app.user.locale.group + result;
			cnt=0;
		}
	}
	if(m != '') 	result += app.user.locale.decimal + m;
	if(negative)	result = '-' + result;
	return result;
}

/**
 * Erzeugt aus einer Zahl einen formatierten Prozentwert
 */
String.prototype.formatPercent = function(decimals) {
	if(typeof(decimals) == 'undefined')	decimals=0;
	var fact = Math.pow(10, decimals);
	return String(Math.round(parseFloat(this) * 100 * fact)/fact).formatNumber(decimals) + '%';
}

/**
 * Erzeugt eine formatierte Zeichenkette aufgrund der Formatbeschreibung
 */
String.prototype.formatString = function(format) {
	if(typeof(format) == 'undefined')	format = '';
	switch(format.toUpperCase()) {
		case 'YEAR':				return this;										break;
		case 'NUMBER':			return this.formatNumber();			break;
		case 'NUMBER_0':		return this.formatNumber(1);		break;
		case 'NUMBER_00':		return this.formatNumber(2);		break;
		case 'NUMBER_000':	return this.formatNumber(3);		break;
		case 'NUMBER_0000':	return this.formatNumber(4);		break;
		case 'PERCENT':			return this.formatPercent();		break;
		case 'PERCENT_0':		return this.formatPercent(1);		break;
		case 'PERCENT_00':	return this.formatPercent(2);		break;
		case 'PERCENT_000':	return this.formatPercent(3);		break;
	}
	return this;
}

/**
 * Erzeugt eine Zeichenkette, bestehend aus fact Wiederholungen
 */
String.prototype.build = function(fact) {
	if(typeof(fact) == 'undefined') fact = 1;
	var result = '';
	for(i=0;i<fact;i++) {
		result += this;
	}
	return result;
}

/**
 * Ersetzt alle Vorkommnisse von source durch target
 */
String.prototype.replaceAll = function(source, target) {
	var result = this.replace(source,target);
	while(result != result.replace(source,target)) {
		result = result.replace(source,target);
	}
	return result;
}

/**
 * Erzeugt ein Datumsformat
 */
dateFormat = function(value, format) {
	if(typeof(format)	==	'undefined')	format = app.user.locale.date;
	if(typeof(value)	==	'undefined')	value = '';
	if(value=='' || value=='0000-00-00' || value == null)	return '';
	return new Date(value).format(format);
}

/**
 * Erzeugt eine Zufallszahl
 */
random = function(a,b) {
	if(typeof(a) == 'undefined')	a=1;
	if(typeof(b) == 'undefined')	b=100;
	return Math.round(Math.random() * (b-a) + a);
}

/**
 * Prüft ob Struktur ein Array ist
 */
is_array = function(a) {
	if(typeof(a) != 'object')		return false;
	return a instanceof Array;
}

/**
 * Prüft ob eine Struktur als JSON vorliegt
 */
is_json = function(a) {
	try {
		JSON.parse(a);
	} catch(e) {
		return false;
	}
	return true;
}

/**
 * Unterbricht die Programmausführung um eine Anzahl Millisekunden
 */
function sleep(milliseconds) {
	var start = new Date().getTime();
	for(var i=0;i< 1e7; i++) {
		if((new Date().getTime()-start) > milliseconds)	break;
	}
}

/**
 * Konvertiert einen Ausdruck in einen Boolean-Wert
 */
function toBool(value) {
	try {
		if(typeof(value) == 'undefined')		return false;
		if(typeof(value) == 'object')				return true;
		if(typeof(value) == 'number')				return value == 0 ? false : true;
		if(typeof(value) == 'string') {
			if(value == '0' || value == 'false' || value == '')		return false;
			return true;
		}
		return true;
	} catch(e) {
		return false;
	}
}