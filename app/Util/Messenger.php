<?php
/**
 * Bietet Hilfsfunktionen zum Mailversand an
 * @author		Mike Ladurner <mike.ladurner@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2016-01-22	ml	from scratch
 */
abstract class Util_Messenger {

	// Variable hält die letzte Fehlermeldung.
	private static $lastError = null;

	/**
	 * Gibt den letzten Fehler zurück.
	 * @return Model_SMTPError
	 */
	public static function getLastError(){
		return self::$lastError;
	}

	/**
	 * Setzt den Fehler im Fehlerfall und schreibt den vorgehenden Fehler in den Log.
	 * @param Model_MessengerError $error
	 */
	private static function setLastError($error){
		self::$lastError = $error;
	}

	/**
	 * Versucht ein Mail zu senden.
	 * @param array $config
	 */
	public static function sendSMTP($config){
		self::$lastError = null;
		try{

			// Erstelle Parameter
			$params = array();
			if(CONFIG_SMTP_AUTH)			$params['auth']			=	CONFIG_SMTP_AUTH;
			if(CONFIG_SMTP_SSL)				$params['ssl']			= CONFIG_SMTP_SSL;
			if(CONFIG_SMTP_PORT)			$params['port']			=	CONFIG_SMTP_PORT;
			if(CONFIG_SMTP_USERNAME)	$params['username']	= CONFIG_SMTP_USERNAME;
			if(CONFIG_SMTP_PASSWORD)	$params['password']	=	CONFIG_SMTP_PASSWORD;


			// Transporter erstellen
			$transport = new Zend_Mail_Transport_Smtp(CONFIG_SMTP_SERVER, $params);

			// Mail bereitstellen
			$mail = new Zend_Mail();
			$mail->setSubject(utf8_decode($config['subject']));
			$mail->setBodyText(utf8_decode($config['bodyText']));
			$mail->setFrom($config['fromMail'], $config['fromName']);
			if(is_array($config['to'])){
				foreach($config['to'] as $to){
					$mail->addTo($to);
				}
			}
			if(is_array($config['cc'])){
				foreach($config['cc'] as $cc){
					$mail->addCc($cc);
				}
			}
			if(is_array($config['attachments'])){
				foreach($config['attachments'] as $att){
					$mail->addAttachment($att);
				}
			}

			$mail->send($transport);

		}
		catch(Exception $ex){
			self::setLastError(new Model_MessengerError(Date::get(null, "Y-m-d"), User::getId(), User::getUserName(), $ex->getMessage()));
		}
	}

	/**
	 * Versucht ein SMS zu senden. Diese Funktion ist noch nicht implementiert.
	 * @param array $config
	 * @throws Exception
	 */
	public static function sendSMS($config){
		self::$lastError = null;
		try{
			if(!$config->text) throw new Exception("Util_Messenger::sendSMS Text is empty.");
			if(!$config->to) throw new Exception("Util_Messenger::sendSMS Recipant is empty.");

			//Send SMS

			$user = CONFIG_SMS_USERNAME;
			$pw = CONFIG_SMS_PASSWORD;

			/*
			  $text = 'Hallo Testempfänger, dies ist eine PHP-SMS Testnachricht.';

				// Aktuell noch Testbenutzer
				$url = 'https://$user:$pw@api.websms.com/rest/smsmessaging/simple?recipientAddressList=41798395275&messageContent='.urlencode($text).'&test=false';

				// Zur Vorsicht lieber ein @ davor, damit im Fehlerfall
				// und falscher php-config die URL nicht publiziert wird

				$response = @file_get_contents($url);

				// nun noch $response auswerten und Rückgabecode prüfen
				$this->out($url);
				$this->out($response);
			*/

			return;
		}
		catch(Exception $ex){
			self::setLastError(new Model_MessengerError(Date::get(null, "Y-m-d"), User::getId(), User::getUser(), $ex->getMessage()));
		}
	}
}