# SearchClass - Full Text Search for Websites

The SearchClass is a full text search for websites with multiple pages. It works with HTML or PHP based sources. The class can be extended with your own templates and/or your translations.

## Features
- All variables are changeable by extending the class
- The search can be limited to parts of the source
- The content of a source can be completely ignored
- You can define your DOCUMENT_ROOT
- Additional folders can be included
- A log file can be written to collect the search terms used
- The file extensions considered for the search can be limited
- Google-Style search results with text excerpts

The class is full of comments and should be self-explanatory.

## Screenshot

![Search Result Page][image-1]

## Content
- ***SearchClass.php*** is the main class
- ***MySearch.php*** is an example extension of the main class
- ***template/search.html*** is an HTML-template to display the search results
- ***template/search.php*** is my own PHP-template to display the search results

## How it works
After calling the class by using a form (which is part of the HTML-template), the class will start a search according to the given folders.

For the initial search filters will be applied:

- Ignore all HTML tags
- Ignore everything that is included via `<script>`
- Ignore all PHP-code aside from your defined meta-tag variables
- If a source contains `<!--no_search -->`, ignore it's content
- Ignore everything that is outside of:
	- `<!-- no_search_start -->`
	- ... and ...
	- `<!-- no_search_stop -->`

The search limiters:

`<!--no_search -->`

`<!-- no_search_start -->`

`<!-- no_search_stop -->`

Will also work inside PHP-code by applying the standard PHP comment in front or around it.

E.g. `//<!-- no_search -->`  or `/*<!-- no_search -- >*/`

All relevant file-paths will be stored in an array then the files will be searched again and a text excerpt will be extracted. Finally the search results will be displayed in Google-style result page.

There are two options to display the page title:

1. Use the content of the title-tag from the HTML header
    - `<title>...</title>`
2. Use the content of a pre-defined PHP variable inside the source file
    - `$this->phpTitle = '...'`

There are also two options to display the page description:

1. Use the content of the description meta-tag from the HTML header
    - `<meta name="description" content="...">`
2. Use the content of a pre-defined PHP variable inside the source file
    - `$this->phpDesc = '...'`

In both cases, the PHP variable will overwrite the meta-tag, if it exists, too.


### Important:

If no limiter is found inside a source, ***ALL*** text will be searched and used for the initial search!

But for the text excerpt itself, all filters will be applied before displaying it. 

At the end of the main class or its extension is the template included that displays the result page. Make sure you include the template only ones. If you don't extend the main class, include the template there. If you extend the main class, include the template in its extension. 

## Limitations

The minimum PHP version is 7.4

[image-1]:screenshot.png
