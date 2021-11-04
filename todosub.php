<?php
$page_title='Подзадачи';
$view_tablesorter=false;

 if (!my_access('act','todolist')) { return; }
 if ($v<=0) { echo error('Не указан код задачи!'); return; }

$data=array();
$row=$db->sql_fetch_assoc($db->sql_query('SELECT name, sub
FROM '.SCIF_PREFIX.'todo
WHERE id="'.$v.'" AND executor="'.$userdata['id'].'"'));
 if (!$row) { echo error('Не найдена задача '.$v); return; }
$page_title='Подзадачи "'.$row['name'].'"';
 if ($row['sub']) {
 $data=unserialize($row['sub']);
 }

// сохранение
if (!empty($_GET['func']) AND $_GET['func']=='save') {
$data=array();
 if (!empty($_POST)) { // если пустой - очистка списка
  foreach ($_POST['name'] AS $key=>$name) {
  $name=htmlclean($name);
   if ($name) {
   $data[]=array('n'=>$name,'s'=>intval($_POST['status'][$key]));
   }
  }
 }
$count_sub=count($data);
$db->sql_query('UPDATE '.SCIF_PREFIX.'todo SET
sub="'.($count_sub?addslashes(serialize($data)):'').'", count_sub="'.$count_sub.'"
WHERE id="'.$v.'" AND executor="'.$userdata['id'].'"');
echo 'OK';
antiddos_end(false);
exit;
}

if (!isset($todo_statuses)) {
$todo_statuses=array(
0=>array('name'=>'В процессе'),
1=>array('name'=>'Завершено','style'=>'background-color: #b9fbb9'),
2=>array('name'=>'Частично','style'=>'background-color: orange'),
3=>array('name'=>'Отложено','style'=>'background-color: silver')
);
}

$meta='<style>
body { font-size:0.875rem; line-height:auto }
#items_body td:nth-child(1), #items_body td:nth-last-child(1) { text-align:center; vertical-align:middle; width:20px; }
#items_body tr { background-color:white; }
i.si_item_delete { cursor:pointer; opacity:0.5 }
td.dnd { cursor: move; }
div[contenteditable] { display:inline-block; width: 100%; min-height: 1rem; box-sizing:border-box; }
div.withlink { width:calc(100% - 23px); word-break: break-word; }
a.link { vertical-align: top; margin: 2px 4px 0 0; display:inline-block; }
.ui-sortable-helper { background-color:#D4E1FC !important; }
select { border:none; padding:5px; width:41px }';
 foreach ($todo_statuses AS $key=>$val) {
  if (!empty($val['style'])) {
  $meta.=PHP_EOL.'td.status'.$key.', td.status'.$key.' select { '.$val['style'].' }';
  }
 }
$meta.='
table.border td { padding:0 2px; }
</style>
<script>
var is_changed=false;
var parent_window=(newwin_ui=="dialogs"?parent:opener);

function filter_status(val) {
 if (val=="") {
 $("#items_body tr").show();
 } else {
  $("#items_body tr").each(function(){
  cur_status=$(this).find(`select[name="status[]"]`).val();
   if (cur_status==val) {
   $(this).show();
   } else {
   $(this).hide();
   }
  });
 }
}

function add_row(to) {
new_row=`<tr><td class="dnd">&#8597;</td>`;
new_row+=`<td><input type="hidden" name="name[]"><div contenteditable=""></div></td>`;
new_row+=`<td><select name="status[]">`';
 foreach ($todo_statuses AS $key=>$val) {
 $meta.=PHP_EOL.'new_row+=`<option value="'.$key.'">'.$val['name'].'</option>`;';
 }
$meta.='
new_row+=`</select></td><td><i class="si si_item_delete" onclick="return delete_item(this)" title="Удалить"></i></td></tr>`;
 if (!to) { // вниз
 $("#items_body").append(new_row);
 new_div=$("#items_body tr:last div");
 } else { // наверх
 $("#items_body").prepend(new_row);
 new_div=$("#items_body").find("tr:first div");
 }
$(new_div).focus();
var scrollTop = $(new_div).offset().top;
$(document).scrollTop(scrollTop);
$("#items_body").sortable("refresh");
return false;
}

// данные изменены
function data_changed() {
$("#btn_save").val("Сохранить").prop("className","button");
is_changed=true;
}

function delete_item(el) {
// if (!confirm("Вы действительно хотите удалить выбранную строку?")) { return false; }
$(el).closest("tr").remove();
data_changed();
}

function save() {
$("#items_body div[contenteditable]").each(function() {
text=$(this).html();
text=text.replace(/\r/gi," ");
text=text.replace(/\n/gi," ");
text=text.replace(/<br \/>/gi,"\n");
text=text.replace(/<br>/gi,"\n");
$(this).closest("td").find(`input[name="name[]"`).val(text);
});
$("#btn_save").val("Сохраняем...").prop("className","button3");
 $.post("?act=todosub&v='.$v.'&func=save", $("#formData").serialize(), function(data){
  if (data!="OK") {
  alert(data);
  }
 $("#btn_save").val("Сохранено").prop("className","button3");
 is_changed=false;
  if (parent_window && !parent_window.closed && parent_window.$("#sub'.$v.'").length) {
  parent_window.$("#sub'.$v.'").html($("#items_body tr").length);
  }
 });
return false;
}

$(document).ready(function() {
'.(!count($data)?'add_row();':'') // первая "приветственная" строка
.'
 $("#items_body")
 .on("change", "select", function() {
  $(this).closest("td").prop("className","status"+$(this).val());
  data_changed();
 })
 .on("input", "div[contenteditable]", function () {
  data_changed();
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
  stop: function( event, ui ) {
  data_changed();
  }
 });
});

window.onbeforeunload = function() {
 if (is_changed) {
 return "Содержимое было изменено!\nВы уверены, что хотите покинуть страницу без сохранения?";
 }
};
</script>';

echo '
<form name="formData" id="formData" method="post">
<table class="border auto page-block" id="items">
<thead><tr class="head">
<th>&nbsp;</th><th align="left">Задача</th>
<th style="text-align:center"><select onchange="filter_status(this.value)">
<option value="">ВСЕ</option>';
 foreach ($todo_statuses AS $key=>$val) {
 echo '<option value="'.$key.'">'.$val['name'].'</option>';
 }
echo '</select></th>
<th>&nbsp;</th>
</tr></thead>
<tbody id="items_body">';

if (count($data)) {
 foreach ($data AS $row) {
 echo '<tr>
 <td class="dnd">&#8597;</td><td>';
 preg_match("#(?:^|\s)((?:http|https):\/\/[^ \"\n\r\t<]*)#is",$row['n'],$links);
  if (!empty($links)) {
  $link='<a href="'.$links[1].'" target="_blank" class="link" title="Перейти по ссылке"><i class="si si_rarrow"></i></a>';
  $class='class="withlink"';
  } else {
  $link=$class='';
  }
 echo '<input type="hidden" name="name[]"><div contenteditable=""'.$class.'>'.nl2br($row['n']).'</div>'.$link
 .'</td>
 <td class="status'.$row['s'].'">
 <select name="status[]">';
  foreach ($todo_statuses AS $key=>$val) {
  echo '<option value="'.$key.'"'.($key==$row['s']?' selected':'').'>'.$val['name'].'</option>';
  }
 echo '</select></td>
 <td><i class="si si_item_delete" onclick="return delete_item(this)" title="Удалить"></i></td>
 </tr>';
 }
}


echo '</tbody>
</table>
<br>
<div class="bottomfix"><div>
<input type="button" value="Сохранить" class="button3" id="btn_save" onclick="return save()">
&nbsp;Добавить строку:
<a href="javascript:void(0)" onclick="return add_row(1)" class="spoiler" title="Вставить в начало списка"><i class="si si_arrow" style="transform:rotate(180deg)"></i>наверх</a> &nbsp;
<a href="javascript:void(0)" onclick="return add_row(0)" class="spoiler" title="Вставить в конец списка"><i class="si si_arrow"></i>вниз</a>
</div></div>
</form>';