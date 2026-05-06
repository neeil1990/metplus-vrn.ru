<?php
/**
 * 🔄 Битрикс: Синхронизация каталога (Donor → Catalog)
 * Веб: https://ваш-домен/iBlockConverter/sync_catalog.php
 * Крон: /opt/php80/bin/php /путь/sync_catalog.php --orphans=deactivate --sleep=100
 */

// 🔧 FIX FOR CLI: В консоли $_SERVER['DOCUMENT_ROOT'] часто пуст. Задаём его автоматически.
if (php_sapi_name() === 'cli' && empty($_SERVER['DOCUMENT_ROOT'])) {
    $_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
}
// Если автоопределение не сработало, раскомментируйте строку ниже и укажите точный путь:
// $_SERVER['DOCUMENT_ROOT'] = '/var/www/metplus-vrn.ru/data/www/metplus-vrn.ru';

// 🔍 Для первой отладки 500 ошибок раскомментируйте эти 3 строки. После запуска - закомментируйте.
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define("BX_CRONTAB_SUPPORT", true);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die("❌ Ошибка: не удалось подключить ядро Битрикс. Проверьте DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'пусто') . "\n");
}

\Bitrix\Main\Loader::includeModule('iblock');
\Bitrix\Main\Loader::includeModule('catalog');
global $USER, $APPLICATION;

// 🔐 ДОСТУП: Админ (веб) или CLI (крон/SSH)
$isCLI = (php_sapi_name() === 'cli');
$hasAccess = $isCLI ? true : (is_object($USER) && method_exists($USER, 'IsAdmin') && $USER->IsAdmin());
if (!$hasAccess) {
    header('HTTP/1.0 403 Forbidden');
    die("❌ Доступ запрещён. Только для администраторов Битрикс или CLI.");
}

// 📥 Парсинг CLI аргументов (--limit=10) в $_GET
if ($isCLI && isset($argv)) {
    foreach ($argv as $arg) {
        if (strpos($arg, '--') === 0 && strpos($arg, '=') !== false) {
            list($k, $v) = explode('=', substr($arg, 2), 2);
            $_GET[$k] = $v;
        }
    }
}

// ⚙️ НАСТРОЙКИ
$settings = [
    'limit'     => isset($_GET['limit']) ? (int)$_GET['limit'] : 0,
    'clean'     => isset($_GET['clean']) && $_GET['clean'] == '1',
    'diag'      => isset($_GET['diag']) && $_GET['diag'] == '1',
    'orphans'   => $_GET['orphans'] ?? 'deactivate',
    'sleep'     => isset($_GET['sleep']) ? (int)$_GET['sleep'] : 100,
    'donCode'   => 'donor-catalog',
    'accCode'   => 'catalog',
    'donPriceId'=> 16
];

set_time_limit(0);
ini_set('memory_limit', '512M');

// 🛠 УТИЛИТЫ
function safeFloat($val) {
    $val = trim($val ?? '');
    return ($val === '' || !is_numeric($val)) ? 0.0 : (float)$val;
}

function log_msg($msg, $type = 'info') {
    global $isCLI;
    $time = date('H:i:s');
    $cls = '';
    if ($type === 'step') $cls = 'step';
    elseif ($type === 'ok') $cls = 'ok';
    elseif ($type === 'warn') $cls = 'warn';
    elseif ($type === 'err') $cls = 'err';
    
    if ($isCLI) {
        echo "[$time] [$type] $msg\n";
    } else {
        echo "<div class='$cls'>[$time] [$type] $msg</div>\n";
        if (ob_get_level()) { ob_flush(); flush(); }
    }
}

// 🌐 ВЕБ-ИНТЕРФЕЙС
if (!$isCLI) {
    if (ob_get_level()) ob_end_clean();
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <title>Синхронизация каталога</title>
        <style>
            body { font-family: 'Consolas', 'Monaco', monospace; background: #0d1117; color: #c9d1d9; margin: 0; padding: 20px; line-height: 1.5; }
            h1 { color: #58a6ff; margin-bottom: 10px; }
            .panel { background: #161b22; padding: 15px; border-radius: 6px; margin-bottom: 15px; font-family: system-ui, sans-serif; font-size: 13px; }
            .controls { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 10px; }
            .btn { padding: 8px 14px; background: #218bff; color: #fff; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 12px; }
            .btn:hover { background: #1976d2; }
            .btn.warn { background: #d29922; } .btn.danger { background: #f85149; } .btn.secondary { background: #30363d; }
            #log { background: #010409; border: 1px solid #30363d; border-radius: 6px; padding: 15px; min-height: 60vh; white-space: pre-wrap; overflow-y: auto; }
            .step { color: #58a6ff; font-weight: bold; margin-top: 12px; }
            .ok { color: #2ea043; } .warn { color: #d29922; } .err { color: #f85149; } .dim { color: #8b949e; }
        </style>
    </head>
    <body>
        <h1>🔄 Синхронизация каталога</h1>
        <div class="panel">
            <div class="controls">
                <a href="?limit=5&orphans=deactivate" class="btn">▶️ Тест: 5 товаров + сироты</a>
                <a href="?orphans=deactivate" class="btn">🔄 Полный запуск + сироты</a>
                <a href="?diag=1" class="btn secondary">🔍 Диагностика разделов</a>
                <a href="?clean=1&orphans=deactivate" class="btn danger" onclick="return confirm('⚠️ Удалить ВСЕ товары в приёмнике?')">⚠️ Очистка + запуск</a>
            </div>
        </div>
        <div id="log">
    <?php
}

// === ЛОГИКА СИНХРОНИЗАЦИИ ===
log_msg("🔍 ЭТАП 1: Поиск инфоблоков", 'step');
$donIB = CIBlock::GetList([], ['CODE' => $settings['donCode']])->Fetch();
$accIB = CIBlock::GetList([], ['CODE' => $settings['accCode']])->Fetch();
if (!$donIB || !$accIB) { log_msg("❌ Не найдены IBLOCK: Donor={$settings['donCode']}, Acceptor={$settings['accCode']}", 'err'); exit; }
log_msg("✅ Донор ID: {$donIB['ID']} | Приемник ID: {$accIB['ID']}", 'ok');

log_msg("📂 ЭТАП 2: Загрузка конфигурации", 'step');
$accFile = __DIR__ . '/groupAccordance.php';
if (!file_exists($accFile)) { log_msg("❌ Файл не найден: $accFile", 'err'); exit; }
$accordances = include($accFile);
if (!is_array($accordances)) { log_msg("❌ Ошибка в $accFile: не вернул массив", 'err'); exit; }
log_msg("✅ Загружено правил маппинга: " . count($accordances), 'ok');

// Диагностика
if ($settings['diag']) {
    log_msg("📊 ДИАГНОСТИКА: Проверка соответствия разделов", 'step');
    $missed = 0;
    foreach ($accordances as $rule) {
        $secCode = $rule['secCode'] ?? '';
        if (!$secCode) continue;
        $sec = CIBlockSection::GetList([], ['IBLOCK_ID' => $accIB['ID'], '=CODE' => $secCode])->Fetch();
        if ($sec) log_msg("   ✅ {$rule[3]} (idDon={$rule['idDon']}) -> CODE=$secCode | ID={$sec['ID']}", 'ok');
        else { log_msg("   ❌ {$rule[3]} (idDon={$rule['idDon']}) -> CODE=$secCode | НЕ НАЙДЕН!", 'err'); $missed++; }
    }
    log_msg($missed ? "⚠️ Пропущено разделов: $missed" : "✅ Все разделы найдены", $missed ? 'warn' : 'ok');
    exit;
}

// Очистка
if ($settings['clean']) {
    log_msg("🗑 Режим очистки приемника", 'warn');
    $delRes = CIBlockElement::GetList(['ID'=>'ASC'], ['IBLOCK_CODE'=>$settings['accCode']], false, false, ['ID']);
    while($el = $delRes->Fetch()) {
        if (class_exists('\Bitrix\Iblock\InheritedProperty\ElementValues')) (new \Bitrix\Iblock\InheritedProperty\ElementValues($accIB['ID'], $el['ID']))->clearValues();
        CIBlockElement::Delete($el['ID']);
    }
    log_msg("✅ Очистка завершена", 'ok');
}

// Карта разделов
$sectMap = [];
foreach ($accordances as $rule) {
    $sec = CIBlockSection::GetList([], ['IBLOCK_ID' => $accIB['ID'], '=CODE' => ($rule['secCode'] ?? '')])->Fetch();
    if ($sec) $sectMap[(string)$rule['idDon']] = (int)$sec['ID'];
}

log_msg("📦 ЭТАП 3: Запуск обработки товаров", 'step');
$filter = ['IBLOCK_CODE' => $settings['donCode']];
$donProducts = CIBlockElement::GetList(['ID' => 'ASC'], $filter, false, false, []);
$total = $donProducts->NavRecordCount ?: 0;
log_msg("📋 Всего товаров в доноре: $total", 'info');

$count = 0; $created = 0; $updated = 0; $errors = 0;
$elObj = new CIBlockElement;
$processedExtIds = [];

while ($donEl = $donProducts->GetNextElement()) {
    if (!$isCLI && connection_aborted()) { log_msg("⛔ Вкладка закрыта", 'warn'); break; }
    if ($settings['limit'] > 0 && $count >= $settings['limit']) { log_msg("🛑 Лимит достигнут ({$settings['limit']})", 'warn'); break; }
    $count++;
    $fields = $donEl->GetFields();
    $props  = $donEl->GetProperties();

    if (!empty($fields['EXTERNAL_ID'])) $processedExtIds[] = $fields['EXTERNAL_ID'];

    $donSectRaw = $props['_2_PODRAZDELSAYTA_NEW']['VALUE'] ?? '';
    if (is_array($donSectRaw)) $donSectRaw = reset($donSectRaw);
    $donSectId = (string)trim($donSectRaw);

    log_msg("🔄 #$count: Don ID={$fields['ID']} | SectID=" . ($donSectId ?: '(пусто)'), 'info');

    $accSearch = CIBlockElement::GetList([], ['IBLOCK_CODE'=>$settings['accCode'], 'EXTERNAL_ID'=>$fields['EXTERNAL_ID']], false, false, ['ID']);
    $accId = ($accFound = $accSearch->GetNext()) ? (int)$accFound['ID'] : false;

    $accSectId = isset($sectMap[$donSectId]) ? $sectMap[$donSectId] : 0;
    if ($donSectId && !$accSectId) log_msg("   ⚠️ Раздел idDon=$donSectId не найден в приёмнике", 'warn');

    $weightRaw = $props['_3_VESPMSAYT']['VALUE'] ?? '';
    $lengthRaw = $props['_7_DLINASHT']['VALUE'] ?? '';
    $weightVal = safeFloat($weightRaw);
    $lengthVal = safeFloat($lengthRaw);

    // 📝 ФОРМИРОВАНИЕ НАЗВАНИЯ (точно как в Updater.php::crtNameForAcc())
    $baseName = $props['_3_NAIMENOVANIESAYT']['VALUE'] ?: $fields['NAME'];
    $lenSuffix = $props['_4_DLINYSAYT']['VALUE'] ? " {$props['_4_DLINYSAYT']['VALUE']}" : '';
    $accName = trim($baseName . $lenSuffix);

    $accFields = [
        'IBLOCK_ID' => $accIB['ID'],
        'NAME' => $accName,
        'CODE' => $fields['CODE'], 'ACTIVE' => 'Y',
        'EXTERNAL_ID' => $fields['EXTERNAL_ID'], 'PREVIEW_TEXT' => $fields['PREVIEW_TEXT'],
        'SORT' => (int)($props['SORTIROVKA_SAYT']['VALUE'] ?: 500),
        'PROPERTY_VALUES' => []
    ];

    if ($accSectId > 0) {
        $accFields['IBLOCK_SECTION_ID'] = $accSectId;
        log_msg("   📂 Раздел привязан: {$accSectId}", 'dim');
    } elseif ($donSectId) {
        log_msg("   ⚠️ Раздел idDon=$donSectId не найден (товар будет в корне)", 'warn');
    }

    $accProps = [];
    $accProps['TYPE_METALL'] = $accProps['SIZE'] = $props['_5_MARKASAYT_ILI_RAZMER_SETKI']['VALUE'] ?? '';
    $accProps['PRICE_CUTTING'] = $props['_6_POREZKASAYT']['VALUE'] ?? '';
    $accProps['WEIGHT_PM'] = $weightVal * 1000;
    $accProps['_3_VESPMSAYT'] = $weightRaw;
    $accProps['_7_DLINASHT'] = $lengthRaw;
    $accProps['_8_SHT'] = $props['_8_SHT']['VALUE'] ?? '';
    $accProps['UPD_STATUS'] = $accId ? 'updated' : 'created';
    $accFields['PROPERTY_VALUES'] = $accProps;

    // Создание / Обновление
    if ($accId) {
        $res = $elObj->Update($accId, $accFields); $errors += $res ? 0 : 1; $updated++;
    } else {
        $accId = $elObj->Add($accFields); $errors += $accId ? 0 : 1; $created++;
    }
    if (!$accId) log_msg("   ❌ Ошибка БД: {$elObj->LAST_ERROR}", 'err');
    else log_msg("   ✅ Сохранен (ID=$accId) | Имя: " . mb_substr($accName, 0, 45) . (mb_strlen($accName) > 45 ? '...' : ''), 'ok');

    if (!$accId) continue;

    // 📦 КАТАЛОГ (Остаток и Вес) - обновляем только при изменении
    $donCat = CCatalogProduct::GetByID($fields['ID']);
    $curQty = $donCat['QUANTITY'] ?? 0;
    $curWeight = $donCat['WEIGHT'] ?? 0;
    $newWeight = $accProps['WEIGHT_PM'];
    if ($curQty != 0 || abs($curWeight - $newWeight) > 0.01) {
        CCatalogProduct::Add(['ID' => $accId, 'QUANTITY' => $curQty, 'QUANTITY_TRACE' => 'D', 'WEIGHT' => $newWeight]);
        log_msg("   📦 Каталог: Qty={$curQty} | Weight={$newWeight}", 'ok');
    } else { log_msg("   📦 Каталог без изменений", 'dim'); }

    // 💰 ЦЕНЫ - обновляем только при изменении > 1 копейки
    $priceDonRes = CPrice::GetList([], ['PRODUCT_ID'=>$fields['ID'], 'CATALOG_GROUP_ID'=>$settings['donPriceId']]);
    $priceDon = ($p = $priceDonRes->Fetch()) ? safeFloat($p['PRICE']) : 0.0;
    $basePrice = ($lengthVal > 0) ? round($priceDon * $lengthVal) : $priceDon;

    $check1 = CPrice::GetList([], ['PRODUCT_ID'=>$accId, 'CATALOG_GROUP_ID'=>1]); $p1 = $check1->Fetch();
    $curBase = $p1 ? (float)$p1['PRICE'] : 0.0;
    if ($basePrice > 0 && abs($curBase - $basePrice) > 0.01) {
        $p1 ? CPrice::Update($p1['ID'], ['PRICE'=>$basePrice, 'CURRENCY'=>'RUB']) : CPrice::Add(['PRODUCT_ID'=>$accId, 'CATALOG_GROUP_ID'=>1, 'PRICE'=>$basePrice, 'CURRENCY'=>'RUB']);
        log_msg("   💰 Base: {$curBase} → {$basePrice}", 'ok');
    } else { log_msg("   💰 Base без изменений: {$basePrice}", 'dim'); }

    $check2 = CPrice::GetList([], ['PRODUCT_ID'=>$accId, 'CATALOG_GROUP_ID'=>$settings['donPriceId']]); $p2 = $check2->Fetch();
    $curDon = $p2 ? (float)$p2['PRICE'] : 0.0;
    if ($priceDon > 0 && abs($curDon - $priceDon) > 0.01) {
        $p2 ? CPrice::Update($p2['ID'], ['PRICE'=>$priceDon, 'CURRENCY'=>'RUB']) : CPrice::Add(['PRODUCT_ID'=>$accId, 'CATALOG_GROUP_ID'=>$settings['donPriceId'], 'PRICE'=>$priceDon, 'CURRENCY'=>'RUB']);
        log_msg("   💰 Donor: {$curDon} → {$priceDon}", 'ok');
    } else { log_msg("   💰 Donor без изменений: {$priceDon}", 'dim'); }

    if ($settings['sleep'] > 0) usleep($settings['sleep'] * 1000);
}

// 👻 ЭТАП 4: ОБРАБОТКА СИРОТ
if ($settings['orphans'] === 'deactivate' && !empty($processedExtIds)) {
    log_msg("🔍 ЭТАП 4: Деактивация сирот", 'step');
    // Собираем ВСЕ EXTERNAL_ID из донора для точной проверки (игнорируя ?limit)
    $donExtIds = [];
    $donCheck = CIBlockElement::GetList([], ['IBLOCK_CODE' => $settings['donCode']], false, false, ['EXTERNAL_ID']);
    while ($row = $donCheck->Fetch()) if (!empty($row['EXTERNAL_ID'])) $donExtIds[] = $row['EXTERNAL_ID'];
    
    if (empty($donExtIds)) {
        log_msg("⚠️ В доноре нет товаров с EXTERNAL_ID. Пропускаем.", 'warn');
    } else {
        $orphanRes = CIBlockElement::GetList([], ['IBLOCK_CODE' => $settings['accCode'], '!EXTERNAL_ID' => $donExtIds, 'ACTIVE' => 'Y'], false, false, ['ID', 'NAME']);
        $deactivated = 0;
        while ($orphan = $orphanRes->GetNext()) {
            $elObj->Update($orphan['ID'], ['ACTIVE' => 'N']); // ✅ Вызов через экземпляр (PHP 8 Safe)
            log_msg("   🗑 Деактивирован: {$orphan['NAME']} (ID: {$orphan['ID']})", 'warn');
            $deactivated++; usleep(10000);
        }
        log_msg($deactivated ? "✅ Деактивировано сирот: $deactivated" : "✅ Сирот не найдено", $deactivated ? 'warn' : 'ok');
    }
}

// 📊 ИТОГИ
log_msg("📊 ИТОГИ:", 'step');
log_msg("   Обработано: $count | Создано: $created | Обновлено: $updated | Ошибок: $errors", $errors>0?'err':'ok');
log_msg("✅ Завершено!", 'step');

if (!$isCLI) { echo "</div></body></html>"; exit; }