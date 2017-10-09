<?php

header("Content-type: text/html; charset=windows-1251");


// инициализация
require_once($_SERVER['DOCUMENT_ROOT'].'/include/init.php');

#############################################################################################################

/**
* 
*/

$ajax = new AjaxCommentAdmin();



class AjaxCommentAdmin {
	protected $id;// id записи
	protected $value_true;// 1 для занесения в базу в столбец edit
	protected $admin;// данные администратора который внес измененния 
	protected $admincoments;// коментарий администратора


	public function __construct(){
		$this->id = $id;
		$this->value_true = $value_true;
		$this->admin = $admin;
		$this->admincoments = $admincoments;
		$this->getrender();
	}

	public function getrender(){
		if($_POST['id_value']){
			$this->validatePostVar();
			$this->add_to_DB_Editmessage($this->id,$this->value_true,$this->admin,$this->admincoments);
		}
	}

	protected function add_to_DB_Editmessage($id,$val,$adm,$admmessage){
		DB::squery("UPDATE feedback_errors_site_users SET edit=?,admin_edit_name=?,admin_coment=?,time_admin_coment=Now() WHERE id=?",$val,$adm,$admmessage,$id);
		if(!DB::_error()){
			echo F::utf2win("Правки внесены в базу!");
		}else{
			echo F::utf2win("Ошибка внесения в базу данных!");
		}
	}

	protected function validatePostVar(){
		$this->id = $_POST['id_value'];
		$this->value_true = $_POST['value_true'];
		$this->admincoments = F::utf2win($_POST['commentadmin']);
		$this->admin = $this->userName = PROTECT::userName();
	}

}


########################################################################################################################################


?>