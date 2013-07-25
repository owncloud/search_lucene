<?php

class OC_User_xmpp_Hooks {
	static public function createXmppSession($params){
		$xmpplogin=new OC_xmpp_login($params['uid'],OCP\Config::getAppValue('xmpp', 'xmppDefaultDomain',''),$params['password'],OCP\Config::getAppValue('xmpp', 'xmppBOSHURL',''));
		$xmpplogin->doLogin();
                
		$stmt = OCP\DB::prepare('SELECT `ocUser` FROM `*PREFIX*xmpp` WHERE `ocUser` = ?');
                $result = $stmt->execute(array($params['uid']));
                if($result->numRows()!=0){
			OC_User_xmpp_Hooks::deleteXmppSession();
                }
                $stmt = OCP\DB::prepare('INSERT INTO `*PREFIX*xmpp` (`ocUser`,`jid`,`rid`,`sid`) VALUES (?,?,?,?)');
                $result=$stmt->execute(array($params['uid'], $xmpplogin->jid, $xmpplogin->rid, $xmpplogin->sid));

	}

	static public function deleteXmppSession(){
		$stmt = OCP\DB::prepare('DELETE FROM `*PREFIX*xmpp` WHERE `ocUser` = ?');
		$stmt->execute(array(OCP\User::getUser()));
	}

	static public function createXmppUser($info){
		$x=new OC_xmpp_login(OCP\Config::getAppValue('xmpp', 'xmppAdminUser',''),OCP\Config::getAppValue('xmpp', 'xmppDefaultDomain',''),OCP\Config::getAppValue('xmpp', 'xmppAdminPasswd',''),OCP\Config::getAppValue('xmpp', 'xmppBOSHURL',''));
		$x->addUser($info['uid'].'@'.OCP\Config::getAppValue('xmpp', 'xmppDefaultDomain',''),$info['password'],$info['password']);
#		system('sudo /usr/sbin/ejabberdctl register '.$info['uid'].' '.OCP\Config::getAppValue('xmpp', 'xmppDefaultDomain','').' '.$info['password']);
	}

	static public function updateXmppUserPassword($info){
		system('sudo /usr/sbin/ejabberdctl change_password '.$info['uid'].' '.OCP\Config::getAppValue('xmpp', 'xmppDefaultDomain','').' '.$info['password']);
	}

	static public function post_updateVCard($id){
		if(OCP\Config::getUserValue(OCP\User::getUser(),'xmpp','autoroster')!=true){ return false; }
		$email='';
		$vcardq=OC_Contacts_Vcard::find($id);
		if($vcardq==false)return false;
		$name=$vcardq['fullname'];
		$data=$vcardq['carddata'];
		$vcard = OC_VObject::parse($data);
		foreach($vcard->children as &$property) {
			if($property->name == 'EMAIL'){
				$email = $property->value;
			}
		}
		if($email!=''){
			$xmpplogin=new OC_xmpp_login(OCP\Config::getAppValue('xmpp', 'xmppAdminUser',''),OCP\Config::getAppValue('xmpp', 'xmppDefaultDomain',''),OCP\Config::getAppValue('xmpp', 'xmppAdminPasswd',''),OCP\Config::getAppValue('xmpp', 'xmppBOSHURL',''));	
			$xuser=$xmpplogin->doLogin(OCP\User::getUser().'@'.OCP\Config::getAppValue('xmpp', 'xmppDefaultDomain',''));

			$xuser->addRoster($email,$name);
			$xmpplogin->logout();
			$xuser->logout();

		}
	}
}

?>
