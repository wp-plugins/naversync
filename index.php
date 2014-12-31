<?php
/*
Plugin Name: SANaverSync
Plugin URI: http://www.saweb.co.kr
Description: 네이버싱크 플러그인은 네이버 블로그에 워드프레스에 동일한 글이 등록.수정.삭제될수 있는 기능을 제공하는 플러그인 입니다.
Version: 0.9
Author: SAWeb
Author URI: http://www.saweb.co.kr
*/

require_once 'classes/SANaverXmlRpc.php';

define( 'OPTION_KEY_RPC_ID', 'SA_RPC_ID' );
define( 'OPTION_KEY_RPC_API_KEY', 'SA_RPC_API_KEY' );
define( 'OPTION_KEY_RPC_USE_YN', 'SA_RPC_USE_YN' );
define( 'OPTION_KEY_RPC_DEL_USE_YN', 'SA_RPC_DEL_USE_YN' );
define( 'OPTION_KEY_RPC_FLAG', 'SA_RPC_FLAG' );
define( 'OPTION_KEY_RPC_FIX_CATE', 'SA_RPC_FIX_CATE' );

class NaverXmlRpcController {
	var $rpc;
	var $blog_id;
	var $blog_api_key;
	var $blog_rpc_useYn;
	var $blog_check_flag;
	var $blog_del_useYn;
	var $fix_cate;

	function __construct() {
		try {
			add_option( OPTION_KEY_RPC_ID, '' );
			add_option( OPTION_KEY_RPC_API_KEY, '' );
			add_option( OPTION_KEY_RPC_USE_YN, 'Y' );
			add_option( OPTION_KEY_RPC_DEL_USE_YN, 'N' );
			add_option( OPTION_KEY_RPC_FLAG, false );

			$this->blog_id         = get_option( OPTION_KEY_RPC_ID );
			$this->blog_api_key    = get_option( OPTION_KEY_RPC_API_KEY );
			$this->blog_rpc_useYn  = get_option( OPTION_KEY_RPC_USE_YN );
			$this->blog_check_flag = get_option( OPTION_KEY_RPC_FLAG );
			$this->blog_del_useYn  = get_option( OPTION_KEY_RPC_DEL_USE_YN );
			$this->fix_cate        = get_option( OPTION_KEY_RPC_FIX_CATE );

			if ( ! empty( $this->blog_id ) && ! empty( $this->blog_api_key ) ) {
				$this->rpc = new SANaverXmlRpc( $this->blog_id, $this->blog_api_key );
			}

			if ( $this->blog_check_flag && $this->blog_rpc_useYn == 'Y' ) {
				add_action( 'init', array( $this, 'add_cmb' ), 9999 );
				add_filter( 'cmb_meta_boxes', array( $this, 'add_meta_box' ) );
				add_filter( 'content_save_pre', array( $this, 'content_save_pre' ) );

				if ( $this->blog_del_useYn == 'Y' ) {
					add_action( 'trashed_post', array( $this, 'trashed_post' ) );
					add_action( 'untrashed_post', array( $this, 'untrashed_post' ) );
				}
			}

			if ( $this->getParameter( 'page' ) == 'naver_xml_rpc' ) {
				add_action( 'admin_init', array( $this, 'add_style' ) );
				add_action( 'admin_init', array( $this, 'process' ) );
			}

			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			add_action( 'publish_future_post', array( $this, 'publish_future_post' ) );
			add_action( 'admin_menu', array( $this, 'enqueue_scripts' ) );
		} catch ( Exception $e ) {
			echo $e->getMessage();
		}
	}

	function enqueue_scripts() {
		wp_register_script( 'naver-sync', plugin_dir_url( __FILE__ ) . 'resources/script.js', array( 'jquery' ) );
		wp_localize_script( 'naver-sync', 'NaverSync', array(
			'fix_cate' => $this->fix_cate
		) );

		wp_enqueue_script( 'naver-sync' );
	}

	function publish_future_post( $post_id ) {
		$future_list = get_option( 'naver_sync_future_list', array() );

		if ( ! empty( $future_list ) ) {
			$key = "future{$post_id}";

			if ( isset( $future_list[ $key ] ) ) {
				$param = $future_list[ $key ];

				$this->rpc->newPost( SANaverRpcParam::fromArray( $param ) );
				unset( $future_list[ $key ] );

				update_option( 'naver_sync_future_list', $future_list );
			}
		}
	}

	function add_style() {
		wp_enqueue_style( 'xmlrpc-admin-style', plugin_dir_url( __FILE__ ) . '/resources/style.css' );
	}

	/*****************************************************************************************************
	 * 설정화면
	 *****************************************************************************************************/
	function process() {
		if ( $_REQUEST['page'] != 'naver_xml_rpc' ) {
			return;
		}

		$mode = $this->getParameter( 'mode' );

		if ( $mode == 'update' ) {
			$id         = $this->getParameter( 'id', '' );
			$api_key    = $this->getParameter( 'api_key', '' );
			$use_yn     = $this->getParameter( 'api_use_yn', '' );
			$del_use_yn = $this->getParameter( 'del_use_yn', '' );
			$fix_cate   = $this->getParameter( 'fix_cate', '' );

			if ( ! empty( $id ) ) {
				update_option( OPTION_KEY_RPC_ID, $id );
			}

			if ( ! empty( $api_key ) ) {
				update_option( OPTION_KEY_RPC_API_KEY, $api_key );
			}

			if ( ! empty( $use_yn ) ) {
				update_option( OPTION_KEY_RPC_USE_YN, $use_yn );
			}

			if ( ! empty( $del_use_yn ) ) {
				update_option( OPTION_KEY_RPC_DEL_USE_YN, $del_use_yn );
			}

			if ( ! empty( $fix_cate ) ) {
				update_option( OPTION_KEY_RPC_FIX_CATE, $fix_cate );
			}

			wp_redirect( '?page=naver_xml_rpc' );
			die();
		}
	}

	function admin_menu() {
		add_options_page( 'naver_xml_rpc', '네이버 싱크', 'manage_options', 'naver_xml_rpc', array( $this, 'view' ) );
	}

	function view() {
		$param = array(
			'id'        => $this->blog_id,
			'api_key'   => $this->blog_api_key,
			'useYn'     => $this->blog_rpc_useYn,
			'del_useYn' => $this->blog_del_useYn,
			'fix_cate'  => $this->fix_cate
		);

		if ( $this->rpc != null ) {
			$userInfo = $this->rpc->getUserInfo();

			if ( $userInfo->isError() ) {
				$param['error_message'] = $userInfo->getErrMessage();
				update_option( OPTION_KEY_RPC_FLAG, false );
			} else {
				$param['error_message'] = '';
				update_option( OPTION_KEY_RPC_FLAG, true );
				$param['user_info'] = $userInfo;
			}
		}

		extract( $param );

		include_once dirname( __FILE__ ) . '/views/setting.php';
	}

	/*****************************************************************************************************
	 * 포스트 메타박스
	 *****************************************************************************************************/

	function cmb_meta_box_url() {
		return plugins_url() . '/naversync/libs/cmb_metabox/';
	}

	function add_cmb() {
		if ( ! class_exists( 'cmb_Meta_Box' ) ) {
			add_filter( 'cmb_meta_box_url', array( $this, 'cmb_meta_box_url' ) );

			require_once dirname( __FILE__ ) . '/libs/cmb_metabox/init.php';
		}
	}

	function add_meta_box( $meta_boxes ) {
		$cate = $this->rpc->getCategories();

		$prefix = 'XML_RPC';

		$meta_boxes[] = array(
			'id'         => 'naver_meta_box',
			'title'      => '네이버 메타박스',
			'pages'      => array( 'post' ),
			'context'    => 'normal',
			'priority'   => 'high',
			'show_names' => true,
			'fields'     => array(
				array(
					'name'    => '네이버 카테고리',
					'desc'    => '네이버에 포스팅될 카테고리를 선택하세요',
					'id'      => $prefix . '_naver',
					'type'    => 'select',
					'options' => $cate->getChangedOptionCategory(),
				)
			)
		);

		if ( $cate->isError() ) {
			$meta_boxes[0]['fields'][0]['desc'] = $cate->getErrMessage();
		}

		return $meta_boxes;
	}

	function trashed_post( $post_id ) {
		$p_op_name = 'xml_rpc' . $post_id;
		$rpc_id    = get_option( $p_op_name );

		$param = new SANaverRpcParam();
		$param->setPost_id( $rpc_id );

		$res = $this->rpc->deletePost( $param );

		return $post_id;
	}

	function untrashed_post( $post_id ) {
		$post = get_post( $post_id );

		$content = apply_filters( 'the_content', get_post_field( 'post_content', $post_id ) );

		$this->save_post( $post_id, $post->post_title, $post->post_content );
	}

	function save_post( $id, $title, $content, $category = '' ) {
		$p_op_name = 'xml_rpc' . $id;

		$param = new SANaverRpcParam();
		$param->setTitle( $title );

		$content = apply_filters( 'the_content', $content );
		$content = str_replace( "\\\"", "\"", $content );


		$param->setDescription( $content );
		$param->addCategory( $category );

		$res = $this->rpc->newPost( $param );

		update_option( $p_op_name, $res->postid . '' );
	}

	function content_save_pre( $content ) {
		global $post;

		$v = @$_POST['XML_RPC_naver'];

		if ( $v == 'no-use' || empty( $_POST['content'] ) || empty( $_POST['post_title'] ) || $_POST['post_type'] == 'page' ) {
			return $content;
		}

		if ( is_object( $post ) ) {
			$status = $post->post_status;

			$p_op_name = 'xml_rpc' . $_POST['post_ID'];
			$p_id      = get_option( $p_op_name );
			$post_id   = $_POST['post_ID'];

			if ( ! $p_id ) {
				add_option( $p_op_name, '' );
			}

			$param = new SANaverRpcParam();
			$param->setPost_id( $p_id );
			$param->setTitle( $_POST['post_title'] );

			if ( $status == 'private' ) {
				$param->setPublish( false );
			}

			$content = apply_filters( 'the_content', $_POST['content'] );
			$content = str_replace( "\\\"", "\"", $content );

			$param->setDescription( $content );
			$param->addCategory( $v );

			$res = null;

			if ( $status == 'future' ) {
				$future_list                     = get_option( 'naver_sync_future_list', array() );
				$future_list["future{$post_id}"] = $param->toArray();

				update_option( 'naver_sync_future_list', $future_list );

				return $content;
			}

			if ( 'publish' == $status || 'private' == $status ) {
				if ( empty( $p_id ) ) {
					$res = $this->rpc->newPost( $param );
					update_option( $p_op_name, $res->postid . '' );
				} else {
					$res = $this->rpc->editPost( $param );
				}
			} else if ( 'draft' == $status || 'auto-draft' == $status ) {
				$res = $this->rpc->newPost( $param );
				update_option( $p_op_name, $res->postid . '' );
			}
		}

		remove_filter( 'content_save_pre', array( $this, 'content_save_pre' ) );

		return $content;
	}

	/****************************************************************************************************
	 * helper 메소드
	 ****************************************************************************************************/
	function getParameter( $name, $defaultValue = '' ) {
		if ( ! isset ( $_REQUEST [ $name ] ) ) {
			return $defaultValue;
		}

		return $_REQUEST [ $name ];
	}
}

function naver_rpc_init() {
	new NaverXmlRpcController();
}

add_action( 'init', 'naver_rpc_init' );