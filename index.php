<?php

/*
  WineHQ
  by Jeremy Newman <jnewman@codeweavers.com>
*/

$file_root = ".";
require($file_root."/include/"."incl.php");

// choose the mode
switch (true)
{
  // view page
  case ($page):
    $text .= view_page($page);
    break;
  
  // view wwn issue
  case ($wwn):
    $text .= wwn_view_issue($wwn);
    break;

  // view latest annouce
  case ($announce):
    $text .= view_announce($announce);
    break;

  // display screen shots
  case ($ss):
    $text .= view_screenshots($ss);
    break;

  // default mode (Home Page)
  default:
    $show_nav = 1;
    $text .= home_page();
	break;
}

// display the page
$page_title = $config->site_name;
if ($html->template_title)
    $page_title = $page_title." - ".$html->template_title;
$html->page = $html->frame_table(
                                 $html->frame_tr($html->frame_td($text)),
                                 "99%", 0, ""
                                );
$html->showpage($config->theme, $page_title);

//done
exit();

// load and display a template page
function view_page ($view)
{
    global $config, $html;
    $template = $html->template("base", $view);
    return $html->theme_box($config->theme, "box_title", $html->template_title, "100%", $template, '10', 'white', 'topMenu');
}

// load the home page
function home_page ()
{
    global $file_root, $config, $html;
	
    // screenshot for homepage (random)
    $shots = get_files($file_root."/images/shots","png");
    $c = intval(rand(0,count($shots) - 1));
    $vars['screenshot'] = $html->ahref($html->img($file_root.'/images/shots/wine_'.$c.'.png','right'), $file_root.'/images/shots/full/wine_'.$c.'.png');
    
	// get aboout box
	$about_box = $html->theme_box($config->theme, "box_title", "About Wine", "99%", $html->template("base", 'home_about', $vars), '10', 'white', 'topMenu');
    
	// theme switch box
	$theme_box = $html->theme_box($config->theme, "box_title", "Change Theme", "99%", $html->template("base", 'theme_box', $vars), '10', 'white', 'topMenu');

	// get link to latest release
	$latest_box = $html->theme_box($config->theme, "box_title", "Latest Release", "97%", $html->template("base", 'wine_release'), '10', 'white', 'topMenu');
	
	// get wwn news
	$wwn = wwn_get_list($config->wwn_xml_path);
	$wwn_box = $html->theme_box($config->theme, "box_title", "Wine Weekly News", "97%", wwn_issues_list($wwn[0], $wwn[1], 3), '10', 'white', 'topMenu');
	
    // sponsor box
    $sponsor_box = $html->template("base", 'sponsor');
    
	// load the template for home page and fill in
	$vars = array(
	              'about_box'  => $about_box,
			      'latest_box' => $latest_box,
			      'wwn_box' => $wwn_box,
                  'sponsor_box' => $sponsor_box,
                  'theme_box' => $theme_box
			     );
	$text = $html->template($config->theme, 'home_page', $vars);	 
	
	return $text;
}

// load the annouce file for latest version of wine
function view_announce ($announce)
{
    global $file_root, $config, $html;
    
    $announce = "http://cvs.winehq.com/cvsweb/~checkout~/wine/ANNOUNCE?rev=".$announce;
    if ($announce = @join("",file($announce)))
    {
        // display page
        $html->template_title = 'Announce';
        return $html->theme_box($config->theme, "box_title", 'Announce', "100%", $html->format_msg($announce), '10', 'white', 'topMenu');
    }
    $html->redirect($file_root);
}

// load the annouce file for latest version of wine
function view_screenshots ($x)
{
    global $file_root, $config, $html;
        
	// Grab list of images from screenshot dir
	$shots = get_files($file_root."/images/shots","png");

	// setup vars
    $x--;
	$total = count($shots);
	$num = 0;
	$where = 0;
    $vars = array();
	$vars['next'] = "&nbsp;";
	$vars['prev'] = "&nbsp;";

	// loop and display images
	while (list($c,$image) = each($shots))
	{
		// do not show images less than current pos
		if ($x > $c)
		  continue;

		// display image
		$vars['im_'.$num] = $html->ahref($html->img($file_root.'/images/shots/wine_'.$c.'.png'), $file_root.'/images/shots/full/wine_'.$c.'.png');
        
		// count number of images displayed.
		$num++;

		// end at 6
		if ($num == 6 or $num == $total)
		{
			$where = $c;
			break;
		}
	}
    
	// display prev link
	if ($x)
	{
		$prev = $x - 6;
		$vars['prev'] = $html->ahref("&lt;&lt; Prev 4 Images","?ss=".$prev,"class=menuItem");
	}

	// display next link
	if (($num + 6) < $total)
	{
		$next = $where + 1;
		$vars['next'] = $html->ahref("Next 4 Images &gt;&gt;","?ss=".$next,"class=menuItem");
	}

    // load into template
    $template = $html->template('base', 'screenshots', $vars);
    
    // return the text
    return $html->theme_box($config->theme, "box_title", 'Screen Shots', "100%", $template, '10', 'white', 'topMenu');
}

// end of file
?>
