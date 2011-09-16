<?php

// piCal xoops�ѥᥤ��⥸�塼��
// index.php
// ����������ɽ�����Խ�����������
// by GIJ=CHECKMATE (PEAK Corp. http://www.peak.ne.jp/)

	require( '../../mainfile.php' ) ;

	// for "Duplicatable"
	$mydirname = basename( dirname( __FILE__ ) ) ;
	if( ! preg_match( '/^(\D+)(\d*)$/' , $mydirname , $regs ) ) echo ( "invalid dirname: " . htmlspecialchars( $mydirname ) ) ;
	$mydirnumber = $regs[2] === '' ? '' : intval( $regs[2] ) ;

	require_once( XOOPS_ROOT_PATH."/modules/$mydirname/include/gtickets.php" ) ;

	// ����ƥ�����
	// $xoopsConfig[ 'language' ] = 'french' ;

	// MySQL�ؤ���³
	// $conn = mysql_connect( XOOPS_DB_HOST , XOOPS_DB_USER , XOOPS_DB_PASS ) or die( "Could not connect." ) ;
	// mysql_select_db( XOOPS_DB_NAME , $conn ) ;
	$conn = $xoopsDB->conn ;

	// setting physical & virtual paths
	$mod_path = XOOPS_ROOT_PATH."/modules/$mydirname" ;
	$mod_url = XOOPS_URL."/modules/$mydirname" ;

	// ���饹������ɤ߹���
	if( ! class_exists( 'piCal_xoops' ) ) {
		require_once( "$mod_path/class/piCal.php" ) ;
		require_once( "$mod_path/class/piCal_xoops.php" ) ;
	}

	// GET,POST�ѿ��μ�����������
	if( empty( $_GET['action'] ) && ! empty( $_GET['event_id'] ) ) $_GET['action'] = 'View' ;

	if( isset( $_GET[ 'action' ] ) ) $action = $_GET[ 'action' ] ;
	else $action = '' ;

	// creating an instance of piCal 
	$cal = new piCal_xoops( "" , $xoopsConfig['language'] , true ) ;

	// setting properties of piCal
	$cal->conn = $conn ;
	include( "$mod_path/include/read_configs.php" ) ;
	$cal->base_url = $mod_url ;
	$cal->base_path = $mod_path ;
	$cal->images_url = "$mod_url/images/$skin_folder" ;
	$cal->images_path = "$mod_path/images/$skin_folder" ;


	// �ǡ����١��������ط��ν����ʤ�����⡢Location�����Ф���
	if( isset( $_POST[ 'update' ] ) ) {
		// ����
		if( ! $editable ) die( _MB_PICAL_ERR_NOPERMTOUPDATE ) ;
		// Ticket Check
		if ( ! $xoopsGTicket->check() ) {
			redirect_header(XOOPS_URL.'/',3,$xoopsGTicket->getErrors());
		}
		$cal->update_schedule( "$admission_update_sql" , $whr_sql_append ) ;
	} else if( isset( $_POST[ 'insert' ] ) || isset( $_POST[ 'saveas' ] ) ) {
		// saveas �ޤ��� ������Ͽ
		if( ! $insertable ) die( _MB_PICAL_ERR_NOPERMTOINSERT ) ;
		$_POST[ 'event_id' ] = "" ;
		// Ticket Check
		if ( ! $xoopsGTicket->check() ) {
			redirect_header(XOOPS_URL.'/',3,$xoopsGTicket->getErrors());
		}
		$cal->update_schedule( ",uid='$user_id' $admission_insert_sql" , '' , 'notify_new_event' ) ;
	} else if( ! empty( $_POST[ 'delete' ] ) ) {
		// ���
		if( ! $deletable ) die( _MB_PICAL_ERR_NOPERMTODELETE ) ;
		// Ticket Check
		if ( ! $xoopsGTicket->check() ) {
			redirect_header(XOOPS_URL.'/',3,$xoopsGTicket->getErrors());
		}
		$cal->delete_schedule( $whr_sql_append , 'global $xoopsModule; xoops_comment_delete($xoopsModule->mid(),$id);' ) ;
	} else if( ! empty( $_POST[ 'delete_one' ] ) ) {
		// �����
		if( ! $deletable ) die( _MB_PICAL_ERR_NOPERMTODELETE ) ;
		// Ticket Check
		if ( ! $xoopsGTicket->check() ) {
			redirect_header(XOOPS_URL.'/',3,$xoopsGTicket->getErrors());
		}
		$cal->delete_schedule_one( $whr_sql_append ) ;
	} else if( ! empty( $_GET[ 'output_ics' ] ) /* || ! empty( $_POST[ 'output_ics' ] ) */ ) {
		// output ics
		$cal->output_ics( ) ;
	}

	// smode�ν���
	if( ! empty( $_GET[ 'smode' ] ) ) $smode = $_GET[ 'smode' ] ;
	else $smode = $default_view ;

	// XOOP�إå��������ν���
	if( $action == 'View' ) {
		$xoopsOption['template_main'] = "pical{$mydirnumber}_event_detail.html" ;
	} else {
		// View�ʳ��Ǥϥ����ȶػ�
		$xoopsModuleConfig['com_rule'] = 0 ;
		if( $smode == 'List' && $action != 'Edit' ) {
			$xoopsOption['template_main'] = "pical{$mydirnumber}_event_list.html" ;
		}
	}

	// XOOPS�إå�����
	include( XOOPS_ROOT_PATH.'/header.php' ) ;

	// embed style sheet �ν��� (thx Ryuji)
	$xoopsTpl->assign( "xoops_module_header" , "<style><!-- \n" . $cal->get_embed_css() . "\n--></style>\n" . $xoopsTpl->get_template_vars( "xoops_module_header" ) ) ;

	// �������顼�˥�󥯤�ؤĤ餻�ʤ� follow -> nofollow
	$meta_robots = str_replace( ',follow' , ',nofollow' , $xoopsTpl->get_template_vars( "xoops_meta_robots" ) ) ;
	$xoopsTpl->assign( "xoops_meta_robots" , $meta_robots ) ;

        // �⥸�塼��ID  // added by naao
        $module_handler =& xoops_gethandler('module');
        $this_module =& $module_handler->getByDirname($mydirname);
        $mid = $this_module->getVar('mid');
 
        // �⥸�塼��config  // added by naao
        $config_handler =& xoops_gethandler("config");
        $mod_config = $config_handler->getConfigsByCat(0, $mid);
        $xoopsTpl->assign("moduleConfig", $mod_config);

	// �¹Ի��ַ�¬��������
	// list( $usec , $sec ) = explode( " " , microtime() ) ;
	// $picalstarttime = $sec + $usec ;

	// �ڡ���ɽ����Ϣ�ν���ʬ��
	if( $action == 'Edit' ) {
		if( is_dir( XOOPS_ROOT_PATH . '/common/jscalendar' ) ) {
			// jscalendar in common (recommend)
			$jscalurl = XOOPS_URL . '/common/jscalendar' ;
			$xoopsTpl->assign( 'xoops_module_header' , '
				<link rel="stylesheet" type="text/css" media="all" href="'.$jscalurl.'/calendar-system.css" title="system" />
				<script type="text/javascript" src="'.$jscalurl.'/calendar.js"></script>
				<script type="text/javascript" src="'.$jscalurl.'/lang/'.$cal->jscalendar_lang_file.'"></script>
				<script type="text/javascript" src="'.$jscalurl.'/calendar-setup.js"></script>
			' . $xoopsTpl->get_template_vars( "xoops_module_header" ) ) ;
			$cal->jscalendar = 'jscalendar' ;
		} else if( is_dir( XOOPS_ROOT_PATH . '/class/calendar' ) ) {
			// jscalendar in XOOPS 2.2 core
			$jscalurl = XOOPS_URL . '/class/calendar' ;
			$xoopsTpl->assign( 'xoops_module_header' , '
				<link rel="stylesheet" type="text/css" media="all" href="'.$jscalurl.'/CSS/calendar-blue.css" title="system" />
				<script type="text/javascript" src="'.$jscalurl.'/calendar.js"></script>
				<script type="text/javascript" src="'.$jscalurl.'/lang/'.$cal->jscalendar_lang_file.'"></script>
				<script type="text/javascript" src="'.$jscalurl.'/calendar-setup.js"></script>
			' . $xoopsTpl->get_template_vars( "xoops_module_header" ) ) ;
			$cal->jscalendar = 'jscalendar' ;
		} else {
			// older jscalendar in XOOPS 2.0.x core
			include XOOPS_ROOT_PATH.'/include/calendarjs.php' ;
			$cal->jscalendar = 'xoops' ;
		}
		echo $cal->get_schedule_edit_html( ) ;
	} else if( $action == 'View' ) {
		// echo $cal->get_schedule_view_html( ) ;
		$xoopsTpl->assign( 'detail_body' , $cal->get_schedule_view_html( ) ) ;
		$xoopsTpl->assign( 'xoops_pagetitle' , $cal->last_summary ) ;
		$xoopsTpl->assign( 'xoops_default_comment_title' , 'Re: ' . $cal->last_summary ) ;
		$xoopsTpl->assign( 'print_link' , "$mod_url/print.php?event_id=".intval($_GET['event_id'])."&action=View" ) ;
		$xoopsTpl->assign( 'com_itemid' , intval($_GET['event_id']) ) ; //added naao
		$xoopsTpl->assign( 'skinpath' , "$cal->images_url" ) ;
		$xoopsTpl->assign( 'lang_print' , _MB_PICAL_ALT_PRINTTHISEVENT ) ;
		$HTTP_GET_VARS['event_id'] = $_GET['event_id'] = $cal->original_id ;
		include XOOPS_ROOT_PATH.'/include/comment_view.php' ;
		// patch for commentAny 
		$commentany = $xoopsTpl->get_template_vars( "commentany" ) ;
		if( ! empty( $commentany['com_itemid'] ) ) {
			$commentany['com_itemid'] = $cal->original_id ;
			$xoopsTpl->assign("commentany",$commentany);
			
		}
	} else if( isset( $_POST[ 'output_ics_confirm' ] ) && ! empty( $_POST[ 'ids' ] ) && is_array( $_POST[ 'ids' ] ) ) {
		echo $cal->output_ics_confirm( "$mod_url/" ) ;
	} else switch( $smode ) {
		case 'Yearly' :
			echo $cal->get_yearly( ) ;
			break ;
		case 'Weekly' :
			echo $cal->get_weekly( ) ;
			break ;
		case 'Daily' :
			echo $cal->get_daily( ) ;
			break ;
		case 'List' :
			$cal->assign_event_list( $xoopsTpl ) ;
			break ;
		case 'Monthly' :
		default :
			echo $cal->get_monthly( ) ;
			break ;
	}

	// �¹Ի���ɽ��
	// list( $usec , $sec ) = explode( " " , microtime() ) ;
	// echo "<p>" . ( $sec + $usec - $picalstarttime ) . "sec.</p>" ;

//	var_dump( $xoopsTpl ) ;

	// XOOPS�եå�����
	include( XOOPS_ROOT_PATH.'/footer.php' ) ;

?>