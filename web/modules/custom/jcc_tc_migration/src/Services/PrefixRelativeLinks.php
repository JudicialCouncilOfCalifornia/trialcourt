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
    
   // if ($parent_url[0] == 'https://www.scscourt.org/general_info/contact/contact.shtml') {  
   //  if ($parent_url[0] == 'https://www.scscourt.org/court_divisions/small_claims/small_claims_rules.shtml') {
   // if ($parent_url[0] == 'https://www.scscourt.org/self_help/traffic/citation_types/citations_home.shtml') {
    foreach ($dom->getElementsByTagName('a') as $item) {
          // The url for the link to determine file name.
            $href = $item->getAttribute('href');
            $scheme = parse_url($href, PHP_URL_SCHEME);     

            if (!$scheme) {
            $path = parse_url($href, PHP_URL_PATH);

            if($path !== NULL) {  

            //For ../../  
            if (str_starts_with($path,'../../') && strpos($path, '../../') !== false) {    
              //$value = preg_replace('/(href\=\"\.\.\/\.\.\/)(?=[^(\.\.\/)])/', 'href="'.'/', $value, 1);                    
              $value = preg_replace('/(href\=\"\.\.\/\.\.\/)(?=[^(\.\.\/)])/', 'href="'.'/', $value);                    
             } else if( ((substr($path, 0, 3)) === '../') && ((substr($path,0,6)) !== '../../') && strpos($path, '../') !== false)  {     //For ../            
               $subdir = $subdir_original;
               $nested_level = count($subdir);          
               array_pop($subdir);   
                array_pop($subdir);   
                //$value = preg_replace('/(href\=\"\.\.\/){1}(?=[^(\.\.\/)])/','href="' .'/'.$subdir[1].'/', $value, 1);  
                $value = preg_replace('/(href\=\"{1,2}\.\.\/){1}(?=[^(\.\.\/)])/','href="' .'/'.$subdir[1].'/', $value);  
                //$value = preg_replace("/(href\=\"{1,2}\.\.\/){1}(?=[^(\.\.\/)])/", $new_path, $value);                   
            }           
            else if (!str_starts_with($path,'/') && !str_starts_with($path,'.')) {     //For others                         
                $subdir = $subdir_original;
                array_pop($subdir); 
                $subdir_text = implode("/", $subdir);
                $new_path = $subdir_text.'/'.$path;
                //$pattern = '/(^|\s)' . preg_quote($path, '/') . '(\s|$)/';
                //$pattern = "/\b$path\b/";
                //$pattern = '/(?<!\w)' . preg_quote($path, '/') . '\b(?!\.)/';
                //$pattern = "/\b' . preg_quote($path, '/') . '\b/";
                //$pattern = '/(?<!\w)' . preg_quote($path, '/') . '(?!\w)/';
                //$value = preg_replace($pattern, $new_path, $value);   
                /*$value = preg_replace_callback($pattern, function ($matches) use ($new_path) {
                  return $new_path;
              }, $value);*/               
                $value = preg_replace("/\b$path\b/", $new_path, $value);  
                //$value = preg_replace("/\b$path\b/", $new_path, $value, 1);                                   
            }                   
           }       
        }         
      } //for loop      
     
   return $value;
  // if condition for page    
    
  }
}     
