<?php


ob_start();
session_start();
// Проверяем имеет ли текущий пользователь право редактировать пользователей.

class admObligacii extends adminModule {

	// ****  ГЛОБАЛЬНЫЕ ПЕРЕМЕННЫЕ МОДУЛЯ
	private $tableVkladyList = 'calc.dcalc_vklad_list';        // Список вкладов
	private $tableVkladUsloviya = 'calc.dcalc_vklad_usloviya'; // Списком условий по вкладам
	private $tableVkladValList = 'calc.dcalc_vklad_valuts';    // Список валют по вкладам
	private $tableVkladKapital = 'calc.dcalk_vklad_kapit';     // Список видов капитализации
	
	protected $action = '';

	//protected $grantName = 'edit_filials';
	protected $grantName = 'edit_calc_obligacii';

	protected $userGrantsLevel = 0;
	protected $userName = '';
	protected $userID = '';

	protected $tplDir = '';
	protected $tplAbsDir = '';

	
	function __construct($filial_id, $action){
		$this->action = ''.$action;
		$this->userGrantsLevel  =  PROTECT::allow($this->grantName);
		
		if($this->userGrantsLevel < 1){ echo 'У вас недостаточно прав'; return 0; }
		
		$this->userLogin = PROTECT::userLogin();
		$this->userName = PROTECT::userName();
		$this->userID = PROTECT::userID();

		$this->tplDir = dirname(__FILE__).'/';
		$this->tplAbsDir = F::absDIR($this->tplDir);

		// Автоматически подгружаем темплейт по имени фйла модуля
		$tpl = preg_replace('~\.php$~','.htm',basename(__FILE__));
		TPL::LoadTplFile($tpl,'module_edit', $this->tplDir, $this->tplAbsDir);
	}

	public function getPage(){


		if($this->userGrantsLevel<1){ print '<br>Недостаточно прав для работы с модулем.<br>'; return 1;}

		if( $mode = $this->getParam('ajax') ){
			// Очищаем буфер если AJAX запрос
			for($i=0;$i<10;$i++){ ob_end_clean(); }
			header('Content-type: text/html; charset=windows-1251');

			switch ($mode){
				case 'edit_vklad':
					$this->ajax_edit_vklad($this->getParam('id'));
					break;
				case 'save_vklad':
					$this->ajax_save_vklad($this->getParam('id'));
					break;
				case 'delete_vklad':
					$this->ajax_delete_vklad($this->getParam('id'));
					break;
				case 'loadVklad':
					$this->ajax_loadVklad($this->getParam('id'));
					break;
				case 'edit_kupons':
					$this->ajax_edit_kupons($this->getParam('id'));
					break;
				case 'save_kupons':
					$this->ajax_save_kupons($this->getParam('id'));
					break;
				case 'add_kupon':
					$this->ajax_add_kupon($this->getParam('obl_id'));
					break;
				case 'del_kupon':
					$this->ajax_del_kupon($this->getParam('obl_id'));
					break;
				case 'update_pictures':
					$this->ajax_update_pictures($this->getParam('id'));
					break;
			}

			exit();

		}

		if( $mode = $this->getParam('mode') ){
			switch ($mode){
				case 'add_vklad':
					$this->addVklad($this->getParam('VKLAD'));
					break;
			}

		}
		
		
		//*********************
		//  по умолчанию
		//*********************
		$DATA = array(
			'action'          =>	$this->action,
			'userGrantsLevel' =>	$this->userGrantsLevel,
			'filter'          =>	$this->getParam('filter'),
			'VKLADY'   		  =>	$this->getVkladyList( $this->getParam('filter') ),
			'VAL'             =>	$this->getValutsList(),
			'VKLADY_NAMES'    =>	$this->getVkladyNames(),
			'KAPITAL'         =>	$this->getKapitalList(),
		);


		//$kupons = DB::
		 
		TPL::PrnTpl('list',$DATA);
		//F::debug($DATA);

	}


	private function getVkladyList( $FILTER ){
		
		$where = ' 1=1 ';
		if($t = mysql_real_escape_string($FILTER['vklad_name']) ) $where .= "AND vklad_name LIKE '$t%'";
		if($t = mysql_real_escape_string($FILTER['vklad_val']) ) $where .= "AND vklad_val LIKE '$t'";
		
		$VkladList = DB::select_array("SELECT * FROM $this->tableVkladyList as t1 LEFT JOIN $this->tableVkladValList as t2 ON t1.vklad_val = t2.val_key WHERE $where  ORDER BY vklad_name");
		for($i =0; $i<count($VkladList);$i++){
			$usl = DB::select_array("SELECT * FROM $this->tableVkladUsloviya WHERE vklad_id = ? ORDER BY priority;",$VkladList[$i]['vklad_id']);
			$VkladList[$i]['usloviya'] = $usl; 
		}
		return $VkladList;
	}

	private function getVkladyNames()
	{
		$VkladNames = DB::select_column("SELECT distinct(vklad_name) FROM $this->tableVkladyList ORDER BY vklad_name;");
		return $VkladNames;
	}
	
	
	private function getVklad( $id ){
		$vklad = DB::select_line("SELECT * FROM $this->tableVkladyList as t1 LEFT JOIN $this->tableVkladValList as t2 ON t1.vklad_val = t2.val_key WHERE vklad_id =? ;",$id);
		$usl = DB::select_array("SELECT * FROM $this->tableVkladUsloviya WHERE vklad_id = ? ORDER BY priority;",$id);
		$vklad['usloviya'] = $usl; 
		return $vklad;
	}

	private function getValutsList(){
		try{
			$T = DB::select_array("SELECT * FROM $this->tableVkladValList ORDER BY priority ASC ;");
			for ($i = 0 ; $i < count($T) ; $i++) {
				$VAL[$T[$i]['val_key']]=$T[$i];
			}
			return $VAL;
		}catch(Exception $e){
			$this->exception($e);	
		}
	}

	private function getKapitalList( $kap_id ){ // Список типов капитализации
		try{
			$KAP = array();
			$T = DB::select_array("SELECT * FROM $this->tableVkladKapital ORDER BY priority ASC;");
			for ($i = 0 ; $i < count($T) ; $i++) {
				$KAP[$T[$i]['kap_id']]=$T[$i];
			}
			return $KAP;
		}catch(Exception $e){
			$this->exception($e);	
		}
	}
	
	
	private function addVklad($VKLAD){
		try{
			// проверка на дублирование	в БД
			$isvipusk = DB::select_value("SELECT id FROM $this->tableVkladyList WHERE vklad_name LIKE ? AND vklad_val LIKE ?;", $VKLAD['vklad_name'],$VKLAD['vklad_val']);
			if ($isvipusk) throw new Exception("Выпуск №".$vipusk_num.' уже есть в БД.');
			
			$DATA = array(
				'vklad_name' => $VKLAD['vklad_name'],
				'vklad_val'  => $VKLAD['vklad_val']
			);
			DB::squery("INSERT INTO $this->tableVkladyList SET ?",$DATA);
			
		}catch(Exception $e){
			$this->exception($e);	
		}
	}
	
	
	private function ajax_edit_vklad($id){
		$DATA = array(
			'userGrantsLevel' => $this->userGrantsLevel,
			'action'=>$this->action,
			'vklad' => $this->getVklad($id),
			'VAL' => $this->getValutsList(),
			'SROK_TYPE' => array('d'=>'дней', 'm'=>'месяцев', 'y'=>'лет',),
			'KAPITAL'         =>	$this->getKapitalList(),
			
		);
		TPL::PrnTpl('filial_edit_vklad',$DATA);
	}

#########################################Вызов  для добавления картинки и запись в BD###############################################	

	private function ajax_update_pictures($id){
		$path_images = $_SERVER['DOCUMENT_ROOT']."/images/markup-images/deposits/";
		foreach ($_FILES as $key=>$value) {
			switch ($key) {
				case (strpos($key,'brend_book_')!==false):
					$name = $id."_brend.".pathinfo($value['name'],PATHINFO_EXTENSION);
					$papka = dirname(__FILE__)."/filesUpload";
					/*$path = $path_images.$name;*/
					$path = $papka.'/'.$name;
					if(move_uploaded_file($value['tmp_name'],$path)){
						DB::squery("UPDATE $this->tableVkladyList SET path_brend_book=? WHERE vklad_id=?",$name,$id);
							if(!DB::_error()){
								echo "Успешно загружен brand_book! ";
								unset($name);
							}else{
								echo "Ошибка внесения в базу данных!";
							}
					}else{
						echo "Не удалось загрузить";
					}
					break;
				case (strpos($key,'picture_')!==false):
					$logoname = $id."_logo.".pathinfo($value['name'],PATHINFO_EXTENSION);
					$papka = dirname(__FILE__)."/filesUpload";
					/*$path = $path_images.$logoname;*/
					$path = $papka.'/'.$logoname;
					if(move_uploaded_file($value['tmp_name'],$path)){
						DB::squery("UPDATE $this->tableVkladyList SET pictures_path=? WHERE vklad_id=?",$logoname,$id);
							if(!DB::_error()){
								echo "Успешно загружен logo! ";
								unset($logoname);
							}else{
								echo "Ошибка внесения в базу данных!";
							}
					}else{
						echo "Не удалось загрузить";
					}
					break;
			}
		}
	}

########################################################################################	

	private function ajax_save_vklad($vklad_id){
		try{
			for($i=0;$i<10;$i++){ ob_end_clean(); }
			if(!is_numeric($vklad_id) || $vklad_id<=0) throw new Exception('Ошибочный формат идентификатора.');

			$VKLAD = $this->getParam('vklad');
			$USL  =  $this->getParam('usloviya');
				
			$options = array();
			for ($i=0; $i < 5; $i++) { 
				if(isset($VKLAD['vklad_options'][$i])) $options[] = $i;
			}
			$VKLAD['vklad_options'] = implode(",",$options);

			// Конвертация кодировки поступающих данных
			foreach($VKLAD as $k=>$v){ $VKLAD[$k] = $this->utf2win($v); }

			DB::squery("UPDATE $this->tableVkladyList SET ? WHERE vklad_id = ?", $VKLAD, $vklad_id);
			if(DB::_error()) throw new Exception('Ошибка SQL - '.DB::_error().'в запросе '.DB::_query());
			
			DB::squery("DELETE FROM $this->tableVkladUsloviya WHERE vklad_id= ?",$vklad_id);
			foreach ($USL as $usl) {
				if(!$usl['show'] || !$usl['procent']) continue(1);
				unset($usl['show']);
				$usl['vklad_id']=$vklad_id;
				DB::squery("INSERT INTO $this->tableVkladUsloviya SET ?",$usl);
				if(DB::_error()) throw new Exception('Ошибка SQL - '.DB::_error().'в запросе '.DB::_query());				
			}
						
			F::debug($USL);
		}catch (Exception $e){
			$this->exception($e);
		}
		exit;
	}

	private function ajax_delete_vklad($id){
		try{
			if(!is_numeric($id) || $id<=0) throw new Exception('Ошибочный формат идентификатора.'.$id);
			
			$vklad = $this->getVklad($id);
			if(!$vklad['vklad_id']) throw new Exception("Ошибка при выборе вклада с id=".$id);
			
			DB::squery("DELETE FROM $this->tableOblKupons WHERE obl_id = ?",$vklad['vklad_id']);
			DB::squery("DELETE FROM $this->tableVkladyList WHERE vklad_id = ?",$vklad['vklad_id']);
			
			
			echo $id; // Не килять (нужно для корректной рабботы AJAX)
			
		}catch(Exception $e){
			$this->exception($e);	
		}
	}

	

	
	private function ajax_loadVklad($id){
		$DATA = array(
			'action'          =>	$this->action,
			'userGrantsLevel' =>	$this->userGrantsLevel,
			'vklad'             =>	$this->getVklad($id),
		);
		TPL::PrnTpl('line',$DATA);
	}

	private function utf2win($text){
		return iconv('utf-8','cp1251',$text);
	}

	private function win2utf($text){
		return iconv('cp1251','utf-8',$text);
	}
	
	private function DateDiff ($date1,$date2,$interval='d') {
    // получает количество секунд между двумя датами
    	preg_match('~(....)-*(..)-*(..)~',$date1,$match);
    	$y=$match[1]; $m=$match[2]; $d=$match[3];
		$t1 = mktime(0,0,1,$m,$d,$y,null);
		
		preg_match('~(....)-*(..)-*(..)~',$date2,$match);
    	$y=$match[1]; $m=$match[2]; $d=$match[3];
		$t2 = mktime(0,0,1,$m,$d,$y,null);
		
		
	    $timedifference = $t2 - $t1;
	    //F::debug($match);
		//echo $date2. " *** $y . $m . $d *** ". date('Y m d',$t2).'***'.$timedifference.'<br>';

	    switch ($interval) {
	        case 'w':
	            $retval = $timedifference/604800;
	            break;
	        case 'd':
	            $retval = $timedifference/86400;
	            break;
	        case 'h':
	            $retval = $timedifference/3600;
	            break;
	        case 'n':
	            $retval = $timedifference/60;
	            break;
	        case 's':
	            $retval = $timedifference;
	            break;
	            
	    }
	    return round($retval+1);

	}

	private function DateSum($date, $delta,$format='Ymd'){
		try{
	    	preg_match('~(....)-*(..)-*(..)~',$date,$match);
	    	$y=$match[1]; $m=$match[2]; $d=$match[3];
	    	
			$t = mktime(0,0,1,$m,$d,$y,null);
			return date($format, $t+$delta*60*60*24);

		}catch(Exception $e){
			$this->exception($e);	
		}
	}
}



$action = P::action('m1 m2');
$MODULE = new admObligacii($filial_id, $action);
$MODULE->getPage();
return 1;


?>

