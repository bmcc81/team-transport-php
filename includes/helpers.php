<?php
function old($key, $default = '') {
    if (!empty($_SESSION['old'][$key])) {
        return htmlspecialchars($_SESSION['old'][$key]);
    }
    return htmlspecialchars($default);
}