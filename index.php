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
  
  // view news archvie
  case ($news):
    $text .= $html->theme_box($config->theme, "box_title", 'News Archives', "100%", view_news($news), '10', 'white', 'topMenu');
    break;
  
  // view wwn issue
  case ($issue):
    $wwn = new wwn();
    $text .= $wwn->view_issue($issue);
    break;

  // view interviews
  case ($interview):
    $wwn = new wwn();
    $text .= $wwn->view_interview($interview);
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
    $c = intval(rand(1,count($shots) - 1));
    $vars['screenshot'] = $html->ahref($html->img('shots/wine_'.$c.'.png','right','Screenshot'), $file_root.'/images/shots/full/wine_'.$c.'.png');
    
	// get aboout box
	$about_box = $html->theme_box($config->theme, "box_title", "About Wine", "99%", $html->template("base", 'home_about', $vars), '10', 'white', 'topMenu');
    
	// get link to latest release
	$latest_box = $html->theme_box($config->theme, "box_title", "Latest Release", "97%", $html->template("base", 'wine_release'), '10', 'white', 'topMenu');
	
    // get regular news
    $news_box = $html->theme_box($config->theme, "box_title", "Latest News", "99%",  view_news('home'), '10', 'white', 'topMenu');
    
	// get wwn news
    $wwn = new wwn();
	$issues = $wwn->get_list($config->wwn_xml_path);
	$wwn_box = $html->theme_box($config->theme, "box_title", "Weekly Newsletters", "97%", $wwn->issues_list($issues[0], $issues[1], 3), '10', 'white', 'topMenu');
	
    // sponsor box
    $sponsor_box = $html->template("base", 'sponsor');
    
	// load the template for home page and fill in
	$vars = array(
	              'about_box'   => $about_box,
                  'news_box'    => $news_box,
			      'latest_box'  => $latest_box,
			      'wwn_box'     => $wwn_box,
                  'sponsor_box' => $sponsor_box
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

// get front page news listing
function view_news ($where)
{
    global $config, $html;
    
    // max count
    $max = 10;
    
    // where are we in news list
    $x = $_GET['x'];
    
    // get list of news items
    $news = get_files($config->news_xml_path, "xml");
    $news = array_reverse ($news);
    $total = count($news);
    
    // loop and display news
    $c = 0;
    foreach ($news as $key => $item)
    {
        // counter
        $c++;

		// do not show images less than current pos
		if ($x != 1 and $x >= $c)
		  continue;

        // get data from XML file
        $vars = array();
        list($vars['date'], $vars['title'], $vars['body']) = get_xml_tags($config->news_xml_path.'/'.$item, array('date', 'title', 'body'));

        // add to news body
        $news_body .= $html->template('base', 'news_row', $vars);
        
        // only show 8 records on home page
        if ($where == 'home' and $c == 8)
        {
            $news_body .= $html->p($html->ahref('More News', '?news=archive'));
            break;
        }
        // show only 25 records on any other page
        else if ($c == $max)
        {
            break;
        }
    } // end of news loop
    
    // prev/next links
    if ($where == 'archive')
    {
        // display prev link
        if ($x > 0)
        {
            $prev = ($total) - $c;
            $prev_link = $html->ahref("&lt;&lt; Previous News","?news=archive;x=".$prev,"class=menuItem");
        }
        // display next link
        if (($x + $max) < $total)
        {
            $next = $x + $max;
            $next_link .= $html->ahref("More News &gt;&gt;","?news=archive;x=".$next,"class=menuItem");
        }    
        // add prev/next links
        $news_body .= $html->p($html->div($prev_link.' &nbsp; '.$next_link, 'align=center'));
    }
    
    // return the finished body
    return $news_body;
}

// load the annouce file for latest version of wine
function view_screenshots ($x)
{
    global $file_root, $config, $html;
        
	// Grab list of images from screenshot dir
	$shots = get_files($file_root."/images/shots","png");

	// setup vars
	$total = count($shots) + 1;
	$num = 0;
	$where = 0;
    $vars = array();
	$vars['next'] = "&nbsp;";
	$vars['prev'] = "&nbsp;";

	// loop and display images
	for ($c = 1; $c < $total; $c++)
	{
		// do not show images less than current pos
		if ($x != 1 and $x >= $c)
		  continue;

		// display image
		$vars['im_'.$num] = $html->ahref($html->img($file_root.'/images/shots/wine_'.$c.'.png'), $file_root.'/images/shots/full/wine_'.$c.'.png');
        
		// end at 9
		if ($num == 9 or $num == $total)
		{
			$where = $c;
			break;
		}
        
        // count number of images displayed.
		$num++;
	}
    
	// display prev link
	if ($x > 1)
	{
		$prev = $total + 1 - $c;
		$vars['prev'] = $html->ahref("&lt;&lt; Previous Screenshots","?ss=".$prev,"class=menuItem");
	}

	// display next link
	if (($x + 9) < $total)
	{
		$next = $where;
		$vars['next'] = $html->ahref("More Screenshots &gt;&gt;","?ss=".$next,"class=menuItem");
	}

    // load into template
    $template = $html->template('base', 'screenshots', $vars);
    
    // return the text
    return $html->theme_box($config->theme, "box_title", 'Screen Shots', "100%", $template, '10', 'white', 'topMenu');
}

// end of file
?>
