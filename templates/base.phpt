<?php declare(strict_types=1);

namespace hikari_no_yume\touchHLE\app_compatibility_db;

if (isset($breadcrumbs)) {

}

?>
<!doctype html>
<meta charset=utf-8>
<title><?php

if (!empty($breadcrumbs)) {
    echo htmlspecialchars(implode(' - ', array_reverse($breadcrumbs))), ' - ';
}
echo htmlspecialchars(SITE_NAME);

?></title>

<link rel=stylesheet href=/style.css>
<script src=/script.js></script>

<h1><?=htmlspecialchars(SITE_NAME)?></h1>

<div id=breadcrumbs>
<a href=/>Home</a>
<?php

if (!empty($breadcrumbs)) {
    echo ' &gt; ', htmlspecialchars(implode(' > ', $breadcrumbs));
}

?>
</div>
