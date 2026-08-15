[![GitHub release](https://img.shields.io/github/release/realdigger/SMF-Who-Downloaded-Attachment.svg)](https://github.com/realdigger/SMF-Who-Downloaded-Attachment/releases)
[![GitHub release downloads](https://img.shields.io/github/downloads/realdigger/SMF-Who-Downloaded-Attachment/total.svg)](https://github.com/realdigger/SMF-Who-Downloaded-Attachment/releases)
[![SMF](https://img.shields.io/badge/SMF-2.0-blue.svg?style==flat)](https://simplemachines.org)
[![SMF](https://img.shields.io/badge/SMF-2.1-blue.svg?style==flat)](https://simplemachines.org)
[![license](https://img.shields.io/github/license/realdigger/SMF-Who-Downloaded-Attachment.svg)](https://github.com/realdigger/SMF-Who-Downloaded-Attachment/blob/master/LICENSE)

# SMF Who Downloaded Attachment mod

## Compatibility notes

Tested with SMF 2.0.19 and SMF 2.1.7.

SMF 2.0:
Core edits: yes, partially hooks
PHP: 5.4+

SMF 2.1:
Core edits: no, integration hooks only
PHP: 7.1+

## Supported languages

* English
* Russian
* Spanish Latin

## Installation

Download and install latest release tar.gz file from [releases page](https://github.com/realdigger/SMF-Who-Downloaded-Attachment/releases).

## Upgrade

Before upgrading, make a backup of forum files and database.

If an older version is already installed, uninstall it through Package Manager first, then install the latest release package.

The download log table is reused, so existing download records are preserved unless the table is removed manually.

## Permissions

After installation, enable the permission for member groups that should be able to view the download list:

* View attachment download list

Internal permission name: `show_download_list`.

## Logged data

For each downloaded attachment, the mod stores:

* attachment ID
* member ID
* download time
* IP address

Guest downloads are not logged.

The mod starts collecting download records only after installation. Existing attachment download counters are not converted into user download history.

## Settings

The mod adds settings for:

* cache time for the downloaders list, default: `60` seconds
* maximum age of displayed records, default: `0` (show all records)
* maximum number of rows in the list, default: `1000`
* showing IP addresses only to administrators, default: disabled

## Description

This modification adds ability to show who downloaded attachment. The list consists of a nickname, date and IP address.

### Features

* Ability to show who downloaded attachment. The list is limited to 1000 members.
* Group permission for this.

![whodownloadedattachment_en](https://cloud.githubusercontent.com/assets/1187218/26278437/a9788b62-3dab-11e7-98b4-a8a8ff6110df.png)

---

# Мод SMF Who Downloaded Attachment

## Установка

Загрузите и установите файл tar.gz актуальной версии со [страницы загрузок](https://github.com/realdigger/SMF-Who-Downloaded-Attachment/releases).

## Обновление

Перед обновлением сделайте резервную копию файлов форума и базы данных.

Если старая версия уже установлена, сначала удалите её через менеджер пакетов, затем установите актуальный пакет.

Таблица журнала скачиваний используется повторно, поэтому существующие записи сохраняются, если таблица не была удалена вручную.

## Права доступа

После установки включите право для групп пользователей, которым нужно разрешить просмотр списка скачавших вложение:

* Просмотр списка скачавших вложение

Внутреннее имя права: `show_download_list`.

## Записываемые данные

Для каждого скачанного вложения мод сохраняет:

* ID вложения
* ID пользователя
* время скачивания
* IP-адрес

Скачивания гостями не записываются.

Мод начинает собирать историю только после установки. Существующие счётчики скачиваний вложений не преобразуются в историю пользователей.

## Настройки

Мод добавляет настройки для:

* времени кэширования списка скачавших, по умолчанию: `60` секунд
* максимального возраста отображаемых записей, по умолчанию: `0` (показывать все записи)
* максимального количества строк в списке, по умолчанию: `1000`
* показа IP-адресов только администраторам, по умолчанию: выключено

## Описание

Мод добавляет возможность просмотра списка пользователей скачавших вложение. Список содержит - ник со ссылкой на профиль, дату и время скачивания, IP адрес.

### Возможности

* Просмотр списка скачавших вложение. Список ограничен 1000 пользователей.
* Назначение прав доступа на просмотр списка для групп.

![whodownloadedattachment_ru](https://cloud.githubusercontent.com/assets/1187218/26278438/a9a0c8de-3dab-11e7-997a-663a4f5cfeb6.png)
