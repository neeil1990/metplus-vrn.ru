<?

// Карта классов. Задает соответствие класса модулю
$map = array(
    'iblock' => 'CIBlock CIBlockElement CIBlockSection CIBlockResult',
    'catalog' => 'CCatalogProduct CPrice',
    'main' => 'CMain CApplicationException',
);

// Преобразуем карту в удобный для обработки вид
$preparedMap = array();

foreach($map as $module => $classes) {
    foreach(explode(' ', $classes) as $class) $preparedMap[$class] = $module;
}

spl_autoload_register(function($classname) use ($preparedMap) {
    // Определяем к какому модулю принадлежит класс
    if (isset($preparedMap[$classname]) && $preparedMap[$classname]) {
        // ... и подключаем этот модуль
        CModule::IncludeModule($preparedMap[$classname]);
        // ... а затем передаем управление автозагрузчику битрикса
        \Bitrix\Main\Loader::autoLoad($classname);
    }
});