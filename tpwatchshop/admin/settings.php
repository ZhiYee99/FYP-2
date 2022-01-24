<?php
defined('admin') or exit;
// Configuration file
$file = '../config.php';
$handle = fopen($file, 'r') or exit('Unable to read configuration file! Make sure the file is readable!');
$contents = fread($handle, filesize($file));
fclose($handle);
// Format key function
function format_key($key) {
    $key = str_replace(['_', 'url', 'db '], [' ', 'URL', 'Database '], strtolower($key));
    return ucwords($key);
}
// Format HTML output function
function format_var_html($key, $value) {
    $html = '';
    $type = 'text';
    $value = htmlspecialchars(trim($value, '\''), ENT_QUOTES);
    $type = strpos($key, 'pass') !== false ? 'password' : $type;
    $type = in_array(strtolower($value), ['true', 'false']) ? 'checkbox' : $type;
    $checked = strtolower($value) == 'true' ? ' checked' : '';
    $html .= '<label for="' . $key . '">' . format_key($key) . '</label>';
    if ($type == 'checkbox') {
        $html .= '<input type="hidden" name="' . $key . '" value="false">';
    }
    $html .= '<input type="' . $type . '" name="' . $key . '" id="' . $key . '" value="' . $value . '" placeholder="' . format_key($key) . '"' . $checked . '>';
    return $html;
}
preg_match_all('/define\(\'(.*?)\', ?(.*?)\)/', $contents, $matches);
if (!empty($_POST)) {
    foreach ($_POST as $k => $n) {
        $n = in_array(strtolower($n), ['true', 'false']) ? strtolower($n) : '\'' . $n . '\'';
        $contents = preg_replace('/define\(\'' . $k . '\'\, ?(.*?)\)/s', 'define(\'' . $k . '\',' . $n . ')', $contents);
    }
    file_put_contents('../config.php', $contents);
    header('Location: index.php?page=settings');
    exit;
}

?>
<?=adminHeader_template('Settings', 'settings')?>

<h2>Settings</h2>

<div class="content-block">
    <form action="" method="post" class="form responsive-width-100">
        <?php for($i = 0; $i < count($matches[1]); $i++): ?>
        <?=format_var_html($matches[1][$i], $matches[2][$i])?>
        <?php endfor; ?>
        <input type="submit" value="Save">
    </form>
</div>

<script>
document.querySelectorAll("input[type='checkbox']").forEach(checkbox => {
    checkbox.onclick = () => {
        checkbox.value = checkbox.checked ? 'true' : 'false';
    };
});
</script>

<?=adminFooter_template()?>
