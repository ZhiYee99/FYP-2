<?php

defined('tpwatchshop') or exit;

$account = [
    'first_name' => '',
    'last_name' => '',
    'address_street' => '',
    'address_city' => '',
    'address_state' => '',
    'address_zip' => '',
    'address_country' => 'Malaysia',
    'name_based_on_card' => '',
    'card_type' => '',
    'expire_month' => '',
    'expire_year' => '2022',
    'CVV' => '',
];
// Error array, output errors on the form
$errors = [];
// Check if user is logged in
if (isset($_SESSION['account_loggedin'])) {
    $statement = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
    $statement->execute([ $_SESSION['account_id'] ]);
    $account = $statement->fetch(PDO::FETCH_ASSOC);
}

if (isset($_POST['first_name'], $_POST['last_name'], $_POST['address_street'], $_POST['address_city'], $_POST['address_state'], $_POST['address_zip'], $_POST['address_country'], $_POST['name_based_on_card'], $_POST['card_type'], 
$_POST['expire_month'], $_POST['expire_year'], $_POST['CVV'], $_SESSION['cart'])) {
    $account_id = null;
    // If the user is already logged in
    if (isset($_SESSION['account_loggedin'])) {
        $statement = $pdo->prepare('UPDATE accounts SET first_name = ?, last_name = ?, address_street = ?, address_city = ?, address_state = ?, address_zip = ?, address_country = ?, name_based_on_card = ?, card_type = ?, 
        expire_month = ?, expire_year = ?, CVV = ? WHERE id = ?');
        $statement->execute([ $_POST['first_name'], $_POST['last_name'], $_POST['address_street'], $_POST['address_city'], $_POST['address_state'], $_POST['address_zip'], $_POST['address_country'], $_POST['name_based_on_card'], $_POST['card_type'], 
        $_POST['expire_month'], $_POST['expire_year'], $_POST['CVV'], $_SESSION['account_id'] ]);
        $account_id = $_SESSION['account_id'];
    } else if (isset($_POST['email'], $_POST['password'], $_POST['cpassword']) && filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        // User is not logged in, check existed account
        $statement = $pdo->prepare('SELECT id FROM accounts WHERE email = ?');
        $statement->execute([ $_POST['email'] ]);
    	if ($statement->fetch(PDO::FETCH_ASSOC)) {
            // Email exists
    		$errors[] = 'Account already exists with this email, please login instead!';
        }
        if (strlen($_POST['password']) > 20 || strlen($_POST['password']) < 5) {
            $errors[] = 'Password must be between 5 and 20 characters long!';
    	}
        if ($_POST['password'] != $_POST['cpassword']) {
            $errors[] = 'Passwords do not match!';
        }
        if (!$errors) {
            // Email doesnt exist, create new account
            $statement = $pdo->prepare('INSERT INTO accounts (email, password, first_name, last_name, address_street, address_city, address_state, address_zip, address_country) VALUES (?,?,?,?,?,?,?,?,?)');
            $password = password_hash($_POST['password'],PASSWORD_DEFAULT);
            $statement->execute([ $_POST['email'], $password, $_POST['first_name'], $_POST['last_name'], $_POST['address_street'], $_POST['address_city'], $_POST['address_state'], $_POST['address_zip'], $_POST['address_country'] ]);
            $account_id = $pdo->lastInsertId();
            $statement = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
            $statement->execute([ $account_id ]);
            $account = $statement->fetch(PDO::FETCH_ASSOC);
        }
    } else if (account_required) {
        $errors[] = 'Account creation required!';
    }



    //Account successfully log in
    if (!$errors) {
        $products_in_cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
        $subtotal = 0.00;
        $shippingtotal = 0.00;
        $discounttotal = 0.00;
        $selected_shipping_method = isset($_SESSION['shipping_method']) ? $_SESSION['shipping_method'] : null;
        
        if ($products_in_cart) {
            $array_to_question_marks = implode(',', array_fill(0, count($products_in_cart), '?'));
            $statement = $pdo->prepare('SELECT p.id, c.id AS category_id, p.* FROM products p LEFT JOIN products_categories pc ON p.id = pc.product_id LEFT JOIN categories c ON c.id = pc.category_id WHERE p.id IN (' . $array_to_question_marks . ') GROUP BY p.id, c.id');
            $statement->execute(array_column($products_in_cart, 'id'));
            $products = $statement->fetchAll(PDO::FETCH_ASSOC);
            
            
            if (isset($_SESSION['discount'])) {
                $statement = $pdo->prepare('SELECT * FROM discounts WHERE discount_code = ?');
                $statement->execute([ $_SESSION['discount'] ]);
                $discount = $statement->fetch(PDO::FETCH_ASSOC);
            }
            date_default_timezone_set("Asia/Kuala_Lumpur");
            $current_date = strtotime((new DateTime())->format('Y-m-d H:i:s'));
            $statement = $pdo->query('SELECT * FROM shipping');
            $shipping_methods = $statement->fetchAll(PDO::FETCH_ASSOC);
            $selected_shipping_method = $selected_shipping_method == null && $shipping_methods ? $shipping_methods[0]['name'] : $selected_shipping_method;
            
            //calculate total price
            foreach ($products_in_cart as &$cart_product) {
                foreach ($products as $product) {
                    if ($cart_product['id'] == $product['id']) {
                        $cart_product['meta'] = $product;
                        $product_price = $cart_product['options_price'] > 0 ? (float)$cart_product['options_price'] : (float)$product['price'];
                        $subtotal += $product_price * (int)$cart_product['quantity'];
                        foreach ($shipping_methods as $shipping_method) {
                            if ($shipping_method['name'] == $selected_shipping_method && $product_price >= $shipping_method['price_from'] && $product_price <= $shipping_method['price_to'] && $product['weight'] >= $shipping_method['weight_from'] && $product['weight'] <= $shipping_method['weight_to']) {
                                $cart_product['shipping_price'] = (float)$shipping_method['price'] * (int)$cart_product['quantity'];
                                $shippingtotal += $cart_product['shipping_price'];
                            }
                        }
                        if (isset($discount) && $discount && $current_date >= strtotime($discount['start_date']) && $current_date <= strtotime($discount['end_date'])) {
                            if ((empty($discount['category_ids']) && empty($discount['product_ids'])) || in_array($product['id'], explode(',', $discount['product_ids'])) || (!empty($product['category_id']) && in_array($product['category_id'], explode(',', $discount['category_ids'])))) {
                                $cart_product['discounted'] = true;
                            }
                        }
                    }
                }
            }
            
            $num_discounted_products = count(array_column($products_in_cart, 'discounted'));
            foreach ($products_in_cart as &$cart_product) {
                if (isset($cart_product['discounted']) && $cart_product['discounted']) {
                    if ($cart_product['options_price'] > 0) {
                        $price = &$cart_product['options_price'];
                    } else {
                        $price = &$cart_product['meta']['price'];
                    }
                    if ($discount['discount_type'] == 'Percentage') {
                        $d = round((float)$price * ((float)$discount['discount_value']/100), 2);
                        $price -= $d;
                        $discounttotal += $d * (int)$cart_product['quantity'];
                    }
                    if ($discount['discount_type'] == 'Fixed') {
                        $d = round((float)$discount['discount_value'] / $num_discounted_products, 2);
                        $price -= round($d / (int)$cart_product['quantity'], 2);
                        $discounttotal += $d;
                    }
                }
            }
        }
       
        //checkout with transaction id
        if (isset($_POST['checkout']) && $products_in_cart) {
            $transaction_id = strtoupper(uniqid('SC') . substr(md5(mt_rand()), 0, 5));
            $statement = $pdo->prepare('INSERT INTO transactions (txn_id, payment_amount, payment_status, created, payer_email, first_name, last_name, address_street, address_city, address_state, address_zip, address_country, account_id, payment_method) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $statement->execute([
                $transaction_id,
                ($subtotal-$discounttotal)+$shippingtotal,
                'Completed',
                date('Y-m-d H:i:s'),
                isset($account['email']) && !empty($account['email']) ? $account['email'] : $_POST['email'],
                $_POST['first_name'],
                $_POST['last_name'],
                $_POST['address_street'],
                $_POST['address_city'],
                $_POST['address_state'],
                $_POST['address_zip'],
                $_POST['address_country'],
                $account_id,
                'website'
            ]);
            $order_id = $pdo->lastInsertId();
            foreach ($products_in_cart as $product) {
                // For every product in the shopping cart insert a new transaction into our database
                $statement = $pdo->prepare('INSERT INTO transactions_items (txn_id, item_id, item_price, item_quantity, item_options, item_shipping_price) VALUES (?,?,?,?,?,?)');
                $statement->execute([ $transaction_id, $product['id'], $product['options_price'] > 0 ? $product['options_price'] : $product['meta']['price'], $product['quantity'], $product['options'], $product['shipping_price'] ]);
                // Update product quantity in the products table
                $statement = $pdo->prepare('UPDATE products SET quantity = quantity - ? WHERE quantity > 0 AND id = ?');
                $statement->execute([ $product['quantity'], $product['id'] ]);
            }
            if ($account_id != null) {
                // Log the user in with the details provided
                session_regenerate_id();
                $_SESSION['account_loggedin'] = TRUE;
                $_SESSION['account_id'] = $account_id;
                $_SESSION['account_admin'] = $account ? $account['admin'] : 0;
            }
            
            header('Location: ' . url('index.php?page=placeorder'));
            exit;
        }
    }


    // Preserve details if error
    $account = [
        'first_name' => $_POST['first_name'],
        'last_name' => $_POST['last_name'],
        'address_street' => $_POST['address_street'],
        'address_city' => $_POST['address_city'],
        'address_state' => $_POST['address_state'],
        'address_zip' => $_POST['address_zip'],
        'address_country' => $_POST['address_country'],
        'name_based_on_card' => $_POST['name_based_on_card'],
        'card_type' => $_POST['card_type'],
        'expire_month' => $_POST['expire_month'],
        'expire_year' => $_POST['expire_year'],
        'CVV' => $_POST['CVV'],
    ];
}

// Redirect the user if the shopping cart is empty
if (empty($_SESSION['cart'])) {
    header('Location: ' . url('index.php?page=cart'));
    exit;
}
// List of countries, card type, expire date (mm/yy)
$countries = ["Afghanistan", "Albania", "Algeria", "American Samoa", "Andorra", "Angola", "Anguilla", "Antarctica", "Antigua and Barbuda", "Argentina", "Armenia", "Aruba", "Australia", "Austria", "Azerbaijan", "Bahamas", "Bahrain", "Bangladesh", "Barbados", "Belarus", "Belgium", "Belize", "Benin", "Bermuda", "Bhutan", "Bolivia", "Bosnia and Herzegowina", "Botswana", "Bouvet Island", "Brazil", "British Indian Ocean Territory", "Brunei Darussalam", "Bulgaria", "Burkina Faso", "Burundi", "Cambodia", "Cameroon", "Canada", "Cape Verde", "Cayman Islands", "Central African Republic", "Chad", "Chile", "China", "Christmas Island", "Cocos (Keeling) Islands", "Colombia", "Comoros", "Congo", "Congo, the Democratic Republic of the", "Cook Islands", "Costa Rica", "Cote d'Ivoire", "Croatia (Hrvatska)", "Cuba", "Cyprus", "Czech Republic", "Denmark", "Djibouti", "Dominica", "Dominican Republic", "East Timor", "Ecuador", "Egypt", "El Salvador", "Equatorial Guinea", "Eritrea", "Estonia", "Ethiopia", "Falkland Islands (Malvinas)", "Faroe Islands", "Fiji", "Finland", "France", "France Metropolitan", "French Guiana", "French Polynesia", "French Southern Territories", "Gabon", "Gambia", "Georgia", "Germany", "Ghana", "Gibraltar", "Greece", "Greenland", "Grenada", "Guadeloupe", "Guam", "Guatemala", "Guinea", "Guinea-Bissau", "Guyana", "Haiti", "Heard and Mc Donald Islands", "Holy See (Vatican City State)", "Honduras", "Hong Kong", "Hungary", "Iceland", "India", "Indonesia", "Iran (Islamic Republic of)", "Iraq", "Ireland", "Israel", "Italy", "Jamaica", "Japan", "Jordan", "Kazakhstan", "Kenya", "Kiribati", "Korea, Democratic People's Republic of", "Korea, Republic of", "Kuwait", "Kyrgyzstan", "Lao, People's Democratic Republic", "Latvia", "Lebanon", "Lesotho", "Liberia", "Libyan Arab Jamahiriya", "Liechtenstein", "Lithuania", "Luxembourg", "Macau", "Macedonia, The Former Yugoslav Republic of", "Madagascar", "Malawi", "Malaysia", "Maldives", "Mali", "Malta", "Marshall Islands", "Martinique", "Mauritania", "Mauritius", "Mayotte", "Mexico", "Micronesia, Federated States of", "Moldova, Republic of", "Monaco", "Mongolia", "Montserrat", "Morocco", "Mozambique", "Myanmar", "Namibia", "Nauru", "Nepal", "Netherlands", "Netherlands Antilles", "New Caledonia", "New Zealand", "Nicaragua", "Niger", "Nigeria", "Niue", "Norfolk Island", "Northern Mariana Islands", "Norway", "Oman", "Pakistan", "Palau", "Panama", "Papua New Guinea", "Paraguay", "Peru", "Philippines", "Pitcairn", "Poland", "Portugal", "Puerto Rico", "Qatar", "Reunion", "Romania", "Russian Federation", "Rwanda", "Saint Kitts and Nevis", "Saint Lucia", "Saint Vincent and the Grenadines", "Samoa", "San Marino", "Sao Tome and Principe", "Saudi Arabia", "Senegal", "Seychelles", "Sierra Leone", "Singapore", "Slovakia (Slovak Republic)", "Slovenia", "Solomon Islands", "Somalia", "South Africa", "South Georgia and the South Sandwich Islands", "Spain", "Sri Lanka", "St. Helena", "St. Pierre and Miquelon", "Sudan", "Suriname", "Svalbard and Jan Mayen Islands", "Swaziland", "Sweden", "Switzerland", "Syrian Arab Republic", "Taiwan, Province of China", "Tajikistan", "Tanzania, United Republic of", "Thailand", "Togo", "Tokelau", "Tonga", "Trinidad and Tobago", "Tunisia", "Turkey", "Turkmenistan", "Turks and Caicos Islands", "Tuvalu", "Uganda", "Ukraine", "United Arab Emirates", "United Kingdom", "United States", "United States Minor Outlying Islands", "Uruguay", "Uzbekistan", "Vanuatu", "Venezuela", "Vietnam", "Virgin Islands (British)", "Virgin Islands (U.S.)", "Wallis and Futuna Islands", "Western Sahara", "Yemen", "Yugoslavia", "Zambia", "Zimbabwe"];
$cards_types = ["Amex","Master","Visa"];
$expire_months = ["01","02","03","04","05","06","07","08","09","10","11","12"];
$expire_years = ["20","21","22","23","24","25","26","27","28","29","30","31"];

?>
<?=header_template('Checkout')?>

<div class="checkout content-wrapper">

    <h1>Checkout</h1>

    <p class="error"><?=implode('<br>', $errors)?></p>

    <?php if (!isset($_SESSION['account_loggedin'])): ?>
    <p>Already have an account? <a href="<?=url('index.php?page=myaccount')?>">Log In</a></p>
    <?php endif; ?>

    <form action="" method="post">

        <?php if (!isset($_SESSION['account_loggedin'])): ?>
        <h2>Create Account<?php if (!account_required): ?> (optional)<?php endif; ?></h2>

        <label for="email">Email</label>
        <input type="email" name="email" id="email" placeholder="john@example.com">

        <label for="password">Password</label>
        <input type="password" name="password" id="password" placeholder="Password">

        <label for="cpassword">Confirm Password</label>
        <input type="password" name="cpassword" id="cpassword" placeholder="Confirm Password">
        <?php endif; ?>

        <h2>Shipping Details</h2>

        <div class="row1">
            <label for="first_name">First Name</label>
            <input type="text" value="<?=$account['first_name']?>" name="first_name" id="first_name" placeholder="John" required>
        </div>

        <div class="row2">
            <label for="last_name">Last Name</label>
            <input type="text" value="<?=$account['last_name']?>" name="last_name" id="last_name" placeholder="Wang" required>
        </div>

        <label for="address_street">Address</label>
        <input type="text" value="<?=$account['address_street']?>" name="address_street" id="address_street" placeholder="Jalan ABC" required>

        <label for="address_city">City</label>
        <input type="text" value="<?=$account['address_city']?>" name="address_city" id="address_city" placeholder="Johor Bahru" required>

        <div class="row1">
            <label for="address_state">State</label>
            <input type="text" value="<?=$account['address_state']?>" name="address_state" id="address_state" placeholder="Johor" required>
        </div>

        <div class="row2">
            <label for="address_zip">Zip</label>
            <input type="text" value="<?=$account['address_zip']?>" name="address_zip" id="address_zip" placeholder="81200" required>
        </div>

        <label for="address_country">Country</label>
        <select name="address_country" required>
            <?php foreach($countries as $country): ?>
            <option value="<?=$country?>"<?=$country==$account['address_country']?' selected':''?>><?=$country?></option>
            <?php endforeach; ?>
        </select>


        <h2>Payment method</h2>
        <label for="name_based_on_card">Name (based on the card)</label>
        <input type="text" value="<?=$account['name_based_on_card']?>" name="name_based_on_card" id="name_based_on_card" placeholder="John Wang" required>
        
        <label for="card_type">Card Type</label>
        <select name="card_type" required>
            <?php foreach($cards_types as $card_type): ?>
            <option value="<?=$card_type?>"<?=$card_type==$account['card_type']?' selected':''?> required><?=$card_type?></option>
            <?php endforeach; ?>
        </select>

        <div class="row1">
            <label for="expire_month">Expire Month</label>
            
            <select name="expire_month" required>
                <?php foreach($expire_months as $expire_month): ?>
                <option value="<?=$expire_month?>"<?=$expire_month==$account['expire_month']?' selected':''?>><?=$expire_month?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="row2">
        <label for="expire_year">Expire Year</label>
            
            <select name="expire_year" required>
                <?php foreach($expire_years as $expire_year): ?>
                <option value="<?=$expire_year?>"<?=$expire_year==$account['expire_year']?' selected':''?>><?=$expire_year?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <label for="CVV">CVV</label>
        <input type="password" value="<?=$account['CVV']?>" name="CVV" id="CVV" minlength="3" maxlength="3" placeholder="CVV" pattern="^\d{3}$" required>


        <button type="submit" name="checkout">Place Order</button>


    </form>

</div>

<?=footer_template()?>
