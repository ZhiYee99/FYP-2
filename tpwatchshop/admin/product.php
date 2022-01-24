<?php
defined('admin') or exit;

$product = [
    'name' => '',
    'description' => '',
    'price' => 0,
    'rrp' => 0,
    'quantity' => -1,
    'date_added' => date('Y-m-d\TH:i:s'),
    'img' => '',
    'imgs' => '',
    'categories' => [],
    'options' => [],
    'options_string' => '',
    'weight' => 0,
    
];

$statement = $pdo->query('SELECT * FROM categories');
$statement->execute();
$categories = $statement->fetchAll(PDO::FETCH_ASSOC);
$imgs = glob('../imgs/*.{jpg,png,gif,jpeg,webp}', GLOB_BRACE);


function addProductImages($pdo, $product_id) {
    if (isset($_POST['images_list'])) {
        $images_list = explode(',', $_POST['images_list']);
        $in  = str_repeat('?,', count($images_list) - 1) . '?';
        $statement = $pdo->prepare('DELETE FROM products_images WHERE product_id = ? AND img NOT IN (' . $in . ')');
        $statement->execute(array_merge([ $product_id ], $images_list));
        foreach ($images_list as $img) {
            if (empty($img)) continue;
            $statement = $pdo->prepare('INSERT IGNORE INTO products_images (product_id,img) VALUES (?,?)');
            $statement->execute([ $product_id, $img ]);
        }
    }
}


function addProductCategories($pdo, $product_id) {
    if (isset($_POST['categories_list'])) {
        $list = explode(',', $_POST['categories_list']);
        $in  = str_repeat('?,', count($list) - 1) . '?';
        $statement = $pdo->prepare('DELETE FROM products_categories WHERE product_id = ? AND category_id NOT IN (' . $in . ')');
        $statement->execute(array_merge([ $product_id ], $list));
        foreach ($list as $cat) {
            if (empty($cat)) continue;
            $statement = $pdo->prepare('INSERT IGNORE INTO products_categories (product_id,category_id) VALUES (?,?)');
            $statement->execute([ $product_id, $cat ]);
        }
    }
}


function addProductOptions($pdo, $product_id) {
    if (isset($_POST['options'])) {
        $list = explode(',', $_POST['options']);
        $statement = $pdo->prepare('SELECT * FROM products_options WHERE product_id = ?');
        $statement->execute([ $product_id ]);
        $options = $statement->fetchAll(PDO::FETCH_ASSOC);
        $remove_list = [];
        foreach ($options as $option) {
            $option_string = $option['title'] . '__' . $option['name'] . '__' . $option['price'];
            if (!in_array($option_string, $list)) {
                $remove_list[] = $option['id'];
            } else {
                array_splice($list, array_search($option_string, $list), 1);
            }
        }
        if ($remove_list) {
            $in = str_repeat('?,', count($remove_list) - 1) . '?';
            $statement = $pdo->prepare('DELETE FROM products_options WHERE id IN (' . $in . ')');
            $statement->execute($remove_list);
        }
        foreach ($list as $option) {
            if (empty($option)) continue;
            $option = explode('__', $option);
            $statement = $pdo->prepare('INSERT INTO products_options (title,name,price,product_id) VALUES (?,?,?,?)');
            $statement->execute([ $option[0], $option[1], $option[2], $product_id ]);
        }
    }
}
if (isset($_GET['id'])) {
    
    $page = 'Edit';
    if (isset($_POST['submit'])) {
        // Update product
        $statement = $pdo->prepare('UPDATE products SET name = ?, description = ?, features = ?, price = ?, rrp = ?, quantity = ?, img = ?, date_added = ?, weight = ? WHERE id = ?');
        $statement->execute([ $_POST['name'], $_POST['description'], $_POST['features'], $_POST['price'], $_POST['rrp'], $_POST['quantity'], $_POST['main_image'], date('Y-m-d H:i:s', strtotime($_POST['date'])), $_POST['weight'], $_GET['id'] ]);
        addProductImages($pdo, $_GET['id']);
        addProductCategories($pdo, $_GET['id']);
        addProductOptions($pdo, $_GET['id']);
        header('Location: index.php?page=products');
        exit;
    }
    
    // Inactive product
    if (isset($_POST['inactive'])) {
        $statement = $pdo->prepare('UPDATE products SET inactive = 1 WHERE id = ?');
        $statement->execute([ $_GET['id'] ]);
        header('Location: index.php?page=products');
        exit;
    }

    // Active product
    if (isset($_POST['active'])) {
        $statement = $pdo->prepare('UPDATE products SET inactive = 0 WHERE id = ?');
        $statement->execute([ $_GET['id'] ]);
        header('Location: index.php?page=products');
        exit;
    }

    // Get product img 
    $statement = $pdo->prepare('SELECT p.*, GROUP_CONCAT(pi.img) AS imgs FROM products p LEFT JOIN products_images pi ON p.id = pi.product_id WHERE p.id = ? GROUP BY p.id');
    $statement->execute([ $_GET['id'] ]);
    $product = $statement->fetch(PDO::FETCH_ASSOC);
    // Get product categories
    $statement = $pdo->prepare('SELECT c.name, c.id FROM products_categories pc JOIN categories c ON c.id = pc.category_id WHERE pc.product_id = ?');
    $statement->execute([ $_GET['id'] ]);
    $product['categories'] = $statement->fetchAll(PDO::FETCH_ASSOC);
    // Get product options
    $statement = $pdo->prepare('SELECT * FROM products_options WHERE product_id = ?');
    $statement->execute([ $_GET['id'] ]);
    $product['options'] = $statement->fetchAll(PDO::FETCH_ASSOC);
    $product['options_string'] = '';
    foreach($product['options'] as $option) {
        $product['options_string'] .= $option['title'] . '__' . $option['name'] . '__' . $option['price'] . ',';
    }
    $product['options_string'] = rtrim($product['options_string'], ',');
} else {
    // Create new product
    $page = 'Create';
    if (isset($_POST['submit'])) {
        $statement = $pdo->prepare('INSERT INTO products (name,description,features,price,rrp,quantity,img,date_added,weight) VALUES (?,?,?,?,?,?,?,?,?)');
        $statement->execute([ $_POST['name'], $_POST['description'], $_POST['features'], $_POST['price'], $_POST['rrp'], $_POST['quantity'], $_POST['main_image'], date('Y-m-d H:i:s', strtotime($_POST['date'])), $_POST['weight'] ]);
        $id = $pdo->lastInsertId();
        addProductImages($pdo, $id);
        addProductCategories($pdo, $id);
        addProductOptions($pdo, $id);
        header('Location: index.php?page=products');
        exit;
    }
}
?>
<?=adminHeader_template($page . ' Product', 'products')?>

<h2><?=$page?> Product</h2>

<div class="content-block">

    <form action="" method="post" class="form responsive-width-100">

        <label for="name">Name</label>
        <input id="name" type="text" name="name" placeholder="Name" value="<?=$product['name']?>" required>

        <label for="description">Description (HTML)</label>
        <textarea id="description" name="description" placeholder="Product Description..."><?=$product['description']?></textarea>

        <label for="description">Features (HTML)</label>
        <textarea id="description" name="features" placeholder="Product Features..."><?=$product['features']?></textarea>

        <label for="price">Price</label>
        <input id="price" type="number" name="price" placeholder="Price" min="0" step=".01" value="<?=$product['price']?>" required>

        <label for="rrp">RRP</label>
        <input id="rrp" type="number" name="rrp" placeholder="rrp" min="0" step=".01" value="<?=$product['rrp']?>" required>

        <label for="quantity">Quantity</span></label>
        <input id="quantity" type="number" name="quantity" placeholder="Quantity" min="-1" value="<?=$product['quantity']?>" title="-1 = unlimited" required>

        <label for="weight">Weight (grams)</span></label>
        <input id="weight" type="number" name="weight" placeholder="Weight (lbs)" min="-1" value="<?=$product['weight']?>" required>

        <label for="date">Date Added</label>
        <input id="date" type="datetime-local" name="date" placeholder="Date" value="<?=date('Y-m-d\TH:i:s', strtotime($product['date_added']))?>" required>

        <label for="add_categories">Categories</label>
        <div style="display:flex;flex-flow:wrap;">
            <select name="add_categories" id="add_categories" style="width:50%;" multiple>
                <?php foreach ($categories as $cat): ?>
                <option value="<?=$cat['id']?>"><?=$cat['name']?></option>
                <?php endforeach; ?>
            </select>
            <select name="categories" style="width:50%;" multiple>
                <?php foreach ($product['categories'] as $cat): ?>
                <option value="<?=$cat['id']?>"><?=$cat['name']?></option>
                <?php endforeach; ?>
            </select>
            <button id="add_selected_categories" style="width:50%;">Add</button>
            <button id="remove_selected_categories" style="width:50%;">Remove</button>
            <input type="hidden" name="categories_list" value="<?=implode(',', array_column($product['categories'], 'id'))?>">
        </div>

        <label for="add_option">Options</label>
        <div class="option" style="display:flex;flex-flow:wrap;">
            <input type="text" name="option_title" placeholder="Option Title (e.g. Colour)" style="width:47%;margin-right:13px;">
            <input type="text" name="option_name" placeholder="Option Name (e.g. Black)" style="width:50%;">
            <input type="number" name="option_price" min="0" step=".01" placeholder="Option Price (e.g. 100.00)">
            <button id="add_option" style="margin-bottom:10px;">Add</button>
            
            <select name="options" multiple>
                <?php foreach ($product['options'] as $option): ?>
                <option value="<?=$option['title']?>__<?=$option['name']?>__<?=$option['price']?>"><?=$option['title']?>,<?=$option['name']?>,<?=$option['price']?></option>
                <?php endforeach; ?>
            </select>
            <button id="remove_selected_options">Remove</button>
            <input type="hidden" name="options" value="<?=$product['options_string']?>">
        </div>


        <label for="add_images">Images</label>
        <div style="display:flex;flex-flow:wrap;">
            <select name="add_images" id="add_images" style="width:50%;" multiple>
                <?php foreach ($imgs as $img): ?>
                <option value="<?=basename($img)?>"><?=basename($img)?></option>
                <?php endforeach; ?>
            </select>
            <select name="images" style="width:50%;" multiple>
                <?php foreach (explode(',', $product['imgs']) as $img): ?>
                <?php if (!empty($img)): ?>
                    <option value="<?=$img?>"><?=$img?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
            <button id="add_selected_images" style="width:50%;">Add</button>
            <button id="remove_selected_images" style="width:50%;">Remove</button>
            <input type="hidden" name="images_list" value="<?=$product['imgs']?>">
            
        </div>

        <div>
            <label for="main_image">Main Image</label>
            <select name="main_image" id="main_image">
                <?php foreach (explode(',', $product['imgs']) as $img): ?>
                <option value="<?=$img?>"<?=$product['img'] == $img ? ' selected' : ''?>><?=$img?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="submit-btns">
            <input type="submit" name="submit" value="Submit">
            <?php if ($page == 'Edit'): ?>
            <input type="submit" name="inactive" value="Inactive" class="inactive">
            <input type="submit" name="active" value="Active" class="active">
            <?php endif; ?>
        </div>

    </form>

</div>

<script>
document.querySelector("#remove_selected_options").onclick = function(e) {
    e.preventDefault();
    document.querySelectorAll("select[name='options'] option").forEach(function(option) {
        if (option.selected) {
            let list = document.querySelector("input[name='options']").value.split(",");
            list.splice(list.indexOf(option.value), 1);
            document.querySelector("input[name='options']").value = list.join(",");
            option.remove();
        }
    });
};
document.querySelector("#add_option").onclick = function(e) {
    e.preventDefault();
    if (document.querySelector("input[name='option_title']").value == "") {
        document.querySelector("input[name='option_title']").focus();
        return;
    }
    if (document.querySelector("input[name='option_name']").value == "") {
        document.querySelector("input[name='option_name']").focus();
        return;
    }
    if (document.querySelector("input[name='option_price']").value == "") {
        document.querySelector("input[name='option_price']").focus();
        return;
    }
    let option = document.createElement("option");
    option.value = document.querySelector("input[name='option_title']").value + '__' + document.querySelector("input[name='option_name']").value + '__' + document.querySelector("input[name='option_price']").value;
    option.text = document.querySelector("input[name='option_title']").value + ',' + document.querySelector("input[name='option_name']").value + ',' + document.querySelector("input[name='option_price']").value;
    document.querySelector("select[name='options']").add(option);
    document.querySelector("input[name='option_title']").value = "";
    document.querySelector("input[name='option_name']").value = "";
    document.querySelector("input[name='option_price']").value = "";
    document.querySelectorAll("select[name='options'] option").forEach(function(option) {
        let list = document.querySelector("input[name='options']").value.split(",");
        if (!list.includes(option.value)) {
            list.push(option.value);
        }
        document.querySelector("input[name='options']").value = list.join(",");
    });
};
document.querySelector("#remove_selected_categories").onclick = function(e) {
    e.preventDefault();
    document.querySelectorAll("select[name='categories'] option").forEach(function(option) {
        if (option.selected) {
            let list = document.querySelector("input[name='categories_list']").value.split(",");
            list.splice(list.indexOf(option.value), 1);
            document.querySelector("input[name='categories_list']").value = list.join(",");
            option.remove();
        }
    });
};
document.querySelector("#add_selected_categories").onclick = function(e) {
    e.preventDefault();
    document.querySelectorAll("select[name='add_categories'] option").forEach(function(option) {
        if (option.selected) {
            let list = document.querySelector("input[name='categories_list']").value.split(",");
            if (!list.includes(option.value)) {
                list.push(option.value);
            }
            document.querySelector("input[name='categories_list']").value = list.join(",");
            document.querySelector("select[name='categories']").add(option.cloneNode(true));
        }
    });
};
document.querySelector("#remove_selected_images").onclick = function(e) {
    e.preventDefault();
    document.querySelectorAll("select[name='images'] option").forEach(function(option) {
        if (option.selected) {
            let images_list = document.querySelector("input[name='images_list']").value.split(",");
            images_list.splice(images_list.indexOf(option.value), 1);
            document.querySelector("input[name='images_list']").value = images_list.join(",");
            document.querySelectorAll("select[name='main_image'] option").forEach(i => i.value == option.value ? i.remove() : false);
            option.remove();
        }
    });
};
document.querySelector("#add_selected_images").onclick = function(e) {
    e.preventDefault();
    document.querySelectorAll("select[name='add_images'] option").forEach(function(option) {
        if (option.selected) {
            let images_list = document.querySelector("input[name='images_list']").value.split(",");
            if (!images_list.includes(option.value)) {
                images_list.push(option.value);
            }
            let add_to_main_images = true;
            document.querySelectorAll("select[name='main_image'] option").forEach(i => add_to_main_images = i.value == option.value ? false : add_to_main_images);
            document.querySelector("input[name='images_list']").value = images_list.join(",");
            document.querySelector("select[name='images']").add(option.cloneNode(true));
            if (add_to_main_images) {
                document.querySelector("select[name='main_image']").add(option.cloneNode(true));
            }
        }
    });
};
</script>

<?=adminFooter_template()?>
