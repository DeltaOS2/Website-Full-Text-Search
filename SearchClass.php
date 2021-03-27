<?php
/**
 * Created by BluePrintOnline.Net
 * Project: Full text search for websites
 * Author: Gert Massheimer
 * Date: 19.Mär.2021
 * Time: 16:51
 * File: SearchClass.php
 * ------------------------------------------------- */

// <!-- no_search -->

class SearchClass
{
  // Settings needed for logfile, only -----------------------------------------
  // Write logfile?
  protected bool $logfileActive = false;
  // Path and filename of logfile
  protected string $searchLogfile = './log/new_search_log.txt';
  // Timezone
  protected string $timezone = 'Europe/Berlin';
  // Local timezone translation to be used
  protected string $local = 'de_DE';
  // ---------------------------------------------------------------------------

  // Root folder from where the search starts. Leave empty if it starts from
  // DOCUMENT_ROOT
  protected string $startFolder  = '';
  // Additional sub-folders basing on startFolder
  protected array  $searchFolders = ['assets/php/modals'];
  // File extension to look for. Case sensitiv! No spaces!
  protected string $extensions = 'htm,html,php,HTM,HTML,PHP';
  // ---------------------------------------------------------------------------

  // Min length for search expression
  protected int $searchMinLength = 3;
  // Max length for text excerpt
  protected int $excerptLength = 30;
  // CSS class to be used to highlight the search expression in the text excerpt
  protected string $textExcerptCSS = "text-warning";
  // ---------------------------------------------------------------------------

  // Meta-tags for php files
  // Variables without leading $-character! One variable per line, only!
  protected string $phpTitle = 'pageTitle';   // Page titel
  protected string $phpDesc  = 'description'; // Page description
  protected string $phpKeyw  = 'keywords';    // Page keywords
  // ---------------------------------------------------------------------------

  // Replacement text for meta-tags if empty or nonexistent
  protected string $title = 'Seite hat keinen Titel';
  protected string $desc  = 'Seite hat keine Beschreibung';
  protected string $keyw  = 'Seite hat keine Schlüsselwörter';
  // ---------------------------------------------------------------------------

  private string $rootFolder;
  protected ?string $searchExpression = null;

  /**
   * SearchClass constructor.
   */
  public function __construct()
  {
    $this->rootFolder = $_SERVER['DOCUMENT_ROOT'];
    if (!empty($this->startFolder)) {
      $this->rootFolder = "$this->rootFolder/$this->startFolder";
    }
    $this->extensions = '{' . $this->extensions . '}';
    echo $this->search($_POST['search_exp'] ?? null);
  }

  /**
   * Kick of the search.
   *
   * @param string|null $searchEx - The search expression to look for
   * @return string
   */
  private function search(?string $searchEx): string
  {
    // No search expression, show error message
    if (empty($searchEx)) {
      return ($this->errorTemplate('empty'));
    }
    // Search expression too short, show error message
    if (strlen($searchEx) < $this->searchMinLength) {
      return ($this->errorTemplate('tooShort'));
    }
    // Strip HTML and PHP tags
    $searchEx = strip_tags($searchEx);
    // Remove special characters
    $searchEx = preg_replace( ["~\+~", "~\s+~"], ['', ' '], $searchEx);
    // Escape regular expression characters
    $searchEx = preg_quote($searchEx, $delimiter = null);
    // Strip whitespace characters
    $searchEx = trim($searchEx);
    $this->searchExpression = $searchEx;
    // If logfile is active, log search expression
    if ($this->logfileActive && !empty($this->searchExpression)) { $this->log(); }
    return $this->analyzeFiles();
  }

  /**
   * Analyze the search expression and find files containing it.
   *
   * @return string - Return the search template
   */
  private function analyzeFiles(): string
  {
    // Number of files with search results
    $searchFilesCount = count($this->findFilesWithSearchExpression());
    if ($searchFilesCount === 0) {
      return $this->errorTemplate('noMatch');
    }
    $overAllHits = 0; $searchResults = '';
    foreach ($this->findFilesWithSearchExpression() as $file) {
      $fileArray = file($file, FILE_IGNORE_NEW_LINES | FILE_IGNORE_NEW_LINES);
      $fileString = implode('', $fileArray);
      $metaArray = $this->getMeta($fileArray);
      [$title, $desc] = array_splice($metaArray, 0, 2);
      $fileLink = preg_replace("~$this->rootFolder~", "", $file);
      $textExcerpt = $this->textExcerpt($this->filter($fileString), $this->searchExpression);
      $hitsPerPage = preg_match_all("~$this->searchExpression~siU", $textExcerpt);
      $overAllHits += $hitsPerPage;
      $searchResults .= $this->searchResultTemplate($hitsPerPage, $fileLink, $title, $desc, $textExcerpt);
    }
    return $this->searchTemplate($this->searchExpression, $overAllHits, $searchFilesCount, $searchResults);
  }

  /**
   * Apply filters to file content.
   *
   * @param string $file
   * @return string|null
   */
  private function filter(string $file): ?string
  {
    // Ignore everything between no_search_start and no_search_stop
    $file = preg_replace("~<!--.no_search_start.-->.*?<!--.no_search_stop.-->~s", "", $file);
    // Ignore all (Java-)Scripts
    $file = preg_replace("~<script.*>.*</script>~siU", "", $file);
    // Ignore all php-code
    $file = preg_replace("~<\?.*?\?>~s", "", $file);
    // Ignore all html-tags
    return preg_replace("~(<[^>]+>)~U", "", $file);
  }

  /**
   * Retrieve some meta data form the file.
   *
   * @param array $file
   * @return array
   */
  private function getMeta(array $file): array
  {
    $title = $desc = $keyw = null;
    foreach ($file as $line) {
      // Get title from php-code
      if (preg_match("~$this->phpTitle.*=~", $line)) {
        preg_match("~['\"](.*?)['\"]~sU", $line, $match);
        $title = $match[1];
      }
      // Get description from php-code
      if (preg_match("~$this->phpDesc.*=~", $line)) {
        preg_match("~['\"](.*?)['\"]~sU", $line, $match);
        $desc = $match[1];
      }
      // Get keywords from php-code
      if (preg_match("~$this->phpKeyw.*=~", $line)) {
        preg_match("~['\"](.*?)['\"]~sU", $line, $match);
        $keyw = $match[1];
      }
      // Get title from html-code
      if (preg_match("~<title>(.*)</title>~sU", $line, $match)) {
        $title = $match[1];
      }
      // Get description from html-code
      if (preg_match("~<meta.*name=['\"]description['\"].*content=['\"](.*)['\"].*>~sU", $line, $match)) {
        $desc = $match[1];
      }
      // Get keywords from html-code
      if (preg_match("~<meta.*name=['\"]keywords['\"].*content=['\"](.*)['\"].*>~sU", $line, $match)) {
        $keyw = $match[1];
      }
    }
    return [($title ?: $this->title), ($desc ?: $this->desc), ($keyw ?: $this->keyw)];
  }

  /**
   * Print text excerpt.
   *
   * Surround the search expression with additional text to help the visitor to
   * understand it's context
   *
   * !! Fair Warning: Don't touch the code. It just works. !!
   *
   * If you really need to change the color of the search expression, use the
   * "$textExcerptCSS" variable. With it change the class to what ever you want
   * to.
   * The <strong> tag itself is part of the code.
   *
   * @param string $string - The string to search
   * @param string $searchEx - The search expression
   * @return string
   */
  private function textExcerpt(string $string, string $searchEx): string
  {
    $text = substr($string, max(stripos($string, $searchEx) - $this->excerptLength, 0), strripos($string, $searchEx) - stripos($string, $searchEx) + strlen($searchEx) + (2 * $this->excerptLength));
    if (strrpos($text, " ") < strripos($text, $searchEx)) {
      $text .= " ";
    }
    if (strpos($text, " ") !== strrpos($text, " ")) {
      $text = substr($text, strpos($text, " "), strrpos($text, " ") - strpos($text, " "));
    }
    $temp = $text;
    $end = substr($text, strripos($text, $searchEx) + strlen($searchEx));
    if (strlen($end) > $this->excerptLength) {
      $end = substr($text, strripos($text, $searchEx) + strlen($searchEx), $this->excerptLength);
      $end = substr($end, 0, strrpos($end, " "));
    }
    $text = "... ";

    while (stripos($temp, $searchEx)) {
      $temp = substr_replace($temp, "<strong class='$this->textExcerptCSS'>", stripos($temp, $searchEx), 0);
      $temp = substr_replace($temp, "</strong>", stripos($temp, $searchEx) + strlen($searchEx), 0);
      $text .= substr($temp, 0, stripos($temp, "</strong>") + 9);
      $temp = substr($temp, stripos($temp, "</strong>") + 9);
      if(stripos($temp, $searchEx) > (2 * $this->excerptLength)) {
        $text .= substr($temp, 0, $this->excerptLength);
        $text = substr($text, 0, strrpos($text, " ")) . " ... ";
        $temp = substr($temp, stripos($temp, $searchEx) - $this->excerptLength);
        $temp = substr($temp, strpos($temp, " "));
      }
    }
    $text .= "$end ... ";
    return $text;
  }

  /**
   * Find files with search expression.
   *
   * Walk through folders and find all files containing the search expression
   *
   * @return array - Array with all filenames of files containing the search expression
   */
  private function findFilesWithSearchExpression(): array
  {
    // List all files in startFolder...
    $files = glob("$this->rootFolder/*.$this->extensions", GLOB_BRACE);
    // ... and searchFolders (as needed)
    if (!empty($this->searchFolders)) {
      foreach ($this->searchFolders as $subFolder) {
        if (is_dir("$this->rootFolder/$subFolder")) {
          $subfiles = glob("$this->rootFolder/$subFolder/*.$this->extensions", GLOB_BRACE);
          foreach ($subfiles as $subfile) {
            $files[] = $subfile;
          }
        }
      }
    }
    // List files containing search expression in a new array
    $filesWithSearchExpression = [];
    foreach ($files as $file) {
      $content = file_get_contents($file);
      if (preg_match("~$this->searchExpression~siU", $this->filter($content))) {
        // Ignore "no_search" files
        if (preg_match('~<!--.no_search.-->~siU', $content)) { continue; }
        $filesWithSearchExpression[] = $file;
      }
    }
    unset($files, $subfiles);
    return $filesWithSearchExpression;
  }

  /**
   * Write logfile.
   */
  private function log(): void
  {
    if (ini_get('date.timezone') !== $this->timezone) { date_default_timezone_set($this->timezone); }
    if (setlocale(LC_ALL, 0) !== $this->local) { setlocale(LC_ALL, $this->local); }
    if (!is_file($this->searchLogfile)) { touch($this->searchLogfile); }

    $tempArray = []; $timestamp = strftime('%c');
    if (preg_match("~$this->searchExpression~siU", file_get_contents($this->searchLogfile))) {
      $logfileArray = file($this->searchLogfile, FILE_IGNORE_NEW_LINES | FILE_IGNORE_NEW_LINES);
      foreach ($logfileArray as $line) {
        if (preg_match("~$this->searchExpression~siU", $line)) {
          $count = explode('|', $line);
          $line = $count[0] + 1 . "|$this->searchExpression|$timestamp";
        }
        if (!empty(trim($line))) { $tempArray[] = $line; }
      }
    } else { // add new line with search expression
      file_put_contents($this->searchLogfile, PHP_EOL . "1|$this->searchExpression|$timestamp", FILE_APPEND | LOCK_EX);
    }
    if (!empty($tempArray)) { // sort array und re-write the logfile
      rsort($tempArray, SORT_NATURAL); // sort array high to low
      file_put_contents($this->searchLogfile, implode(PHP_EOL, $tempArray));
    }
    unset($tempArray, $timestamp);
  }

  // -------------------------------------------------------------------------------------------------------------------
  // User functions (templates) to overwrite in an class extension
  // -------------------------------------------------------------------------------------------------------------------

  /**
   * Print error template.
   *
   * @param $error - The error code
   * @return string
   */
  protected function errorTemplate($error): string
  {
    switch ($error) {
      case 'tooShort':
        // Error message: Search expression to short
        $errorMsg = "<strong class='text-danger'>Fehler!</strong> Suche nicht möglich!";
        $infoMsg  = "<strong>Der Suchbegriff ist zu kurz!</strong><br><br>Der Suchbegriff muß mindestens <strong>$this->searchMinLength</strong> Zeichen enthalten.<br><br>Bitte noch einmal mit einem anderen Suchbegriff versuchen.";
        break;
      case 'noMatch':
        // Error message: Search expression not found
        $errorMsg = "<strong class='text-danger'>Fehler!</strong> Suche nach <strong class='text-danger'>$this->searchExpression</strong> ergebnislos!";
        $infoMsg  = "<strong>Der Suchbegriff wurde nicht gefunden!</strong><br><br>Bitte noch einmal mit einem anderen Suchbegriff versuchen.<br>Bitte keine Anführungszeichen, Bindestriche, Komma usw. verwenden!<br>Bei Eingabe von mehr als einem Suchbegriff sind nur Leerzeichen erlaubt!";
        break;
      default:
        // Error message: No Search expression
        $errorMsg = "<strong class='text-danger'>Fehler!</strong> Kein Suchbegriff eingeben!";
        $infoMsg  = "Bitte noch einmal mit einem Suchbegriff versuchen.";
    }
    return "
      <div class='alert alert-danger'>
        $errorMsg
      </div>
      <div class='alert alert-info'>
        $infoMsg
      </div>
      <br>
      ";
  }

  /**
   * Print the search result.
   *
   * This template will be repeated for every page containing the search expression
   *
   * @param string $hitsPerPage - Total hits per page
   * @param string $fileLink - Link to file containing the search expression
   * @param string $title - Title of file containing the search expression
   * @param string $desc - Description of file containing the search expression
   * @param string $textExcerpt - Generated text excerpts
   * @return string
   */
  protected function searchResultTemplate(string $hitsPerPage, string $fileLink, string $title, string $desc, string $textExcerpt): string
  {
    return "
    <tr>
      <td class='text-center'>$hitsPerPage</td>
      <td class='h5'><a class='blue-link' href='$fileLink'>$title</a></td>
    </tr>
    <tr>
      <td>&nbsp;</td>
      <td><span class='text-success'>$desc</span>
        <br><em>$textExcerpt</em><br><br></td>
    </tr>
    ";
  }

  /**
   * Print search template.
   *
   * @param string $searchExpression - The search expression
   * @param int $overAllHits - Number of total hits
   * @param int $overAllPages - Number of total pages
   * @param string $searchResults - The search results using the search result template
   * @return string
   */
  protected function searchTemplate(string $searchExpression, int $overAllHits, int $overAllPages, string $searchResults): string
  {
    return "
      <h3 class='sm-h'>Die Suche nach <strong class='text-danger'>$searchExpression</strong>
        hat <strong class='text-danger'>$overAllHits</strong> Treffer auf auf <strong class='text-danger'>$overAllPages</strong> Seite(n) ergeben:</h3>
      <div class='row'>
        <div class='col-12'>
          <table class='table table-sm'>
            <colgroup>
              <col style='max-width: 8rem;'>
              <col>
            </colgroup>
            <thead>
              <tr>
                <th class='text-center'>Anzahl</th>
                <th>Seite</th>
              </tr>
            </thead>
            <tbody>
              $searchResults
            </tbody>
          </table>
          <br>
        </div>
      </div>
      ";
  }
}

// -----------------------------------------------------------------------------
// Uncomment the include link only if you do NOT extend this class!
// -----------------------------------------------------------------------------

// Check the template and make sure, you're calling the right class in the template!

// This is the template, I'm using on my own website:
// include_once './templates/search.php';

// This is just a basic Bootstrap 5 template. Change it to fit your existing website:
// include_once './templates/search.html';
