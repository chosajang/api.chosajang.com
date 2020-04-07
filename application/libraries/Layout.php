<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Layout {
	var $obj;
	var $layout;
	var $layout_dir = "/_layout/";
	var $view_data = array();

	function __construct($layout='blank') {
		// log_message('debug', "Layout Class Initialized");

		$this->obj =& get_instance();
		$this->layout = $layout;
	}

	function setLayout($layout) {
		$this->layout = $layout;
	}

	function view($view, $data=null, $return=false) {
	     $this->layout = $this->layout_dir . $this->layout;

		$loadedData = array();
		$loadedData['content'] = $this->obj->load->view($view, $data, true);

		/*
		 * append_view를 통해 호출된 템플릿 정보를 loadedData에 할당한다
		 * loadedData에 삽입되는 키값은 append_view에 사용된 $mode값으로 삽입된다
		 */
		foreach( array_keys( $this->view_data ) as $key ){
		     $loadedData[$key] = $this->view_data[ $key ];
		}

		if($return) {
			$output = $this->obj->load->view($this->layout, $loadedData, true);
			return $output;
		}else {
			$this->obj->load->view($this->layout, $loadedData, false);
		}
	}

	/*
	 * layout에 추가적으로 view template을 붙이는 함수
	 * $mode - 지정된 규칙을 사용하기 위한 변수 폴더 및 파일의 접미사(suffix)를 붙여준다
	 *  ex) 'sidebar' - '/_layouts/sidebar/base_sidebar'
	 * 실제 파일 경로/이름 - '/_layouts/sidebar/base_sidebar.php'
	 */
	function append_view($mode, $view, $data=null) {
	     $view_path = $this->layout_dir . $mode . DIRECTORY_SEPARATOR . $view;

	     $this->view_data[$mode] = $this->obj->load->view($view_path, $data, true);
	}//      EOF       function append_view($view, $data=null)

}//            EOC       class Layout