<?php
$pageTitle   = 'DeltaOS.de | Suchergebnisse';
$description = 'Suchergebnisse auf DeltaOs.de';
$keywords    = 'Suche';
$pageTopic   = 'Search Results on DeltaOS.de';

require_once $_SERVER['DOCUMENT_ROOT'] . '/assets/php/functions.php';
$page     = pathinfo(__FILE__, PATHINFO_FILENAME);
$lang     = 'de';

echo "<!doctype html>\n";
echo "<html lang='$lang'>\n";
headPHP($pageTitle, $keywords, $description);
echo "<body>\n";
navbarPHP($page);
echo "  <div class='container-fluid px-md-5'>\n";
  echo "    <a id='top-content'></a>\n";
  echo "    <div id='pageContent'>\n";
    echo "  <div class='d-flex justify-content-between'>\n";
      echo "    <h1 class='text-warning'>$pageTopic</h1>\n";
      modeSwitch();
      echo "  </div>\n";
    echo "  <hr class='mb-3'>\n";

    // make sure, you're calling the right class!
    new MySearch();

    echo "      </div>\n";
  echo "    </div>\n";
footerPHP($lang);
jsPHP();
echo "  </body>\n";
echo "</html>\n\n";
