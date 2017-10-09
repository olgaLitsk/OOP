<?php
$action = P::action('m1 m2 page');
define ('MESSAGES_PER_PAGE',10);//сообщений на странице
define ('PAGINATION_INDENT',5);//пагинация с права от текущей страницы и слева
$REG_USERS = new reg_users(REG::I()->PARAM, $action);
$REG_USERS->getPage();
return;


class reg_users{

  // ****  ГЛОБАЛЬНЫЕ (НАСЛЕДУЕМЫЕ) ПЕРЕМЕННЫЕ МОДУЛЯ
  protected $tableSlovar = 'shop_items';
  protected $action = '';
  protected $tplDir = '';
  protected $tplAbsDir = '';
  protected $pageNumber;
  protected $messageAmount;
  protected $pagesAmount;
  protected $messagesPerPage;
  protected $messagesArray;
  protected $pageMessages;
  protected $paginationIndent;
  protected $url;



  function __construct($PARAM, $action){
    $this->pageNumber = isset($_GET['page'])?intval($_GET['page']):1;
    $this->messageAmount = $messageAmount;
    $this->pagesAmount = $pagesAmount;
    $this->messagesPerPage = $messagesPerPage;
    $this->messagesArray = $messagesArray;
    $this->pageMessages = $pageMessages;
    $this->paginationIndent = $paginationIndent;
    $this->action = $action;
    $this->tplDir = dirname(__FILE__).'/';
    $this->tplAbsDir = F::absDIR($this->tplDir);
    $tpl = preg_replace('~\.php$~','.htm',basename(__FILE__));// Автоматически подгружаем темплейт по имени фйла модуля
    $this->countMessageOfBase();
    $this->setPaginationIndent();
    $this->setMessagePerPage();
    $this->setPagesAmount();
    TPL::LoadTplFile($tpl,'module_edit', $this->tplDir, $this->tplAbsDir);
  }

  public function getPage(){
      $this->printList();
      $this->renderPagination();
  }
  
  private function printList(){
    $this->messagesArray = DB::select_array("SELECT id,comment,url_err,edit,admin_edit_name,DATE_FORMAT(time_admin_coment,\"%H:%i:%s %d-%m-%Y\") as time_admin,admin_coment,ip_user,users_text,DATE_FORMAT(time_message,\"%H:%i:%s %d-%m-%Y\") as date_time FROM feedback_errors_site_users ORDER BY time_message DESC");
    $messageOffset = ($this->pageNumber-1) * $this->messagesPerPage;
    $messageOffsetNew = $messageOffset + 1;//для нумерации в шаблоне 
    $this->pageMessages = array_slice($this->messagesArray, $messageOffset, $this->messagesPerPage);//извлекаем сообщения для страницы
    $DATA = array(
      'pageMessages'=> $this->pageMessages,
      'action'=> $this->action,
      'messageOffsetNew'=>$messageOffsetNew,
    );
    TPL::PrnTpl('cardList',$DATA);
    
  }


//начало методов пагинации
  private function renderPagination(){
      if ($this->pagesAmount < 2){//количество страниц
        return;
      } 
      $start = $this->pageNumber - $this->paginationIndent;
      if ($start < 1){
        $start = 1;
      }    
      $finish = $this->pageNumber + $this->paginationIndent;
      if ($finish > $this->pagesAmount) {
          $finish = $this->pagesAmount; //$paginationIndent количество номеров слева и справа от текущей страницы в пагинаторе
      }
      for ($i = $start; $i <= $finish; $i++){
          if ($i == $this->pageNumber){
              echo "<span style=\"font-size: 200%; color: red; text-align: center;\">".$i."</span>".'&nbsp;';
          }else{
              echo '<a href="'.P::action("m1 m2 "). '&page=' . $i . '">' . "<span style=\"font-size: 150%; color: blue; text-align: center;\">".$i."</span>" . '</a>&nbsp;';
          }
      }
  }

  protected function setPaginationIndent(){
    $this->paginationIndent = PAGINATION_INDENT;
  }

  public function getPaginationIndent(){
    return $this->paginationIndent;
  }

  protected function setMessagePerPage(){
    $this->messagesPerPage = MESSAGES_PER_PAGE;
  }

  public function getMessagePerPage(){
    return $this->messagesPerPage;
  }

  protected function countMessageOfBase(){
    $this->messageAmount = DB::select_value("SELECT count(id) FROM feedback_errors_site_users");
  }

  public function getcountMessageOf(){
    return $this->messageAmount;
  }

  protected function setPagesAmount(){
    $this->pagesAmount = ceil($this->messageAmount/$this->messagesPerPage);
  }

  public function getPagesAmount(){
    return $this->pagesAmount;
  }
//конец методов пагинации

  private function getParam($name){ 
    return P::get($name); 
  }

  private function redirect($redirectURL, $debug = false){
    //$debug = true;
    echo '<br><br><a href="'.$redirectURL.'">REDIRECT LINK</a>';
    if(!$debug) header("Location: ".$redirectURL);
    exit();
  }

  private function utf2win($text){
    return iconv('utf-8','cp1251',$text);
  }

  private function win2utf($text){
    return iconv('cp1251','utf-8',$text);
  }

}

?>