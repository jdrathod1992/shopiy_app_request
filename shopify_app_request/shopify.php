

<?php
// Helper method to determine if a shop domain is valid
function validateShopDomain($shop)
{
  $substring = explode('.', $shop);

  // 'blah.myshopify.com'
  if (count($substring) != 3) {
    return FALSE;
  }

  // allow dashes and alphanumberic characters
  $substring[0] = str_replace('-', '', $substring[0]);
  return (ctype_alnum($substring[0]) && $substring[1] . '.' . $substring[2] == 'myshopify.com');
}

// Helper method to determine if a request is valid
function validateHmac($params, $secret)
{
  $hmac = $params['hmac'];
  unset($params['hmac']);
  ksort($params);

  $computedHmac = hash_hmac('sha256', http_build_query($params), $secret);

  //$calculatedHmac = base64_encode(hash_hmac('sha256', $hmacSignature, $clientSharedSecret, true));

  //$calculated_hmac = base64_encode(hash_hmac('sha256', $params, $SHOPIFY_API_SECRET, true));

  return hash_equals($hmac, $computedHmac);
  //return $hmac.equals($computedHmac);
  //return strcmp($hmac, $computedHmac);
}

// Helper method for exchanging credentials
function getAccessToken($shop, $apiKey, $secret, $code)
{
  $query = array(
    'client_id' => $apiKey,
    'client_secret' => $secret,
    'code' => $code
  );

  // Build access token URL
  $access_token_url = "https://{$shop}/admin/oauth/access_token";

  // Configure curl client and execute request
  $curl = curl_init();
  $curlOptions = array(
    CURLOPT_RETURNTRANSFER => TRUE,
    CURLOPT_URL => $access_token_url,
    CURLOPT_SSL_VERIFYPEER => FALSE,
    CURLOPT_POSTFIELDS => http_build_query($query)
  );
  curl_setopt_array($curl, $curlOptions);
  $jsonResponse = json_decode(curl_exec($curl), TRUE);
  curl_close($curl);


  return $jsonResponse['access_token'];
}

// Helper method for making Shopify API requests
function shopify_call($shop, $token, $resource, $params = array(), $method = 'GET')
{
  $url = "https://{$shop}/admin/{$resource}.json";




  $curlOptions = array(
    CURLOPT_RETURNTRANSFER => TRUE,
    CURLOPT_SSL_VERIFYPEER => FALSE
  );

  if ($method == 'GET') {
    if (!is_null($params)) {

      $url = $url . "?" . http_build_query($params);
    }
  } else {
    $curlOptions[CURLOPT_CUSTOMREQUEST] = $method;
  }

  //print_r($url);

  $curlOptions[CURLOPT_URL] = $url;

  $requestHeaders = array(
    "X-Shopify-Access-Token: ${token}",
    "Accept: application/json"
  );

  if ($method == 'POST' || $method == 'PUT') {
    $requestHeaders[] = "Content-Type: application/json";

    if (!is_null($params)) {

      $curlOptions[CURLOPT_POSTFIELDS] = json_encode($params);
    }
  }

  $curlOptions[CURLOPT_HTTPHEADER] = $requestHeaders;

  $curl = curl_init();
  curl_setopt_array($curl, $curlOptions);
  $response = curl_exec($curl);


  //print_r($response);







  curl_close($curl);
  // print_r($response);exit();
  return json_decode($response, TRUE);
}


function checkpayment($shop, $accessToken)
{

  //  echo $shop." ".$accessToken;
  $curl = curl_init();

  curl_setopt_array($curl, array(
    //CURLOPT_URL => "https://{$shop}/admin/api/2019-10/recurring_application_charges/{$getChargeId}.json?access_token={$accessToken}",
    CURLOPT_URL => "https://{$shop}/admin/api/2019-10/recurring_application_charges.json?access_token={$accessToken}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_VERBOSE, true,
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_POSTFIELDS =>  '',
    CURLOPT_HTTPHEADER => array(
      "Content-Type: application/json",
      "Postman-Token: ******************************",
      "X-Shopify-Storefront-Access-Token: {$accessToken}",
      "Access-Control-Allow-Origin :*",
      "cache-control: no-cache",
      "X-Frame-Options : sameorigin"
    ),
  ));

  $response = curl_exec($curl);
  $err = curl_error($curl);
  curl_close($curl);

  if ($err) {
    $result = array(
      "status" => 0,
      "result" => $err
    );
    //  return  json_encode($result);
    return  $result;
  } else {
    // print_r($response);
    $res = json_decode(trim($response), TRUE);
    $arr = array();
    $validcharge = false;
    foreach ($res['recurring_application_charges'] as $key => $value) {
      if (!$validcharge) {
        $validcharge = true;
        $arr = $value;
      }
    }

    $result = array(
      "status" => 1,
      "result" => $arr
    );
    //  return  json_encode($result);
  }

  return  $result;
}

//<?php
require_once 'connect.php';

$shop = '';
$access_token = '';

ini_set('max_execution_time', '0');

$startRange = (int)$_GET['start'];
$endRange = (int)$_GET['end'];
$apiCallCount = 1;

$start = 1;
$end = 7;
$row = 1;
$style_code_check = '';
$product_id = '';
// $ApiLoopingCount = 1;
$related_items = array();
$locationdetails =  shopify_call($shop, $access_token, 'api/2022-01/locations', array(), 'GET');


function addSEOMetafieldsData($shop, $access_token, $product_id, $web_description1)
{
  $meta_seo_description_curl_body = [
    "metafield" => [
      "key" => "description_tag",
      "value" => $web_description1,
      "namespace" => "global",
      "value_type" => "string"
    ]
  ];
  $meta_seo_description_curl_body = json_encode($meta_seo_description_curl_body);
  shopify_call($shop, $access_token, 'api/2021-10/products/' . $product_id . '/metafields', $meta_seo_description_curl_body, 'POST');
}



if (($handle = fopen("jewelinv.csv", "r")) !== FALSE) {
  while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
    // $num = count($data);
    // echo '$row'.$row.$start.$end;
    // if($row != 1 && $start <= $row && $end >= $row){
    if ($row != 1 && $startRange <= $apiCallCount && $endRange >= $apiCallCount) {
      $warehouse = $data[0];
      $manufacturer = $data[1];
      $vendor_description = $data[2];
      $master_category = $data[3];
      $category = $data[4];
      $subcategory_1 = $data[5];
      $subcategory_2 = $data[6];
      $subcategory_3 = $data[7];
      $collection = $data[8];
      $base_material = $data[9];
      $finish = $data[10];
      $stone = $data[11];
      $product_group = $data[12];
      $length = $data[13];
      $width = $data[14];
      $height = $data[15];
      $style_code = $data[16];
      $barcode = $data[17];
      $upc = $data[18];
      $onhand = $data[19];
      $booked = $data[20];
      $onorder = $data[21];
      $description = $data[22];
      $web_description = $data[23];
      $web_description1 = $data[24];
      $v_color = $data[25];
      $cmp_color = $data[26];
      $color_description = $data[27];
      $family_stone = $data[28];
      $family_color = $data[29];
      $size_description = $data[30];
      $cmp_size = $data[31];
      $cmp_style = $data[32];
      $style_description = $data[33];
      $web_enabled = $data[34];
      $whs_enabled = $data[35];
      $cost = $data[36];
      $price = $data[37];
      $msrp = $data[38];
      $whs_discount_price = $data[39];
      $ret_discount_price = $data[40];
      $related_item_1 = $data[41];
      $related_items = addRelatedItem($related_items, $related_item_1);
      $related_item_2 = $data[42];
      $related_items = addRelatedItem($related_items, $related_item_2);
      $related_item_3 = $data[43];
      $related_items = addRelatedItem($related_items, $related_item_3);
      $related_item_4 = $data[44];
      $related_items = addRelatedItem($related_items, $related_item_4);
      $related_item_5 = $data[45];
      $related_items = addRelatedItem($related_items, $related_item_5);
      $related_item_6 = $data[46];
      $related_items = addRelatedItem($related_items, $related_item_6);
      $related_item_7 = $data[47];
      $related_items = addRelatedItem($related_items, $related_item_7);
      $related_item_8 = $data[48];
      $related_items = addRelatedItem($related_items, $related_item_8);
      $image = $data[49];
      $item_weight = $data[50];
      $location = $data[51];
      $clasp_type = $data[52];
      // echo  $image;

      $tags = array();
      if (!empty($warehouse)) {
        array_push($tags, $warehouse);
      }
      if (!empty($subcategory_1)) {
        array_push($tags, $subcategory_1);
      }
      if (!empty($subcategory_2)) {
        array_push($tags, $subcategory_2);
      }
      if (!empty($subcategory_3)) {
        array_push($tags, $subcategory_3);
      }
      if (!empty($v_color)) {
        array_push($tags, $v_color);
      }
      if (!empty($cmp_color)) {
        array_push($tags, $cmp_color);
      }
      if (!empty($color_description)) {
        array_push($tags, $color_description);
      }
      if (!empty($master_category)) {
        array_push($tags, $master_category);
      }
      if (!empty($category)) {
        array_push($tags, $category);
      }
      if (!empty($collection)) {
        array_push($tags, $collection);
      }
      $tags = implode(",", $tags);
      // echo $tags;

      if ($web_enabled == 'YES') {
        $web_enabled = 'active';
      } else {
        $web_enabled = 'draft';
      }

      if (!empty($family_color) && !empty($size_description) && $family_color != 'NA' && $size_description != 'NA') {
        $variantTitle = $family_color . ' / ' . $size_description;
      } else if (!empty($family_color) && $family_color != 'NA') {
        $variantTitle = $family_color;
      } else if (!empty($size_description) && $size_description != 'NA') {
        $variantTitle = $size_description;
      } else {
        $variantTitle = 'Default';
      }

      $variant_option1 = null;
      $variant_option2 = null;
      $variant_option3 = null;
      $options_array = array();
      $variant_label1 = 'null';
      $variant_label2 = 'null';
      if (isset($family_color) && !empty($family_color) && isset($size_description) && !empty($size_description) && $family_color != 'NA' && $size_description != 'NA') {
        $variant_option1 = $family_color;
        $variant_option2 = $size_description;
        $variant_label1 = 'color';
        $variant_label2 = 'size';
      } else if (isset($family_color) && !empty($family_color)) {
        $variant_option1 = $family_color;
        $variant_label1 = 'color';
      } else if (isset($size_description) && !empty($size_description)) {
        $variant_option1 = $size_description;
        $variant_label1 = 'size';
      }

      $additional_data = json_encode([
        "manufacturer" => $manufacturer,
        "base_material" => $base_material,
        "finish" => $finish,
        "stone" => $stone,
        "length" => $length,
        "width" => $width,
        "height" => $height,
        "upc" => $upc,
        "family_stone" => $family_stone,
        "v_color" => $v_color,
        "cmp_color" => $cmp_color,
        "color_description" => $color_description,
        "cmp_size" => $cmp_size,
        "cmp_style" => $cmp_style,
        "style_description" => $style_description,
        "web_enabled" => $web_enabled,
        "clasp_type" => $clasp_type,
        "whs_enabled" => $whs_enabled,
        "msrp" => $msrp,
        "related_items" => '.$related_items.'
      ]);
      $create_product_meta_curl_body = [
        "metafield" => [
          "key" => "additional_data",
          "value" => $additional_data,
          "namespace" => "custom",
          "value_type" => "json_string"
        ]
      ];
      $create_product_meta_curl_body = json_encode($create_product_meta_curl_body);

      $update_exists_variant = ["variant" => [
        "id" => 808950810,
        "option1" => $variant_option1,
        "option2" => $variant_option2,
        "sku" => $style_code,
        "price" => $price,
        "barcode" => $barcode
      ]];


      $variantOptions = array();
      $variantposition = 1;
      if (!empty($family_color) && $family_color != 'NA' && $family_color != 'na') {
        $variantlevel1 =  [
          "id" => 1055547205,
          "product_id" => 123123,
          "name" => "Color",
          "position" => $variantposition,
          "values" => [$family_color],
        ];
        array_push($variantOptions, $variantlevel1);
        $variantposition++;
      } else {
        $family_color = null;
      }

      if (!empty($size_description) && $size_description != 'NA' && $size_description != 'na') {
        $variantlevel2 = [
          "id" => 1055547205,
          "product_id" => 123123,
          "name" => "Size",
          "position" => $variantposition,
          "values" => [$size_description],
        ];
        array_push($variantOptions, $variantlevel2);
      } else {
        $size_description = null;
      }



      $variant1 = [
        "id" => 1070325048,
        "product_id" => 123123,
        "title" => $variantTitle,
        "price" => $price,
        "sku" => $style_code,
        "position" => 1,
        "inventory_policy" => "deny",
        "compare_at_price" => null,
        "fulfillment_service" => "manual",
        "inventory_management" => "shopify",
        "option1" => $variant_option1,
        "option2" => $variant_option2,
        "option3" => null,
        "taxable" => true,
        "barcode" => $barcode,
        "grams" =>  convertWeight($item_weight),
        "image_id" => null,
        "weight" =>  convertWeight($item_weight),
        "weight_unit" => "g",
        "inventory_item_id" => 1070325048,
        "presentment_prices" => [
          [
            "price" => [
              "amount" => $price,
              "currency_code" => "USD",
            ],
            "compare_at_price" => null,
          ],
        ],
        "requires_shipping" => true,
      ];

      $addVariantToexisting =  [
        "variant" => [
          "barcode" => $barcode,
          "compare_at_price" => "0.00",
          "fulfillment_service" => "manual",
          "grams" =>  convertWeight($item_weight),
          "id" => 808950810,
          "inventory_item_id" => 342916,
          "inventory_management" => "shopify",
          "inventory_policy" => "deny",
          "option1" => $variant_option1,
          "option2" => $variant_option2,
          "option3" => null,
          "presentment_prices" => [
            [
              "price" => [
                "amount" => $price,
                "currency_code" => "USD"
              ],
              "compare_at_price" => null
            ]
          ],
          "price" => $price,
          "product_id" => 632910392,
          "requires_shipping" => true,
          "sku" => $style_code,
          "taxable" => true,
          "title" => $variantTitle,
          "weight" =>  convertWeight($item_weight),
          "weight_unit" => "g"
        ],
      ];

      // print_r($addVariantToexisting);

      if (empty($variantOptions)) {


        echo '-------------default varnat---';
        $defaultOption = [
          "id" => 1055547205,
          "product_id" => 123123,
          "name" => "Title",
          "position" => $variantposition,
          "values" => [
            "Default Title"
          ]
        ];
        array_push($variantOptions, $defaultOption);
        $variant1 =  [
          "id" => 1070325048,
          "product_id" => 123123,
          "title" => "Default Title",
          "price" => $price,
          "sku" => $style_code,
          "position" => 1,
          "inventory_policy" => "deny",
          "compare_at_price" => null,
          "fulfillment_service" => "manual",
          "inventory_management" => "shopify",
          "option1" => "Default Title",
          "option2" => null,
          "option3" => null,
          "taxable" => true,
          "barcode" => $barcode,
          "grams" =>  convertWeight($item_weight),
          "image_id" => null,
          "weight" =>  convertWeight($item_weight),
          "weight_unit" => "g",
          "inventory_item_id" => 1070325048,
          "presentment_prices" => [
            [
              "price" => [
                "amount" => $price,
                "currency_code" => "USD",
              ],
              "compare_at_price" => null,
            ],
          ],
          "requires_shipping" => true,
        ];
      }

      $variantsList = array();
      array_push($variantsList, $variant1);


      $newProduct = [
        "product" => [
          "id" => 123123,
          "title" => $description,
          "body_html" => $web_description,
          "vendor" => $vendor_description,
          "product_type" => $product_group,
          "template_suffix" => null,
          "status" => $web_enabled,
          "published_scope" => "web",
          "tags" => $tags,
          "collection" => null,
          "track_inventory" => 1,
          "variants" => $variantsList,
          "options" => $variantOptions,
          "image" => null,
        ],
      ];

      $productHaveNoVariant = array(
        "sku" => $style_code,
        "price" => $price,
        "inventory_management" => 'shopify',
        "inventory_policy" => 'continue',
        "inventory_quantity" => $onhand,
        "barcode" => $barcode
      );

      $productHaveNoVariant = [
        "product" => [
          "id" => 123123,
          "title" => $description,
          "body_html" => $web_description,
          "vendor" => $vendor_description,
          "product_type" => $product_group,
          "template_suffix" => null,
          "status" => $web_enabled,
          "published_scope" => "web",
          "tags" => $tags,
          "collection" => null,
          "track_inventory" => 1,
          "image" => null,
          "variants" => [$productHaveNoVariant]
        ]
      ];
      $productHaveNoVariant = json_encode($productHaveNoVariant);

      //print_r($productHaveNoVariant);
      $newProduct = json_encode($newProduct);
      $variantAddToExistingPro = json_encode($addVariantToexisting);

      $style_code = strval($style_code);

      $selectQuery = 'select * from products where style_code = "' . $style_code . '"';
      $variantsCreated = mysqli_query($conn, $selectQuery);
      // print_r($selectQuery);
      $rowcount = mysqli_num_rows($variantsCreated);
      $variant_id = null;
      $variant_item_id = null;
      // print_r($variantsCreated);

      $variantsCreated = $variantsCreated->fetch_assoc();
      $exit_product_id = $variantsCreated['product_id'];

      /* check product already exists or not*/
      if ($rowcount > 0) {
        echo '<br><b>------------------variant---------------------</b><br>';

        $checkhaveVariant = "select * from variants where product_id = '" . $exit_product_id . "'";

        $query = mysqli_query($conn, $checkhaveVariant);
        $havevariant = $query->fetch_assoc();

        /* check variant exists then update variant data*/
        if (mysqli_num_rows($query) > 0) {

          $checkexisitng = "select * from variants where product_id = '" . $exit_product_id . "' and  option1_value = '" . $variant_option1 . "' and option2_value = '" . $variant_option2 . "' and option1 = '" . $variant_label1 . "' and option2 = '" . $variant_label2 . "'";
          echo 'check variant exists --';
          $query = mysqli_query($conn, $checkexisitng);
          $variantsexist = $query->fetch_assoc();
          print_r($variantsexist);
          if (mysqli_num_rows($query) > 0) {
            echo 'update <br>';
            $update_variant = json_encode($update_exists_variant);
            $variant_id = $variantsexist['variant_id'];

            echo '<br> product updated---- id : ' . $exit_product_id . ' update variant id : ' . $variant_id;

            $response = shopify_call($shop, $access_token, '/api/2022-01/products/' . $exit_product_id . '/variants/' . $variant_id, $update_variant, 'PUT');
            print_r($response);
            if (!array_key_exists("errors", $response)) {
              $variant_item_id =  $response['variant']['inventory_item_id'];
              echo '<br>exists variant update ********************* <br>';
            }
          } else {
            /* check product already created and get new variant */
            echo 'merge varaints <br>----';
            $response2 = shopify_call($shop, $access_token, '/api/2022-01/products/' . $exit_product_id . '/variants', $variantAddToExistingPro, 'POST');
            print_r($response2);

            if (!array_key_exists("errors", $response2)) {
              $variant_id = $response2['variant']['id'];
              $variant_item_id =  $response2['variant']['inventory_item_id'];

              echo '<br> product updated---- id : ' . $exit_product_id . ' merge variant id : ' . $variant_id;

              $variantInsert = "INSERT INTO variants (product_id, option1,option2,option1_value, option2_value, option3_value, variant_id) VALUES ('" . $product_id . "','" . $variant_label1 . "','" . $variant_label2 . "','" . $variant_option1 . "','" . $variant_option2 . "','" . $variant_option3 . "','" . $variant_id . "')";
              $query = mysqli_query($conn, $variantInsert);
            }
          }
          $update_meta_rest_api = 'api/2021-10/products/' . $exit_product_id . '/variants/' . $variant_id . '/metafields';
          $product_id = $exit_product_id;
        } else {

          /* check default product and get new variant then merge or update the variant*/
          if ((!empty($family_color) ||  !empty($size_description)) && $family_color != 'NA' && $size_description != 'NA') {

            $response = shopify_call($shop, $access_token, '/api/2022-01/products/' . $exit_product_id . '/variants', $variantAddToExistingPro, 'POST');
            print_r($response);
            $variant_id = $response['variant']['id'];

            echo '<br> product added in default product ---- id : ' . $exit_product_id . ' merge variant id : ' . $variant_id;

            $variantInsert = "INSERT INTO variants (product_id, option1,option2,option1_value, option2_value, option3_value, variant_id) VALUES ('" . $exit_product_id . "','" . $variant_label1 . "','" . $variant_label2 . "','" . $variant_option1 . "','" . $variant_option2 . "','" . $variant_option3 . "','" . $variant_id . "')";
            $query = mysqli_query($conn, $variantInsert);

            $variant_item_id =  $response['variant']['inventory_item_id'];
            $update_meta_rest_api = 'api/2021-10/products/' . $exit_product_id . '/variants/' . $variant_id . '/metafields';


            // delete default variant if new variant added 
            $response = shopify_call($shop, $access_token, '/api/2022-01/products/' . $exit_product_id . '/variants', array(), 'GET');
            echo 'delete default';
            print_r($response);
            foreach ($response['variants'] as $key => $value) {
              $checkhaveVariant = "select * from variants where product_id = '" . $value['id'] . "'";
              $query = mysqli_query($conn, $checkhaveVariant);
              $def_var_id = $value['id'];
              if (mysqli_num_rows($query) <= 0) {
                echo 'not found----<br>--' . $exit_product_id . '--<br>' . $def_var_id;
                $response = shopify_call($shop, $access_token, '/api/2022-01/products/' . $exit_product_id . '/variants/' . $def_var_id, array(), 'DELETE');
                print_r($response);
                break;
              } else {
                echo 'found';
              }
            }
          } else {

            /* update  default product and have no variant*/
            $response = shopify_call($shop, $access_token, 'api/2021-10/products/' . $exit_product_id, $productHaveNoVariant, 'PUT');
            print_r($response);
            $update_meta_rest_api = 'api/2021-10/products/' . $exit_product_id . '/metafields';
            echo '<br> have not variant updated---- id : ' . $exit_product_id . '<br>';

            addProductImage($shop, $access_token, $exit_product_id, $image, '', $conn);
          }
        }

        $productDataUpdate = [
          "product" => [
            "id" => 123123,
            "title" => $description,
            "body_html" => $web_description,
            "vendor" => $vendor_description,
            "product_type" => $product_group,
            "template_suffix" => null,
            "status" => $web_enabled,
            "published_scope" => "web",
            "tags" => $tags,
            "collection" => null,
            "track_inventory" => 1,
            "image" => null,
          ]
        ];
        $productDataUpdate = json_encode($productDataUpdate);
        addSEOMetafieldsData($shop, $access_token, $product_id, $web_description1);
        $response = shopify_call($shop, $access_token, 'api/2021-10/products/' . $exit_product_id, $productDataUpdate, 'PUT');
      } else {
        echo '<br><b>-----------------product-------------------</b><br>';
        // print_r($newProduct);
        $response = shopify_call($shop, $access_token, 'api/2021-10/products', $newProduct, 'POST');

        echo 'product created----------';
        print_r($response);

        if (!isset($response['errors'])) {

          $product_id = strval($response['product']['id']);
          $variant_id = $response['product']['variants'][0]['id'];

          echo 'product created---- id : ' . $product_id . ' default variant id : ' . $variant_id;
          $variant_item_id =  $response['product']['variants'][0]['inventory_item_id'];
          //print_r($variants_ids);
          $variant_image_id =  $response['product']['variants'][0]['image_id'];

          echo 'variant_image_id' . $variant_image_id;

          $sql = "INSERT INTO products (product_id, style_code, image_id) VALUES ('" . $product_id . "','" . $style_code . "','" . $variant_image_id . "')";
          $query = mysqli_query($conn, $sql);

          if ($variant_option1 != null) {
            $variantInsert = "INSERT INTO variants (product_id, option1,option2,option1_value, option2_value, option3_value, variant_id) VALUES ('" . $product_id . "','" . $variant_label1 . "','" . $variant_label2 . "','" . $variant_option1 . "','" . $variant_option2 . "','" . $variant_option3 . "','" . $variant_id . "')";
            $query = mysqli_query($conn, $variantInsert);
            $update_meta_rest_api = 'api/2021-10/products/' . $product_id . '/variants/' . $variant_id . '/metafields';
          } else {
            $update_meta_rest_api = 'api/2021-10/products/' . $product_id . '/metafields';
          }

          addSEOMetafieldsData($shop, $access_token, $product_id, $web_description1);
        }

        //   addVariantImage($variant_id,$image);

      }

      shopify_call($shop, $access_token, $update_meta_rest_api, $create_product_meta_curl_body, 'POST');


      /* img update */
      //echo 'img update product_id '.$product_id. ' variant_id '.$variant_id;



      if ($variant_id != null || !empty(($variant_id))) {
        echo 'update inventory------------';
        // add inventory
        $location_id = 69813469411;
        foreach ($locationdetails['locations'] as $key => $value) {
          $warehouse = 'Ahmedabad';
          if ($value['name'] == $warehouse) {
            //  $location_id = $value['id'];
            //  return false;
            break;
          }
        }

        addProductImage($shop, $access_token, $product_id, $image, $variant_id, $conn);


        $onhand = (int)$onhand;
        $inventoryData = [
          "location_id" => $location_id,
          "inventory_item_id" => $variant_item_id,
          "available" => $onhand,
        ];
        $inventoryData = json_encode($inventoryData);
        print_r($inventoryData);
        $addInventory = shopify_call($shop, $access_token, 'api/2021-10/inventory_levels/set', $inventoryData, 'POST');
        //print_r($inventoryData);



      }
      // return $result;


      // if($style_code_check == $style_code){
      //     $style_code_check = $style_code;
      //     echo 'update beased on product_id variable';
      //   }else{
      //     $style_code_check = $style_code;
      //     echo 'insert';
      //     $product_id = 'response of product id';
      //   }



    }
    $apiCallCount++;
    $row++;
  }
  fclose($handle);
}


function addProductImage($shop, $access_token, $product_id, $image, $variant_id, $conn)
{
  if (!empty($image)) {

    $image_url = 'https://www.example.cpm/' . $image;
    if (empty($variant_id)) {
      $product_Variant_img_curl_body = [
        "image" => [
          "src" => $image_url,
          "filename" => $image
        ]
      ];
    } else {
      $product_Variant_img_curl_body = [
        "image" => [
          "src" => $image_url,
          "filename" => $image,
          "variant_ids" => [$variant_id]
        ]
      ];
    }

    //print_r($product_Variant_img_curl_body);
    $product_Variant_img_curl_body = json_encode($product_Variant_img_curl_body);
    $imageUploaded = shopify_call($shop, $access_token, 'api/2021-10/products/' . $product_id . '/images', $product_Variant_img_curl_body, 'POST');
    echo 'heree---<br>';
    print_r($imageUploaded);
    // print_r($conn);
    echo '<br><br>-----variant id---' . $variant_id . '-----products id----' . $product_id . '<br><br>';
    if (!isset($imageUploaded['errors']) && !empty($variant_id) && $variant_id != null) {
      $selectQuery = 'select * from variants where variant_id = "' . $variant_id . '"';
      // echo $selectQuery;
      $variantsCreated = mysqli_query($conn, $selectQuery);
      // print_r($selectQuery);
      $rowcount = mysqli_num_rows($variantsCreated);
      if ($rowcount > 0) {

        echo '-------------variant get---<br>';
        $variantsexist = $variantsCreated->fetch_assoc();
        $image_id = $variantsexist['image_id'];
        if (isset($image_id) && $image_id != NULL) {
          echo '-------------image not null---<br>';
          $imageDeleted = shopify_call($shop, $access_token, 'api/2021-10/products/' . $product_id . '/images/' . $image_id, array(), 'DELETE');
          $variantInsert = "UPDATE variants SET image_id =  '" . $imageUploaded['image']['id'] . "'  WHERE variant_id =  '" . $variant_id . "' ";
          $query = mysqli_query($conn, $variantInsert);
        }
      } else {
        $variantInsert = "UPDATE products SET image_id =  '" . $imageUploaded['image']['id'] . "'  WHERE product_id =  '" . $product_id . "' ";
        $query = mysqli_query($conn, $variantInsert);
        echo 'default new variant create';
      }
    } else if (!isset($imageUploaded['errors']) && empty($variant_id)) {

      echo '-------------not variant table---<br>';

      $selectQuery = 'select * from products where product_id = "' . $product_id . '"';
      $variantsCreated = mysqli_query($conn, $selectQuery);
      $rowcount = mysqli_num_rows($variantsCreated);
      if ($rowcount > 0) {
        echo '-------------pro id---<br>';
        $variantsexist = $variantsCreated->fetch_assoc();
        $image_id = $variantsexist['image_id'];
        print_r($variantsexist);
        echo 'image id**********' . $image_id;
        // if($image_id != NULL){
        echo 'default variant image----------';
        $imageDeleted = shopify_call($shop, $access_token, 'api/2021-10/products/' . $product_id . '/images/' . $image_id, array(), 'DELETE');
        //  }
        $variantInserttoProduct = "UPDATE products SET image_id = '" . $imageUploaded['image']['id'] . "'  WHERE product_id =  '" . $product_id . "' ";
        $query = mysqli_query($conn, $variantInserttoProduct);
      }

      // echo 'default variant';
    }
  }
}

function storeIfError($style_code, $product_id)
{
}

function convertWeight($weight)
{
  $weight = '12g';
  if (strpos($weight, 'g') !== false) {
    $weight = str_replace('g', '', $weight);
    $weight = (int)$weight;
  } else {
    $weight = 0;
  }
  // echo $weight.'--'.gettype($weight);
  return $weight;
}


function shopify_call($shop, $access_token, $resource, $params = array(), $method = 'GET')
{
  $url = "https://{$shop}/admin/{$resource}.json";

  $curlOptions = array(
    CURLOPT_RETURNTRANSFER => TRUE,
    CURLOPT_SSL_VERIFYPEER => FALSE
  );

  if ($method == 'GET') {
    if (!is_null($params)) {

      $url = $url . "?" . http_build_query($params);
    }
  } else {
    $curlOptions[CURLOPT_CUSTOMREQUEST] = $method;
  }

  $curlOptions[CURLOPT_URL] = $url;

  $requestHeaders = array(
    "X-Shopify-Access-Token: ${access_token}",
    "Accept: application/json"
  );

  if ($method == 'POST' || $method == 'PUT') {
    $requestHeaders[] = "Content-Type: application/json";

    if (!is_null($params)) {

      $curlOptions[CURLOPT_POSTFIELDS] = $params;
    }
  }

  $curlOptions[CURLOPT_HTTPHEADER] = $requestHeaders;

  $curl = curl_init();
  curl_setopt_array($curl, $curlOptions);
  $response = curl_exec($curl);

  curl_close($curl);
  //print_r($response);


  //print_r($response);exit();
  return json_decode($response, TRUE);
}




function addRelatedItem($related_items, $check_realted_item)
{

  if ($check_realted_item != '' && gettype($check_realted_item) != null) {
    //echo 'check';
    array_push($related_items, "$check_realted_item");
  }

  return $related_items;
}






function shopify_call_json($shop, $access_token, $resource, $params = array(), $method = 'GET')
{
  $url = "https://{$shop}/admin/{$resource}.json";

  $curlOptions = array(
    CURLOPT_RETURNTRANSFER => TRUE,
    CURLOPT_SSL_VERIFYPEER => FALSE
  );

  if ($method == 'GET') {
    if (!is_null($params)) {

      $url = $url . "?" . http_build_query($params);
    }
  } else {
    $curlOptions[CURLOPT_CUSTOMREQUEST] = $method;
  }

  $curlOptions[CURLOPT_URL] = $url;

  $requestHeaders = array(
    "X-Shopify-Access-Token: ${access_token}",
    "Accept: application/json"
  );

  if ($method == 'POST' || $method == 'PUT') {
    $requestHeaders[] = "Content-Type: application/json";

    if (!is_null($params)) {

      $curlOptions[CURLOPT_POSTFIELDS] = $params;
    }
  }

  $curlOptions[CURLOPT_HTTPHEADER] = $requestHeaders;

  $curl = curl_init();
  curl_setopt_array($curl, $curlOptions);
  $response = curl_exec($curl);
  $err = curl_error($curl);

  curl_close($curl);

  if ($err) {
    print_r($err);
    exit;
    return  json_decode($err, TRUE);
  } else {
    // echo 'success';
  }


  //print_r($response);


  //print_r($response);exit();
  return json_decode($response, TRUE);
}
?>