<?php
// print "Hello World";
// dpm($form, $name = NULL);

drupal_session_start();

   if (!array_key_exists('hexagram', $_SESSION)) {
	$_SESSION['hexagram'] = array();
   } 
   if (!array_key_exists('changed', $_SESSION)) {
	$_SESSION['changed'] = array();
   } 
   if (!array_key_exists('question', $_SESSION)) {
	$_SESSION['question'] = "";
   } 

  print "<div class='iching_content'>";

 print drupal_render_children($form);

    print "<div class='iching_blurb'>";
	print "<br>";
    print "</div>"; // .iching_blurb



  $arraysize = count($_SESSION['hexagram']);
  $s = $arraysize -1;

   if ($arraysize == 0) {

    print "<div class='iching_coindiv'>";

    print "<img src='/sites/all/modules/tao_iching/imgs/heads.png'>";
    print "<img src='/sites/all/modules/tao_iching/imgs/heads.png'>";
    print "<img src='/sites/all/modules/tao_iching/imgs/heads.png'>";

    print "</div>"; // .iching_coindiv

   }

   if ($arraysize != 0) {
    print "<div class='coins_container'>";

    print "<div class='iching_coindiv'>";

	if ($_SESSION['coinsval'] == 9) {
    print "<img src='/sites/all/modules/tao_iching/imgs/heads.png'>";
    print "<img src='/sites/all/modules/tao_iching/imgs/heads.png'>";
    print "<img src='/sites/all/modules/tao_iching/imgs/heads.png'>";
	}

	if ($_SESSION['coinsval'] == 8) {
    print "<img src='/sites/all/modules/tao_iching/imgs/heads.png'>";
    print "<img src='/sites/all/modules/tao_iching/imgs/heads.png'>";
    print "<img src='/sites/all/modules/tao_iching/imgs/tails.png'>";
	}

	if ($_SESSION['coinsval'] == 7) {
    print "<img src='/sites/all/modules/tao_iching/imgs/heads.png'>";
    print "<img src='/sites/all/modules/tao_iching/imgs/tails.png'>";
    print "<img src='/sites/all/modules/tao_iching/imgs/tails.png'>";
	}

	if ($_SESSION['coinsval'] == 6) {
    print "<img src='/sites/all/modules/tao_iching/imgs/tails.png'>";
    print "<img src='/sites/all/modules/tao_iching/imgs/tails.png'>";
    print "<img src='/sites/all/modules/tao_iching/imgs/tails.png'>";
	}

    print "</div>"; // .iching_coindiv

// fix our code-like output to be readable
    $raw = $_SESSION['hexagram'][$s];

    if ($raw == "yang_changing") {
	$legible = str_replace("yang_changing", "Yang, changing.", $raw);
    }

    if ($raw == "yin") {
	$legible = str_replace("yin", "Yin.", $raw);
    }

    if ($raw == "yin_changing") {
	$legible = str_replace("yin_changing", "Yin, changing.", $raw);
    }

    if ($raw == "yang") {
	$legible = str_replace("yang", "Yang.", $raw);
    }

    print "<div class='iching_blurb'>";
    print "Coin toss " . $arraysize . " yields "  . $legible;
    print "</div>"; // .iching_blurb

    print "<div class='sm_line'>";
    print "<img src='/sites/all/modules/tao_iching/imgs/" . $_SESSION['hexagram'][$s] . "_sm.png'>";
    print "</div>"; // sm_line

    print "</div>"; // .coins_container

    print "<div class='iching_blurb'>";
    print "<br>";
    print "</div>"; // .iching_blurb

   } // end the array size if()


// print "<pre>" . print_r($_SESSION['hexagram'], 1) . "</pre>";
// print "<pre>" . print_r($_SESSION['changed'], 1) . "</pre>";

  $socblock = block_load('addtoany', 'addtoany_button');
  $output =_block_get_renderable_array(_block_render_blocks(array($socblock)));
  print render($output);

print "</div>"; // end .iching_content
$s++;
?>

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





