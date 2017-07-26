<?php
/* 
Plugin Name: FW Mini-Termin
Version: 2.0
Plugin URI: http://www.wieser.at/wordpress/plugins
Description: Mini Termin Plugin Prototyp - Metabox für Termindatum bei Beiträge, Sidebarwidget, Shortcode [termine] für Terminliste im Content (Seiten, Beiträge, usw..), InfoSeite
Author: Franz Wieser
Author URI: http://www.wieser.at
*/ 
add_shortcode('terminkalender', 'get_termin_calendar');

 function get_termin_calendar($initial = true, $echo = true) {
	        global $wpdb, $m, $monthnum, $year, $wp_locale, $posts;
	
	        $cache = array();
	        $key = md5( $m . $monthnum . $year );
	        if ( $cache = wp_cache_get( 'get_calendar', 'calendar' ) ) {
	                if ( is_array($cache) && isset( $cache[ $key ] ) ) {
	                        if ( $echo ) {
	                                echo apply_filters( 'get_calendar',  $cache[$key] );
	                                return;
	                        } else {
	                                return apply_filters( 'get_calendar',  $cache[$key] );
	                        }
	                }
	        }
	
	        if ( !is_array($cache) )
	                $cache = array();
	
	        // Quick check. If we have no posts at all, abort!
	        if ( !$posts ) {
	                $gotsome = $wpdb->get_var("SELECT 1 as test FROM $wpdb->posts WHERE post_type = 'post' AND post_status = 'publish' LIMIT 1");
	                if ( !$gotsome ) {
	                        $cache[ $key ] = '';
	                        wp_cache_set( 'get_calendar', $cache, 'calendar' );
	                        return;
	                }
	        }
	
	        if ( isset($_GET['w']) )
	                $w = ''.intval($_GET['w']);
	
	        // week_begins = 0 stands for Sunday
	        $week_begins = intval(get_option('start_of_week'));
	
	        // Let's figure out when we are
	        if ( !empty($monthnum) && !empty($year) ) {
	                $thismonth = ''.zeroise(intval($monthnum), 2);
	                $thisyear = ''.intval($year);
	        } elseif ( !empty($w) ) {
	                // We need to get the month from MySQL
	                $thisyear = ''.intval(substr($m, 0, 4));
	                $d = (($w - 1) * 7) + 6; //it seems MySQL's weeks disagree with PHP's
	                $thismonth = $wpdb->get_var("SELECT DATE_FORMAT((DATE_ADD('{$thisyear}0101', INTERVAL $d DAY) ), '%m')");
	        } elseif ( !empty($m) ) {
	                $thisyear = ''.intval(substr($m, 0, 4));
	                if ( strlen($m) < 6 )
	                                $thismonth = '01';
	                else
	                                $thismonth = ''.zeroise(intval(substr($m, 4, 2)), 2);
	        } else {
	                $thisyear = gmdate('Y', current_time('timestamp'));
	                $thismonth = gmdate('m', current_time('timestamp'));
	        }
	
	        $unixmonth = mktime(0, 0 , 0, $thismonth, 1, $thisyear);
	        $last_day = date('t', $unixmonth);
	
	        // Get the next and previous month and year with at least one post
	        $previous = $wpdb->get_row("SELECT MONTH(post_date) AS month, YEAR(post_date) AS year
	                FROM $wpdb->posts
	                WHERE post_date < '$thisyear-$thismonth-01'
	                AND post_type = 'post' AND post_status = 'publish'
	                        ORDER BY post_date DESC
	                        LIMIT 1");
	        $next = $wpdb->get_row("SELECT MONTH(post_date) AS month, YEAR(post_date) AS year
	                FROM $wpdb->posts
	                WHERE post_date > '$thisyear-$thismonth-{$last_day} 23:59:59'
	                AND post_type = 'post' AND post_status = 'publish'
	                        ORDER BY post_date ASC
	                        LIMIT 1");
	
	        /* translators: Calendar caption: 1: month name, 2: 4-digit year */
	        $calendar_caption = _x('%1$s %2$s', 'calendar caption');
	        $calendar_output = '<table id="wp-calendar">
	        <caption>' . sprintf($calendar_caption, $wp_locale->get_month($thismonth), date('Y', $unixmonth)) . '</caption>
	        <thead>
	        <tr>';
	
	        $myweek = array();
	
	        for ( $wdcount=0; $wdcount<=6; $wdcount++ ) {
	                $myweek[] = $wp_locale->get_weekday(($wdcount+$week_begins)%7);
	        }
	
	        foreach ( $myweek as $wd ) {
	                $day_name = (true == $initial) ? $wp_locale->get_weekday_initial($wd) : $wp_locale->get_weekday_abbrev($wd);
	                $wd = esc_attr($wd);
	                $calendar_output .= "\n\t\t<th scope=\"col\" title=\"$wd\">$day_name</th>";
	        }
	
	        $calendar_output .= '
	        </tr>
        </thead>
	
	        <tfoot>
	        <tr>';
	
	        if ( $previous ) {
	                $calendar_output .= "\n\t\t".'<td colspan="3" id="prev"><a href="' . get_month_link($previous->year, $previous->month) . '" title="' . esc_attr( sprintf(__('View posts for %1$s %2$s'), $wp_locale->get_month($previous->month), date('Y', mktime(0, 0 , 0, $previous->month, 1, $previous->year)))) . '">&laquo; ' . $wp_locale->get_month_abbrev($wp_locale->get_month($previous->month)) . '</a></td>';
	        } else {
	                $calendar_output .= "\n\t\t".'<td colspan="3" id="prev" class="pad">&nbsp;</td>';
	        }
	
	        $calendar_output .= "\n\t\t".'<td class="pad">&nbsp;</td>';
	
	        if ( $next ) {
	                $calendar_output .= "\n\t\t".'<td colspan="3" id="next"><a href="' . get_month_link($next->year, $next->month) . '" title="' . esc_attr( sprintf(__('View posts for %1$s %2$s'), $wp_locale->get_month($next->month), date('Y', mktime(0, 0 , 0, $next->month, 1, $next->year))) ) . '">' . $wp_locale->get_month_abbrev($wp_locale->get_month($next->month)) . ' &raquo;</a></td>';
	        } else {
	                $calendar_output .= "\n\t\t".'<td colspan="3" id="next" class="pad">&nbsp;</td>';
	        }
	
	        $calendar_output .= '
	        </tr>
	        </tfoot>
	
	        <tbody>
	        <tr>';
	
	        // Get days with posts
	        $dayswithposts = $wpdb->get_results("SELECT DISTINCT DAYOFMONTH(post_date)
	                FROM $wpdb->posts WHERE post_date >= '{$thisyear}-{$thismonth}-01 00:00:00'
	                AND post_type = 'post' AND post_status = 'publish'
	                AND post_date <= '{$thisyear}-{$thismonth}-{$last_day} 23:59:59'", ARRAY_N);
	        if ( $dayswithposts ) {
	                foreach ( (array) $dayswithposts as $daywith ) {
	                        $daywithpost[] = $daywith[0];
	                }
	        } else {
	                $daywithpost = array();
	        }
	
	        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false || stripos($_SERVER['HTTP_USER_AGENT'], 'camino') !== false || stripos($_SERVER['HTTP_USER_AGENT'], 'safari') !== false)
	                $ak_title_separator = "\n";
	        else
	                $ak_title_separator = ', ';
	
	        $ak_titles_for_day = array();
	        $ak_post_titles = $wpdb->get_results("SELECT ID, post_title, DAYOFMONTH(post_date) as dom "
	                ."FROM $wpdb->posts "
	                ."WHERE post_date >= '{$thisyear}-{$thismonth}-01 00:00:00' "
	                ."AND post_date <= '{$thisyear}-{$thismonth}-{$last_day} 23:59:59' "
	                ."AND post_type = 'post' AND post_status = 'publish'"
	        );
	        if ( $ak_post_titles ) {
	                foreach ( (array) $ak_post_titles as $ak_post_title ) {
	
	                                /** This filter is documented in wp-includes/post-template.php */
	                                $post_title = esc_attr( apply_filters( 'the_title', $ak_post_title->post_title, $ak_post_title->ID ) );
	
	                                if ( empty($ak_titles_for_day['day_'.$ak_post_title->dom]) )
	                                        $ak_titles_for_day['day_'.$ak_post_title->dom] = '';
	                                if ( empty($ak_titles_for_day["$ak_post_title->dom"]) ) // first one
	                                        $ak_titles_for_day["$ak_post_title->dom"] = $post_title;
	                                else
	                                        $ak_titles_for_day["$ak_post_title->dom"] .= $ak_title_separator . $post_title;
	                }
	        }
	
	        // See how much we should pad in the beginning
	        $pad = calendar_week_mod(date('w', $unixmonth)-$week_begins);
	        if ( 0 != $pad )
	                $calendar_output .= "\n\t\t".'<td colspan="'. esc_attr($pad) .'" class="pad">&nbsp;</td>';
	
	        $daysinmonth = intval(date('t', $unixmonth));
	        for ( $day = 1; $day <= $daysinmonth; ++$day ) {
	                if ( isset($newrow) && $newrow )
	                        $calendar_output .= "\n\t</tr>\n\t<tr>\n\t\t";
	                $newrow = false;
	
	                if ( $day == gmdate('j', current_time('timestamp')) && $thismonth == gmdate('m', current_time('timestamp')) && $thisyear == gmdate('Y', current_time('timestamp')) )
	                        $calendar_output .= '<td id="today">';
	                else
	                        $calendar_output .= '<td>';
	
	                if ( in_array($day, $daywithpost) ) // any posts today?
	                                $calendar_output .= '<a href="' . get_day_link( $thisyear, $thismonth, $day ) . '" title="' . esc_attr( $ak_titles_for_day[ $day ] ) . "\">$day</a>";
	                else
	                        $calendar_output .= $day;
	                $calendar_output .= '</td>';
	
	                if ( 6 == calendar_week_mod(date('w', mktime(0, 0 , 0, $thismonth, $day, $thisyear))-$week_begins) )
	                        $newrow = true;
	        }
	
	        $pad = 7 - calendar_week_mod(date('w', mktime(0, 0 , 0, $thismonth, $day, $thisyear))-$week_begins);
	        if ( $pad != 0 && $pad != 7 )
	                $calendar_output .= "\n\t\t".'<td class="pad" colspan="'. esc_attr($pad) .'">&nbsp;</td>';
	
	        $calendar_output .= "\n\t</tr>\n\t</tbody>\n\t</table>";
	
	        $cache[ $key ] = $calendar_output;
	        wp_cache_set( 'get_calendar', $cache, 'calendar' );
	
	        if ( $echo )
	                echo apply_filters( 'get_calendar',  $calendar_output );
	        else
	                return apply_filters( 'get_calendar',  $calendar_output );
	
	}
	


//custom meta boxes
$prefix = '';

$termin_meta_box = array(
    'id' => 'TerminMetaBox',
    'title' => 'Termin',
    'context' => 'normal',
    'priority' => 'high',
    'fields' => array(
    	array(
        	'name' => __('Datum'),
        	'id' => $prefix . 'termindatum',
        	'type' => 'date',
        	'desc' => __('Termindatum'),
        	'std' => ''
     	),
  array(
        	'name' => __('Datum bis'),
        	'id' => $prefix . 'termindatumbis',
        	'type' => 'date',
        	'desc' => __('Termindatumbis'),
        	'std' => ''
     	),
  
  array(
        	'name' => __('testdatum'),
        	'id' => $prefix . 'testdatum',
        	'type' => 'date2',
        	'desc' => __('testdatum'),
        	'std' => ''
     	),
    	array(        	'name' => __('Zeit'),
        	'id' => $prefix . 'terminzeit',
        	'type' => 'zeit',
        	'desc' => __('Terminzeit'),
        	'std' => ''
     	),
     	array(
        	'name' => __('Ort'),
        	'id' => $prefix . 'ort',
        	'type' => 'text',
        	'desc' => __('Terminort'),
        	'std' => ''
     	),
     	
     	

         )
);


add_filter( 'the_content', 'termin_filter', 20 );

function termin_filter( $content ) {
   $datummeta=get_post_meta(get_the_ID(), 'termindatum', true);
   $fdatum=new DateTime($datummeta);
   $fdate=$fdatum->format('d.m.Y');
   $bisdatummeta=get_post_meta(get_the_ID(), 'termindatumbis', true);
   $bisdatum=new DateTime($bisdatummeta);
   $bisdate=$bisdatum->format('d.m.Y');
        if ( $datummeta!='' && (is_front_page() || is_single()) )
        {
	            $fcontent = '<h2>Termin: '.$fdate;
	            if ($bisdatum>$fdatum)
	            {$fcontent.=' bis '.$bisdate;}
	            $fcontent.='</h2>';
	            if (get_post_meta(get_the_ID(),'terminzeit',true)!='')
	              $fcontent.='<h2>Zeit: '.get_post_meta(get_the_ID(),'terminzeit',true).'</h3>';
	              
	            if (get_post_meta(get_the_ID(),'ort',true)!='')
	              $fcontent.='<h2>Ort: '.get_post_meta(get_the_ID(),'ort',true).'</h3>';
	              
        }
	            $fcontent.=$content;
	   

    return $fcontent;
}


function TerminAddMetaBoxes() {
    global $termin_meta_box, $posted;
    	//add_submenu_page( 'edit.php?post_type=kassa', 'Kassabuch', 'Kassabuch', 'manage_options', 'kassabuch-custom-page', 'kassabuch_page_callback' ); 


//echo "Termindaten eingeben:".$posted;
	$post_types = get_post_types(array('public' => true, 'show_ui' => true), 'objects');
	//foreach ($post_types as $page)     
	add_meta_box($termin_meta_box['id'], $termin_meta_box['title'], 'terminMetaBox', 'post', $termin_meta_box['context'], $termin_meta_box['priority']);
	
	
     
}
add_action('admin_menu', 'terminAddMetaBoxes');

function terminMetabox()
{
	global $termin_meta_box, $post;
echo "<table>";
 foreach ($termin_meta_box['fields'] as $field) {
        // get current post meta data
        echo "<tr>";
        $meta = get_post_meta($post->ID, $field['id'], true);
        
        switch ($field['type']) {
        	case 'text':
        		echo '<td>'.$field['name'].':</td><td> <input type="text" name="', $field['id'], '" id="', $field['id'], '" value="'.$meta.'" />'.$field['desc'].'</td>';
        		break;
	case 'editor':
	//	the_editor($post->post_content);
        		
        		break;
        			case 'date':
        				if ($meta!='')
        				{$tidate = new DateTime($meta);
$dmeta= $tidate->format('d.m.Y');
}


echo '<td>'.$field['name'].':</td><td> <input class="calendarpicker" type="text" name="', $field['id'], '" id="', $field['id'], '" value="'.$dmeta.'" />'.$field['desc'].'</td>';
//echo '<script type="text/javascript">jQuery(document).ready(function() {    jQuery(\'#termindatum\').datepicker1({        dateFormat : \'dd.mm.yy\'/   });});</script>';

break;
            case 'date2':
            $metas = get_post_meta($post->ID, $field['id'], false);
            
            
        				if ($meta!='')
        				{$tidate = new DateTime($meta);
$dmeta= $tidate->format('d.m.Y');
}
foreach ($metas as $d2meta) {
echo '<td>'.$field['name'].':</td><td> <input type="text" name="', $field['id'], '" id="', $field['id'], '" value="'.$d2meta.'" />'.$field['desc'].'</td>';
}
            echo '<td>'.$field['name'].':</td><td> <input class="calendarpicker" type="text" name="', $field['id'], '" id="', $field['id'], '" value="" />'.$field['desc'].'</td>';
//echo '<script type="text/javascript"> jQuery(document).ready(function() {    jQuery(\'#testdatum\').datepicker({        dateFormat : \'dd.mm.yy\'    });});</script>';

break;
	case 'editor':
	//	the_editor($post->post_content);
        		
        		break;
        		            case 'select':
            	echo "selected Box";
                break;
            case 'checkbox':
                echo '<input type="checkbox" name="', $field['id'], '" id="', $field['id'], '"', $meta ? ' checked="checked"' : '', ' />';
                break;
        }
        echo "</tr>";

}
echo '</table>';	
}

function terminSaveData($post_id) {
global $termin_meta_box;
global $my_save_post_flag;

if ($my_save_post_flag == 0) {






$post = array(
		'post_title'	=> 'request',
		'post_content'	=> $posted,
		'post_status'	=> 'publish',
		'post_type'	=> 'suchergebnis' 
	);
//	wp_insert_post($post);  
    
    // verify nonce
   // if (!wp_verify_nonce($_POST['kassaMetaNonce'], basename(__FILE__))) {
    //    return $post_id;
    //}

    // check autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $post_id;
    }

    // check permissions
    if ('page' == $_POST['post_type']) {
        if (!current_user_can('edit_page', $post_id)) {
            return $post_id;
        }
    } elseif (!current_user_can('edit_post', $post_id)) {
        return $post_id;
    }
    
   

   
    
    /*
        else
        {
    */
    foreach ($termin_meta_box['fields'] as $field) {
        $old = get_post_meta($post_id, $field['id'], true);
        
        $new = $_POST[$field['id']];
        if ($field['id']=='termindatum' and $new!='')
        {
        $tdate= new DateTime($new);
$new =$tdate->format('Y-m-d H:i:s');
        }
        $bfsam.= $new.'-';
        
        
        if ($new && $new != $old) {
            update_post_meta($post_id, $field['id'], $new);
        } elseif ('' == $new && $old) {
            delete_post_meta($post_id, $field['id'], $old);
        }
        
        } //not RK_ID
        update_post_meta($post_id, 'requestbf', $bfsam);
   
    


}
$my_save_post_flag = 1;
    
    
}

add_action('save_post', 'terminsaveData');

function termine_shortcode($atts)
{
	extract( shortcode_atts( array(
		'cat' => '',
		'category_name' => '',
	), $atts ) );
global $wpdb;
	$out='<div class="wrap"><div id="icon-tools" class="icon32"></div>';
		$out.='<h2>Termine</h2>';
		$arg1=array(
'post_type' => 'post',

'orderby' => 'meta_value termindatum', 
'meta_key' => 'termindatum',
'order' => 'ASC',
'cat'=>'',
'posts_per_page'=>'-1',
);

$out.='<ul>';

	$my_query = new WP_Query( $arg1);
   if (  $my_query->have_posts() ) { 
       while ( $my_query->have_posts() ) {
       	$my_query->the_post();
           //$out.= "<tr>".' ';
           $tdate=get_post_meta(get_the_ID(), 'termindatum',true);
           $bisdate=get_post_meta(get_the_ID(), 'termindatumbis',true);
           $wdate = new DateTime($tdate);
           $bdate = new DateTime($bisdate);
           $heutedate=new DateTime();
           if ($bdate>=$heutedate) 
           {
           	
           $wheutedate=$heutedate->format('d.m.Y');
$terminwdatum= $wdate->format('d.m.Y');
$bisterminwdatum= $bdate->format('d.m.Y');

           $out.="<li>".$terminwdatum;
           if ($bdate>$wdate)
           $out.=' bis '.$bisterminwdatum;
           
           $out.='<br/>';
           $out.='<a href="'.get_permalink().'">'.get_the_title().'</a></li>';   
           
           }
           
       }
       	
} // if have posts
		$out.="";
		$out.="</ul>";			$out.='</div>';
		return $out;
		
}
add_shortcode('termine', 'termine_shortcode');

function minitermin_sidebarwidget()
{
global $wpdb;
	$out='<aside id="termine" class="widget widget_termine">';
		$out.='<h3 class="widget-title">Termine</h3>';
		$arg1=array(
'post_type' => 'post',

'orderby' => 'meta_value termindatum', 
'meta_key' => 'termindatum',
'order' => 'ASC',
'posts_per_page'=>'-1',
);

$out.='<ul>';

	$my_query = new WP_Query( $arg1);
   if (  $my_query->have_posts() ) { 
       while ( $my_query->have_posts() ) {
       	$my_query->the_post();
           //$out.= "<tr>".' ';
           $tdate=get_post_meta(get_the_ID(), 'termindatum',true);
           $bisdate=get_post_meta(get_the_ID(), 'termindatumbis',true);
           $wdate = new DateTime($tdate);
           $bdate = new DateTime($bisdate);
           $heutedate=new DateTime();
           if ($bdate>=$heutedate) 
           {
           	
           $wheutedate=$heutedate->format('d.m.Y');
$terminwdatum= $wdate->format('d.m.Y');
$bisterminwdatum= $bdate->format('d.m.Y');

           $out.="<li>".$terminwdatum;
           if ($bdate>$wdate)
           $out.=' bis '.$bisterminwdatum;
           
           $out.='<br/>';
           $out.='<a href="'.get_permalink().'">'.get_the_title().'</a></li>';   
           
           }
           
       }
       	
} // if have posts
		$out.="";
		$out.="</ul>";
			$out.='</aside>';
		echo $out;
		
		


}

function minitermin_widget_init()
{
   wp_register_sidebar_widget( 'minitermin',__('MiniTermin'),'minitermin_sidebarwidget');
}
add_action("plugins_loaded", "minitermin_widget_init");


function minitermin_info_seite()
{
global $current_user;
   echo '<div class="wrap">';
   echo '<H3>Mini Termin</H3>';
   echo 'diese MiniTermin Plugin ermöglicht auf einfachste Weise aus jedem Beitrag eine Termin zu machen:</p>';
   echo 'Jeder Beitrag hat eine MiniTermin Metabox für Datum. Datum bis, Zeit und Ort Eintrag, sobald im Feld Datum etwas eingetragen ist, wird beim Beitrag im Frontend am Anfang des Content zB "Termin: 24.12.2013" angezeit, wird Datum bis eingetragen, erscheint die anzeige: Termin: 1.12.2013 bis 24.12.2013, wird das Feld Zeit und Ort befüllt erscheinen diese Feldinhalte ebenfalls.<p/>';
   echo 'Ein Sidebarwidget kann ebenfalls eingebunden werden.<p/>';
   echo 'mit dem Shortcode [termine] kann die Liste der Terminbeiträge im Content (Seite, Beitrag) angezeigt werden. [termine cat='2'] zeigt nur Terminbeiträge mit Kategorie 2 an.<p/>';
   echo 'unabhängig vom Plugin können Kategorien genutzt werden, um verschiedene Arten von Termin mit der Standard Kategori Ansich zb als Extra Menüpunkt anzuzeigen.<p/>';
   echo 'Diese Plugin sollte noch nicht für Realeinsatz verwendet werden, da ich einige kleine Dinge noch nicht eingebaut habe:<br/>';
   echo 'es fehlt zum Beispiel: sortierung nach Datum und Zeit, Datumskalender eingabefeld in der Metabox<p/>';
   echo 'wer Ideen hat und sogar selbst Code, darf natürlich erweitern und mir diese Ideen schicken: an franz@wieser.at<p/>';
   echo '</div>';
	
}

function minitermin_plugin_menu()
{
add_menu_page('Mini Termin', 'Mini Termin', 'read', 'minitermin', 'minitermin_info_seite');
}


add_action('admin_menu', 'minitermin_plugin_menu');
?>
