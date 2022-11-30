<?php

// Set variables for our request
$shop = $_GET['shop'];
$api_key = "";
$scopes = "read_orders,write_products,read_script_tags,write_script_tags,read_themes,write_themes,read_orders,write_orders";
$redirect_uri = "https://example.com/generate_token.php";

// Build install/approval URL to redirect to
$install_url = "https://" . $shop . ".myshopify.com/admin/oauth/authorize?client_id=" . $api_key . "&scope=" . $scopes . "&redirect_uri=" . urlencode($redirect_uri);

// Redirect
header("Location: " . $install_url);
die();
//https://example.com/install.php?shop=auto-aprendizaje
