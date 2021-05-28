<?php
set_time_limit(0);
ini_set('max_execution_time', 0);
ini_set('memory_limit', '2048M');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$html = file_get_contents('https://www.checkers.co.za/c-2256/All-Departments');
// echo $html;
// $start = stripos($html, '<div class="product__listing product__grid"');
// $end = stripos($html, '<div id="addToCartTitle"', $offset = $start);
// $length = $end - $start;
// $htmlSection = substr($html, $start, $length);
// echo $htmlSection;
// echo "<br />";echo "<br />";echo "<br />";

$regex = '/<p class="total-number-of-results pull-right">(.*?)<\/p>/s';
preg_match_all($regex, $html, $matches);
$listItems = $matches[0];
foreach ($listItems as $item) {
	$total_product = preg_replace('/[^0-9]/', '', $item);
}
$per_page = 20;
if($total_product){
	$total_pages = ceil($total_product / $per_page );
}
// $total_pages = 200; // SET THE PAGE COUNT



echo "<pre>";
print_r($listItems);
die ("i m here");



$product_list = array();
$product_tot = 0;
for($page = 0; $page < $total_pages; $page++) {
	
	$url = "https://www.checkers.co.za/c-2256/All-Departments?q=%3Arelevance%3AbrowseAllStoresFacetOff%3AbrowseAllStoresFacetOff&page=". $page ."";	
	$html = file_get_contents($url);
	$start = stripos($html, '<div class="product__listing product__grid"');
	$end = stripos($html, '<div id="addToCartTitle"', $offset = $start);
	$length = $end - $start;
	$htmlSection = substr($html, $start, $length);

	//SKU
	$regex = '/<div class="hidden productListJSON">(.*?)<\/div>/s';
	if (preg_match($regex, $htmlSection, $list) ) {
		$data = json_decode(strip_tags($list[0]));
		// echo $list[0];
		// echo "<pre>";print_r($data);echo "</pre>";
		$sku_arr = $price_arr = $format_price_arr = array();
		foreach($data as $key => $val) {
			$sku_arr[] = $val->code;
			$price_arr[] = $val->price->value;
			$format_price_arr[] = $val->price->formattedValue;
		}
	}

	//Price
	$regex = '/<span class="now">(.*?)<\/span>/s';
	preg_match_all($regex, $htmlSection, $matches);
	$listItems = $matches[0];
	$price_new_arr = array();
	foreach ($listItems as $item) {
		$price_new_arr[] = strip_tags($item);
	}

	//Product Name
	$regex = '/<h3 class="item-product__name">(.*?)<\/h3>/s';
	preg_match_all($regex, $htmlSection, $matches);
	$listItems = $matches[0];
	$product_name = array();
	foreach ($listItems as $item) {
		$product_name[] = strip_tags($item);
	}

	// Image
	preg_match_all('/<img[^>]+>/', $htmlSection, $matches);
	$listItems = $matches[0];
	$product_url = array();
	foreach ($listItems as $item) {
		preg_match( '@data-original-src="([^"]+)"@' , $item, $match );
		$url = "https://www.checkers.co.za/{$match[1]}";
		$product_url[] = $url;
	}
	
	// echo "<pre>";
	// print_r($sku_arr);
	// print_r($price_arr);
	// print_r($format_price_arr);
	// print_r($price_new_arr);
	// print_r($product_name);
	// print_r($product_url);
	
	$count_tot = count($sku_arr);
	if(
		count($sku_arr) == $count_tot ||
		count($price_arr) == $count_tot ||
		count($format_price_arr) == $count_tot ||
		count($price_new_arr) == $count_tot ||
		count($product_name) == $count_tot ||
		count($product_url) == $count_tot 
		) {

		for($row = 0; $row < $count_tot; $row++) {
			$product_list[$product_tot]['sku'] = $sku_arr[$row];
			$product_list[$product_tot]['price'] = $price_arr[$row];
			$product_list[$product_tot]['price_format'] = $format_price_arr[$row];
			$product_list[$product_tot]['price_new'] = $price_new_arr[$row];
			$product_list[$product_tot]['product_name'] = $product_name[$row];
			$product_list[$product_tot]['product_url'] = $product_url[$row];
			$product_tot++;
		}
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

<?php 
/*
<script>
// alert(1);
// $( document ).ready(function() {
// alert(2);
	// $(".product-frame.js-rainmaker").each(function( index ) {
		// alert(3);
		// console.log($( this ).text());
	// });
	
// });
</script>

*/

// https://www.checkers.co.za/c-2256/All-Departments?q=%3Arelevance%3AbrowseAllStoresFacetOff%3AbrowseAllStoresFacetOff&page=1 
// https://www.checkers.co.za/c-2256/All-Departments?q=%3Arelevance%3AbrowseAllStoresFacetOff%3AbrowseAllStoresFacetOff&page=6
/*-
$curl = curl_init('https://www.checkers.co.za/c-2256/All-Departments?q=%3Arelevance%3AbrowseAllStoresFacetOff%3AbrowseAllStoresFacetOff&page=6');
curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
 
$page = curl_exec($curl);
if(curl_errno($curl)) // check for execution errors
{
	echo 'Scraper error: ' . curl_error($curl);
	exit;
}
curl_close($curl);
*/


/*-
echo "<pre>";
// print_r($page);
echo "<div>".  $page ."</div>";
echo "</pre>";
data-product-ga="{"id": "10125342EA", "name": "Fatti's & Moni's Spaghetti Pasta 500g", "price": "14.99", "brand": "", "category": "", "position": "1", "variant": "", "list": ""}"
*/


// $regex = '/<div id="case_textlist">(.*?)<\/div>/s';
// $regex = '/<div class="hidden productListJSON">(.*?)<\/div>/s';
// $regex = '/<main data-currency-iso-code="ZAR"> (.*?)<\/main>/s';
// if (preg_match($regex, $page, $list) )
    // echo $list[0];
// else
    // print "Not found";

// echo "<br />";echo "<br />";echo "<br />";



function curl_download($Url){
  
    if (!function_exists('curl_init')){
        die('cURL is not installed. Install and try again.');
    }
  
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $Url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    curl_close($ch);
  
    return $output;
}


// echo "<div class='xyz' style='background-color:#f20000;'>";
// print curl_download('https://www.checkers.co.za/c-2256/All-Departments');
// echo "</div>";


/*
echo "1";
$curl = curl_init('http://www.flipkart.com/apple-iphone-5s/p/itmdv6f75dyxhmt4?pid=MOBDPPZZDX8WSPAT');
curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($curl,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');

echo "2";
$page = curl_exec($curl);

echo "3";

if(!empty($curl)) { //if any html is actually returned

    $pokemon_doc = new DOMDocument;
    libxml_use_internal_errors(true);
    $pokemon_doc->loadHTML($page);
echo "<pre>";
print_r($page);
echo "</pre>";

    libxml_clear_errors();

    $pokemon_xpath = new DOMXPath($pokemon_doc);
echo "4";
    $price = $pokemon_xpath->evaluate('string(//div[@class="prices"]/meta[@itemprop="price"]/@content)');
    echo $price;

    $rupees = $pokemon_xpath->evaluate('string(//div[@class="prices"]/div/span)');
    echo $rupees;
}
else {
    print "Not found";
}
*/
?>