<?php

require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

if (!$USER->IsAdmin()) {
    LocalRedirect('/');
}

\Bitrix\Main\Loader::includeModule('iblock');

$row = 1; 
$IBLOCK_ID = 4;  

$el = new CIBlockElement; 
$arProps = []; 
$arNameProps = ["Требования", "Обязанности", "Условия работы"]; // Массив с именами свойств
$ibpenum = new CIBlockPropertyEnum;

$i = 0;

// Достаём имеющиеся свойства для сравнения
$properties = CIBlockProperty::GetList(Array("sort"=>"asc"), Array("ACTIVE"=>"Y", "IBLOCK_ID"=>$IBLOCK_ID));
while ($prop_fields = $properties->GetNext())
{
    $arIbProp[] = $prop_fields["NAME"];
    $arIbPropID[] = $prop_fields["ID"];
}

// Добавление свойств
while ($i < count($arNameProps)) {
    if ($arIbProp[$i] == $arNameProps[$i]) {
        echo "Свойство &quot;" . $arIbProp[$i] . "&quot; уже существует в инфоблоке!<br>";
    } else {
        $arFields = ( [
            "IBLOCK_ID" => $IBLOCK_ID,
            "NAME" => $arNameProps[$i],
            "ACTIVE" => "Y",
            "PROPERTY_TYPE" => "L",
            "LIST_TYPE" => "L",
            "MULTIPLE" => "Y" 
        ]);
        $ibp = new CIBlockProperty;
        $PropID = $ibp->Add($arFields);
        echo "Свойство &quot;" . $arNameProps[$i] . "&quot; добавлено!<br>";   
    }
    $i++;
}

$handle = fopen($_SERVER["DOCUMENT_ROOT"] . "/local/parser/vacancy.csv", "r");

// Работа с данными из файла
if ($handle) {
    while (($data = fgetcsv($handle,1000, ",")) !== false) {
        if ($row == 1) {
            $row++;
            continue;
        }
        $row++;

        $PROP["COMPLEX"] = $data[0] . "&#44; " . $data[1]; // № п/п,Комбинат
        $PROP["LOCATION"] = $data[2]; // Местоположение
        $PROP["POSITION"] = $data[3]; // Название должности
        $PROP["REQUIREMENTS"] = $data[4]; //Требования
        $PROP["CHARGE"] = $data[5]; // Обязанности
        $PROP["CONDITIONS"] = $data[6]; // Условия работы 
        $PROP["SALARY"] = $data[7]; // Зарплата
        $PROP["CATEGORY_POSITION"] = $data[8]; //Категория позиции
        $PROP["TYPE_EMPLOYMENT"] = $data[9]; // Тип занятости
        $PROP["SCHEDULE"] = $data[10]; // График работы
        $PROP["FIELD"] = $data[11]; // Сфера деятельности
        $PROP["EMAIL"] = $data[12]; // Кому направить резюме (e-mail)
        $PROP["DATE"] = date('d.m.Y'); // Дата

        foreach ($PROP as $key => $value) {
            if (strpos($value, "•") !== false) {
                $value = str_ireplace("•", "", $value);
                $value = explode(";", $value);
            }
            $PROP[$key] = $value; 
        }

        if ($PROP["REQUIREMENTS"]) {
            foreach ($PROP["REQUIREMENTS"] as $value) {
                $arRequirements[] = $value;
                $arRequirements = array_slice(array_unique($arRequirements), 0, 5); // Экспериментальная строка (отрезали массив до 5-ти элементов)
            }
        }
        if ($PROP["CHARGE"]) {
            foreach ($PROP["CHARGE"] as $value) {
                $arCharge[] = $value;
                $arCharge = array_slice(array_unique($arCharge), 0, 5); // Экспериментальная строка (отрезали массив до 5-ти элементов)
            }
        }
        if ($PROP["CONDITIONS"]) {
            foreach ($PROP["CONDITIONS"] as $value) {
                $arConditions[] = $value;
                $arConditions = array_slice(array_unique($arConditions), 0, 5); // Экспериментальная строка (отрезали массив до 5-ти элементов)
            }
        }    
 
        $arLoadProductArray = Array(
            "MODIFIED_BY"    => $USER->GetID(),
            "ACTIVE" => "Y", 
            "IBLOCK_ID" => 4,
            "PROPERTY_VALUES"=> $PROP,
            "NAME" => $data[3],
        );        
        if($PRODUCT_ID = $el->Add($arLoadProductArray)) {
            echo "Добавлен элемент с ID: " . $PRODUCT_ID . "<br>";
        }
        else {
            echo "Ошибка: ".$el->LAST_ERROR;
        }
    }
    fclose($handle);
} else {
    echo "Невозможно открыть файл";
}
foreach ($arRequirements as $item) {
    if ($PropID = $ibpenum->Add([
        "PROPERTY_ID"=>$arIbPropID[0], 
        "VALUE"=>$item,
    ]))
    echo "Добавлено значение списка &quot;Требования&quot; с ID: ".$PropID."<br>";
}

foreach ($arCharge as $item) {
    if ($PropID = $ibpenum->Add([
        "PROPERTY_ID"=>$arIbPropID[1], 
        "VALUE"=>$item,
    ]))
    echo "Добавлено значение списка &quot;Обязанности&quot; с ID: ".$PropID."<br>";
}

foreach ($arConditions as $item) {
    if ($PropID = $ibpenum->Add([
        "PROPERTY_ID"=>$arIbPropID[2], 
        "VALUE"=>$item,
    ]))
    echo "Добавлено значение списка &quot;Условия работы&quot; с ID: ".$PropID."<br>";
}