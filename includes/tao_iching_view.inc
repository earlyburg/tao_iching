<?php

/**
 * Display the final results.
 */
function tao_iching_viewport($callbackResult) {
  $I_Ching = new I_Ching();
  if ($I_Ching->readingExist($callbackResult)) { ##- 6 CLICKS METHOD -##
    /* retreive the first hexagram */
    $localChing = $I_Ching->myIching($callbackResult);
    /* generate the final reading */
    $finalChing = $I_Ching->complete($localChing['initial']);
    $finalchg = $finalChing['changed'];
    /* flip the arrays because traditionally I-Ching is read bottom to top */
    $flippedInitial = array_reverse($finalChing['initial']);
    if($finalchg != "No Change") {
      $flippedChanged = array_reverse($finalChing['changed']);
    }
    $content = "<div class='iching_content'>";
    $content .= "<div class='sm_hexag'>";
    foreach ($flippedInitial as $key => $value) {
      $content .=  "<div class='min_line'>";
      $content .=  "<img src='/modules/tao_iching/imgs/" . $value['line'] . "_min.png'>";
      $content .=  "</div>";
    }
    $content .= "</div>";
    $content .= "<div class='sm_hexag'>";
    if ($finalChing['changed'] == "No Change") {
      foreach ($flippedInitial as $key => $value) {
        $content .=  "<div class='min_line'>";
        $content .=  "<img src='/modules/tao_iching/imgs/" . $value['line'] . "_min.png'>";
        $content .=  "</div>";
      }
    } else {
      foreach ($flippedChanged as $key => $value) {
        $content .=  "<div class='min_line'>";
        $content .=  "<img src='/modules/tao_iching/imgs/" . $value['line'] . "_min.png'>";
        $content .=  "</div>";
      }
    }
    $content .= "</div>";
    $content .= "<div class='iching_blurb'>";
    $content .= "<a href='/i-ching'><b>Click Here to start again.</b></a>";
    $content .= "</div>";
    $content .= "</div>";
    $content .= "<div class='iching_container'>";
    /* get rid of the change indicators and display the final hexagram(s) */
    $finalhex = $I_Ching->rawhex_cleanup($flippedInitial);
    /* get the book numbers for the hexagram(s) */
    $bookname = $I_Ching->findBooknum($finalhex);
    /* get the book from the first hexagram */
    $origBook = $I_Ching->findBook($finalhex);
    if($finalchg != "No Change") {
      $ch_bookname = $I_Ching->findBooknum($flippedChanged);
      /* get the second book if there is one */
      $chBook = $I_Ching->findBook($flippedChanged);
    }
    /* print the question */
    if ($finalChing['question'] != NULL) {
      $content .= "<div class='iching_blurb'>";
      $content .= "<b>";
      $content .= $finalChing['question'];
      $content .= "</b>";
      $content .= "</div>";
    }
    $content .= "<div class='iching_blurb'>";
    $content .= "<br>";
    $content .= "</div>";
    $content .= "<div class='iching_tab'>";
    /* html to make our javascript work */
    $content .= file_get_contents(backdrop_get_path('module', 'tao_iching') . "/includes/result_html.php");
    $content .= "</div>";
    $content .= "<div id='Current' class='iching_tabcontent'>";
    $content .= "<h3>Current</h3>"; /* CURRENT */
    $content .= "<div class='hexag'>";
    foreach ($finalhex as $name) {
      $content .= "<div class='sm_line'>";
      $content .= "<img src='/modules/tao_iching/imgs/" . $name['line'] . "_sm.png'>";
      $content .= "</div>";
    }
    $content .= "</div>";
    $content .= "<div class='iching_links'>";
    $content .= "<a class='colorbox-inline' href='?width=600&height=500&inline=true#iching_j_text'>The Judgement</a>";
    $content .= "<div style='display: none;'>";
    $content .= "<div id='iching_j_text'><p>" . $origBook['judge'] . "</p></div>";
    $content .= "</div>";
    $content .= "<a class='colorbox-inline' href='?width=600&height=500&inline=true#iching_i_text'>The Image</a>";
    $content .= "<div style='display: none;'>";
    $content .= "<div id='iching_i_text'><p>" . $origBook['image'] . "</p></div>";
    $content .= "</div>";
    $content .= "</div>";
    $content .= $bookname . " - ";
    $content .= $origBook['descr'];
    $content .= "</div>";
    $content .= "<div id='Changing' class='iching_tabcontent'>";
    $content .= "<h3>Changing</h3>"; /* CHANGING */

    if($finalchg != "No Change") {
           $content .= "<div class='hexag'>";

      $book_cng_key = $I_Ching->findTopChanging($flippedInitial);
       foreach ($flippedInitial as $name) {
          $content .= "<div class='sm_line'>";
          $content .= "<img src='/modules/tao_iching/imgs/" . $name['line'] . "_sm.png'>";
          $content .= "</div>";
       }
           $content .= "</div>";
    } else {
      $content .= "<div>There are no changing lines. The situation is expected to remain the same in the near future.</div>";
    }
    if($finalchg != "No Change") {
      $content .= $chBook[$book_cng_key];
    }
    $content .= "</div>";
    $content .= "<div id='Transformed' class='iching_tabcontent'>";
    $content .= "<h3>Transformed</h3>"; /* TRANSFORMED */

    if($finalchg != "No Change") {
      $content .= "<div class='hexag'>";
      foreach ($flippedChanged as $chgname) {
        $content .= "<div class='sm_line'>";
        $content .= "<img src='/modules/tao_iching/imgs/" . $chgname['line'] . "_sm.png'>";
        $content .= "</div>";
      }
      $content .= "</div>";
    } else {
      $content .= "<div>There are no changing lines. The situation is expected to remain the same in the near future.</div>";
    }
    if($finalchg != "No Change") {
      $content .= "<div class='iching_links'>";
      $content .= "<a class='colorbox-inline' href='?width=600&height=500&inline=true#iching_j_ch_text'>The Judgement</a>";
      $content .= "<div style='display: none;'>";
      $content .= "<div id='iching_j_ch_text'><p>" . $chBook['judge'] . "</p></div>";
      $content .= "</div>";
      $content .= "<a class='colorbox-inline' href='?width=600&height=500&inline=true#iching_i_ch_text'>The Image</a>";
      $content .= "<div style='display: none;'>";
      $content .= "<div id='iching_i_ch_text'><p>" . $chBook['image'] . "</p></div>";
      $content .= "</div>";
      $content .= "</div>";
      $content .= $ch_bookname . " - ";
      $content .= $chBook['descr'];
       }
    $content .= "</div>";
    $content .= "</div>";
    $content .= "<SCRIPT>";
    $content .= "document.getElementById('defaultOpen').click();";
    $content .= "</SCRIPT>";
    /* clear the session ID */
#    config_clear('tao_iching.settings', 'sessionId');
  } else if ($callbackResult == "instant") { ##- INSTANT METHOD -##
    $hexagram = $I_Ching->hexagram();
    $finalChing = $I_Ching->complete($hexagram);
    $finalchg = $finalChing['changed'];
    /* flip the arrays because traditionally I-Ching is read bottom to top */
    $flippedInitial = array_reverse($finalChing['initial']);
    if($finalchg != "No Change") {
      $flippedChanged = array_reverse($finalChing['changed']);
    }
    $content = "<div class='iching_content'>";
    $content .= "<div class='sm_hexag'>";
    foreach ($flippedInitial as $key => $value) {
      $content .=  "<div class='min_line'>";
      $content .=  "<img src='/modules/tao_iching/imgs/" . $value['line'] . "_min.png'>";
      $content .=  "</div>";
    }
    $content .= "</div>";
    $content .= "<div class='sm_hexag'>";
    if ($finalChing['changed'] == "No Change") {
      foreach ($flippedInitial as $key => $value) {
        $content .=  "<div class='min_line'>";
        $content .=  "<img src='/modules/tao_iching/imgs/" . $value['line'] . "_min.png'>";
        $content .=  "</div>";
      }
    } else {
      foreach ($flippedChanged as $key => $value) {
        $content .=  "<div class='min_line'>";
        $content .=  "<img src='/modules/tao_iching/imgs/" . $value['line'] . "_min.png'>";
        $content .=  "</div>";
      }
    }
    $content .= "</div>";
    $content .= "<div class='iching_blurb'>";
    $content .= "<a href='/i-ching'><b>Click Here to start again.</b></a>";
    $content .= "</div>";
    $content .= "</div>";
    $content .= "<div class='iching_container'>";
    /* get rid of the change indicators and display the final hexagram(s) */
    $finalhex = $I_Ching->rawhex_cleanup($flippedInitial);
    /* get the book numbers for the hexagram(s) */
    $bookname = $I_Ching->findBooknum($finalhex);
    /* get the book from the first hexagram */
    $origBook = $I_Ching->findBook($finalhex);
    if($finalchg != "No Change") {

       $ch_bookname = $I_Ching->findBooknum($flippedChanged);
    /* get the second book if there is one */
    $chBook = $I_Ching->findBook($flippedChanged);
    }
    /* print the question */
    if ($finalChing['question'] != NULL) {  // TODO not a form submit so never going to work
      $content .= "<div class='iching_blurb'>";
      $content .= "<b>";
      $content .= $finalChing['question'];
      $content .= "</b>";
      $content .= "</div>";
    }
    $content .= "<div class='iching_blurb'>";
    $content .= "<br>";
    $content .= "</div>";
    $content .= "<div class='iching_tab'>";
    /* html to make our javascript work */
    $content .= file_get_contents(backdrop_get_path('module', 'tao_iching') . "/includes/result_html.php");
    $content .= "</div>";
    $content .= "<div id='Current' class='iching_tabcontent'>";
    $content .= "<h3>Current</h3>"; /* CURRENT */
    $content .= "<div class='hexag'>";
    foreach ($finalhex as $name) {
      $content .= "<div class='sm_line'>";
      $content .= "<img src='/modules/tao_iching/imgs/" . $name['line'] . "_sm.png'>";
      $content .= "</div>";
    }
    $content .= "</div>";
    $content .= "<div class='iching_links'>";
    $content .= "<a class='colorbox-inline' href='?width=600&height=500&inline=true#iching_j_text'>The Judgement</a>";
    $content .= "<div style='display: none;'>";
    $content .= "<div id='iching_j_text'><p>" . $origBook['judge'] . "</p></div>";
    $content .= "</div>";
    $content .= "<a class='colorbox-inline' href='?width=600&height=500&inline=true#iching_i_text'>The Image</a>";
    $content .= "<div style='display: none;'>";
    $content .= "<div id='iching_i_text'><p>" . $origBook['image'] . "</p></div>";
    $content .= "</div>";
    $content .= "</div>";
    $content .= $bookname . " - ";
    $content .= $origBook['descr'];
    $content .= "</div>";
    $content .= "<div id='Changing' class='iching_tabcontent'>";
    $content .= "<h3>Changing</h3>"; /* CHANGING */
    if($finalchg != "No Change") {
      $content .= "<div class='hexag'>";
      $book_cng_key = $I_Ching->findTopChanging($flippedInitial);
      foreach ($flippedInitial as $name) {
        $content .= "<div class='sm_line'>";
        $content .= "<img src='/modules/tao_iching/imgs/" . $name['line'] . "_sm.png'>";
        $content .= "</div>";
      }
      $content .= "</div>";
    } else {
      $content .= "<div>There are no changing lines. The situation is expected to remain the same in the near future.</div>";
    }
    if($finalchg != "No Change") {
      $content .= $chBook[$book_cng_key];
    }
    $content .= "</div>";
    $content .= "<div id='Transformed' class='iching_tabcontent'>";
    $content .= "<h3>Transformed</h3>"; /* TRANSFORMED */
    if($finalchg != "No Change") {
      $content .= "<div class='hexag'>";
      foreach ($flippedChanged as $chgname) {
        $content .= "<div class='sm_line'>";
        $content .= "<img src='/modules/tao_iching/imgs/" . $chgname['line'] . "_sm.png'>";
        $content .= "</div>";
      }
      $content .= "</div>";
    } else {
      $content .= "<div>There are no changing lines. The situation is expected to remain the same in the near future.</div>";
    }
    if($finalchg != "No Change") {
      $content .= "<div class='iching_links'>";
      $content .= "<a class='colorbox-inline' href='?width=600&height=500&inline=true#iching_j_ch_text'>The Judgement</a>";
      $content .= "<div style='display: none;'>";
      $content .= "<div id='iching_j_ch_text'><p>" . $chBook['judge'] . "</p></div>";
      $content .= "</div>";
      $content .= "<a class='colorbox-inline' href='?width=600&height=500&inline=true#iching_i_ch_text'>The Image</a>";
      $content .= "<div style='display: none;'>";
      $content .= "<div id='iching_i_ch_text'><p>" . $chBook['image'] . "</p></div>";
      $content .= "</div>";
      $content .= "</div>";
    }
    if($finalchg != "No Change") {
      $content .= $ch_bookname . " - ";
      $content .= $chBook['descr'];
    }
    $content .= "</div>";
    $content .= "</div>";
    $content .= "<SCRIPT>";
    $content .= "document.getElementById('defaultOpen').click();";
    $content .= "</SCRIPT>";
    /* clear the session ID */
#    config_clear('tao_iching.settings', 'sessionId');
  } else {
    $content = "<div class='iching_blurb'>";
    $content .= "Sorry, that I-Ching reading is not in our database.<br>";
    $content .= "<a href='/i-ching'><b>Click Here to start again.</b></a>";
    $content .= "</div>";
    /* clear the session ID */
#    config_clear('tao_iching.settings', 'sessionId');
  }
  return $content;
}
