<?php
// print "Hello World";
// dpm($_SESSION, $name = NULL);

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

 print drupal_render_children($form);

  print "<div class='iching_content'>";

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
    print "<div class='iching_container'>";

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

    print "</div>"; // .iching_container

    print "<div class='iching_blurb'>";
    print "<br>";
    print "</div>"; // .iching_blurb

   } // end the array size if()


// print "<pre>" . print_r($_SESSION['hexagram'], 1) . "</pre>";
// print "<pre>" . print_r($_SESSION['changed'], 1) . "</pre>";

  $socblock = block_load('addtoany', 'addtoany_button');
  $output =_block_get_renderable_array(_block_render_blocks(array($socblock)));
  print render($output);

print "</div>"; // iching_content

$s++;






