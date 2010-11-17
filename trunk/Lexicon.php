<?php
/*
 * Copyright (C) 2007  BarkerJr (C) 2008 Benjamin Kahn (C) 2010 Saman Desilva and (C) 2010 Mason McLead
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/
 
$wgExtensionCredits['parserhook'][] = array(
  'name' => 'Lexicon',
  'description' => 'Provides tooltips from the [[Terminology]] defined for all instances of the given term',
  'version' => '20100905',
  'author' => 'BarkerJr modified by Benjamin Kahn (xkahn@zoned.net) modified by Saman Desilva, Wiikno Inc'
);
 
$wgExtensionFunctions[] = 'terminologySetup';
function terminologySetup() {
  global $wgOut, $wgScriptPath;
  $wgOut->addHTML("<style text=\"text/css\" media=\"screen\"><!-- .terminologydef {border-bottom: 1px dashed green;} --></style>");
  if (is_file ('extensions/tooltip/wz_tooltip.js')) {
    $wgOut->addHTML("<script type='text/javascript' src='$wgScriptPath/extensions/tooltip/wz_tooltip.js'></script>");
  }
}
 
$wgHooks['ParserBeforeTidy'][] = 'terminologyParser';
function terminologyParser(&$parser, &$text) {
  global $wgRequest,$wgTemplateName,$wgTermField,$wgDefinitionField;
  
  $templatename = $wgTemplateName?$wgTemplateName:"Term";
  $termfield = $wgTermField?$wgTermField:"Term";
  $definitionfield = $wgDefinitionField?$wgDefinitionField:"Definition";
 
  $action = $wgRequest->getVal( 'action', 'view' );             
  if ($action=="edit" || $action=="ajax" || isset($_POST['wpPreview'])) return false;
  $cache_state = get_cache_state();
  $dbr = wfGetDB( DB_SLAVE );
  if ( $cache_state == 'reload') {
    full_refresh();
  }
  $res = $dbr->select( array('Lexicon_cache'),
                         array('id','term','def','url','dirty'),
                         array(),
                         __METHOD__);
			  #array('ORDER BY' => 'LENGTH(TRIM(term))');
  $changed = false;
  $doc = new DOMDocument();
@     $doc->loadHTML('<meta http-equiv="content-type" content="charset=utf-8"/>' . $text);
  while ($row = $dbr->fetchObject( $res )){
     if ($row->id == '0') { continue; }
     if ($row->dirty) {
       if (!refresh(&$row)){
         continue;
       }
     }  
     $term = $row->term;
     $definition=$row->def;
     $url=$row->url;
  
     if (terminologyParseThisNode($doc, $doc->documentElement, $term, $definition,$url)) {
       $changed = true;
     }
  }
  if ($changed) {
   $text = $doc->saveHTML();
  }
  return true;
}
 
function terminologyParseThisNode($doc, $node, $term, $definition,$url) {
  $changed = false;
  if ($node->nodeType == XML_TEXT_NODE) {
    $texts = preg_split('/\b('.preg_quote($term).'s?)\b/u', $node->textContent, -1, PREG_SPLIT_DELIM_CAPTURE);
    if (count($texts) > 1) {
      $container = $doc->createElement('span');
      for ($x = 0; $x < count($texts); $x++) {
        if ($x % 2) {
          $link = $doc->createElement('a', $texts[$x]);
          #$url = "/mw/index.html/AAA";
          $link->setAttribute('href',$url);
          $span = $doc->createElement('span', '');
          $span->appendChild($link);
 
	  if (!is_file ('extensions/tooltip/wz_tooltip.js')) {
	    $span->setAttribute('title', $term . ": " . $definition);
            $span->setAttribute('class', 'terminologydef');
	  } else {
            $bad = array ("\"", "'");
            $good = array ("\\\"", "\'");
	    $span->setAttribute('onmouseover', "Tip('".str_replace ($bad, $good, $definition)."', STICKY, true, DURATION, -1000, WIDTH, -600)");
	    $span->setAttribute('class', 'terminologydef');
	    $span->setAttribute('onmouseout', "UnTip()");
	  }
 
          $span->setAttribute('style', 'cursor:help');
          $container->appendChild($span);
        } else {
          $container->appendChild($doc->createTextNode($texts[$x]));
        }
      }
      $node->parentNode->replaceChild($container, $node);
      $changed = true;
    }
  } elseif ($node->hasChildNodes()) {
    // We have to do this because foreach gets confused by changing data
    $nodes = $node->childNodes;
    $previousLength = $nodes->length;
    for ($x = 0; $x < $nodes->length; $x++) {
      if ($nodes->length <> $previousLength) {
        $x += $nodes->length - $previousLength;
      }
      $previousLength = $nodes->length;
      $child = $nodes->item($x);
      if (terminologyParseThisNode($doc, $child, $term, $definition,$url)) {
        $changed = true;
      }
    }
  }
  return $changed;
}

function update_cache ($id,$term,$def,$url) {
   $fields = array ('id' => $id,
                    'term' => $term,
                    'def' => $def,
                    'dirty' => '0',
                    'url' => $url);

   
   $dbr = wfGetDB( DB_MASTER );
   $res = $dbr->select( array('Lexicon_cache'), array('term'), array( 'id'=> $id), __METHOD__);
   $dbw = wfGetDB( DB_MASTER );
   if ($row = $dbr->fetchObject( $res )){
      $dbw->update ( 'Lexicon_cache',$fields,array('id'=>$id));
   }else {
     $dbw->insert( 'Lexicon_cache', $fields, __METHOD__, array( 'IGNORE' ) );
   }
}
function get_cache_state(){
    $dbr = wfGetDB( DB_MASTER );
    $res = $dbr->select( array('Lexicon_cache'),
                         array('term'),
                         array( 'id'=> '0'),
                         __METHOD__);
  
  if ($row = $dbr->fetchObject( $res )){
     return $row->term;
  }
  $dbw = wfGetDB( DB_MASTER );
  $dbw->insert( 'Lexicon_cache', array(term => 'reload','id'=> '0'), __METHOD__, array( 'IGNORE' ) );
  return 'reload';
}
function set_cache_state ($state) {
   $dbw = wfGetDB( DB_MASTER );
   $time = date("F j, Y, g:i a");
   $dbw->update ( 'Lexicon_cache',array('term' => $state,'def'=>$time),array('id'=>'0'));
  
}
function full_refresh () {
  global $wgRequest,$wgTemplateName,$wgTermField,$wgDefinitionField;
  
  $templatename = $wgTemplateName?$wgTemplateName:"Term";
  $termfield = $wgTermField?$wgTermField:"Term";
  $definitionfield = $wgDefinitionField?$wgDefinitionField:"Definition";
   $dbw = wfGetDB(DB_MASTER);
   $res = $dbw->doQuery("TRUNCATE TABLE Lexicon_cache");
   $dbr = wfGetDB(DB_MASTER);
   $res = $dbr->select( array('templatelinks'),
                           array('tl_from','tl_namespace'),
                         array('tl_title' => $templatename),
                         __METHOD__);
   while ($row = $dbr->fetchObject( $res )){ 
     if (!$row->tl_from) {
       continue;
     }
     $term = $definition = '';
     $title = Title::newFromID($row->tl_from);
     $id = $title->getArticleId();
     $article = Article::newFromId($id);
     $content1 = $article->getRawText();
     $content = str_replace("\n",'',$article->getRawText());
     $url = $title->getFullUrl();
     $termTeplate_reg = "/{{[\s]*".$templatename."(.*)}}/i";
     if (preg_match($termTeplate_reg,$content,$matches)){
       $paras= $matches[1];
     }else{
      continue;
     }
     $fields = preg_split("/\|/",$paras);
     foreach ($fields as $field) {
       $value = preg_split("/=/",$field);
       if (trim($value[0]) == $termfield ) { $term = trim ($value[1]);}
       if (trim($value[0]) == $definitionfield ) { $definition = trim ($value[1]);}
     }
     if ( (!strlen($definition)) || (!strlen($term)) ) { continue;}
     update_cache ($row->tl_from,$term,$definition,$url);
  }//End of while
  //Update status of cache; id=0 used for status of the cache
  update_cache ("0","fresh","","");
}
function refresh (&$row) {
  global $wgRequest,$wgTemplateName,$wgTermField,$wgDefinitionField;
  
  $templatename = $wgTemplateName?$wgTemplateName:"Term";
  $termfield = $wgTermField?$wgTermField:"Term";
  $definitionfield = $wgDefinitionField?$wgDefinitionField:"Definition";
   $dbr = wfGetDB (DB_MASTER);
   $res = $dbr->select ('templatelinks',
                 array('tl_from'),
                 array('tl_from'=>$row->id,
                       'tl_title' => $templatename),
                 __METHOD__);
   if ($row1 = $res->fetchObject()) {
     $term = $definition = '';
     $title = Title::newFromID($row1->tl_from);
     $id = $title->getArticleId();
     $article = Article::newFromId($id);
     $content1 = $article->getRawText();
     $content = str_replace("\n",'',$article->getRawText());
     $url = $title->getFullUrl();
     $termTeplate_reg = "/{{[\s]*".$templatename."(.*)}}/i";
     if (preg_match($termTeplate_reg,$content,$matches)){
       $paras= $matches[1];
     }else{
      return false;
     }
     $fields = preg_split("/\|/",$paras);
     foreach ($fields as $field) {
       $value = preg_split("/=/",$field);
       if (trim($value[0]) == $termfield ) { $term = trim ($value[1]);}
       if (trim($value[0]) == $definitionfield ) { $definition = trim ($value[1]);}
     }
     if ( (!strlen($definition)) || (!strlen($term)) ) { return false;}
     $row->term = $term;
     $row->def = $definition;
     $row->url=$url;
     update_cache ($row1->tl_from,$term,$definition,$url);
   }else {
     $dbw = wfGetDB (DB_MASTER);
     $dbw->delete ( 'Lexicon_cache',
                    array('id' => $row->id),
                   __METHOD__);
     return false;
   }
   return true;
}
?>
