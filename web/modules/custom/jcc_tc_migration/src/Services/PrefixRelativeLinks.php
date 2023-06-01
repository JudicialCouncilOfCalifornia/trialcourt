<?php

namespace Drupal\jcc_tc_migration\Services;

/**
 * Class PrefixRelativeLinks.
 *
 * Add leading slash to relative paths when needed.
 */
class PrefixRelativeLinks {

  /**
   * Add a lead / to relative links in markup when required.
   *
   * @param string $value
   *   The html in which to replace links.
   *
   * @return string
   *   The updated string.
   */
  public function replace($value) {
    $dom = new \DomDocument();
    $dom->loadHTML($value);

    $parent_url = explode("**", $value);                           
    $subdir_original = explode("/", parse_url($parent_url[0], PHP_URL_PATH)); 
    //$value_original =  $value;         
    
if ($parent_url[0] == 'https://www.scscourt.org/general_info/contact/contact.shtml') {
  
    foreach ($dom->getElementsByTagName('a') as $item) {
      // The url for the link to determine file name.
      $href = $item->getAttribute('href');
      $scheme = parse_url($href, PHP_URL_SCHEME);      
      // Add leading slash to original link if needed.
      if (!$scheme) {
        $path = parse_url($href, PHP_URL_PATH);

        if($path !== NULL) {

          //$value = $value_original;         
         // if (preg_match('/^\.\.(?!\/\.\.)[^\/]/', $path)) {
         //if(((substr($path, 0, 3) === '../') && (substr($path,0,6) !== '../../'))) {    
         if (preg_match('/^\.\.\/[^\/]+$/', $path )) { //Last tried             
            //$value = $value_original;
            $subdir = $subdir_original;
            $nested_level = count($subdir);          
            array_pop($subdir);   
            array_pop($subdir);                 
            $value_original = str_replace('../', '/'.$subdir[1].'/', $value);  
            $value = $value_original; 
        }  elseif (strpos($path, '../../') !== false) {   //For ../../
           // echo "Path contains ../../";
            //$value = $value_original;
            $value_original = str_replace('../../', '/', $value);  
            $value = $value_original; 
        } else if (!str_starts_with($path,'/') && !str_starts_with($path,'.')) {
          // $value = $value_original;
            echo "Invalid path";
            /*$subdir = $subdir_original;
            array_pop($subdir); 
            $subdir_text = implode("/", $subdir);
            $new_path = '/'.$subdir_text.'/'.$value;
            $value_original = str_replace($path, $new_path, $value); */
        }
       // $value = $value_original;

        } // End of path null check here   
      }
    }
    //return $value;
    return $value_original;        
      }    
       
  }
}     
