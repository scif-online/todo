<?php
$page_title='Список дел';
$view_tablesorter=false;
$iframe=(isset($_GET['iframe'])?'&iframe':'');
$search_contr_fields=array('name','fullname','phone','email','site','note'); // в каких полях искать контрагента

if (!my_access('act',$act)) { return; }

$now=time();
$contr=(!empty($_REQUEST['contr'])?intval($_REQUEST['contr']):0);
 if ($contr) { // открыто из карточки контрагента
 $view_menu=false;
 } else {
 $view_menu=true;
 }

// =============== AJAX ===================
if ($func) {$contr=(!empty($_POST['contr'])?intval($_POST['contr']):0);
 switch ($func) { case 'search_contr':
 $filter_name=urldecode(trim($_GET['term']));
 $sql='SELECT id, name AS value
 FROM '.SCIF_PREFIX.'spr_contrs
 WHERE (';
  foreach ($search_contr_fields AS $field) {
  $sql.=$field.' LIKE "%'.$filter_name.'%" OR ';
  }
 $sql=mb_substr($sql,0,-4);
 $sql.=')'
 .my_access_where(array(6=>'parent'))
 .' ORDER BY name LIMIT 10';
 $result=$db->sql_query($sql);
  if ($db->sql_num_rows($result)) {
   while ($row=$db->sql_fetch_assoc($result)) {
   $label=htmlspecialchars_decode($row['value'],ENT_QUOTES);
   $row['label']=$label;
   $arr[]=$row;
   }
  } else {
  $arr[]=array('label'=>'Ничего не найдено по данному запросу');
  }
 echo json_encode($arr);
 break;

 case 'insert': // добавление
 $name=(!empty($_POST['name'])?htmlclean($_POST['name']):'');
 $db->sql_query('INSERT INTO '.SCIF_PREFIX.'todo SET name="'.$name.'", date_insert="'.$now.'", user_insert="'.$userdata['id'].'", executor="'.$userdata['id'].'", contr="'.$contr.'"');
 echo 'OK';
 break;

 case 'items': // список
 echo items();
 break;

 case 'sort':
  if (!empty($_POST['order']) AND preg_match('#^[0-9,]+$#',$_POST['order'])) {
  $order=substr($_POST['order'],0,-1);
   if ($db->sql_query('UPDATE '.SCIF_PREFIX.'todo SET `sort`=FIELD(id,'.$order.') WHERE id IN ('.$order.') AND executor="'.$userdata['id'].'"')) {
   echo 'OK';
   }
  }
 break;

 case 'update': // обновление полей
 $id=(!empty($_POST['id'])?intval($_POST['id']):0);
 // на будущее, пока поле одно
 $field=((!empty($_POST['field']) AND in_array($_POST['field'],array('name')))?$_POST['field']:'');
  if ($field AND $id>0) {
  $val=(!empty($_POST['val'])?htmlclean($_POST['val']):'');
   if ($db->sql_query('UPDATE '.SCIF_PREFIX.'todo
   SET `'.$field.'`="'.$val.'" WHERE id="'.$id.'" AND executor="'.$userdata['id'].'"')) {
   echo 'OK';
   } else {
   echo 'Ошибка обновления строки задачи!';
   }
  } else {
  echo 'Не указано или некорректно указано поле для обновления задачи!';
  }
 break;

 case 'delete': // удаление
 $id=(!empty($_POST['id'])?intval($_POST['id']):0);
  if ($id>0) {
   if ($db->sql_query('DELETE FROM '.SCIF_PREFIX.'todo WHERE id="'.$id.'" AND executor="'.$userdata['id'].'"')) {
   echo 'OK';
   } else {   echo 'Ошибка удаления задачи!';
   }
  }
 break;
 }
antiddos_end(false);
exit;
}

$meta='<style>
#name_contr { font-weight: bold; }
i.si_item_delete { cursor:pointer; opacity:0.5 }
#items_body td:nth-child(1), #items_body td:nth-last-child(1) { text-align:center; vertical-align:middle; width:20px; }
#items_body tr { background-color:white; }
td.dnd { cursor: move; }
.ui-sortable-helper { background-color:#D4E1FC !important; }
input[data-field] { border:none; }
span[data-contr] { color:blue; cursor:pointer; text-decoration:underline #548bd8; }
span.subtasks { background-color: #eff6fc; border: 1px solid #74b2e2; border-radius: 15px; padding: 0px 5px; text-decoration: none; color: #0A5DAF; cursor:pointer; }
</style>
<script>
var my_window=`height=`+full_height+`,width=`+(full_width/2)+`,left=`+(full_width/2+50)+`,top=5,resizable=yes,scrollbars=yes`;
function insert() {
var contr=$("#contr").val();
// if (!contr) { alert("Не указан клиент!"); $("#search_contr").focus(); return false; }
var name=$("#name").val();
 if (!name) { alert("Не задана задача"); $("#name").focus(); return false; }
$("#btn_insert").prop("disabled",true);
$("#result").html("Сохраняем...");
 $.post("?act='.$act.'&func=insert",{ contr:contr, name:name },function(data){
  if (data=="OK") {
  $("#result").html("Сохранено!").css("color","green");
  $("#name").val("");
  } else {
  $("#result").html(data).css("color","red");
  }
 items();
 $("#btn_insert").prop("disabled",false);
 });
}

function items() {
$("#items_body").css("opacity", 0.3);
 $.post("?act='.$act.'&func=items", function(data) {
 $("#items_body").html(data);
 $("#items_body").sortable("refresh");
 $("#items_body").css("opacity", 1);
 });
}

function delete_item(el) {
 if (!confirm("Удалить задачу?")) { return false; }
var tr=$(el).closest("tr");
 $.post("?act='.$act.'&func=delete", {id: $(tr).attr("data-id")}, function(data) {
  if (data=="OK") {
  $(tr).remove();
  } else {
  alert(data);
  }
 });
}

$(document).ready(function() {

 $("#search_contr").autocomplete({
 autoFocus: true,
 minLength: 3,
 source: "?act='.$act.'&func=search_contr",
  select: function( event, ui ) {
   if (ui.item.id!=undefined) {
   $("#contr").val(ui.item.id).change();
   str_name=ui.item.value+` <a href="?act=spritem&t=6&v=`+ui.item.id+`" target="_blank" title="Открыть карточку"><i class="si si_item_edit"></i></a>`;
   $("#name_contr").html(str_name);
   $("#name_contr").removeClass("require");
   $("#name").focus();
   }
  $("#search_contr").val("");
  return false;
  }
 });

 var oldtext="";
 $("#items_body").on("change", "input[data-field]", function(){
  $.post("?act='.$act.'&func=update", { id: $(this).closest("tr").attr("data-id"), field: $(this).attr("data-field"), val:$(this).val() }, function(data){
   if (data!="OK") {
   alert(data);
   }
  });
 })
 .on("focus", "div[contenteditable]", function() {
 oldtext=$(this).text();
 })
 .on("blur", "div[contenteditable]", function() {  if (oldtext!=$(this).text()) {
   $.post("?act='.$act.'&func=update", { id: $(this).closest("tr").attr("data-id"), field: $(this).attr("data-field"), val:$(this).text() }, function(data){
    if (data!="OK") {
    alert(data);
    }
   });
  }
 })
 .on("keydown", "div[contenteditable]", function(e) {
  if (e.keyCode === 13) {
  $(e.target).closest("tr").find("input")[0].focus();
  e.preventDefault();
  }
 })
 .on("click", "span.subtasks", function(){
 sel_item=$(this).closest("tr").attr("data-id");
 win_open(`?act=todosub'.$iframe.'&v=`+sel_item,`todosub`+sel_item,my_window);
 })
 .on("click", "span[data-contr]", function(){
 win_open(`?act=spritem&t=6'.$iframe.'&v=`+$(this).attr("data-contr"),`contr`,my_window);
 });

 var fixHelper = function(e, ui) {
  ui.children().each(function() {
  $(this).width($(this).width());
  });
 return ui;
 };
 $("#items_body").sortable({
 helper: fixHelper,
 cursor: "move",
 cancel: "td:not(.dnd)",
  stop: function( event, ui ) {  var order="";
   $("#items_body tr").each(function(n) {
   order=$(this).attr("data-id")+","+order;
   });  $.post("?act='.$act.'&func=sort", { order: order });
  }
 });

});
</script>';

// форма
echo '<div class="form-controls" id="formData" style="border: 1px solid rgba(0, 0, 0, 0.1);background-color: #FFFDF9; padding: 5px;">
<div>
 <span style="white-space:nowrap">
 <input type="text" id="name" size="30" placeholder="Новая задача" autofocus>
 <input type="button" onclick="insert()" id="btn_insert" class="button narrow" title="Сохранить задачу" value="&crarr;">
 <a href="javascript:void(0)" onclick="$(`#todo_links`).toggle()" title="Связь с клиентом"><i class="si si_addons"></i></a>
 </span>
 <span id="name_contr"></span>
 <span id="result"></span>
</div>
<div id="todo_links" style="white-space:nowrap;display:none" class="margin" >
 <input type="hidden" name="contr" id="contr" value="0">
 <input type="text" id="search_contr" value="" size="30" placeholder="Клиент">
 <input type="button" value="..." onclick="win_open(`?act=sprlist&t=6&f=contr'.$iframe.'`,`spr6`,full_window);" class="button2 narrow" title="Выбрать клиента">
</div>
</div>';

// список
echo '<div class="page-block margin">
<table id="items" class="border auto">
<tbody id="items_body">
'.items().'
</tbody></table>
</div>';

function items() {global $db, $userdata, $now;
$div='';
$res=$db->sql_query('SELECT t.id, t.name, t.date_insert, t.contr, t.count_sub, c.name AS contr_name, c.phone
FROM '.SCIF_PREFIX.'todo t
LEFT JOIN '.SCIF_PREFIX.'spr_contrs c ON t.contr=c.id
WHERE t.executor="'.$userdata['id'].'"
ORDER BY t.sort DESC, t.date_insert');
 if ($db->sql_num_rows($res)) {
  while ($row=$db->sql_fetch_assoc($res)) {
  $div.='<tr id="item'.$row['id'].'" data-id="'.$row['id'].'">
  <td class="dnd">&#8597;</td>
  <td><span title="Подзадачи" class="subtasks" id="sub'.$row['id'].'">'.($row['count_sub']?$row['count_sub']:'+').'</span></td>
  <td><div data-field="name" contenteditable="">'.$row['name'].'</div></td>
  <td>';
   if ($row['contr']) {   $div.='<span data-contr="'.$row['contr'].'">'.$row['contr_name'].'</span>'
   .($row['phone']?' <a href="tel:'.preg_replace('#[^\d\+]+#','',$row['phone']).'"><i class="si si_phone"></i></a>':'');
   } else {
   $div.='&nbsp;';
   }
  $div.='</td>
  <td>'.date('d/m H:i',$row['date_insert']).'</td>
  <td class="num">'.round(($now-$row['date_insert'])/60/60).'</td>
  <td><i class="si si_item_delete" onclick="return delete_item(this)" title="Удалить"></i></td>
  </tr>';
  }
 }
return $div;
}