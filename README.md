# IP Installer

IP Installer — це плагін WordPress, який спрощує встановлення та оновлення моїх плагінів і скриптів безпосередньо з репозиторіїв GitHub.

## Функції

- **Встановлення в один клік**: Встановлюйте плагіни та скрипти з репозиторіїв GitHub одним натисканням
- **Керування плагінами**: Активуйте, деактивуйте та видаляйте плагіни прямо з адміністративного інтерфейсу
- **Керування оновленнями**: Перевіряйте наявність оновлень та легко оновлюйтеся до останніх версій
- **Підтримка скриптів**: Встановлюйте окремі PHP-скрипти у кореневий каталог WordPress
- **Зручний інтерфейс**: Чистий та інтуїтивно зрозумілий інтерфейс для керування всіма вашими плагінами та скриптами

![https://github.com/pekarskyi/assets/raw/master/ip-installer/ip-installer.jpg](https://github.com/pekarskyi/assets/raw/master/ip-installer/ip-installer.jpg)

## Як це працює

IP Installer підключається до репозиторіїв GitHub для завантаження та встановлення плагінів і скриптів. Плагін надає елегантний адміністративний інтерфейс, який дозволяє вам:

1. Переглядати всі доступні плагіни та скрипти
2. Бачити, які плагіни встановлені та їхній стан активації
3. Перевіряти наявність доступних оновлень
4. Встановлювати, оновлювати, активувати, деактивувати або видаляти одним натисканням

## Підтримувані плагіни та скрипти

IP Installer попередньо налаштований на роботу з кількома корисними плагінами та скриптами:

### Плагіни:
- [IP GET Logger](https://github.com/pekarskyi/ip-get-logger)
- [Delivery for WooCommerce](https://github.com/pekarskyi/ip-delivery-shipping)
- [IP Language Quick Switcher for WordPress](https://github.com/pekarskyi/ip-language-quick-switcher-for-wp)
- [IP Search Log](https://github.com/pekarskyi/ip-search-log)
- [IP Woo Attributes Converter](https://github.com/pekarskyi/ip-woo-attribute-converter)
- [IP Woo Cleaner](https://github.com/pekarskyi/ip-woo-cleaner)

### Скрипти:
- [IP WordPress URL Replacer](https://github.com/pekarskyi/ip-wordpress-url-replacer)
- [IP Debug Log Viewer](https://github.com/pekarskyi/ip-debug-log-viewer)

## Встановлення

1. Завантажте плагін IP Installer (зелена кнопка Code - Download ZIP).
2. Завантажте його на ваш сайт WordPress. Переконайтесь, що папка плагіна має назву `ip-installer` (назва на роботу плагіна не впливає, але це впливає на отримання подальших оновлень).
3. Активуйте плагін.
4. Перейдіть до "IP Installer" у бічній панелі адміністратора.
5. Почніть встановлювати та керувати плагінами і скриптами.

## Вимоги

- WordPress 5.0 або вище
- PHP 7.0 або вище
- Дозволи на запис у каталозі плагінів та кореневому каталозі WordPress

## Часті запитання

- Як часто IP Installer перевіряє наявність оновлень? - IP Installer перевіряє наявність оновлень тільки тоді, коли ви натискаєте кнопку "Перевірити наявність оновлень". Це забезпечує мінімальне навантаження на ваш сервер та API GitHub.

- Чи можу я додавати власні репозиторії GitHub? - Поточна версія підтримує попередньо визначений список репозиторіїв. У майбутніх версіях може бути додана можливість додавати власні репозиторії.

- Чи сумісний цей плагін з мультисайтовими інсталяціями? - Так, IP Installer працює на мультисайтових інсталяціях WordPress.

## Історія змін
1.1.0 - 04.04.2025:
- Додав функцію оновлення плагінів
- Додав систему оновлення плагіна IP Installer

1.0.0 - 03.04.2025:
- Підтримка встановлення, активації, деактивації та видалення плагінів
- Підтримка встановлення та видалення скриптів
- Функціональність перевірки оновлень