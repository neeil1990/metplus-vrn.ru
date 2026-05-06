<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");


?>
<br />
<br />
<br />

<div style="margin:0 auto">

<?
$APPLICATION->IncludeComponent(
	"prime:ads.btn", 
	".default", 
	[
		"SHOW" => "Y",
		"DESCRIPTION" => "ООО «КОРПОРАЦИЯ МЕТАЛЛИНВЕСТ» <br /> erid:2Wfslghjslgk1",
		"COMPONENT_TEMPLATE" => ".default"
	],
	false
); 
?>

</div>

<br />
<br />
<br />
<br />
<br />
<br />
<br />



<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>