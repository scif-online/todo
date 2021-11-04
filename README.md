# Список дел с подзадачами
Модуль является альтернативой входящей в версию СКИФ Pro [системы управления задачами и поручениями](https://www.webnice.biz/upravlenie-sotrudnikami-proektami-porucheniyami/) и ориентирован на быструю и удобную работу с индивидуальным списком дел (пользователи видят только свои задачи).
## Возможности
- быстрое добавление задач в один клик с главной страницы системы учета
- сортировка задач в списке перетаскиванием мышкой
- списки подзадач, которые тоже можно упорядочивать перетаскиванием, отмечать статус (Выполнено/Отложено/В процессе) и фильтровать по статусу
- к задаче можно прикрепить клиента, он будет выводиться в списке в виде ссылки на карточку и кликабельным номером телефона.
- возможность настроить добавление и просмотр задач из бота Telegram.
Список дел также можно формировать автоматически, исходя из вашего бизнес-процесса, например, выставлять задачи менеджерам при появлении заказа или наступлении срока оплаты.
## Инструкция по установке
1. Скопируйте файлы в папку /scif/includes/acts/ на вашем сайте с сохранением структуры директорий.
2. Создайте таблицу для хранения задач (это можно сделать через /admin/phpmyadmin/):
```
CREATE TABLE IF NOT EXISTS `wn_scif1_todo` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `date_insert` int(10) unsigned NOT NULL DEFAULT '0',
  `user_insert` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `executor` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `contr` smallint(5) unsigned NOT NULL DEFAULT '0',
  `sort` smallint(5) unsigned NOT NULL DEFAULT '0',
  `sub` text NOT NULL COMMENT 'Подзадачи',
  `count_sub` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT 'Количество подзадач',
  PRIMARY KEY (`id`),
  KEY `executor` (`executor`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Список дел';
```
3. Добавьте в файл настроек wn_settings.php в корне установки системы следующий код (это можно сделать через меню Сервис-Редактор кода):
```php
// Список дел
$my_scif_widgets['todolist']=array('name'=>'Список дел','color'=>'#28c3ed','position'=>'new_doc');
$scif_actions[2040]=array('name'=>'Список дел','desc'=>'Список дел','file'=>'todolist','token'=>'токен телеграм');
```
После этого вверху главной страницы системы учета появится виджет с формой добавления и списком задач. Описание параметров настройки виджетов смотрите [здесь](https://www.webnice.biz/faq-scif/?q52#q52)
