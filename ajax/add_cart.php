<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if( !empty( $_GET["id"] ))
    $id = (int)$_GET["id"];

if( !empty( $_GET["quantity"] ) )
    $quantity = (int)$_GET["quantity"];
else
    $quantity = 1;

if( !$id )
    return false;

CModule::IncludeModule( 'catalog' );
CModule::IncludeModule( 'sale' );

Add2BasketByProductID( $id, $quantity );
?>