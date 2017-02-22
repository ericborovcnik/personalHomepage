/**
 * color-Klasse bietet Methoden zur CSS-Farbenberechnung-Gestaltung an
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2016-04-26	eb
 * 
 * @outline
 * _getHCharPos()							Ermittelt die Position einer HEX-Nummer
 * _hexToDec()								Konvertiert einen Hex- in einen Dec-Wert
 * _decToHex()								Konvertiert einen Dec- in einen Hex-Wert
 * _hexToRgb()								Konvertiert einen Hex-Farbschlüssel in eine RGB-Struktur
 * _hexInvert()								Konvertiert einen Hex-Farbschlüssel in die Inversionsfarbe
 */

color = function() {

	var _getHCharPos = function(c) {
		return '0123456789ABCDEF'.indexOf(c.toUpperCase());
	};

	var _hexToDec = function(hex) {
		var s = hex.split('');
		return _getHCharPos(s[0])*16 + _getHCharPos(s[1]);
	}
	
	var _decToHex = function(n) {
		var HCHARS = '0123456789ABCDEF';
		n = parseInt(n, 10);
		n = (!isNaN(n)) ? n : 0;
		n = (n > 255 || n < 0) ? 0 : n;
		return HCHARS.charAt((n - n % 16) / 16) + HCHARS.charAt(n % 16);
	}
	
	var _hexToRgb = function(hex) {
		hex = hex.replace('#','');
		if(hex.length != 6)	hex = 'CCCCCC';		//	grau als Standard-Antwort bei ungültiger Syntax
		return [
			_hexToDec(hex.substr(0, 2)),
			_hexToDec(hex.substr(2, 2)),
			_hexToDec(hex.substr(4, 2))
		];
	}

	var _hexInvert = function(hex) {
		var rgb = _hexToRgb(hex);
		return '#'+_decToHex(255-rgb[0])+_decToHex(255-rgb[1])+_decToHex(255-rgb[2]);
	}
	
	////////////////////////
	//	PUBLIC INTERFACE	//
	////////////////////////
	return {
		hexToRgb:				_hexToRgb,
		hexInvert:			_hexInvert
	}
}();