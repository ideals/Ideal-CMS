Ideal CMS v. 0.2
=========

Система управления контентом с открытым исходным кодом, написанная на PHP.

Используемые технологии и продукты:

* PHP 5.3+,
* MySQL 4+, 
* MVC, 
* PSR-0, PSR-1, PSR-2
* Twig, 
* jQuery,
* Twitter Bootstrap,
* CKEditor,
* CKFinder, 
* FirePHP

Все подробности на сайте [idealcms.ru](http://idealcms.ru/)

Переход на версию 0.2
---

1. Изменён корневой .htacces, теперь адрес страницы не передаётся в GET-переменной,
а берётся в роутере из $_SERVER['REQUEST_URI']