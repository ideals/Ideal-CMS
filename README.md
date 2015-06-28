Ideal CMS v. 2.3.5
=========

Система управления контентом с открытым исходным кодом, написанная на PHP.

Используемые технологии и продукты:

* PHP 5.3+,
* MySQL 4+, 
* MVC, 
* PSR-0, PSR-1, PSR-2
* Twig, 
* jQuery,
* Twitter Bootstrap 3,
* CKEditor,
* CKFinder, 
* FirePHP.

Все подробности на сайте [idealcms.ru](http://idealcms.ru/)

Версия 2.3.5
---
1. FIX: конвертация кода амперсанда в знак амперсанда при сборе карты сайта
1. FIX: автозагрузка классов FormPhp при использовании его отдельно от фреймворка

Версия 2.3.4
---
1. ADD: в файл _.php добавлена проверка $isConsole
1. FIX: получение количества скриптов для обновления

Версия 2.3.3
---
1. FIX: неверное определение подключения Google Analytics в FormPhp
1. FIX: вывод сообщений валидатора при одной ошибке в форме FormPhp
1. FIX: пример использования FormPhp

Версия 2.3.2
---
1. FIX: расположение кнопок редактирования и удаления элемента
1. FIX: убрал лишнее уведомление об удалении элемента
1. FIX: уведомление об ошибке при составлении xml-карты сайта, если ссылка заканчивается на пробел
1. ADD: класс для тестирования методов Sitemap\Crawler
1. FIX: в поле Url не сохранялись ссылки на другие страницы
1. FIX: Функция получения значения во фреймворке форм была переименована в 'getValue'
1. ADD: во фреймворк форм добавлен метод для отправки сообщений
1. WRN: В FormPhp/Field/FileMulti/Controller метод getFileInputBlock переименован в getInputText
1. ADD: варианты подключения окружения в примере использования фреймворка форм FormPhp
1. ADD: абстрактные методы для получения html-кода меток и полей ввода
1. ADD: пример отправки письма через фреймворк форм
1. ADD: поле Ideal_Price
1. FIX: поле Ideal_Integer - html5-защита от ввода дробных чисел
1. ADD: структура заказов с сайта Ideal_Order
1. ADD: поле Ideal_Referer в ideal CMS
1. ADD: поле Referer во фреймворке форм FormPhp

Версия 2.3.1
---
1. FIX: ошибки в обработке is_skip для вложенных структур
1. FIX: notice при сохранении site_data.php
1. ADD: в библиотеке форм, добавлено срабатывание целей Google Analytics
1. FIX: исключение из html-карты сайта вложенных элементов из скрытых разделов
1. FIX: если у элемента прописан is_skip=1 и url='---', то в html-карте сайта не выводим его url

Версия 2.3
---
1. ADD: полностью переписан скрипт сбора xml-карты сайта
1. ADD: обновление на одну версию возможно локально, через консоль setup/update.php
1. Улучшено обновление модулей из админки
1. FIX: проблема с обращением к админке по любому адресу, начинающемуся с названия админки
1. FIX: проверка существования переменной в $_REQUEST при помощи функции isset()

Версия 2.2
---
1. Реализована проверка целостности скриптов CMS
1. WRN: при создании элементов в админке поля не пустые, а полностью отсутствуют в pageData
1. FIX: нулевые значения для числовых полей в БД
1. ADD: метод для получения номера отображаемой страницы

Версия 2.1.1
---
1. FIX: экшены AjaxController теперь могут возвращать контент, который затем выведет FrontController
1. ADD: подсветка розовым ссылки на главную в шапке админки, если находимся в режиме разработчика
1. FIX: проверка на существование такой страницы, если страница выдаёт не 200 и не 404
1. FIX: проблемы с файловым кэшированием
1. FIX: проверка пустого значения при редактировании поля в админке 
   (теперь число 0 не будет считаться как незаполненное поле)
1. ADD: переменная isAdmin во View, определяющая, залогинен пользователь в админку или нет         

Версия 2.1
---
1. FIX: создание новых элементов при повторном нажатии на кнопку Применить при создании элемента
1. ADD: FormPhp\Select
1. UPD: bootstarp-multiselect
1. Защита от подбора брутфорсом доступа к админке
1. Файловое кэширование (создание статических файлов для страничек, генерируемых из БД)
1. FIX: определение дублированных URL

Версия 2.0
---
1. ADD: класс поля для загрузки файлов в фреймворк FormPhp
1. WARNING!!! В контроллере вьюха не переинициализируется при повторном вызове templateInit(), 
   если она уже была инициализирована
1. ADD: теги Ideal_Tag и подключены к новостям
1. FIX: html-версия письма отправляется в quoted-printable
1. FIX: ошибки в сервисе бэкапа
1. Запрещено создание страниц с одинаковым URL
1. FIX: подсветка полей с ошибками в админке
1. Проверка случая, если в Ideal_Part за найденным элементом с is_skip есть ещё элементы с is_skip
1. ADD: в Site\Model.php метод-заглушка, используемый для построения html-карты сайта

Версия 2.0b17
---
1. ADD: фреймворк FormPhp для работы с формами
1. FIX: работа карты сайта с указанным на странице html-тегом <base>
1. FIX: тема уведомления о 404-ой ошибке заменена на "Страница не найдена (404) на сайте ..."
1. ADD: автопродолжение сбора карты сайта в админке
1. ADD: окно логина после ajax-запроса на сохранение данных
1. FIX: проблема с определением состояния auto url при формировании url по полю отличному от name
1. UPD: пересохранение конфигурационных файлов, чтобы в значениях были двойные кавычки
1. ADD: импорт базы данных через админку
1. ADD: добавление номера версии админки к названию файла бэкапа 
1. ADD: добавление комментариев к файлам бэкапа
1. FIX: приоритеты продвигаемых ссылок при создании карты сайта

Версия 2.0b16
---
1. FIX: сохранение атрибута data-* у тегов в Rich_Edit
2. FIX: при проверке домена для установки опции isProduction теперь не учитывается www
3. ADD: отображение в списке элементов админки значка картинки и отображение всплывающей 
   картинки при наведении на значок
4. FIX: удаление старой временной папки CMS при обновлении
5. ADD: возможность генерировать данные из шаблона в AjaxController
6. ADD: для known404 можно записывать правила в формате htaccess
7. FIX: работа с переводами строки при редактировании конфигов через админку

Версия 2.0b15
---
1. ADD: возможность указывать по какому полю генерировать url
2. FIX: в файле бэкапа базы таблицы дропаются перед созданием
3. Обновление Twig до версии 1.16.3
4. ADD: resize для png-файлов
5. FIX: в конфиге значение параметра может быть окружено как одинарными кавычками, 
   так и двойными, а сохраняет только двойными
6. ADD: отправка писем о битых ссылках, за исключением $config->cms['known404']

Версия 2.0b14
---
1. FIX: отображение is_skip страниц
2. FIX: правильное определение URL, когда один из элементов пути - ссылка
3. FIX: обработка случая, когда по одному url есть несколько новостей
4. FIX: указание номера страницы в title
5. __FIX: по умолчанию номер страницы равен 1, а если идёт запрос списка страниц, то номер страницы будет null__
6. FIX: создание файла update.log
7. FIX: отображение multiselect
8. FIX: пропуск незаполненных sql-полей при создании таблицы

Версия 2.0b13
---
0. __ВАЖНО: Изменено название метода Util::is_email на Util::isEmail !!!__
1. В скрипте отправки писем сделана возможность указывать только html-код письма, без plain-версии
2. Чтобы не накручивать статистику Метрики и Аналитики добавлена возможность определения места выполнения скрипта
(production/development)
3. Обновление кода FirePHP до самого актуального
4. FIX: копирование минифайеров при установке CMS
5. ADD: возможность указания в .htaccess логина, пароля и названия базы данных
6. CKEditor обновлён до версии 4.4.6
7. FIX: выдача 404 ошибки на неправильно сформированный параметр action в query_string

Версия 2.0b12
---
1. Обновлён скрипт изменения размера изображения
2. Тег \<style\> теперь можно использовать в визуальном редакторе текста
3. Свойство sqlAdd должно быть инициализировано для каждого редактируемого поля
4. Indirect modification массивов в классе View
5. FIX: неправильные иконки в CKEditor
6. ADD: метод finishMod в Helper для финальных модификаций в тексте страницы

Версия 2.0b11
---
1. Улучшен внешний вид редактирования поля SelectMulti
2. ADD: правило в .htaccess для создания картинок с изменёнными размерами
3. ADD: суффикс тайтла для листалки
4. FIX: карта сайта не будет создаваться, если не были собраны ссылки
5. FIX: принудительное создание карты в админке
6. FIX: проблема с разбором site_data, при наличии символа табуляции вместо пробелов
7. FIX: проблемы связанные с обновлением системы

Версия 2.0b10
---
1. FIX: название файла с классом минификатора в генераторах минифицированных файлов
2. FIX: гарантированная установка body в классе отправки почты
3. FIX: подключение js-файла локализации для DateTimePicker
4. FIX: не убирать из RichEdit пустые span и span с классами
5. FIX: возврат к версии CKEditor 4.4.4, так как в 4.4.5 не работает CodeMirror

Версия 2.0b9
---
1. Исправлено некорректное формирование url у новостей
2. Удалена типизация в методе Core\AjaxController::run, так как теперь там может быть и Site и Admin
3. Исправлена генерация капчи на новых версиях PHP
4. Обновлены библиотеки Moment.js и bootstrap-datatime-picker для корректной работы в Chrome

Версия 2.0b8
---
1. Усовершенствована система обновлений:
2. Каждый этап обновления происходит с помощью отдельного ajax-запроса
3. Скрипты обновления разделены на две части: работающие до обновления CMS и работающие после обновления CMS
4. Добавлен метод рекурсивной смены прав для папок и файлов

Версия 2.0b7
---

1. FIX: удаление в админке элементов ростера и пользователей
2. FIX: дублирование слэшей в поле Area
3. Изменение схемы вызова ajax-контроллеров
4. Создание файла настроек site_map.php в корне админки, если его нет в системе
5. Подключение twig-шаблонов внутри самих шаблонов с помощью указания пути к шаблону от корня админки
6. CKFinder обновлён до версии 2.4.2
7. Twitter Bootstrap обновлён до версии 3.2.0
8. Переход на версию JQuery 2.1.1 (в админке не поддерживаются IE 6, 7, 8)
9. CKEditor обновлён до версии 4.4.5
10. Добавлен объединитель и минимизатор JS и CSS файлов
11. FIX: система обновлений

Версия 2.0b6
---
1. FIX: если не определён mysqli_result::fetch_all (не подключён mysqlnd)
2. Изменена структура файла site_data.php:
3. Поля startUrl, errorLog выведены во вкладку cms
4. Поле tmpDir перенесено во вкладку cms и переименовано в tmpFolder
5. Удалено поле templateCachePath
6. Поля isTemplateCache и isTemplateAdminCache переименованы в templateSite и templateAdmin и перенесены во вкладку cache
7. Во вкладку cache добавлено поле memcache

Версия 2.0b5
---
1. Вкладки в окне редактирования перенесены в заголовок
2. FIX: в CKEditor удалялся тег script и атрибуты style и class
3. Отображение страниц с is_skip=1
4. FIX: формат конфигурационного файла в папке установки
5. FIX: постраничная навигация, лог ошибок в файл, удаление элементов в админке

Версия 2.0b4
---
1. При обновлении CMS и модулей могут выполнятся php и sql скрипты
2. Внедрение нового класса доступа к БД, расширяющего mysqli и с кэшированием через memcached
3. Завершение перевода работы с картой сайта через админку

Версия 2.0b3
---
1. Обновление CKEditor до версии 4.4.3 и удаление нескольких неиспользуемых модулей
2. При обычном подключении RichEdit появляются ВСЕ кнопки
3. Мелкие правки для устранения notice и warning сообщений

Версия 2.0b2
---
1. Показ миниатюры картинки для поля Ideal_Image
2. Добавлена новая сущность Medium
3. Обновлён FirePHP
4. Добавлено поле Ideal_SelectMulti
5. Исправления в карте сайта (обработка ссылок tel, многострочных html-комментариев)
6. Исправлена страница установки CMS для работы под Twi Bootstrap 3 и сделана двухколоночная вёрстка
7. Регулярные выражения для исключения URL в html-карте сайта
8. Исправлена отправка писем с разными типами вложений
9. Работа с картой сайта через админку
10. Исправлена проблема с экранированием слэшей и кавычек в Ideal_Area
11. Обновление CKEditor до версии 4.4.2
12. Отображение на сайте скрытой страницы для авторизированных в админку пользователей

Версия 2.0c
---
1. Обновление jquery-плагина datetimepicker до версии 3.0
2. FIX: определение кол-ва элементов на странице
3. FIX: проверка наличия кастомных и модульных папок Template в виде таблиц в базе
4. FIX: размер модального окна в админке при изменении размера окна браузера
5. FIX: получение default значения
6. __ADD: Новый тип поля Ideal_Integer__
7. FIX: фильтр для toolbar в админке
8. Новая вёрстка шаблона front-end под Twitter Bootstrap 3

Версия 2.0b
---
1. FIX: листалка в админке в стиле Twi 3
2. FIX: доработка редактирования редиректов под Twi 3
3. FIX: доработка создания резервных копий БД под Twi 3
4. Обновление Twitter Bootstrap до версии 3.1.1
5. FIX: Исправлена проблема с автоматической генерацией url
6. ADD: вкладки в настройках в админке

Версия 2.0a
---
1. __Обновление Twitter Bootstrap до версии 3__
2. Изменения в админской части для перехода на Bootstrap 3

Переход на версию 1.0
---

1. Во всех структурах поле structure_path изменено на prev_structure и содержит
ID родительской структуры и ID родительского элемента в этой структуре.

2. Изменён принцип роутинга. Теперь для вложенных структур метод detectPageByUrl
вызывается не из роутера, а из родительской структуры. Что даёт возможность
правильно обрабатывать вложенный структуры с элементами is_skip.

3. Изменён корневой .htacces, теперь адрес страницы не передаётся в GET-переменной,
а берётся в роутере из `$_SERVER['REQUEST_URI']`.

4. Переменная модели object переименована в pageData и сделана protected, а также
переименованы соответствующие методы.

5. Определение 404-ошибки перенесено из роутера в методы detectPageBy* модели.
В этих методах должны инициализироваться свойства класса path и is404, а сами
методы возвращают либо свой объект (`$this`), либо объект вложенной модели. Для
404 ошибки добавлен специальный шаблон 404.twig и экшен error404Action в контроллерах.