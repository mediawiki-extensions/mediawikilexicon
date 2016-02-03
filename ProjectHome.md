This extension automatically links all instances of individual terms within a wiki to their definition.

## What can this extension do? ##
This extension is based on [Extension:Terminology](http://www.mediawiki.org/wiki/Extension:Terminology) and was further developed by [WiiKno Inc.](http://www.wiikno.com)
This extension  terms located in a template and when the term is found in a topic, it will be highlighted, linked to term definition and mousing over it will reveal the definition in a tool tip.
Extension uses Walter Zorn's "JavaScript, DHTML Tooltips" library so you can customize the appearance of the tooltips.
## Usage ##
By default, extension uses a template (that you must define) with the name “Lexicon”. In the “Lexicon” template it is expecting a parameter “term”  and a parameter “definition”. In the “term” field it is expected to have the 'term name' and in the “definition” parameter it is expected to have definition of the term. It's also possible to override these default template name and default parameter names.
The template should look something like this (as a suggestion; you can format however you wish):

```
<noinclude>
This is the "Lexicon" template.
It should be called in the following format:
<pre>
{{Lexicon
|term=
|definition=
}}
</pre>
Edit the page to see the template text.
</noinclude><includeonly>
{| class="wikitable"
! Term
| {{{term|}}}
|-
! Definition
| {{{definition|}}}
|}
[[Category:Lexicon]]
</includeonly>

```

## Download instructions ##
Please cut and paste the code found [[#Code|below]] and place it in ```
$IP/extensions/Lexicon/Lexicon.php```.  ''Note: [[Manual:$IP|$IP]] stands for the root directory of your MediaWiki installation, the same directory that holds [[Manual:LocalSettings.php|LocalSettings.php]]''.

## Installation ##
Currently tested on:
  * ediawiki version 1.15.4
  * ysql 5.0+ (_older may work_) (**other databases are not supported at this time**)

  * efore enabling the Extension in Localsetting file, apply the mysql script located on extensions/Lexicon directory, script requires mysql root privileged access.
> > `  Example : mysql -u root -p {MediaWiki database name} < Lexicon.sql`

> Enter mysql root password when prompted
    * Creates a '''table''' “Lexicon\_cache”
    * Create three '''Triggers''' on tables “revisions” and “templatelinks”
  * Enable extension using following line in Localsettings.php
> > `require_once("$IP/extensions/Lexicon/Lexicon.php");`

  * (Optional)  If you wish to use the Javascript-based tooltips, you need to download Walter Zorn's "JavaScript, DHTML Tooltips" library (found at: [here](http://gualtierozorni.altervista.org/tooltip/tooltip_e.htm) or [there](http://www.walterzorn.com/tooltip/tooltip_e.htm)). Create the directory: extensions/tooltip.


> `mkdir -p extensions/tooltip`

And uncompress the archive "wz\_tooltip.zip" into that directory.  You should have the latest version of the library (version 5.13 at the time of this writing.)  (At the end, you should have 3 files: extensions/tooltip/tip\_centerwindow.js, extensions/tooltip/tip\_followsscroll.js, and extensions/tooltip/wz\_tooltip.js )

### Configuration parameters ###
Optionally use following variables in LocalSettings.php file to override the Default names.
```
 $wgTemplateName="template name";
 $wgTermField="parameter name used in template to indicate the term";
 $wgDefinitionField="parameter name used in template to indicate definition of term";
```
### Startup Instructions (IMPORTANT) ###
On the first time running the extension, extension will build a cache of all the terms and their definitions. There after  cache will be updated one term at time. If the number of terms found on the first run of extension is very large (thousands), it may take 30 seconds or more to build the cache.  In this case, there will be error on MediaWiki view of Topic, as maximum allowed time to process a request is limited to 30 seconds in typical MediaWiki installtion. Therefore, maximum allowed time to process a request must be increased to build the entire cache for the first time.

Two variables limiting execution time in MediaWiki
**php.ini file “max\_execution\_time =”** httpd.conf file “php\_value max\_execution\_time”
Need put time in second to a value sufficant to update full term cache

Once cache is build for the first time, these values can be revert back to default values.

### Rebuild Cache ###
To rebuild the entire cache, after the initial setup, run following sql query on Mediawiki database.

`` UPDATE `Lexicon_cache` SET `term` = 'reload' WHERE `id` =0;``