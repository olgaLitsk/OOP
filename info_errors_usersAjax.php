<?php

header("Content-type: text/html; charset=windows-1251");


// инициализация
require_once($_SERVER['DOCUMENT_ROOT'].'/include/init.php');

#############################################################################################################

/**
* 
*/

$ajax = new AjaxCommentUSer();



class AjaxCommentUser {
	protected $userIp;
	protected $comentUser;
	protected $texterrors;
	protected $urlpageerrors;
	protected $countMessage;
	protected $timeLastMessage;
	protected $nowTimeMessage;




	public function __construct(){
		$this->userIp = $userIp;
		$this->commentUser = $comentUser;
		$this->texterrors = $texterrors;
		$this->urlpageerrors = $urlpageerrors;
		$this->countMessage = $countMessage;
		$this->timeLastMessage = $timeLastMessage;
		$this->nowTimeMessage = $nowTimeMessage;
		$this->getrender();
	}

	public function getrender(){
		$this->render();
	}

	protected function render(){
		$this->validatePostVar();
		$this->nowTimeMessage = new DateTime('Now');
		$LastDateTime = new DateTime($this->timeLastMessage);
		$difference = $this->nowTimeMessage->diff($LastDateTime);

		if($this->countMessage == 0){
			$this->DBinsertMessage($this->texterrors,$this->userIp,$this->urlpageerrors,$this->comentUser);
			echo F::utf2win("Спасибо за Ваше участие!"."\r\n"."Ваше сообщение будет доставлено администраторам сайта");
		}else if($this->countMessage < 11 && $difference->i > 0){
			$this->DBinsertMessage($this->texterrors,$this->userIp,$this->urlpageerrors,$this->comentUser);
			echo F::utf2win("Спасибо за Ваше участие!"."\r\n"."Ваше сообщение будет доставлено администраторам сайта");
		}else{
			echo  F::utf2win("Нельзя оставлять более одной заявки в минуту и более десяти за день!");
		}

	}

	protected function DBinsertMessage($txt,$Ip,$url,$coment){
		DB::squery("INSERT INTO feedback_errors_site_users SET users_text=?,ip_user=?,url_err=?,comment=?,time_message=Now()",$txt,$Ip,$url,$coment);
	}

	protected function validatePostVar(){
		$this->userIp = $_SERVER['REMOTE_ADDR'];
		$this->comentUser = F::utf2win(trim(htmlspecialchars($_POST['comment'])));
		$this->texterrors = F::utf2win(trim($_POST['text']));
		$this->urlpageerrors = $_POST['pageurl'];
		$this->countMessage($this->userIp);
		$this->getLastTimeMessage($this->userIp);
	}

	public function countMessage($ip){
		$this->countMessage = DB::select_value("SELECT count(id) FROM feedback_errors_site_users WHERE ip_user=? AND time_message>DATE_SUB(NOW(), INTERVAL 1 DAY)",$ip);
		return $this->countMessage;
	}

	public function getLastTimeMessage($ip){
		$this->timeLastMessage = DB::select_value("SELECT max(time_message) FROM feedback_errors_site_users WHERE ip_user=?",$ip);
		return $this->timeLastMessage;
	}


}


########################################################################################################################################


?>