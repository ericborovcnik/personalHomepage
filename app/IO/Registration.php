<?php
/**
 * Enthält Benutzer-Registrierungs-Methoden
 * @author		Christian Zelenka <christian.zelenka@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2015-02-09	cz	doRegister
 *
 */
class IO_Registration extends IO_Base {

	const CHECK_LOGIN = false;
	const ACCESS = '';

	/**
	 * Haupt-Einstiegspunkt für die Registrierung
	 */
	public function doRegister() {
		if (!$this->params->form) return Util::jsonSystemError('missing parameter \'form\'');

		$form = Zend_Json::decode($this->params->form);
		if (!$form) return Util::jsonSystemError('invalid parameter \'form\'');

		if (!isset($form['name'])) return Util::jsonSystemError('missing parameter \'name\'');
		if (!isset($form['email'])) return Util::jsonSystemError('missing parameter \'email\'');
		if (!isset($form['phone'])) return Util::jsonSystemError('missing parameter \'phone\'');
		if (!isset($form['company'])) return Util::jsonSystemError('missing parameter \'company\'');
		if (!isset($form['comments'])) return Util::jsonSystemError('missing parameter \'comments\'');

		$errors = array();
		$form['name'] = trim($form['name']);
		if (!$form['name']) {
			//@todo translate
			$errors['name'] = $this->_('registration error field empty name');
		}

		$form['email'] = trim($form['email']);
		$validator = new Zend_Validate_EmailAddress();
		if (!$validator->isValid($form['email'])) {
			//@todo translate
			$errors['email'] = $this->_('registration error field invalid email');
		}

		$form['phone'] = trim($form['phone']);
		$form['company'] = trim($form['company']);
		$form['comments'] = trim($form['comments']);

		if ($errors) {
			return Util::jsonError(array('error' => 'invalid form', 'errors' => $errors));
		} else {
			//interne mail an support adresse
			$mailConfig = array();
			$mailConfig['fromMail'] = CONFIG_SMTP_FROM;
			$mailConfig['fromName'] = CONFIG_SMTP_FROM;
			$mailConfig['to'] = array(CONFIG_SMTP_SUPPORT);

			//@todo translate
			$mailConfig['subject'] = $this->_('red registration subject internal notification');
			//@todo translate
			$mailConfig['bodyText'] = $this->_('registration email text internal notification {name},{email},{phone},{company},{comments}',
					array('name' 			=> $form['name'],
								'email' 		=> $form['email'],
								'phone' 		=> $form['phone'],
								'company' 	=> $form['company'],
								'comments' 	=> $form['comments']
					));

 			Util_Messenger::sendSMTP($mailConfig);

			if (Util_Messenger::getLastError()) {
				return Util::jsonSystemError('mailer error'); //system error, weil es nicht passieren soll, nicht erwartbar ist
			}

			//bestätigungsmail an registrant
			$mailConfig = array();
			$mailConfig['fromMail'] = CONFIG_SMTP_FROM;
			$mailConfig['fromName'] = CONFIG_SMTP_FROM;
			$mailConfig['to'] = array($form['name'] => $form['email']);

			//@todo translate
			$mailConfig['subject'] = $this->_('red registration subject to registrant');
			//@todo translate
			$mailConfig['bodyText'] = $this->_('registration email text to registrant {name},{email},{phone},{company},{comments}',
					 array(	'name' 			=> $form['name'],
					 				'email' 		=> $form['email'],
					 				'phone' 		=> $form['phone'],
									'company' 	=> $form['company'],
									'comments' 	=> $form['comments']
						));

 			Util_Messenger::sendSMTP($mailConfig);

			if (Util_Messenger::getLastError()) {
				return Util::jsonSystemError('mailer error'); //system error, weil es nicht passieren soll, nicht erwartbar ist
			}
		}

	}

}