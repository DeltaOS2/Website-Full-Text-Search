<?php
/**
 * Created by BluePrintOnline.Net.
 * Project: Full text search for websites
 * Author: Gert Massheimer
 * Date: 24.Mär.2021
 * Time: 11:00
 * File: MySearch.php
 * ------------------------------------------------- */

include_once 'SearchClass.php';

// <!-- no_search -->

class MySearch extends SearchClass
{

  public function __construct()
  {
    // Settings needed for logfile, only -----------------------------------------
    // Write logfile?
    $this->logfileActive = true;
    // Path and filename of logfile
    $this->searchLogfile = './log/new_search_log.txt';
    // Timezone
    $this->timezone = 'America/New_York';
    // Local timezone translation to be used
    $this->local = 'en_US';
    // ---------------------------------------------------------------------------

    // Root folder from where the search starts. Leave empty if it starts from
    // DOCUMENT_ROOT
//    $this->startFolder  = '';
    // Additional sub-folders basing on startFolder
//    $this->searchFolders = ['assets/php/modals'];
    // File extension to look for. Case sensitiv! No spaces!
//    $this->extensions = 'htm,html,php,HTM,HTML,PHP';
    // ---------------------------------------------------------------------------

    // Min length for search expression
//    $this->searchMinLength = 3;
    // Max length for text excerpt
//    $this->excerptLength = 30;
    // CSS class to be used to highlight the search expression in the text excerpt
    $this->textExcerptCSS = "text-info";
    // ---------------------------------------------------------------------------

    // Meta-tags for php files
    // Variables without leading $-character! One variable per line!
//    $this->phpTitle = 'pageTitle';   // Page titel
//    $this->phpDesc  = 'description'; // Page description
//    $this->phpKeyw  = 'keywords';    // Page keywords
    // ---------------------------------------------------------------------------

    // Replacement text for meta-tags if empty or nonexistent
    $this->title = 'No title available';
    $this->desc  = 'No description available';
    $this->keyw  = 'No keywords available';
    // ---------------------------------------------------------------------------

    // Call constructor from parent class
    parent::__construct();
  }

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
        $errorMsg = "<strong class='text-danger'>Error!</strong> Search not possible!";
        $infoMsg  = "<strong>The search expression is too short!</strong><br><br>The search expression should have at least <strong>$this->searchMinLength</strong> character.<br><br>Please try again with a different search term.";
        break;
      case 'noMatch':
        // Error message: Search expression not found
        $errorMsg = "<strong class='text-danger'>Error!</strong> Search for <strong class='text-danger'>$this->searchExpression</strong> without result!";
        $infoMsg  = "<strong>The search term was not found!</strong><br><br>Please try again with a different search term.<br>Please do not use quotation marks, hyphens, commas, etc.!<br>When entering more than one search term, only spaces are allowed!";
        break;
      default:
        // Error message: No search expression
        $errorMsg = "<strong class='text-danger'>Error!</strong> No search term given!";
        $infoMsg  = "Please try again with a search term.";
    }
    return "
      <div class='container'>
        <br><br><br>
        <div class='alert alert-danger'>
          $errorMsg
        </div>
        <div class='alert alert-info'>
          $infoMsg
        </div>
        <br>
      </div>
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
      <div class='container'>
        <br><br><br>
        <h3 class='sm-h'>The search for <strong class='text-danger'>$searchExpression</strong>
        produced <strong class='text-danger'>$overAllHits</strong> hits on <strong class='text-danger'>$overAllPages</strong> page(s):</h3>
        <table class='table table-sm'>
          <colgroup>
            <col style='max-width: 8rem;'>
            <col>
          </colgroup>
          <thead>
            <tr>
              <th class='text-center'>Hits</th>
              <th>Page</th>
            </tr>
          </thead>
          <tbody>
            $searchResults
          </tbody>
        </table>
        <br>
      </div>
      ";
  }
}

// !! Check the template and make sure, you're calling the right class in the template !!

// --- This is the template, I'm using on my own website:
//include_once './templates/search.php';

// --- This is just a basic Bootstrap 5 template. Change it to fit your existing website:
include_once './templates/search.html';
