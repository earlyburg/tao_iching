<?php

/**
 * @file
 * Default theme implementation to display a single Drupal page.
 *
 * The doctype, html, head and body tags are not in this template. Instead they
 * can be found in the html.tpl.php template in this directory.
 *
 * Available variables:
 *
 * General utility variables:
 * - $base_path: The base URL path of the Drupal installation. At the very
 *   least, this will always default to /.
 * - $directory: The directory the template is located in, e.g. modules/system
 *   or themes/bartik.
 * - $is_front: TRUE if the current page is the front page.
 * - $logged_in: TRUE if the user is registered and signed in.
 * - $is_admin: TRUE if the user has permission to access administration pages.
 *
 * Site identity:
 * - $front_page: The URL of the front page. Use this instead of $base_path,
 *   when linking to the front page. This includes the language domain or
 *   prefix.
 * - $logo: The path to the logo image, as defined in theme configuration.
 * - $site_name: The name of the site, empty when display has been disabled
 *   in theme settings.
 * - $site_slogan: The slogan of the site, empty when display has been disabled
 *   in theme settings.
 *
 * Navigation:
 * - $main_menu (array): An array containing the Main menu links for the
 *   site, if they have been configured.
 * - $secondary_menu (array): An array containing the Secondary menu links for
 *   the site, if they have been configured.
 * - $breadcrumb: The breadcrumb trail for the current page.
 *
 * Page content (in order of occurrence in the default page.tpl.php):
 * - $title_prefix (array): An array containing additional output populated by
 *   modules, intended to be displayed in front of the main title tag that
 *   appears in the template.
 * - $title: The page title, for use in the actual HTML content.
 * - $title_suffix (array): An array containing additional output populated by
 *   modules, intended to be displayed after the main title tag that appears in
 *   the template.
 * - $messages: HTML for status and error messages. Should be displayed
 *   prominently.
 * - $tabs (array): Tabs linking to any sub-pages beneath the current page
 *   (e.g., the view and edit tabs when displaying a node).
 * - $action_links (array): Actions local to the page, such as 'Add menu' on the
 *   menu administration interface.
 * - $feed_icons: A string of all feed icons for the current page.
 * - $node: The node object, if there is an automatically-loaded node
 *   associated with the page, and the node ID is the second argument
 *   in the page's path (e.g. node/12345 and node/12345/revisions, but not
 *   comment/reply/12345).
 *
 * Regions:
 * - $page['help']: Dynamic help text, mostly for admin pages.
 * - $page['highlighted']: Items for the highlighted content region.
 * - $page['content']: The main content of the current page.
 * - $page['sidebar_first']: Items for the first sidebar.
 * - $page['sidebar_second']: Items for the second sidebar.
 * - $page['header']: Items for the header region.
 * - $page['footer']: Items for the footer region.
 *
 * @see template_preprocess()
 * @see template_preprocess_page()
 * @see template_process()
 * @see html.tpl.php
 *
 * @ingroup themeable
 */

?>

  <div id="page-wrapper"><div id="page">

    <div id="header"><div class="section clearfix">

      <?php if ($logo): ?>
        <a href="<?php print $front_page; ?>" title="<?php print t('Home'); ?>" rel="home" id="logo">
          <img src="<?php print $logo; ?>" alt="<?php print t('Home'); ?>" />
        </a>
      <?php endif; ?>

      <?php if ($site_name || $site_slogan): ?>
        <div id="name-and-slogan">
          <?php if ($site_name): ?>
            <?php if ($title): ?>
              <div id="site-name"><strong>
                <a href="<?php print $front_page; ?>" title="<?php print t('Home'); ?>" rel="home"><span><?php print $site_name; ?></span></a>
              </strong></div>
            <?php else: /* Use h1 when the content title is empty */ ?>
              <h1 id="site-name">
                <a href="<?php print $front_page; ?>" title="<?php print t('Home'); ?>" rel="home"><span><?php print $site_name; ?></span></a>
              </h1>
            <?php endif; ?>
          <?php endif; ?>

          <?php if ($site_slogan): ?>
            <div id="site-slogan"><?php print $site_slogan; ?></div>
          <?php endif; ?>
        </div> <!-- /#name-and-slogan -->
      <?php endif; ?>

      <?php print render($page['header']); ?>

    </div></div> <!-- /.section, /#header -->

    <?php if ($main_menu || $secondary_menu): ?>
      <div id="navigation"><div class="section">
        <?php print theme('links__system_main_menu', array('links' => $main_menu, 'attributes' => array('id' => 'main-menu', 'class' => array('links', 'inline', 'clearfix')), 'heading' => t('Main menu'))); ?>
        <?php print theme('links__system_secondary_menu', array('links' => $secondary_menu, 'attributes' => array('id' => 'secondary-menu', 'class' => array('links', 'inline', 'clearfix')), 'heading' => t('Secondary menu'))); ?>
      </div></div> <!-- /.section, /#navigation -->
    <?php endif; ?>

    <?php if ($breadcrumb): ?>
      <div id="breadcrumb"><?php print $breadcrumb; ?></div>
    <?php endif; ?>

    <?php print $messages; ?>

    <div id="main-wrapper"><div id="main" class="clearfix">

      <div id="content" class="column"><div class="section">
        <?php if ($page['highlighted']): ?><div id="highlighted"><?php print render($page['highlighted']); ?></div><?php endif; ?>
        <a id="main-content"></a>
        <?php print render($title_prefix); ?>
        <?php if ($title): ?><h1 class="title" id="page-title"><?php print $title; ?></h1><?php endif; ?>
        <?php print render($title_suffix); ?>
        <?php if ($tabs): ?><div class="tabs"><?php print render($tabs); ?></div><?php endif; ?>
        <?php print render($page['help']); ?>
        <?php if ($action_links): ?><ul class="action-links"><?php print render($action_links); ?></ul><?php endif; ?>

	<div class="iching_content">
        <?php print render($page['content']); ?>
	</div>

	<div class="iching_container">
   <?php

  if (isset($_SESSION['hexagram'])) {
    $arraysize = count($_SESSION['hexagram']);
  } else $arraysize = 0;

  $s = $arraysize -1;
   if ($arraysize != 0) {

 if ($_SESSION['question'] != NULL) {  
    print "<div class='iching_blurb'>";
    print "<b>";
    print $_SESSION['question'];
    print "</b>";
    print "</div>";
 }
// dpm($_SESSION, $name = NULL);

    print "<div class='line'>";
 if (isset($_SESSION['hexagram'][5])) {
    print "<img src='/sites/all/modules/tao_iching/imgs/" . $_SESSION['hexagram'][5] . ".png'>";
 }
    print "</div>";



    print "<div class='line'>";
 if (isset($_SESSION['hexagram'][4])) {
    print "<img src='/sites/all/modules/tao_iching/imgs/" . $_SESSION['hexagram'][4] . ".png'>";
 }
    print "</div>";



    print "<div class='line'>";
 if (isset($_SESSION['hexagram'][3])) {
    print "<img src='/sites/all/modules/tao_iching/imgs/" . $_SESSION['hexagram'][3] . ".png'>";
 }
    print "</div>";



    print "<div class='line'>";
 if (isset($_SESSION['hexagram'][2])) {
    print "<img src='/sites/all/modules/tao_iching/imgs/" . $_SESSION['hexagram'][2] . ".png'>";
 }
    print "</div>";



    print "<div class='line'>";
 if (isset($_SESSION['hexagram'][1])) {
    print "<img src='/sites/all/modules/tao_iching/imgs/" . $_SESSION['hexagram'][1] . ".png'>";
 }
    print "</div>";



    print "<div class='line'>";
 if (isset($_SESSION['hexagram'][0])) {
    print "<img src='/sites/all/modules/tao_iching/imgs/" . $_SESSION['hexagram'][0] . ".png'>";
 }
    print "</div>";

 } else if ($arraysize == 0) {

?>


  <div class="intro_blurb">	
  This program uses the three coins method to produce hexagram lines. <br>
  The applicable changing lines are obtained through Master Yin's rules.<br>
  </div>
  <div class="intro_blurb">
  Keep in mind that as long as the algorithm can actually reach each and every
  possible combination of hexagrams and changing lines, it doesn't really matter what the 
  probability is that each symbol can appear if you believe that a supreme Force is responsible for influencing the results.
  </div>

<div class="creditcontainer_toggle_wrapper">
  <a href="#0" id="creditcontainer_toggle">Credits</a>
</div> 

 <div id="creditcontainer" style="display:none;">
  <div class="creditdiv">
  This application was inspired entirely by the I-Ching Android app authored by Digital Illusion which can be found here:<br>
  <a href="https://play.google.com/store/apps/details?id=org.digitalillusion.droid.iching&hl=en"target="_new">https://play.google.com/store/apps/details?id=org.digitalillusion.droid.iching&hl=en</a>
  </div>
  <div class="creditdiv">
  All the contents available to this application were found on the net.
  In particular the most complete and freely available sources are:<br>
  </div>
  <div class="creditdiv">
  <a href="http://wengu.tartarie.com/wg/wengu.php?l=Yijing"target="_new">http://wengu.tartarie.com/wg/wengu.php?l=Yijing</a><br>
  <a href="http://www.ichingonline.net/instruction.php"target="_new">http://www.ichingonline.net/instruction.php</a><br>
  <a href="http://www.onlineclarity.co.uk/learn/consult/mml.php"target="_new">http://www.onlineclarity.co.uk/learn/consult/mml.php</a><br><br>
  </div>
</div>
 <?php } // end array size if else if() ?>
	</div> <!-- END iching_container -->

        <?php print $feed_icons; ?>
      </div></div> <!-- /.section, /#content -->

      <?php if ($page['sidebar_first']): ?>
        <div id="sidebar-first" class="column sidebar"><div class="section">
          <?php print render($page['sidebar_first']); ?>
        </div></div> <!-- /.section, /#sidebar-first -->
      <?php endif; ?>

      <?php if ($page['sidebar_second']): ?>
        <div id="sidebar-second" class="column sidebar"><div class="section">
          <?php print render($page['sidebar_second']); ?>
        </div></div> <!-- /.section, /#sidebar-second -->
      <?php endif; ?>

    </div></div> <!-- /#main, /#main-wrapper -->

    <div id="footer"><div class="section">
      <?php print render($page['footer']); ?>
    </div></div> <!-- /.section, /#footer -->

  </div></div> <!-- /#page, /#page-wrapper -->
