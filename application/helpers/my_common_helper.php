<?php

/**
 * CORS(Cross-Origin Resource Sharing) 처리
 * - cors 처리를 위한 헤더 선언
 */
if( !function_exists('header_cors') ) {
    function header_cors(){
        Header("Access-Control-Allow-Origin: *");
        Header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
        Header("Access-Control-Max-Age: 3600");
        Header("Access-Control-Allow-Headers: Origin,Accept,X-Requested-With,Content-Type,Access-Control-Request-Method,Access-Control-Request-Headers,Authorization");
        Header('Cache-Control: no-cache, must-revalidate');
        Header('Content-type: application/json');
        // <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests"> 
    }//     EOF     function header_cors()
}//     EO      if( !function_exists('header_cors') ) {
/**
 * print_r 확장
 * - print_r 결과값에 디자인요소 추가
 */
if( !function_exists('printR') ){
     function printR($messsage, $title = NULL){
          $result = '';
          $result .= '<div style="width:100%;z-index:999999;">';
          $result .= '<pre style="background-color:#000000;color:#00FF00; padding:5px; font-size:14px;z-index:999999;">';
          if( $title ){ $result .=  "<strong style='color:#fff'>{$title} :</strong> \n"; }
          $result .= print_r($messsage, TRUE);
          $result .= '</pre>';
          $result .= '</div>';
          $result .= '<br />';

          echo $result;
     }//          EOF          function printR($msg, $title = NULL)
}//          EO          if ( !function_exists('printR') )
if( !function_exists('vardump') ){
     function vardump($variable){
          echo "<pre style='background: black; color: white; padding: 20px;width:100%;max-height:100%;z-index:9999;font-size:14px;overflow:auto;'>";
          var_dump($variable);
          echo "</pre>";
     }//          EOF          function vardump($variable)
}//          EO          if ( !function_exists('vardump') )

if( !function_exists('nl2br_space') ){
     function nl2br_space($content){
          $content = str_replace(' ','&nbsp;',$content);
          $content = nl2br($content);

          return $content;
     }//       EOF       function nl2br_space($contents)
}//       EO        if( !function_exists('nl2br_space') )

/*
 * 널값 처리
 */
if( !function_exists('nvl') ){
     function nvl($var,$default=''){
          $temp = trim($var);
          return $temp === '' ? $default : $var;
     }
}

/*
 * false => null
 */
if( !function_exists('nul') ) {
     function nul($var) {
         $return = $var;
         if ($var == 0)
         {
             $return =  null;
         }
         return $return;
     }
}

/*
 * BR태그를 개행문자로 치환
 */
if( !function_exists('br2nl')){
    function br2nl($string){
        return preg_replace('/\<br(\s*)?\/?\>/i', "\n", $string);
    }
}

/*
 * 개행문자를 BR태그로 치환
 */
if( !function_exists('nl2br')){
    function nl2br($string){
        return preg_replace('/\R/u', '<br/>', $string);
    }
}

/*
 * json 체크
 */
if( !function_exists('is_json')){
    function is_json($string){
        return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
    }
}

/**
 * 2차원 배열 value값으로 정렬
 * - 정렬대상 array, 정렬 기준 key, 오름/내림차순
 */
if( !function_exists('arr_sort')){
    function arr_sort($array, $key, $sort='asc') {
        $keys = array();
        $vals = array();

        foreach ( $array as $k=>$v ) {
            $i = $v[$key].'.'.$k;
            $vals[$i] = $v;
            array_push($keys, $k);
        }
        unset($array);

        if ($sort=='asc') {
            ksort($vals);
        } else {
            krsort($vals);
        }

        $ret = array_combine($keys, $vals);
        unset($keys);
        unset($vals);
        
        return $ret;
    }
}

/**
 * 배열 key 대소문자 변경
 */
if( !function_exists('ci_array_change_key_case') ){
    function ci_array_change_key_case( $array, $case=CASE_LOWER ) {
        $array = array_change_key_case( $array, $case );
        foreach( $array as &$obj ){
            if( is_array( $obj ) ){
                $obj = ci_array_change_key_case( $obj, $case );
            }
        }

        return $array;
    }
}

/**
 * 문자열숫자 확인하여 숫자로 변환
 *  > 빈값인 기본값 반환
 */
if( !function_exists('nvl_no') ) {
    function nvl_no($var,$default=1){
        $temp = trim($var);
        $temp = $temp === '' ? $default : $var;
        $temp = is_numeric($temp) ? (int)$temp : false;
        return $temp;
   }
}

/* Location: ./application/helpers/My_common_helper.php */