# Local Runbook: Справочник типов воинских должностей ВС РФ v1

## 1. Назначение

Все implementation-файлы хранятся в удалённой ветке:

```text
feature/military-positions-directory
```

Локально не применяются patch-файлы и не создаются исходники вручную. Оператор выполняет только безопасную синхронизацию полного клона, затем запускает единый PowerShell 5.1 test runner.

## 2. Обязательные пути

```text
repository: C:\Project\ASU-VCH
deploy root: C:\OSPanel\home\asu-vch.local
URL: https://asu-vch.local
```

## 3. Перед началом

- Open Server Panel запущен;
- Apache, PHP 8.5 и MySQL 8.4 доступны;
- `C:\Project\ASU-VCH` является полным Git-клоном;
- `C:\OSPanel\home\asu-vch.local\config\local.php` существует и содержит рабочие локальные параметры;
- рабочее дерево Git чистое;
- незакоммиченные локальные изменения отсутствуют.

Содержимое `config/local.php`, пароли, cookies и session identifiers не публикуются в логах.

## 4. Синхронизация ветки

Открыть **Windows PowerShell 5.1** и выполнить команды по одной:

```powershell
Set-Location -LiteralPath 'C:\Project\ASU-VCH'

git status --short
git fetch --prune origin

git branch --show-current
git branch --list 'feature/military-positions-directory'
```

Если локальная feature-ветка отсутствует:

```powershell
git switch --track -c feature/military-positions-directory origin/feature/military-positions-directory
```

Если локальная feature-ветка уже существует:

```powershell
git switch feature/military-positions-directory
git pull --ff-only origin feature/military-positions-directory
```

После переключения обязательно выполнить:

```powershell
git status --short
git rev-parse HEAD
git rev-parse origin/feature/military-positions-directory
git rev-list --left-right --count origin/feature/military-positions-directory...HEAD
git merge-base origin/main HEAD
```

Ожидается:

```text
current branch: feature/military-positions-directory
HEAD == origin/feature/military-positions-directory
divergence: 0 0
merge-base: 8cc604eec7e973c2917ea0b1f9b08b976b673f41
working tree: clean
```

Если `git status --short` выводит хотя бы одну строку, Testing не запускать. Сначала сохранить или удалить локальные изменения отдельным осознанным решением.

## 5. Запуск автоматизированного Testing

Из `C:\Project\ASU-VCH` выполнить:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass `
    -File '.\tools\Test-MilitaryPositionsDirectory.ps1' `
    -AllowInvalidCertificate
```

`-AllowInvalidCertificate` разрешён только для локального self-signed сертификата `asu-vch.local`.

Runner самостоятельно:

1. повторно делает `git fetch --prune origin`;
2. проверяет branch, HEAD, merge-base, divergence и чистоту рабочего дерева;
3. проверяет точный список из 22 implementation-путей относительно утверждённого baseline;
4. объединяет пять base64-частей, проверяет SHA-256 gzip-архива, распаковывает canonical migration 010 и проверяет SHA-256 SQL;
5. проверяет `git diff --check`;
6. фиксирует SHA-256 `config/local.php`;
7. создаёт SQL backup через `tools\Backup-Database.ps1`;
8. выполняет deploy через `deploy\Deploy-Local.ps1`;
9. восстанавливает и проверяет `config/local.php`;
10. публикует test tools в deploy-копию;
11. запускает PHP lint;
12. запускает installer два раза;
13. проверяет migration 010 и справочник;
14. запускает directory, security, theme и Organization regressions;
15. проверяет source/deploy parity;
16. выполняет HTTP smoke;
17. подтверждает неизменность Git checkout и локального конфига.

Runner не создаёт commit, push или PR.

## 6. Успешный финал

В конце должны присутствовать маркеры:

```text
IMPLEMENTATION_SCOPE_STATUS=PASS
SOURCE_DEPLOY_PARITY_STATUS=PASS
AUTOMATED_TESTING_STATUS=PASS
MANUAL_DESKTOP_ACCEPTANCE_STATUS=NOT_RUN
MOBILE_TESTING_STATUS=OUT_OF_SCOPE_NOT_RUN
COMMIT_PUSH_PR_STATUS=IMPLEMENTATION_COMMIT_ALREADY_ON_GITHUB_PR_NOT_CREATED
```

## 7. Что отправить в чат

Скопировать полный безопасный вывод PowerShell начиная со строки:

```text
=== Repository preflight and remote verification ===
```

и заканчивая итоговыми статусами.

Не отправлять:

- содержимое `config/local.php`;
- пароль БД;
- cookies;
- session identifiers;
- приватные ключи и токены.

## 8. После automated PASS

Следующий gate — manual desktop acceptance в трёх встроенных темах. Commit реализации уже находится в feature-ветке GitHub только для синхронизации и Testing. PR, merge и удаление ветки не выполняются без отдельных явных разрешений владельца.

```text
mobile testing: OUT OF SCOPE / NOT RUN
mobile PASS: NOT CLAIMED
```
