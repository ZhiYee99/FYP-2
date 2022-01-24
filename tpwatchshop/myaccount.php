<?php
defined('tpwatchshop') or exit;
if (isset($_POST['login'], $_POST['email'], $_POST['password']) && filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $statement = $pdo->prepare('SELECT * FROM accounts WHERE email = ?');
    $statement->execute([ $_POST['email'] ]);
    $account = $statement->fetch(PDO::FETCH_ASSOC);
    if ($account && password_verify($_POST['password'], $account['password'])) {
        session_regenerate_id();
        $_SESSION['account_loggedin'] = TRUE;
        $_SESSION['account_id'] = $account['id'];
        $_SESSION['account_admin'] = $account['admin'];
        $products_in_cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
        if ($products_in_cart) {
            header('Location: ' . url('index.php?page=checkout'));
        } else {
            header('Location: ' . url('index.php?page=myaccount'));
        }
        exit;
    } else {
        $error = 'Incorrect Email/Password!';
    }
}

//register 
$register_error = '';
if (isset($_POST['register'], $_POST['email'], $_POST['password'], $_POST['cpassword']) && filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $statement = $pdo->prepare('SELECT * FROM accounts WHERE email = ?');
    $statement->execute([ $_POST['email'] ]);
    $account = $statement->fetch(PDO::FETCH_ASSOC);

    // check account exist or not
    if ($account) { // Account exists!
        $register_error = 'Account already exists with this email!';
    } else if ($_POST['cpassword'] != $_POST['password']) {
        $register_error = 'Passwords do not match!';
    } else if (strlen($_POST['password']) > 20 || strlen($_POST['password']) < 5) {
        $register_error = 'Password must be between 5 and 20 characters long!';
    } else { // Account not exist, create new account
        $statement = $pdo->prepare('INSERT INTO accounts (email, password, first_name, last_name, address_street, address_city, address_state, address_zip, address_country) VALUES (?,?,"","","","","","","")');
        $password = password_hash($_POST['password'],PASSWORD_DEFAULT);
        $statement->execute([ $_POST['email'], $password ]);
        $account_id = $pdo->lastInsertId();
        
        session_regenerate_id();
        $_SESSION['account_loggedin'] = TRUE;
        $_SESSION['account_id'] = $account_id;
        $_SESSION['account_admin'] = 0;
        $products_in_cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : array();
        if ($products_in_cart) {
            header('Location: ' . url('index.php?page=checkout')); //item in cart
        } else {
            header('Location: ' . url('index.php?page=myaccount'));
        }
        exit;
    }
}
// user logged in
if (isset($_SESSION['account_loggedin'])) {
    $statement = $pdo->prepare('SELECT
        p.img,
        p.name,
        t.txn_id,
        t.created AS transaction_date,
        ti.item_price AS price,
        ti.item_quantity AS quantity,
        ti.item_shipping_price
        FROM transactions t
        JOIN transactions_items ti ON ti.txn_id = t.txn_id
        JOIN accounts a ON a.id = t.account_id 
        JOIN products p ON p.id = ti.item_id
        WHERE t.account_id = ?
        ORDER BY t.created DESC');
    $statement->execute([ $_SESSION['account_id'] ]);
    $transactions = $statement->fetchAll(PDO::FETCH_ASSOC);
}
?>
<?=header_template('My Account')?>

<div class="myaccount content-wrapper">

    <?php if (!isset($_SESSION['account_loggedin'])): ?>

    <div class="login-register">

        <div class="login">

            <h1>Login</h1>

            <form action="" method="post">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" placeholder="john@example.com" required>
                <label for="password">Password</label>
                <input type="password" name="password" id="password" placeholder="Password" required>
                <input name="login" type="submit" value="Login">
            </form>

            <?php if ($error): ?>
            <p class="error"><?=$error?></p>
            <?php endif; ?>

        </div>

        <div class="register">

            <h1>Register</h1>

            <form action="" method="post">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" placeholder="john@example.com" required>
                <label for="password">Password</label>
                <input type="password" name="password" id="password" placeholder="Password" required>
                <label for="cpassword">Confirm Password</label>
                <input type="password" name="cpassword" id="cpassword" placeholder="Confirm Password" required>
                <input name="register" type="submit" value="Register">
            </form>

            <?php if ($register_error): ?>
            <p class="error"><?=$register_error?></p>
            <?php endif; ?>

        </div>

    </div>

    <?php else: ?>

    <h1>My Account</h1>

    <h2>My Orders</h2>

    <table>
        <thead>
            <tr>
                <td colspan="2">Product</td>
                <td class="rhide">Date</td>
                <td class="rhide">Price</td>
                <td class="rhide">Shipping</td>
                <td>Quantity</td>
                <td>Total</td>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($transactions)): ?>
            <tr>
                <td colspan="7" style="text-align:center;">You have no recent orders</td>
            </tr>
            <?php else: ?>
                
            <?php foreach ($transactions as $transaction): ?>
            <tr>
                <td class="img">
                    <?php if (!empty($transaction['img']) && file_exists('imgs/' . $transaction['img'])): ?>
                    <img src="<?=base_url?>imgs/<?=$transaction['img']?>" width="50" height="50" alt="<?=$transaction['name']?>">
                    <?php endif; ?>
                </td>
                <td>
                    <?=$transaction['name']?>
                </td>
                <td class="rhide"><?=$transaction['transaction_date']?></td>
                <td class="price rhide"><?=currency_code?><?=number_format($transaction['price'],2)?></td>
                <td class="price rhide"><?=currency_code?><?=number_format($transaction['item_shipping_price'],2)?></td>
                <td class="quantity"><?=$transaction['quantity']?></td>
                <td class="price"><?=currency_code?><?=number_format($transaction['price'] * $transaction['quantity'] + $transaction['item_shipping_price'],2)?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php endif; ?>

</div>

<?=footer_template()?>
