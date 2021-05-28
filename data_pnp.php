<?php
set_time_limit(0);
ini_set('max_execution_time', 0);
ini_set('memory_limit', '2048M');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$html = file_get_contents('https://www.pnp.co.za/pnpstorefront/pnp/en/pnpmerchandising/2021/May/Week-13/Smart-Price-is-Our-Best-Price/c/smart-price-is-our-best-price-1591389952');

$regex = '/<div class="totalResults">(.*?)<\/div>/s';
preg_match_all($regex, $html, $matches);
$listItems = $matches[0];
foreach ($listItems as $item) {
	$product_count = strip_tags($item);
	$product_count = explode("OF", $product_count);
	$total_product = isset($product_count[1]) ? trim($product_count[1]) : 0;
}

$product_list = array();
$product_tot = 0;

$html = file_get_contents("https://www.pnp.co.za/pnpstorefront/pnp/en/pnpmerchandising/2021/May/Week-13/Smart-Price-is-Our-Best-Price/c/smart-price-is-our-best-price-1591389952?q=%3Arelevance&pageSize={$total_product}&page=0");

$start = stripos($html, '<ul class="col-md-12 product-listing product-grid product-grid">');
$end = stripos($html, '</ul>', $offset = $start);
$length = $end - $start;
$htmlSection = substr($html, $start, $length);
// print_r($htmlSection);

//Product Name
$regex = '/<div class="item-name">(.*?)<\/div>/s';
preg_match_all($regex, $htmlSection, $matches);
$listItems = $matches[0];
$product_name = array();
foreach ($listItems as $item) {
	$product_name[] = strip_tags($item);
}

//Price
$regex = '/<div class="product-price">(.*?)<\/div>/s';
preg_match_all($regex, $htmlSection, $matches);
$listItems = $matches[0];
$price_arr = array();
$price_new_org = array();
foreach ($listItems as $item) {
	$item_price = explode("<span>", $item);
	$price_int = isset($item_price[0]) ? strip_tags($item_price[0]) : "R0";
	$price_dec = isset($price_dec[1]) ? strip_tags($item_price[1]) : "00";
	$price_arr[] = trim($price_int). "." . trim($price_dec);
	$price_new_org[] = trim(strip_tags($item));
}

// Image & SKU
preg_match_all('/<img[^>]+>/', $htmlSection, $matches);
$listItems = $matches[0];
$product_url = $sku_arr = array();
foreach ($listItems as $item) {
	preg_match( '@src="([^"]+)"@' , $item, $match );
	preg_match( '@title="([^"]+)"@' , $item, $title ); // Get img tag title value
	preg_match( '@alt="([^"]+)"@' , $item, $alt_val ); // Get img tag alt value
	// $url = "https://www.checkers.co.za/{$match[1]}";	
	if(isset($title[1]))  {
		$product_url[] = $match[1];
		$full_sku = explode("/", $match[1]);
		$sku_arr[] = isset($full_sku[7]) ? strip_tags($full_sku[7]) : "unknown-sku";
	}
}

	
// echo "<pre>";
// print_r($product_name);
// print_r($price_arr);
// print_r($price_new_org);
// print_r($sku_arr);
// print_r($product_url);

$count_tot = count($product_name);
if(
	count($sku_arr) == $count_tot ||
	count($price_arr) == $count_tot ||
	count($price_new_org) == $count_tot ||
	count($product_name) == $count_tot ||
	count($product_url) == $count_tot 
	) {

	for($row = 0; $row < $count_tot; $row++) {
		$product_list[$product_tot]['sku'] = $sku_arr[$row];
		$product_list[$product_tot]['price'] = $price_arr[$row];
		$product_list[$product_tot]['price_orgi'] = $price_new_org[$row];
		$product_list[$product_tot]['product_name'] = $product_name[$row];
		$product_list[$product_tot]['product_url'] = $product_url[$row];
		$product_tot++;
	}
}

// echo "<pre>";
// print_r($product_list);
// // echo "</br>";echo "</br>";echo "</br>";echo "</br>";echo "</br>";echo "</br>";echo "</br>";

download_send_headers("data_export_" . date("Y-m-d-H-i-s") . ".csv");
echo array2csv($product_list);




/******************************************************************************************************************/
/******************************************************************************************************************/
/**************************************************** FUNCTION ****************************************************/
/******************************************************************************************************************/
/******************************************************************************************************************/
function array2csv(array &$array)
{
	if (count($array) == 0) {
		return null;
	}
	ob_start();
	$df = fopen("php://output", 'w');
	fputcsv($df, array_keys(reset($array)));
	foreach ($array as $row) {
		fputcsv($df, $row);
	}
	fclose($df);
	return ob_get_clean();
}

function download_send_headers($filename) {
	// disable caching
	$now = gmdate("D, d M Y H:i:s");
	header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
	header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
	header("Last-Modified: {$now} GMT");

	// force download  
	header("Content-Type: application/force-download");
	header("Content-Type: application/octet-stream");
	header("Content-Type: application/download");

	// disposition / encoding on response body
	header("Content-Disposition: attachment;filename={$filename}");
	header("Content-Transfer-Encoding: binary");
}
?>