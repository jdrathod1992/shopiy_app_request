<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopify</title>
    <link rel="stylesheet" href="https://unpkg.com/@shopify/polaris@8.0.0/build/esm/styles.css" />
</head>

<body>
    <div class="Polaris-Page Polaris-Page--fullWidth">
        <div class="Polaris-Page__Content">
            <div class="Polaris-Layout">
                <div class="Polaris-Layout__Section ">
                    <div class="Polaris-Card">
                        <div class="Polaris-Card__Header">
                            <h2 class="Polaris-Heading">Header</h2>
                        </div>
                        <div class="Polaris-Card__Section">
                            <p>Use to follow a normal section with a secondary section to create a 2/3 + 1/3 layout on detail pages (such as individual product or order pages). Can also be used on any page that needs to structure a lot of content. This layout stacks the columns on small screens.</p>
                        </div>
                    </div>
                </div>
                <div class="Polaris-Layout__Section ">
                    <div class="Polaris-Card">
                        <div class="Polaris-Card__Header">
                            <h2 class="Polaris-Heading">Order details</h2>
                        </div>
                        <div class="Polaris-Card__Section">
                            <p>Use to follow a normal section with a secondary section to create a 2/3 + 1/3 layout on detail pages (such as individual product or order pages). Can also be used on any page that needs to structure a lot of content. This layout stacks the columns on small screens.</p>
                        </div>
                    </div>
                </div>
                <div class="Polaris-Layout__Section Polaris-Layout__Section--secondary">
                    <div class="Polaris-Card">
                        <div class="Polaris-Card__Header">
                            <h2 class="Polaris-Heading">Tags</h2>
                        </div>
                        <div class="Polaris-Card__Section">
                            <p>Add tags to your order.</p>
                        </div>
                    </div>
                </div>
                <div class="Polaris-Layout__Section ">
                    <div class="Polaris-Card">
                        <div class="Polaris-Card__Header">
                            <h2 class="Polaris-Heading">Footer</h2>
                        </div>
                        <div class="Polaris-Card__Section">
                            <p>Use to follow a normal section with a secondary section to create a 2/3 + 1/3 layout on detail pages (such as individual product or order pages). Can also be used on any page that needs to structure a lot of content. This layout stacks the columns on small screens.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    // echo "hello";
    // exit;

    require_once("inc/functions.php");
    require_once("connect_to_mysql.php");

    $requests = $_GET;
    $hmac = $_GET['hmac'];
    $serializeArray = serialize($requests);
    $requests = array_diff_key($requests, array('hmac' => ''));
    ksort($requests);

    // print_r($requests);
    $getShop = $requests['shop'];

    $mysqlQuery = "SELECT * FROM stores WHERE store_url = '" . $getShop . "'  ORDER BY id DESC LIMIT 1";
    $result = $mysql->query($mysqlQuery);

    if ($result->num_rows < 1) {
        // header("location: please add new route/weeklyhow/install.php?shop=" . $requests['shop']);
        header("location: https://4af5-103-240-204-23.in.ngrok.io/weeklyhow/install.php?shop=" . $requests['shop']);

        exit();
    }

    $storeData = $result->fetch_assoc();

    ///******************** GET ACCESS TOKEN ***************** */

    $encryption = $storeData['access_token'];
    $options = 0;
    $decryption_iv = '1234567891011121';
    $ciphering = "AES-128-CTR";
    $decryption_key = "ShopifyAccessToken";
    $decryption = openssl_decrypt(
        $encryption,
        $ciphering,
        $decryption_key,
        $options,
        $decryption_iv
    );
    ///************************************************************ */
    $shop = $storeData['store_url'];

    $token = $decryption;

    $query = [
        "product" => [
            "title" => "Hiking backpack"
        ]
    ];
    // $collectionList = shopify_call($token, $shop, "/admin/api/2022-07/products.json", $query, 'POST', $requests);

    $collectionList = shopify_call($token, $shop, "/admin/api/2022-07/custom_collections.json", array(), 'GET', $requests);
    // echo "<pre>";
    // print_r($collectionList);
    // exit;

    $collectionList = json_decode($collectionList['response'], JSON_PRETTY_PRINT);
    $collection_id = $collectionList['custom_collections'][0]['id'];

    $array = array("collection_id" => $collection_id);
    $collects = shopify_call($token, $shop, "/admin/api/2022-07/products.json", [], 'GET');
    $collects = json_decode($collects['response'], JSON_PRETTY_PRINT);

    foreach ($collects as $collect) {
        foreach ($collect as $key => $value) {

            $products = shopify_call($token, $shop, "/admin/api/2022-07/products/" . $value['id'] . ".json", array(), 'GET');
            $products = json_decode($products['response'], JSON_PRETTY_PRINT);
            echo $products['product']['title'];
            echo '</br>';
        }
    }

    $themes = shopify_call($token, $shop, "/admin/api/2022-07/themes.json", [], 'GET');
    $themes = json_decode($themes['response'], JSON_PRETTY_PRINT);

    foreach ($themes as $key => $theme) {
        foreach ($theme as $key => $value) {

            if ($value['role'] == 'main') {
                $themeId = $value['id'];
            }
        }
    }
    $assets = shopify_call($token, $shop, "/admin/api/2022-07/themes/" . $themeId . "/assets.json", array(), 'GET');
    $assets = json_decode($assets['response'], JSON_PRETTY_PRINT);


    $assetQuery = [
        "asset" => [
            "key" => 'layout/theme.test.liquid',
            'value' => '{{ content_for_header }} TEST {{ content_for_layout }}'
        ]
    ];
    //print_r($themeId);

    // $putAsset = shopify_call($token, $shop, "/admin/api/2022-07/themes/" . $themeId . "/assets.json", $assetQuery, 'PUT', $requests);
    // $putAsset = json_decode($putAsset['response'], JSON_PRETTY_PRINT);
    // echo "<pre>";
    // print_r($putAsset);


    ?>
    <script src="https://unpkg.com/@shopify/app-bridge@3"></script>
    <script src="https://unpkg.com/@shopify/app-bridge-utils@3"></script>

    <script>
        var AppBridge = window['app-bridge'];
        var actions = AppBridge.actions;
        var TitleBar = actions.TitleBar;
        var Button = actions.Button;
        var Redirect = actions.Redirect;
        var AppLink = actions.AppLink;
        var NavigationMenu = actions.NavigationMenu;

        console.log(actions);


        const config = {
            apiKey: 'b66d1fd597ecc57041aeddac3219a5d4',
            host: new URLSearchParams(location.search).get("host"),
            forceRedirect: true
        };

        const app = AppBridge.createApp(config);
        var breadcrumb = Button.create(app, {
            label: 'My breadcrumb'
        });

        const redirect = Redirect.create(app);
        // Go to {appOrigin}/settings
        redirect.dispatch(Redirect.Action.APP, 'weeklyhow/items.php');

        breadcrumb.subscribe(Button.Action.CLICK, function() {
            app.dispatch(Redirect.toApp({
                path: '/weeklyhow/items.php'
            }));
        });

        var titleBarOptions = {
            title: 'My page title',
            breadcrumbs: breadcrumb
        };

        var myTitleBar = TitleBar.create(app, titleBarOptions);

        const itemsLink = AppLink.create(app, {
            label: 'Items',
            destination: '/weeklyhow/items.php',
        });

        const settingsLink = AppLink.create(app, {
            label: 'Settings',
            destination: '/settings',
        });

        // create NavigationMenu with no active links

        const navigationMenu = NavigationMenu.create(app, {
            items: [itemsLink, settingsLink],
        });

        // or create a NavigationMenu with the settings link active

        // const navigationMenu = NavigationMenu.create(app, {
        //     items: [itemsLink, settingsLink],
        //     active: settingsLink,
        // });

        // var AppBridge = window['app-bridge'];
        // var AppBridgeUtils = window['app-bridge-utils'];

        // var createApp = AppBridge.createApp;
        // var actions = AppBridge.actions;
        // var Redirect = actions.Redirect;

        // var apiKey = '';
        // var redirectUri = 'allowed redirect URI from Shopify Partner Dashboard';
        // var host = 'auto-aprendizaje';
        // var scopes = 'read_orders,write_products';
        // var permissionUrl = 'https://' +
        //     host +
        //     '/admin' +
        //     '/oauth/authorize?client_id=' +
        //     apiKey +
        //     '&scope=' + scopes + '&redirect_uri=' +
        //     redirectUri;

        // // If your app is embedded inside Shopify, then use App Bridge to redirect
        // if (AppBridgeUtils.isShopifyEmbedded()) {
        //     var app = createApp({
        //         apiKey: apiKey,
        //         host: host
        //     });

        //     Redirect.create(app).dispatch(Redirect.Action.REMOTE, permissionUrl);

        //     // Otherwise, redirect using the `window` object
        // } else {
        //     window.location.assign(permissionUrl);
        // }
    </script>
</body>

</html>