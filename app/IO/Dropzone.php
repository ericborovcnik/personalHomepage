<?php
/**
 * Dieses Modul verarbeitet die Dropzone-Uploads
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2016-06-17	eb	from scratch
 */
class IO_Dropzone extends IO_Base {
	
	public function removeAllFiles() {
		
	}
	
	public function uploadFile() {
		$file = $_FILES['Filedata'];
		if(!$file)				return $this->JsonError($this->_('Invalid upload information'));
		if($file['error'] != 0) {
			@unlink($file['tmp_name']);
			return $this->jsonError($this->_('Error occured during upload'));
		}
		/*
		 * Move uploaded file to /media/upload/tempfolder
		 */
		$uploadId = md5(uniqid());
		$dir = User::getDir('upload').'/'.$uploadId;
		mkdir($dir, 0770, true);
		$targetfile = $file['name'];
		if(!move_uploaded_file($file['tmp_name'], $dir.'/'.$targetfile)) {
			@unlink($file['tmp_name']);
			Log::err(error_get_last());
			return $this->jsonError(_('Could not archive upload-file. See debug-log for further information.'));
		}
		$state = new Util_State($dir);
		$state->reset(array(
			'file'	=>	$targetfile
		));
		return $this->jsonResponse(array(
			'id'		=>	$uploadId
		));
	}

	/**
	 * Löscht eine oder mehrere Dateien, die durch die Idents bestimmt sind
	 * $this->params->removeFiles	array mit Upload-IDs
	 */
	public function removeFiles() {
		$ids = Zend_Json::decode($this->params->removeFiles);
		foreach($ids as $folder) {
			if($folder) {
				Util::cleanDir('media/upload/' . $folder, true);
			}
		}
	}

}